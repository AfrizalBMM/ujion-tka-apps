<?php

namespace Tests\Feature;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gate-1 QA (t_f42754e9) — re-verifikasi fix/sync-paket-kunci-jawaban-v2.
 * Fokus: kunci kode huruf vs opsi TEKS, kunci di luar A-D dilewati, jenjang, idempotency.
 */
class SyncPaketSoalKunciJawabanQaTest extends TestCase
{
    use RefreshDatabase;

    private function setup1(array $options, ?string $answerKey, string $jenjangKode = 'SD'): array
    {
        $jenjang = Jenjang::firstOrCreate(['kode' => $jenjangKode], ['nama' => 'Sekolah '.$jenjangKode, 'urutan' => 1]);
        Material::firstOrCreate(
            ['jenjang' => $jenjangKode, 'sub_unit' => 'Menghitung Keliling Lingkaran'],
            ['mapel' => 'Matematika', 'curriculum' => 'K-13', 'subelement' => 'Bilangan', 'unit' => 'Unit', 'link' => null]
        );

        GlobalQuestion::create([
            'jenjang_id' => $jenjang->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Keliling lingkaran diameter 28 cm adalah...',
            'options' => $options,
            'answer_key' => $answerKey,
            'is_active' => true,
        ]);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket QA '.$jenjangKode,
            'tahun_ajaran' => '2026/2027',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'otomatis',
            'kode_komponen' => 'matematika',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        return [$jenjang, $paket, $mapel];
    }

    public function test_kunci_huruf_a_pada_opsi_teks_menandai_tepat_satu_is_benar_di_a(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], 'A');
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();
        $benar = PilihanJawaban::where('soal_id', $soal->id)->where('is_benar', true)->get();

        $this->assertSame(4, PilihanJawaban::where('soal_id', $soal->id)->count());
        $this->assertCount(1, $benar);
        $this->assertSame('A', $benar->first()->kode);
        $this->assertSame('22 cm', $benar->first()->teks);
    }

    public function test_kunci_huruf_kecil_dan_spasi_tetap_terpetakan(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], ' c ');
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();
        $benar = PilihanJawaban::where('soal_id', $soal->id)->where('is_benar', true)->get();

        $this->assertCount(1, $benar);
        $this->assertSame('C', $benar->first()->kode);
        $this->assertSame('88 cm', $benar->first()->teks);
    }

    public function test_kunci_e_pada_5_opsi_dilewati_total_tanpa_soal_setengah_jadi(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm', '176 cm'], 'E');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])
            ->expectsOutputToContain('kunci tak valid 1')
            ->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(0, PilihanJawaban::count());
    }

    public function test_kunci_teks_yang_ada_di_opsi_ke_5_juga_dilewati(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm', '176 cm'], '176 cm');
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(0, PilihanJawaban::count());
    }

    public function test_soal_tanpa_kunci_dilewati_bukan_disimpan_tanpa_jawaban_benar(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], null);
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(0, PilihanJawaban::count());
    }

    public function test_campuran_valid_dan_invalid_hanya_menyimpan_yang_valid(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], 'B');

        GlobalQuestion::create([
            'jenjang_id' => Jenjang::where('kode', 'SD')->firstOrFail()->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Keliling lingkaran diameter 28 cm versi kedua adalah...',
            'options' => ['1 cm', '2 cm', '3 cm', '4 cm', '5 cm'],
            'answer_key' => 'E',
            'is_active' => true,
        ]);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])
            ->expectsOutputToContain('kunci tak valid 1')
            ->assertSuccessful();

        $soals = Soal::where('mapel_paket_id', $mapel->id)->get();
        $this->assertCount(1, $soals);
        $this->assertSame(4, PilihanJawaban::count());
        $this->assertSame(1, PilihanJawaban::where('is_benar', true)->count());
    }

    public function test_sub_unit_sama_beda_jenjang_tidak_bocor_lintas_jenjang(): void
    {
        // Bank berisi soal SD. Paket SMP dengan sub_unit materi bernama identik harus tetap kosong.
        [, $paketSd, $mapelSd] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], 'C', 'SD');
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paketSd->id])->assertSuccessful();
        $this->assertSame(1, Soal::where('mapel_paket_id', $mapelSd->id)->count());

        $smp = Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'Sekolah SMP', 'urutan' => 2]);
        Material::create([
            'jenjang' => 'SMP',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Unit',
            'sub_unit' => 'Menghitung Keliling Lingkaran',
            'link' => null,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);
        $paketSmp = PaketSoal::create([
            'jenjang_id' => $smp->id,
            'nama' => 'Paket QA SMP',
            'tahun_ajaran' => '2026/2027',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $mapelSmp = MapelPaket::create([
            'paket_soal_id' => $paketSmp->id,
            'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'otomatis',
            'kode_komponen' => 'matematika',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paketSmp->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapelSmp->id)->count());
        $this->assertSame(0, Soal::where('mapel_paket_id', $mapelSmp->id)
            ->where('pertanyaan', 'Keliling lingkaran diameter 28 cm adalah...')->count());
    }

    public function test_idempotent_2x_run_kunci_huruf_tidak_duplikasi_dan_is_benar_tetap_satu(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], 'C');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();
        $snapshot1 = PilihanJawaban::orderBy('id')->get(['kode', 'teks', 'is_benar'])->toArray();

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();
        $snapshot2 = PilihanJawaban::orderBy('id')->get(['kode', 'teks', 'is_benar'])->toArray();

        $this->assertSame(1, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(4, PilihanJawaban::count());
        $this->assertSame(1, PilihanJawaban::where('is_benar', true)->count());
        $this->assertSame($snapshot1, $snapshot2);
    }

    public function test_dry_run_tidak_menulis_apa_pun_termasuk_soal_invalid(): void
    {
        [, $paket, $mapel] = $this->setup1(['22 cm', '44 cm', '88 cm', '154 cm'], 'C');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id, '--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(0, PilihanJawaban::count());
    }
}
