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
use App\Models\PaketSoal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Tests\TestCase;

class FlowBankPaketUjianTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private Jenjang $jenjang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->jenjang = Jenjang::firstOrCreate(['kode' => 'SMP'], ['nama' => 'SMP', 'urutan' => 2]);
    }

    public function test_import_soal_global_question_text_kosong_dilewati(): void
    {
        $file = UploadedFile::fake()->createWithContent('soal.csv', implode("\n", [
            'question_type,question_text,material_mapel,material_curriculum,material_subelement,material_unit,material_sub_unit,option_a,option_b,answer_key',
            'multiple_choice,,Matematika,Merdeka,Numerasi,Aljabar,Persamaan linear,A,B,A',
            'multiple_choice,Soal valid,Matematika,Merdeka,Numerasi,Aljabar,Persamaan linear,A,B,A',
        ]));

        $response = $this->actingAs($this->superadmin)->post(route('superadmin.global-questions.import'), [
            'jenjang_id' => $this->jenjang->id,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('global_questions', 1);
        $this->assertDatabaseHas('global_questions', ['question_text' => 'Soal valid']);
        $this->assertStringContainsString('dilewati: 1', (string) session('flash.message'));
    }

    public function test_bank_builder_dropdown_jenjang_tidak_bocor(): void
    {
        Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'SD', 'urutan' => 1]);
        $paket = $this->buatPaket();
        $mapel = $this->buatMapel($paket);
        $milikPaket = $this->buatGq('Soal milik paket');
        $this->buatGq('Soal jenjang lain', jenjangId: Jenjang::firstWhere('kode', 'SD')->id);

        $url = route('superadmin.soal.bank-builder', [$paket, $mapel]);
        $this->actingAs($this->superadmin)->get($url);
        $response = $this->actingAs($this->superadmin)->get($url, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ]);

        $response->assertOk();
        $jenjangIds = collect($response->json('props.jenjangs'))->pluck('id')->all();
        $this->assertSame([$paket->jenjang_id], $jenjangIds);
        $bankIds = collect($response->json('props.bankSoals'))->pluck('id')->all();
        $this->assertContains($milikPaket->id, $bankIds);
        $this->assertCount(1, $bankIds);
    }

    public function test_import_from_bank_melebihi_jumlah_soal_dilewati_tanpa_error(): void
    {
        $paket = $this->buatPaket();
        $mapel = $this->buatMapel($paket, jumlahSoal: 2);
        $ids = [
            $this->buatGq('Bank A')->id,
            $this->buatGq('Bank B')->id,
            $this->buatGq('Bank C')->id,
            $this->buatGq('Bank D')->id,
        ];

        $response = $this->actingAs($this->superadmin)->post(
            route('superadmin.soal.import-from-bank', [$paket, $mapel]),
            ['global_question_ids' => $ids]
        );

        $response->assertRedirect(route('superadmin.soal.index', [$paket, $mapel]));
        $this->assertSame(2, $mapel->soals()->count());
        $this->assertStringContainsString('2 soal dilewati', (string) session('flash.message'));

        $this->actingAs($this->superadmin)->post(
            route('superadmin.soal.import-from-bank', [$paket, $mapel]),
            ['global_question_ids' => [$this->buatGq('Bank E')->id]]
        );

        $this->assertSame(2, $mapel->soals()->count());
    }

    public function test_import_from_bank_mapel_bukan_milik_paket_404(): void
    {
        $paket = $this->buatPaket();
        $this->buatMapel($paket);
        $paketLain = $this->buatPaket(nama: 'Paket Lain');
        $mapelLain = $this->buatMapel($paketLain);
        $gq = $this->buatGq('Soal uji 404');

        $this->actingAs($this->superadmin)
            ->post(route('superadmin.soal.import-from-bank', [$paket, $mapelLain]), [
                'global_question_ids' => [$gq->id],
            ])
            ->assertNotFound();

        $this->actingAs($this->superadmin)
            ->get(route('superadmin.soal.bank-builder', [$paket, $mapelLain]))
            ->assertNotFound();

        $this->assertSame(0, $mapelLain->soals()->count());
    }

    public function test_alur_bank_ke_ujian_siswa_memakai_soal_paket(): void
    {
        $paket = $this->buatPaket();
        $mapel = $this->buatMapel($paket, jumlahSoal: 3);
        $gqIds = [
            $this->buatGq('Bank soal 1')->id,
            $this->buatGq('Bank soal 2')->id,
            $this->buatGq('Bank soal 3')->id,
        ];

        $this->actingAs($this->superadmin)->post(
            route('superadmin.soal.import-from-bank', [$paket, $mapel]),
            ['global_question_ids' => $gqIds]
        )->assertRedirect();

        $this->assertSame(3, $mapel->soals()->count());

        $this->actingAs($this->superadmin)->post(route('superadmin.exams.store'), [
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Alur Bank Paket',
            'tanggal_terbit' => now()->format('Y-m-d H:i:s'),
            'max_peserta' => 30,
            'timer' => 75,
            'status' => 'draft',
        ])->assertRedirect();

        $exam = Exam::where('judul', 'Ujian Alur Bank Paket')->firstOrFail();
        $this->assertNotNull($exam->examMapelTokens()->where('mapel_paket_id', $mapel->id)->first());

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Siswa Alur Bank',
            'session_token' => Str::random(40),
            'status' => 'mengerjakan',
            'timer_state' => [
                $mapel->id => [
                    'duration_seconds' => 4500,
                    'remaining_seconds' => 4400,
                    'started_at' => now()->toIso8601String(),
                    'finished_at' => null,
                ],
            ],
        ]);

        $url = route('siswa.ujian');
        $response = $this->withSession(['participant_token' => $sesi->session_token])->get($url, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ]);

        $response->assertOk();
        $this->assertSame('Ujian/Pengerjaan', $response->json('component'));
        $questions = $response->json('props.questions');
        $this->assertCount(3, $questions);
        $this->assertSame(
            ['Bank soal 1', 'Bank soal 2', 'Bank soal 3'],
            collect($questions)->pluck('pertanyaan')->all()
        );
    }

    public function test_ujian_publik_landing_memakai_soal_paket_yang_sama(): void
    {
        $paket = $this->buatPaket();
        $mapel = $this->buatMapel($paket, jumlahSoal: 3);
        $gqIds = [
            $this->buatGq('Bank publik 1')->id,
            $this->buatGq('Bank publik 2')->id,
            $this->buatGq('Bank publik 3')->id,
        ];

        $this->actingAs($this->superadmin)->post(
            route('superadmin.soal.import-from-bank', [$paket, $mapel]),
            ['global_question_ids' => $gqIds]
        )->assertRedirect();

        $exam = Exam::create([
            'user_id' => $this->superadmin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Publik Alur Bank',
            'tanggal_terbit' => now(),
            'max_peserta' => 100,
            'timer' => 75,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        ExamMapelToken::create([
            'exam_id' => $exam->id,
            'mapel_paket_id' => $mapel->id,
        ]);

        $landingExam = LandingExam::create([
            'exam_id' => $exam->id,
            'jenjang' => 'SMP',
            'slug' => 'alur-bank-paket',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $landingMapel = LandingExamMapel::create([
            'landing_exam_id' => $landingExam->id,
            'mapel_paket_id' => $mapel->id,
            'price' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post(route('ujian-online.register', ['smp', $landingExam]), [
            'nama' => 'Siswa Publik',
            'nomor_wa' => '6281234567890',
            'mapel_id' => $landingMapel->id,
        ])->assertRedirect();

        $order = LandingExamOrder::where('landing_exam_mapel_id', $landingMapel->id)->firstOrFail();
        $this->get(route('ujian-online.start', $order->session_token))->assertRedirect(route('siswa.petunjuk'));

        $sesi = UjianSesi::where('landing_exam_order_id', $order->id)->firstOrFail();
        $this->assertSame($mapel->id, $sesi->mapel_paket_id);
        $this->assertSame(3, $sesi->mapelPaket->soals()->count());

        $response = $this->withSession(['participant_token' => $sesi->session_token])->get(route('siswa.ujian'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('props.questions'));
        $this->assertSame(
            ['Bank publik 1', 'Bank publik 2', 'Bank publik 3'],
            collect($response->json('props.questions'))->pluck('pertanyaan')->all()
        );
    }

    private function buatPaket(string $nama = 'Paket QA Alur'): PaketSoal
    {
        return PaketSoal::create([
            'jenjang_id' => $this->jenjang->id,
            'nama' => $nama,
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $this->superadmin->id,
        ]);
    }

    private function buatMapel(PaketSoal $paket, int $jumlahSoal = 30): MapelPaket
    {
        return MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'score',
            'kode_komponen' => 'MAT',
            'is_wajib' => true,
            'jumlah_soal' => $jumlahSoal,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);
    }

    private function buatGq(string $label, ?int $jenjangId = null): GlobalQuestion
    {
        return GlobalQuestion::create([
            'jenjang_id' => $jenjangId ?? $this->jenjang->id,
            'question_type' => 'multiple_choice',
            'question_text' => $label,
            'options' => ['1', '2', '3', '4'],
            'answer_key' => '2',
            'is_active' => true,
            'material_mapel' => 'Matematika',
            'material_curriculum' => 'Merdeka',
        ]);
    }
}
