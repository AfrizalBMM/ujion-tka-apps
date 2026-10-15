<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamMapelToken;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gate-1 QA (t_f42754e9) skenario 5 — ujian end-to-end tetap jalan setelah fix kunci jawaban.
 * Bank berisi 2 soal: kunci 'C' (4 opsi teks) yang valid + kunci 'E' (5 opsi) yang harus dilewati.
 * Siswa masuk lewat token, halaman ujian harus render 1 soal, dan menjawab 'C' harus skor ceiling.
 */
class SyncPaketKunciJawabanE2eTest extends TestCase
{
    use RefreshDatabase;

    public function test_ujian_end_to_end_render_soal_dan_skor_benar_setelah_fix_kunci(): void
    {
        $jenjang = Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'SD', 'urutan' => 1]);
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        Material::create([
            'jenjang' => 'SD', 'mapel' => 'Matematika', 'curriculum' => 'K-13',
            'subelement' => 'Geometri', 'unit' => 'Bangun Datar',
            'sub_unit' => 'Menghitung Keliling Lingkaran', 'link' => null,
        ]);

        GlobalQuestion::create([
            'jenjang_id' => $jenjang->id, 'question_type' => 'multiple_choice',
            'question_text' => 'Keliling lingkaran diameter 28 cm adalah...',
            'options' => ['22 cm', '44 cm', '88 cm', '154 cm'],
            'answer_key' => 'C', 'is_active' => true,
        ]);
        GlobalQuestion::create([
            'jenjang_id' => $jenjang->id, 'question_type' => 'multiple_choice',
            'question_text' => 'Keliling taman lingkaran diameter 28 m adalah...',
            'options' => ['22 m', '44 m', '88 m', '154 m', '176 m'],
            'answer_key' => 'E', 'is_active' => true,
        ]);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id, 'nama' => 'Paket E2E', 'tahun_ajaran' => '2026/2027',
            'is_active' => true, 'created_by' => $admin->id,
        ]);
        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id, 'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
            'kategori_komponen' => 'akademik', 'mode_penilaian' => 'otomatis',
            'kode_komponen' => 'matematika', 'is_wajib' => true,
            'jumlah_soal' => 30, 'durasi_menit' => 60, 'urutan' => 1,
        ]);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        // Soal berkunci E dilewati: hanya 1 soal tersimpan.
        $this->assertSame(1, Soal::where('mapel_paket_id', $mapel->id)->count());

        $exam = Exam::create([
            'user_id' => $admin->id, 'paket_soal_id' => $paket->id, 'judul' => 'Ujian E2E',
            'tanggal_terbit' => now(), 'max_peserta' => 50, 'timer' => 60,
            'status' => 'terbit', 'is_active' => true,
        ]);
        $token = ExamMapelToken::create(['exam_id' => $exam->id, 'mapel_paket_id' => $mapel->id]);

        $this->post(route('siswa.token.validate'), ['token' => $token->token])
            ->assertRedirect(route('siswa.identitas'))->assertSessionHasNoErrors();
        $this->post(route('siswa.mulai'), ['nama' => 'Siswa E2E', 'wa' => '081234567890'])
            ->assertRedirect(route('siswa.petunjuk'))->assertSessionHasNoErrors();
        $this->get(route('siswa.petunjuk'))->assertOk();

        $resp = $this->get(route('siswa.ujian'), ['X-Inertia' => 'true']);
        if ($resp->getStatusCode() === 409) {
            $resp = $this->get(route('siswa.ujian'), [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $resp->headers->get('X-Inertia-Version', ''),
            ]);
        }

        $resp->assertOk();
        $this->assertCount(1, $resp->json('props.questions'));
        $this->assertCount(4, $resp->json('props.questions.0.pilihan'));
        $this->assertSame(['A', 'B', 'C', 'D'], array_column($resp->json('props.questions.0.pilihan'), 'kode'));

        // Jawab opsi C (kunci huruf yang diresolve ke teks '88 cm') → benar, skor ceiling TKA.
        $soal = Soal::where('mapel_paket_id', $mapel->id)->firstOrFail();
        $this->postJson(route('siswa.api.save_answer'), [
            'question_id' => $soal->id, 'mapel_paket_id' => $mapel->id,
            'tipe_soal' => 'pilihan_ganda', 'jawaban_pg' => 'C',
        ])->assertOk();

        $this->post(route('siswa.selesai.submit'))->assertRedirect(route('siswa.selesai'));

        $sesi = UjianSesi::latest('id')->firstOrFail();
        $this->assertSame(800.0, (float) $sesi->skor);
    }
}
