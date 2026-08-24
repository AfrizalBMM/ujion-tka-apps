<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\Material;
use App\Models\MaterialPracticePackageAttempt;
use App\Models\MaterialPracticeSession;
use App\Models\MaterialPracticeToken;
use App\Models\PaketSoal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthFlowHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_resume_rejects_mismatched_name_and_creates_new_session(): void
    {
        [$exam, $mapel] = $this->createExamFixture(10);

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Budi Santoso',
            'nomor_wa' => '628111222333',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
        ]);

        $response = $this->withSession([
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ])->post(route('siswa.mulai'), [
            'nama' => 'Attacker Lain',
            'wa' => '628111222333',
        ]);

        $response->assertRedirect(route('siswa.petunjuk'));
        $this->assertSame(2, UjianSesi::count());
    }

    public function test_exam_resume_accepts_matching_name_with_titles(): void
    {
        [$exam, $mapel] = $this->createExamFixture(10);

        $existing = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Dr. Budi Santoso, S.Pd.',
            'nomor_wa' => '628111222333',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
        ]);

        $response = $this->withSession([
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ])->post(route('siswa.mulai'), [
            'nama' => 'Budi Santoso',
            'wa' => '628111222333',
        ]);

        $response->assertRedirect(route('siswa.petunjuk'));
        $this->assertSame(1, UjianSesi::count());
        $this->assertSame($existing->session_token, session('participant_token'));
    }

    public function test_exam_rejects_new_session_when_quota_is_full(): void
    {
        [$exam, $mapel] = $this->createExamFixture(1);

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Peserta Pertama',
            'nomor_wa' => '628111222333',
            'session_token' => Str::random(60),
            'status' => 'selesai',
        ]);

        $response = $this->withSession([
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ])->post(route('siswa.mulai'), [
            'nama' => 'Peserta Kedua',
            'wa' => '628999999999',
        ]);

        $response->assertRedirect(route('siswa.identitas'));
        $response->assertSessionHasErrors('nama');
        $this->assertSame(1, UjianSesi::count());
    }

    public function test_exam_resume_still_allowed_when_quota_is_full(): void
    {
        [$exam, $mapel] = $this->createExamFixture(1);

        $existing = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Peserta Pertama',
            'nomor_wa' => '628111222333',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
        ]);

        $response = $this->withSession([
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ])->post(route('siswa.mulai'), [
            'nama' => 'Peserta Pertama',
            'wa' => '628111222333',
        ]);

        $response->assertRedirect(route('siswa.petunjuk'));
        $this->assertSame(1, UjianSesi::count());
        $this->assertSame($existing->session_token, session('participant_token'));
    }

    public function test_regenerate_packages_archives_attempts_instead_of_deleting(): void
    {
        $token = $this->createPracticeTokenWithQuestions(12);

        $session = MaterialPracticeSession::create([
            'material_practice_token_id' => $token->id,
            'nama' => 'Siswa Latihan',
            'nomor_wa' => '628111222333',
            'session_token' => Str::random(60),
            'status' => 'selesai',
        ]);

        $package = $token->packages()->where('paket_no', 1)->firstOrFail();

        MaterialPracticePackageAttempt::create([
            'material_practice_session_id' => $session->id,
            'material_practice_package_id' => $package->id,
            'paket_no' => $package->paket_no,
            'status' => 'selesai',
            'total_soal' => 10,
            'benar' => 8,
            'skor' => 80.0,
        ]);

        $attemptId = MaterialPracticePackageAttempt::firstOrFail()->id;

        $token->regeneratePackages();

        $archivedAttempt = MaterialPracticePackageAttempt::find($attemptId);
        $this->assertNotNull($archivedAttempt, 'Attempt harus tetap ada setelah regenerate.');
        $this->assertNull($archivedAttempt->material_practice_package_id);
        $this->assertSame(1, $archivedAttempt->paket_no);
        $this->assertSame(80.0, (float) $archivedAttempt->skor);
        $this->assertSame(3, $token->packages()->count());
    }

    private function createExamFixture(int $maxPeserta): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Auth',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $exam = Exam::create([
            'user_id' => $user->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Auth',
            'tanggal_terbit' => now(),
            'max_peserta' => $maxPeserta,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        return [$exam, $mapel];
    }

    private function createPracticeTokenWithQuestions(int $count): MaterialPracticeToken
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $material = Material::create([
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Numerasi',
            'unit' => 'Unit 1',
            'sub_unit' => 'Sub Unit 1',
            'jenjang' => 'SMP',
            'mapel' => 'Matematika',
        ]);

        for ($i = 0; $i < $count; $i++) {
            GlobalQuestion::create([
                'material_id' => $material->id,
                'question_type' => 'multiple_choice',
                'question_text' => 'Soal '.$i,
                'options' => ['A', 'B', 'C', 'D'],
                'answer_key' => 'A',
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        $token = MaterialPracticeToken::create([
            'material_id' => $material->id,
            'jumlah_soal_per_paket' => 10,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $token->regeneratePackages();

        return $token->refresh();
    }
}
