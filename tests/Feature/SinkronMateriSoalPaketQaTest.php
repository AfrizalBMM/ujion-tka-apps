<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamMapelToken;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\LandingExam;
use App\Models\LandingExamMapel;
use App\Models\LandingExamOrder;
use App\Models\MapelPaket;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SinkronMateriSoalPaketQaTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function jenjang(string $kode): Jenjang
    {
        return Jenjang::firstOrCreate(
            ['kode' => $kode],
            ['nama' => 'Sekolah '.$kode, 'urutan' => 1]
        );
    }

    private function material(string $subUnit, string $jenjang = 'SD', string $mapel = 'Matematika'): Material
    {
        return Material::create([
            'jenjang' => $jenjang,
            'mapel' => $mapel,
            'curriculum' => 'K-13',
            'subelement' => 'Geometri',
            'unit' => 'Bangun Datar',
            'sub_unit' => $subUnit,
            'link' => null,
        ]);
    }

    private function globalQuestion(string $text, int $jenjangId, array $overrides = []): GlobalQuestion
    {
        return GlobalQuestion::create(array_merge([
            'jenjang_id' => $jenjangId,
            'question_type' => 'multiple_choice',
            'question_text' => $text,
            'options' => ['A. satu', 'B. dua', 'C. tiga', 'D. empat'],
            'answer_key' => 'B. dua',
            'is_active' => true,
        ], $overrides));
    }

    private function paket(int $jenjangId, User $creator, string $nama = 'Paket Uji'): PaketSoal
    {
        return PaketSoal::create([
            'jenjang_id' => $jenjangId,
            'nama' => $nama,
            'tahun_ajaran' => '2026/2027',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
    }

    private function mapel(int $paketId, string $namaMapel, int $jumlah = 30, string $kategori = 'akademik', int $urutan = 1): MapelPaket
    {
        return MapelPaket::create([
            'paket_soal_id' => $paketId,
            'nama_mapel' => $namaMapel,
            'kategori_komponen' => $kategori,
            'mode_penilaian' => $kategori === 'survey' ? 'profiling' : 'otomatis',
            'kode_komponen' => $namaMapel,
            'is_wajib' => true,
            'jumlah_soal' => $jumlah,
            'durasi_menit' => 60,
            'urutan' => $urutan,
        ]);
    }

    public function test_scenario_1_materi_soal_fills_full_snapshot_only_for_matching_keyword(): void
    {
        $jenjang = $this->jenjang('SD');
        $material = $this->material('Menghitung Keliling Lingkaran');

        $cocok = $this->globalQuestion('Keliling lingkaran dengan diameter 28 cm adalah ...', $jenjang->id);
        $takCocok = $this->globalQuestion('Pernyataan yang tidak terkait kurikulum apa pun sama sekali?', $jenjang->id);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $cocok->refresh();
        $this->assertSame($material->id, $cocok->material_id);
        $this->assertSame('Matematika', $cocok->material_mapel);
        $this->assertSame('K-13', $cocok->material_curriculum);
        $this->assertSame('Geometri', $cocok->material_subelement);
        $this->assertSame('Bangun Datar', $cocok->material_unit);
        $this->assertSame('Menghitung Keliling Lingkaran', $cocok->material_sub_unit);

        $takCocok->refresh();
        $this->assertNull($takCocok->material_id);
        $this->assertNull($takCocok->material_mapel);
        $this->assertNull($takCocok->material_sub_unit);
    }

    public function test_scenario_1_materi_soal_is_idempotent_across_two_runs(): void
    {
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');
        $this->material('Menentukan Modus Data Tunggal', 'SD', 'Matematika');

        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah ...', $jenjang->id);
        $this->globalQuestion('Modus dari 5, 6, 6, 7, 7, 7, 8, 9 adalah ...', $jenjang->id);

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();
        $after1 = GlobalQuestion::orderBy('id')->get(['material_id', 'material_sub_unit'])->toArray();

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();
        $after2 = GlobalQuestion::orderBy('id')->get(['material_id', 'material_sub_unit'])->toArray();

        $this->assertSame($after1, $after2);
        $this->assertSame(2, GlobalQuestion::whereNotNull('material_id')->count());
        $this->assertSame(2, GlobalQuestion::count());
    }

    public function test_scenario_2_paket_soal_fills_academic_mapel_and_skips_survey(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah ...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $akademik = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30, 'akademik', 1);

        $paketSurvey = $this->paket($jenjang->id, $admin, 'Paket Survey');
        $survey = MapelPaket::create([
            'paket_soal_id' => $paketSurvey->id,
            'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
            'kategori_komponen' => 'survey',
            'mode_penilaian' => 'profiling',
            'kode_komponen' => 'survey_karakter',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 30,
            'urutan' => 1,
        ]);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paketSurvey->id])->assertSuccessful();

        $this->assertSame(1, Soal::where('mapel_paket_id', $akademik->id)->count());
        $this->assertSame(0, Soal::where('mapel_paket_id', $survey->id)->count());
    }

    public function test_scenario_2_paket_soal_does_not_leak_jenjang(): void
    {
        $admin = $this->superadmin();
        $sd = $this->jenjang('SD');
        $smp = $this->jenjang('SMP');

        $this->material('Menghitung Keliling Lingkaran', 'SD');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah ...', $sd->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paketSmp = $this->paket($smp->id, $admin, 'Paket SMP');
        $mapelSmp = $this->mapel($paketSmp->id, MapelPaket::NAMA_MATEMATIKA);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paketSmp->id])->assertSuccessful();

        $this->assertSame(0, Soal::where('mapel_paket_id', $mapelSmp->id)->count());
    }

    public function test_scenario_2_paket_soal_respects_jumlah_soal_limit(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');

        for ($i = 1; $i <= 5; $i++) {
            $this->globalQuestion("Keliling lingkaran ke-{$i} dengan diameter 28 cm adalah ...", $jenjang->id);
        }

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 3);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(3, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(3, Soal::where('mapel_paket_id', $mapel->id)->distinct('nomor_soal')->count('nomor_soal'));
    }

    public function test_scenario_2_paket_soal_is_idempotent_across_two_runs(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');

        for ($i = 1; $i <= 3; $i++) {
            $this->globalQuestion("Keliling lingkaran ke-{$i} dengan diameter 28 cm adalah ...", $jenjang->id);
        }

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $this->assertSame(3, Soal::where('mapel_paket_id', $mapel->id)->count());
        $this->assertSame(12, PilihanJawaban::whereIn('soal_id', Soal::where('mapel_paket_id', $mapel->id)->pluck('id'))->count());
    }

    public function test_scenario_3_every_synced_soal_has_exactly_one_correct_answer_and_four_options(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');

        for ($i = 1; $i <= 4; $i++) {
            $this->globalQuestion("Keliling lingkaran ke-{$i} dengan diameter 28 cm adalah ...", $jenjang->id);
        }

        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30);

        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $soals = Soal::where('mapel_paket_id', $mapel->id)->get();
        $this->assertCount(4, $soals);

        foreach ($soals as $soal) {
            $this->assertSame('pilihan_ganda', $soal->tipe_soal);
            $this->assertSame(4, PilihanJawaban::where('soal_id', $soal->id)->count());
            $this->assertSame(1, PilihanJawaban::where('soal_id', $soal->id)->where('is_benar', true)->count());
            $this->assertSame(1, PilihanJawaban::where('soal_id', $soal->id)->where('kode', 'B')->where('is_benar', true)->count());
        }
    }

    private function inertialGet(string $url): TestResponse
    {
        $response = $this->get($url, ['X-Inertia' => 'true']);

        if ($response->getStatusCode() === 409) {
            $version = $response->headers->get('X-Inertia-Version', '');
            $response = $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => $version]);
        }

        return $response;
    }

    public function test_scenario_4_student_exam_page_renders_questions_not_error_redirect(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah ...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30);
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $exam = Exam::create([
            'user_id' => $admin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian SD QA',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Siswa QA',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
            'timer_state' => [
                $mapel->id => [
                    'duration_seconds' => 3600,
                    'remaining_seconds' => 3600,
                    'started_at' => now()->toIso8601String(),
                    'finished_at' => null,
                ],
            ],
        ]);

        $this->withSession([
            'participant_token' => $sesi->session_token,
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ]);

        $response = $this->inertialGet(route('siswa.ujian'));

        $response->assertOk();
        $this->assertSame('Ujian/Pengerjaan', $response->json('component'));
        $this->assertCount(1, $response->json('props.questions'));
        $this->assertSame('Keliling lingkaran diameter 28 cm adalah ...', $response->json('props.questions.0.pertanyaan'));
        $this->assertCount(4, $response->json('props.questions.0.pilihan'));
        $this->assertStringNotContainsString('Soal ujian belum siap', (string) $response->getContent());
    }

    public function test_scenario_4_student_token_login_leads_to_exam_page_with_questions(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah ...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30);
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $exam = Exam::create([
            'user_id' => $admin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian SD QA Token',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $examMapelToken = ExamMapelToken::create([
            'exam_id' => $exam->id,
            'mapel_paket_id' => $mapel->id,
        ]);

        $this->post(route('siswa.token.validate'), ['token' => $examMapelToken->token])
            ->assertRedirect(route('siswa.identitas'))
            ->assertSessionHasNoErrors();

        $this->post(route('siswa.mulai'), ['nama' => 'Siswa QA Token', 'wa' => '081234567890'])
            ->assertRedirect(route('siswa.petunjuk'))
            ->assertSessionHasNoErrors();

        $this->get(route('siswa.petunjuk'))->assertOk();

        $response = $this->inertialGet(route('siswa.ujian'));

        $response->assertOk();
        $this->assertSame('Ujian/Pengerjaan', $response->json('component'));
        $this->assertCount(1, $response->json('props.questions'));
        $this->assertStringNotContainsString('Soal ujian belum siap', (string) $response->getContent());
    }

    public function test_scenario_4_public_landing_exam_path_renders_questions(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');
        $this->material('Menghitung Keliling Lingkaran');
        $this->globalQuestion('Keliling lingkaran diameter 28 cm adalah ...', $jenjang->id);
        $this->artisan('tka:sync-materi-soal')->assertSuccessful();

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30);
        $this->artisan('tka:sync-paket-soal', ['--paket' => $paket->id])->assertSuccessful();

        $exam = Exam::create([
            'user_id' => $admin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Online SD',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $landingExam = LandingExam::create([
            'exam_id' => $exam->id,
            'jenjang' => 'SD',
            'short_description' => 'Uji coba',
            'description' => 'Uji coba ujian online',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $landingMapel = LandingExamMapel::create([
            'landing_exam_id' => $landingExam->id,
            'mapel_paket_id' => $mapel->id,
            'price' => 0,
            'original_price' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('ujian-online.show', ['jenjang' => 'sd', 'landingExam' => $landingExam->slug]))
            ->assertOk();

        $this->post(route('ujian-online.register', ['jenjang' => 'sd', 'landingExam' => $landingExam->slug]), [
            'nama' => 'Peserta Publik',
            'nomor_wa' => '081234567891',
            'mapel_id' => $landingMapel->id,
        ])->assertRedirect();

        $order = LandingExamOrder::latest('id')->firstOrFail();
        $this->assertTrue($order->isPaid());

        $this->get(route('ujian-online.start', $order->session_token))
            ->assertRedirect(route('siswa.petunjuk'));

        $this->get(route('siswa.petunjuk'))->assertOk();

        $response = $this->inertialGet(route('siswa.ujian'));

        $response->assertOk();
        $this->assertSame('Ujian/Pengerjaan', $response->json('component'));
        $this->assertCount(1, $response->json('props.questions'));
        $this->assertStringNotContainsString('Soal ujian belum siap', (string) $response->getContent());
    }

    public function test_scenario_5_public_landing_exam_without_soal_still_blocks_gracefully(): void
    {
        $admin = $this->superadmin();
        $jenjang = $this->jenjang('SD');

        $paket = $this->paket($jenjang->id, $admin);
        $mapel = $this->mapel($paket->id, MapelPaket::NAMA_MATEMATIKA, 30);

        $exam = Exam::create([
            'user_id' => $admin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Kosong',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Siswa Kosong',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
            'timer_state' => [
                $mapel->id => [
                    'duration_seconds' => 3600,
                    'remaining_seconds' => 3600,
                    'started_at' => now()->toIso8601String(),
                    'finished_at' => null,
                ],
            ],
        ]);

        $this->withSession([
            'participant_token' => $sesi->session_token,
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ])->get(route('siswa.ujian'))
            ->assertRedirect(route('siswa.petunjuk'))
            ->assertSessionHasErrors('ujian');
    }
}
