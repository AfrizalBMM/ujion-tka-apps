<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\LandingExam;
use App\Models\LandingExamMapel;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\UjianSesi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembahasanPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_status_endpoint_returns_unlock_state(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        $response = $this->getJson(route('payments.pembahasan.status', $sesi));

        $response->assertOk()
            ->assertJson([
                'exam_session_id' => $sesi->id,
                'pembahasan_unlocked' => false,
                'unlocked_at' => null,
            ]);
    }

    public function test_status_endpoint_shows_unlocked_when_paid(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);
        $sesi->update(['pembahasan_unlocked_at' => now()]);

        $response = $this->getJson(route('payments.pembahasan.status', $sesi));

        $response->assertOk()
            ->assertJsonPath('pembahasan_unlocked', true);
        $this->assertNotNull($response->json('unlocked_at'));
    }

    public function test_start_rejects_non_owner(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        $otherUser = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);

        $response = $this->actingAs($otherUser)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => $sesi->id,
            ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_start_returns_already_unlocked_when_free(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        // Set pembahasan_price to 0 → free → immediate unlock
        AppSetting::putValue('pembahasan_price', '0');

        $response = $this->actingAs($superadmin)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => $sesi->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_unlocked', true);

        $sesi->refresh();
        $this->assertNotNull($sesi->pembahasan_unlocked_at);
    }

    public function test_start_returns_already_unlocked_when_already_paid(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);
        $sesi->update(['pembahasan_unlocked_at' => now()]);

        $response = $this->actingAs($superadmin)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => $sesi->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_unlocked', true);
    }

    public function test_start_validates_exam_session_id_required(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)
            ->postJson(route('payments.pembahasan.start'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['exam_session_id']);
    }

    public function test_start_validates_exam_session_exists(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['exam_session_id']);
    }

    public function test_start_returns_503_when_doku_not_enabled(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        // Ensure Doku is disabled (default state — no settings configured)
        AppSetting::putValue('pembahasan_price', '10000');

        $response = $this->actingAs($superadmin)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => $sesi->id,
            ]);

        // Doku not enabled → RuntimeException → 500
        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_guest_can_access_status_endpoint(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        // status endpoint has no auth middleware
        $response = $this->getJson(route('payments.pembahasan.status', $sesi));

        $response->assertOk();
    }

    public function test_start_accepts_owner_by_nomor_wa_match(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        // Create a guru user with matching nomor_wa
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
            'no_wa' => $sesi->nomor_wa,
        ]);

        // Set price to 0 so we get the free unlock path (no Doku needed)
        AppSetting::putValue('pembahasan_price', '0');

        $response = $this->actingAs($guru)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => $sesi->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_start_uses_landing_exam_mapel_price_over_default(): void
    {
        $superadmin = $this->createSuperadmin();
        $sesi = $this->createExamSession($superadmin);

        // Create a landing exam mapel with price=0 linked to the sesi's mapel
        $landingExam = LandingExam::create([
            'exam_id' => $sesi->exam_id,
            'jenjang' => 'SMP',
            'slug' => 'test-exam',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        LandingExamMapel::create([
            'landing_exam_id' => $landingExam->id,
            'mapel_paket_id' => $sesi->mapel_paket_id,
            'price' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Default price is non-zero, but landing exam mapel price is 0 → should be free
        AppSetting::putValue('pembahasan_price', '10000');

        $response = $this->actingAs($superadmin)
            ->postJson(route('payments.pembahasan.start'), [
                'exam_session_id' => $sesi->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('already_unlocked', true);

        $sesi->refresh();
        $this->assertNotNull($sesi->pembahasan_unlocked_at);
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createExamSession(User $owner): UjianSesi
    {
        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Test',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $owner->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $exam = Exam::create([
            'user_id' => $owner->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Pembahasan Test',
            'tanggal_terbit' => now(),
            'max_peserta' => 100,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        return UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'mapel_paket_id' => $mapel->id,
            'user_id' => $owner->id,
            'nama' => 'Test Siswa',
            'nomor_wa' => '6281234567890',
            'session_token' => 'test-token-'.uniqid(),
            'status' => 'completed',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now(),
            'skor' => 80,
        ]);
    }
}
