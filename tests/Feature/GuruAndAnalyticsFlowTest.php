<?php

namespace Tests\Feature;

use App\Http\Controllers\Guru\ExamController as GuruExamController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\ExamAnalysisController as SuperadminExamAnalysisController;
use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PersonalQuestion;
use App\Models\PilihanJawaban;
use App\Models\PricingPlan;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Tests\TestCase;

class GuruAndAnalyticsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_dashboard_uses_ujian_sesi_metrics(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
            'no_wa' => '0812345',
        ]);

        $exam = $this->createExamSuite($guru)['exam'];

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => $guru->name,
            'nomor_wa' => $guru->no_wa,
            'session_token' => 'tok-1',
            'status' => 'selesai',
            'skor' => 80,
            'waktu_selesai' => now(),
        ]);

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => $guru->name,
            'nomor_wa' => $guru->no_wa,
            'session_token' => 'tok-2',
            'status' => 'mengerjakan',
        ]);

        Auth::login($guru);

        $view = app(GuruDashboardController::class)->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(2, $view->getData()['ujianDibuat']);
        $this->assertSame(1, $view->getData()['totalPeserta']);
        $this->assertSame(80.0, $view->getData()['rataRataKelas']);
    }

    public function test_guru_can_join_exam_into_student_flow(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
            'no_wa' => '0812345',
        ]);

        $suite = $this->createExamSuite($guru);
        $examMapelToken = $suite['examMapelToken'];

        $response = $this->actingAs($guru)->post(route('guru.exams.join'), [
            'token' => $examMapelToken->token,
        ]);

        $response->assertRedirect(route('siswa.petunjuk'));
        $this->assertDatabaseHas('ujian_sesis', [
            'exam_id' => $suite['exam']->id,
            'nomor_wa' => $guru->no_wa,
            'status' => 'menunggu',
        ]);
    }

    public function test_guru_can_view_completed_exam_result(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
            'no_wa' => '0812345',
        ]);

        $suite = $this->createExamSuite($guru);
        $exam = $suite['exam'];
        $soal = $suite['soal'];

        $session = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => $guru->name,
            'nomor_wa' => $guru->no_wa,
            'session_token' => 'tok-result',
            'status' => 'selesai',
            'skor' => 100,
            'waktu_selesai' => now(),
        ]);

        $session->jawabanSiswas()->create([
            'soal_id' => $soal->id,
            'tipe_soal' => 'pilihan_ganda',
            'jawaban_pg' => 'B',
        ]);

        Auth::login($guru);

        $view = app(GuruExamController::class)->result($exam);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('100.00', data_get($view->getData(), 'result.skor'));
        $this->assertSame('2 + 2 = ?', data_get($view->getData(), 'pembahasan.0.pertanyaan'));
        $this->assertSame('B', data_get($view->getData(), 'pembahasan.0.jawaban_user'));
    }

    public function test_superadmin_dashboard_uses_real_metrics(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
            'no_wa' => '08999',
        ]);

        PersonalQuestion::create([
            'user_id' => $guru->id,
            'jenjang' => 'SMP',
            'kategori' => 'Sedang',
            'tipe' => 'PG',
            'pertanyaan' => 'Kontribusi guru',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'A',
            'status' => 'terbit',
        ]);

        PricingPlan::create([
            'name' => 'Plan 1',
            'price' => '150000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $suite = $this->createExamSuite($superadmin);
        UjianSesi::create([
            'exam_id' => $suite['exam']->id,
            'paket_soal_id' => $suite['exam']->paket_soal_id,
            'nama' => 'Peserta Aktif',
            'nomor_wa' => '081111',
            'session_token' => 'tok-live',
            'status' => 'mengerjakan',
        ]);

        Auth::login($superadmin);

        $view = app(SuperadminDashboardController::class)->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(1, $view->getData()['activeTeachersCount']);
        $this->assertSame(1, $view->getData()['ongoingExamsCount']);
        $this->assertSame(150000, $view->getData()['totalRevenue']);
        $this->assertSame($guru->name, $view->getData()['topTeacherName']);
    }

    public function test_superadmin_exam_analysis_uses_real_sessions(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $exam = $this->createExamSuite($superadmin)['exam'];

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => 'Peserta A',
            'nomor_wa' => '08001',
            'session_token' => 'tok-a',
            'status' => 'selesai',
            'skor' => 95,
            'waktu_selesai' => now(),
        ]);

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => 'Peserta B',
            'nomor_wa' => '08002',
            'session_token' => 'tok-b',
            'status' => 'selesai',
            'skor' => 72,
            'waktu_selesai' => now(),
        ]);

        Auth::login($superadmin);

        $view = app(SuperadminExamAnalysisController::class)->show($exam);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(2, $view->getData()['participantsCount']);
        $this->assertSame(83.5, $view->getData()['averageScore']);
        $this->assertSame('Peserta A', data_get($view->getData(), 'ranking.0.name'));
        $this->assertSame('Peserta B', data_get($view->getData(), 'ranking.1.name'));
    }

    private function createExamSuite(User $owner): array
    {
        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Analitik',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $owner->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 30,
            'urutan' => 1,
        ]);

        $soal = Soal::create([
            'mapel_paket_id' => $mapel->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'indikator' => 'Penjumlahan dasar',
            'pertanyaan' => '2 + 2 = ?',
            'bobot' => 1,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $kode) {
            PilihanJawaban::create([
                'soal_id' => $soal->id,
                'kode' => $kode,
                'teks' => $kode === 'B' ? '4' : 'Pilihan '.$kode,
                'is_benar' => $kode === 'B',
            ]);
        }

        $exam = Exam::create([
            'user_id' => $owner->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Analitik',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 30,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $examMapelToken = \App\Models\ExamMapelToken::create([
            'exam_id' => $exam->id,
            'mapel_paket_id' => $mapel->id,
            'token' => strtoupper(substr(md5((string) now()->timestamp.$owner->id.rand()), 0, 6)),
        ]);

        return compact('exam', 'paket', 'mapel', 'soal', 'examMapelToken');
    }
}
