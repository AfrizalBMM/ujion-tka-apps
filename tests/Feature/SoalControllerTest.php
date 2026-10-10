<?php

namespace Tests\Feature;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Tests\TestCase;

class SoalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_redirected_from_soal_index(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $response = $this->get(route('superadmin.soal.index', [$paket, $mapel]));

        $response->assertRedirect(route('login'));
    }

    public function test_guru_cannot_access_superadmin_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);

        $response = $this->actingAs($guru)
            ->getJson(route('superadmin.soal.index', [$paket, $mapel]));

        $response->assertForbidden();
    }

    public function test_superadmin_can_view_soal_index(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $url = route('superadmin.soal.index', [$paket, $mapel]);
        $response = $this->actingAs($superadmin)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame('Superadmin/Soal/Index', $response->json('component'));
        $this->assertArrayHasKey('soals', $response->json('props'));
    }

    public function test_superadmin_can_view_create_form(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $url = route('superadmin.soal.create', [$paket, $mapel]);
        $response = $this->actingAs($superadmin)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame('Superadmin/Soal/Create', $response->json('component'));
    }

    public function test_superadmin_can_create_pilihan_ganda_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.soal.store', [$paket, $mapel]), [
                'nomor_soal' => 1,
                'tipe_soal' => 'pilihan_ganda',
                'indikator' => 'Tes indikator',
                'pertanyaan' => 'Berapa hasil 2 + 2?',
                'bobot' => 1,
                'pilihan' => [
                    ['kode' => 'A', 'teks' => '3'],
                    ['kode' => 'B', 'teks' => '4'],
                    ['kode' => 'C', 'teks' => '5'],
                    ['kode' => 'D', 'teks' => '6'],
                ],
                'jawaban_benar' => 'B',
            ]);

        $response->assertRedirect(route('superadmin.soal.index', [$paket, $mapel]));

        $this->assertDatabaseHas('soals', [
            'mapel_paket_id' => $mapel->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Berapa hasil 2 + 2?',
        ]);

        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();
        $this->assertDatabaseHas('pilihan_jawabans', [
            'soal_id' => $soal->id,
            'kode' => 'B',
            'teks' => '4',
            'is_benar' => true,
        ]);
        $this->assertDatabaseHas('pilihan_jawabans', [
            'soal_id' => $soal->id,
            'kode' => 'A',
            'teks' => '3',
            'is_benar' => false,
        ]);
    }

    public function test_superadmin_can_create_menjodohkan_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.soal.store', [$paket, $mapel]), [
                'nomor_soal' => 1,
                'tipe_soal' => 'menjodohkan',
                'indikator' => 'Menjodohkan istilah',
                'pertanyaan' => 'Pasangkan ibukota yang benar',
                'bobot' => 1,
                'pasangan' => [
                    ['teks_kiri' => 'Jakarta', 'teks_kanan' => 'DKI Jakarta'],
                    ['teks_kiri' => 'Bandung', 'teks_kanan' => 'Jawa Barat'],
                    ['teks_kiri' => 'Surabaya', 'teks_kanan' => 'Jawa Timur'],
                ],
            ]);

        $response->assertRedirect(route('superadmin.soal.index', [$paket, $mapel]));

        $this->assertDatabaseHas('soals', [
            'mapel_paket_id' => $mapel->id,
            'tipe_soal' => 'menjodohkan',
        ]);

        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();
        $this->assertDatabaseHas('pasangan_menjodohkans', [
            'soal_id' => $soal->id,
            'teks_kiri' => 'Jakarta',
            'teks_kanan' => 'DKI Jakarta',
            'urutan' => 1,
        ]);
        $this->assertDatabaseHas('pasangan_menjodohkans', [
            'soal_id' => $soal->id,
            'teks_kiri' => 'Bandung',
            'teks_kanan' => 'Jawa Barat',
            'urutan' => 2,
        ]);
        $this->assertDatabaseCount('pasangan_menjodohkans', 3);
    }

    public function test_store_rejects_duplicate_nomor_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $this->createSoal($mapel, 1, 'pilihan_ganda');

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.soal.store', [$paket, $mapel]), [
                'nomor_soal' => 1,
                'tipe_soal' => 'pilihan_ganda',
                'indikator' => 'Duplikat',
                'pertanyaan' => 'Soal duplikat nomor',
                'bobot' => 1,
                'pilihan' => [
                    ['kode' => 'A', 'teks' => 'A'],
                    ['kode' => 'B', 'teks' => 'B'],
                    ['kode' => 'C', 'teks' => 'C'],
                    ['kode' => 'D', 'teks' => 'D'],
                ],
                'jawaban_benar' => 'A',
            ]);

        $response->assertSessionHasErrors(['nomor_soal']);
    }

    public function test_store_rejects_exceeding_max_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 2,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $this->createSoal($mapel, 1, 'pilihan_ganda');
        $this->createSoal($mapel, 2, 'pilihan_ganda');

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.soal.store', [$paket, $mapel]), [
                'nomor_soal' => 3,
                'tipe_soal' => 'pilihan_ganda',
                'indikator' => 'Kelebihan',
                'pertanyaan' => 'Soal ke-3 di mapel yang max 2',
                'bobot' => 1,
                'pilihan' => [
                    ['kode' => 'A', 'teks' => 'A'],
                    ['kode' => 'B', 'teks' => 'B'],
                    ['kode' => 'C', 'teks' => 'C'],
                    ['kode' => 'D', 'teks' => 'D'],
                ],
                'jawaban_benar' => 'A',
            ]);

        $response->assertSessionHasErrors(['nomor_soal']);
    }

    public function test_superadmin_can_update_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);
        $soal = $this->createSoal($mapel, 1, 'pilihan_ganda');

        $response = $this->actingAs($superadmin)
            ->put(route('superadmin.soal.update', [$paket, $mapel, $soal]), [
                'nomor_soal' => 1,
                'tipe_soal' => 'pilihan_ganda',
                'indikator' => 'Updated indikator',
                'pertanyaan' => 'Pertanyaan yang sudah diubah',
                'bobot' => 2,
                'pilihan' => [
                    ['kode' => 'A', 'teks' => 'A-updated'],
                    ['kode' => 'B', 'teks' => 'B-updated'],
                    ['kode' => 'C', 'teks' => 'C-updated'],
                    ['kode' => 'D', 'teks' => 'D-updated'],
                ],
                'jawaban_benar' => 'C',
            ]);

        $response->assertRedirect(route('superadmin.soal.index', [$paket, $mapel]));

        $this->assertDatabaseHas('soals', [
            'id' => $soal->id,
            'pertanyaan' => 'Pertanyaan yang sudah diubah',
            'bobot' => 2,
        ]);

        $this->assertDatabaseHas('pilihan_jawabans', [
            'soal_id' => $soal->id,
            'kode' => 'C',
            'teks' => 'C-updated',
            'is_benar' => true,
        ]);
    }

    public function test_superadmin_can_delete_soal(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);
        $soal = $this->createSoal($mapel, 1, 'pilihan_ganda');

        $response = $this->actingAs($superadmin)
            ->delete(route('superadmin.soal.destroy', [$paket, $mapel, $soal]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('soals', ['id' => $soal->id]);
        $this->assertDatabaseMissing('pilihan_jawabans', ['soal_id' => $soal->id]);
    }

    public function test_superadmin_can_import_from_bank(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = $this->createMapel($paket);

        $globalQuestion = GlobalQuestion::create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Ibukota Indonesia?',
            'options' => ['Jakarta', 'Bandung', 'Surabaya', 'Medan'],
            'answer_key' => 'Jakarta',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
                'global_question_ids' => [$globalQuestion->id],
            ]);

        $response->assertRedirect(route('superadmin.soal.index', [$paket, $mapel]));

        $this->assertDatabaseHas('soals', [
            'mapel_paket_id' => $mapel->id,
            'pertanyaan' => 'Ibukota Indonesia?',
            'tipe_soal' => 'pilihan_ganda',
        ]);

        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();
        $this->assertDatabaseHas('pilihan_jawabans', [
            'soal_id' => $soal->id,
            'kode' => 'A',
            'teks' => 'Jakarta',
            'is_benar' => true,
        ]);
    }

    public function test_import_from_bank_respects_max_slot(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 1,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        // Fill the single slot
        $this->createSoal($mapel, 1, 'pilihan_ganda');

        $globalQuestion = GlobalQuestion::create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Soal yang tidak muat',
            'options' => ['A', 'B', 'C', 'D'],
            'answer_key' => 'A',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
                'global_question_ids' => [$globalQuestion->id],
            ]);

        $response->assertRedirect();

        // Should still only have 1 soal (the original), not 2
        $this->assertDatabaseCount('soals', 1);
        $this->assertDatabaseMissing('soals', [
            'pertanyaan' => 'Soal yang tidak muat',
        ]);
    }

    public function test_index_aborts_on_mismatched_mapel_paket(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket1 = $this->createPaket($superadmin);
        $mapel1 = $this->createMapel($paket1);
        $paket2 = $this->createPaket($superadmin, 'Paket Kedua');

        $response = $this->actingAs($superadmin)
            ->get(route('superadmin.soal.index', [$paket2, $mapel1]));

        $response->assertNotFound();
    }

    private function inertiaHeaders(string $url): array
    {
        $this->get($url);

        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ];
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createPaket(User $superadmin, string $nama = 'Paket Test'): PaketSoal
    {
        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        return PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => $nama,
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);
    }

    private function createMapel(PaketSoal $paket): MapelPaket
    {
        return MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 30,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);
    }

    private function createSoal(MapelPaket $mapel, int $nomor, string $tipe): Soal
    {
        $soal = Soal::create([
            'mapel_paket_id' => $mapel->id,
            'nomor_soal' => $nomor,
            'tipe_soal' => $tipe,
            'jenis_instrumen' => 'akademik',
            'indikator' => 'Test soal',
            'pertanyaan' => 'Test pertanyaan '.$nomor,
            'bobot' => 1,
        ]);

        if ($tipe === 'pilihan_ganda') {
            foreach (['A', 'B', 'C', 'D'] as $kode) {
                PilihanJawaban::create([
                    'soal_id' => $soal->id,
                    'kode' => $kode,
                    'teks' => 'Pilihan '.$kode,
                    'is_benar' => $kode === 'A',
                ]);
            }
        }

        return $soal;
    }
}
