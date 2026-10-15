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
use Tests\TestCase;

/**
 * Regression: jalur import bank (superadmin SoalController@importFromBank, guru SoalGuruController@importFromUjion)
 * harus pakai resolver kunci yang sama — kunci huruf dipetakan ke indeks opsi, teks opsi tetap cocok,
 * dan kunci ke opsi ke-5 (tak tertampung enum kode A-D) dilewati tanpa 500.
 */
class ImportBankAnswerKeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superadmin_import_resolves_letter_answer_key_against_text_options(): void
    {
        [$superadmin, $paket, $mapel] = $this->superadminContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm'], 'C');

        $this->actingAs($superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect(route('superadmin.soal.index', [$paket, $mapel]));

        $benar = $this->benarUntukMapel($mapel);

        $this->assertCount(1, $benar, 'Tepat satu opsi harus jadi kunci.');
        $this->assertSame('C', $benar->first()->kode);
        $this->assertSame('88 cm', $benar->first()->teks);
        $this->assertSame(4, PilihanJawaban::count(), 'Opsi ke-4.. tidak boleh terbuang.');
    }

    public function test_superadmin_import_still_matches_answer_key_by_text(): void
    {
        [$superadmin, $paket, $mapel] = $this->superadminContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm'], '154 cm');

        $this->actingAs($superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect();

        $benar = $this->benarUntukMapel($mapel);

        $this->assertCount(1, $benar);
        $this->assertSame('D', $benar->first()->kode);
    }

    public function test_superadmin_import_skips_soal_with_answer_key_on_fifth_option(): void
    {
        [$superadmin, $paket, $mapel] = $this->superadminContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm', '176 cm'], 'E');

        $this->actingAs($superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('flash');

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count(), 'Soal setengah jadi tidak boleh tersimpan.');
        $this->assertSame(0, PilihanJawaban::count());
    }

    public function test_superadmin_import_skips_soal_tanpa_kunci_valid(): void
    {
        [$superadmin, $paket, $mapel] = $this->superadminContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm'], null);

        $this->actingAs($superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
    }

    public function test_guru_import_resolves_letter_answer_key_against_text_options(): void
    {
        [$guru, $paket, $mapel] = $this->guruContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm'], 'C');

        $this->actingAs($guru)
            ->post(route('guru.soal.import-ujion', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect(route('guru.soal.index', [$paket, $mapel]));

        $benar = $this->benarUntukMapel($mapel);

        $this->assertCount(1, $benar);
        $this->assertSame('C', $benar->first()->kode);
        $this->assertSame('88 cm', $benar->first()->teks);
    }

    public function test_guru_import_resolves_text_answer_key(): void
    {
        [$guru, $paket, $mapel] = $this->guruContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm'], '88 cm');

        $this->actingAs($guru)
            ->post(route('guru.soal.import-ujion', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect();

        $benar = $this->benarUntukMapel($mapel);

        $this->assertCount(1, $benar);
        $this->assertSame('C', $benar->first()->kode);
    }

    public function test_guru_import_fifth_option_key_skipped_not_500(): void
    {
        [$guru, $paket, $mapel] = $this->guruContext();
        $gq = $this->globalQuestion(['22 cm', '44 cm', '88 cm', '154 cm', '176 cm'], 'E');

        $this->actingAs($guru)
            ->post(route('guru.soal.import-ujion', [$paket, $mapel]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertRedirect();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(0, PilihanJawaban::count());
    }

    private function benarUntukMapel(MapelPaket $mapel)
    {
        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();

        return PilihanJawaban::where('soal_id', $soal->id)->where('is_benar', true)->get();
    }

    private function globalQuestion(array $options, ?string $answerKey): GlobalQuestion
    {
        return GlobalQuestion::create([
            'jenjang_id' => Jenjang::where('kode', 'SMP')->value('id'),
            'question_type' => 'multiple_choice',
            'question_text' => 'Keliling bangun berikut adalah '.uniqid(),
            'options' => $options,
            'answer_key' => $answerKey,
            'is_active' => true,
        ]);
    }

    private function superadminContext(): array
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $paket = $this->paket($superadmin);

        return [$superadmin, $paket, $this->mapel($paket)];
    }

    private function guruContext(): array
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);

        $paket = $this->paket($guru);

        return [$guru, $paket, $this->mapel($paket)];
    }

    private function paket(User $owner): PaketSoal
    {
        return PaketSoal::create([
            'jenjang_id' => Jenjang::where('kode', 'SMP')->firstOrFail()->id,
            'nama' => 'Paket Import Kunci',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $owner->id,
        ]);
    }

    private function mapel(PaketSoal $paket): MapelPaket
    {
        return MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);
    }
}
