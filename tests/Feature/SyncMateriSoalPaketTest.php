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

class SyncMateriSoalPaketTest extends TestCase
{
    use RefreshDatabase;

    private function jenjang(string $kode = 'SD'): Jenjang
    {
        return Jenjang::firstOrCreate(
            ['kode' => $kode],
            ['nama' => 'Sekolah '.$kode, 'urutan' => 1]
        );
    }

    private function material(string $subUnit, string $mapel = 'Matematika'): Material
    {
        return Material::create([
            'jenjang' => 'SD',
            'mapel' => $mapel,
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Unit',
            'sub_unit' => $subUnit,
            'link' => null,
        ]);
    }

    private function globalQuestion(string $text, int $jenjangId, string $tipe = 'multiple_choice'): GlobalQuestion
    {
        return GlobalQuestion::create([
            'jenjang_id' => $jenjangId,
            'question_type' => $tipe,
            'question_text' => $text,
            'options' => ['A', 'B', 'C', 'D'],
            'answer_key' => 'A',
            'is_active' => true,
        ]);
    }

    private function paket(int $jenjangId, string $nama = 'Paket SD'): PaketSoal
    {
        return PaketSoal::create([
            'jenjang_id' => $jenjangId,
            'nama' => $nama,
            'tahun_ajaran' => '2026/2027',
            'is_active' => true,
            'created_by' => User::factory()->create(['role' => User::ROLE_SUPERADMIN])->id,
        ]);
    }

    private function mapel(int $paketId, string $namaMapel, int $jumlah = 30): MapelPaket
    {
        return MapelPaket::create([
            'paket_soal_id' => $paketId,
            'nama_mapel' => $namaMapel,
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'otomatis',
            'kode_komponen' => $namaMapel,
            'is_wajib' => true,
            'jumlah_soal' => $jumlah,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);
    }

    public function test_sync_materi_soal_links_question_to_material_by_keyword(): void
    {
        $jenjang = $this->jenjang();
        $material = $this->material('Menghitung Keliling Lingkaran');
        $gq = $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $jenjang->id);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $gq->refresh();

        $this->assertSame($material->id, $gq->material_id);
        $this->assertSame('Matematika', $gq->material_mapel);
        $this->assertSame('K-13', $gq->material_curriculum);
        $this->assertSame('Bilangan', $gq->material_subelement);
        $this->assertSame('Menghitung Keliling Lingkaran', $gq->material_sub_unit);
    }

    public function test_sync_materi_soal_is_idempotent(): void
    {
        $jenjang = $this->jenjang();
        $this->material('Menentukan Modus Data Tunggal');
        $this->globalQuestion('Modus dari 5, 6, 6, 7, 7, 7, 8, 9 adalah...', $jenjang->id);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $this->assertSame(1, GlobalQuestion::whereNotNull('material_id')->count());
    }

    public function test_sync_materi_soal_leaves_unmatched_question_untouched(): void
    {
        $jenjang = $this->jenjang();
        $this->material('Menghitung Keliling Lingkaran');
        $gq = $this->globalQuestion('Pertanyaan yang tidak terkait materi apa pun?', $jenjang->id);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $this->assertNull($gq->fresh()->material_id);
    }

    public function test_sync_paket_soal_fills_academic_mapel_from_linked_questions(): void
    {
        $jenjang = $this->jenjang();
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id);
        $mapel = $this->mapel($paket->id, 'matematika');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $soals = Soal::where('mapel_paket_id', $mapel->id)->get();

        $this->assertCount(1, $soals);
        $this->assertSame('pilihan_ganda', $soals->first()->tipe_soal);
        $this->assertSame(4, PilihanJawaban::where('soal_id', $soals->first()->id)->count());
        $this->assertSame(1, PilihanJawaban::where('soal_id', $soals->first()->id)->where('is_benar', true)->count());
    }

    public function test_sync_paket_soal_skips_survey_mapel(): void
    {
        $jenjang = $this->jenjang();
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id);
        $survey = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'kategori_komponen' => 'survey',
            'mode_penilaian' => 'otomatis',
            'kode_komponen' => 'survey_karakter',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 30,
            'urutan' => 2,
        ]);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $survey->id)->count());
    }

    public function test_sync_paket_soal_respects_jenjang_and_limit(): void
    {
        $sd = $this->jenjang('SD');
        $smp = $this->jenjang('SMP');

        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $sd->id);

        $paketSmp = $this->paket($smp->id, 'Paket SMP');
        $mapelSmp = $this->mapel($paketSmp->id, 'matematika');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paketSmp->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapelSmp->id)->count());
    }

    public function test_sync_paket_soal_is_idempotent(): void
    {
        $jenjang = $this->jenjang();
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id);
        $mapel = $this->mapel($paket->id, 'matematika');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(1, Soal::where('mapel_paket_id', $mapel->id)->count());
    }

    public function test_sync_materi_soal_does_not_collide_across_jenjang(): void
    {
        $sd = $this->jenjang('SD');
        $smp = $this->jenjang('SMP');

        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Bilangan',
            'unit' => 'Unit',
            'sub_unit' => 'Menghitung Keliling Lingkaran',
            'link' => null,
        ]);
        $materialSmp = Material::create([
            'jenjang' => 'SMP',
            'mapel' => 'Matematika',
            'curriculum' => 'K-13',
            'subelement' => 'Geometri',
            'unit' => 'Lingkaran',
            'sub_unit' => 'Menghitung Keliling Lingkaran',
            'link' => null,
        ]);

        $gq = $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $smp->id);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $this->assertSame($materialSmp->id, $gq->fresh()->material_id);
    }

    public function test_sync_paket_soal_skips_question_without_jenjang(): void
    {
        $jenjang = $this->jenjang();
        $this->material('Menghitung Keliling Lingkaran');
        $gq = $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah...', $jenjang->id);
        $gq->forceFill(['material_id' => Material::first()->id, 'material_mapel' => 'Matematika', 'jenjang_id' => null])->save();

        $paket = $this->paket($jenjang->id);
        $mapel = $this->mapel($paket->id, 'matematika');

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapel->id)->count());
    }
}
