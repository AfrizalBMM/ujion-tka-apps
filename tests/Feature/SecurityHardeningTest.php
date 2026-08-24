<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_wa_webhook_rejects_requests_when_key_is_not_configured(): void
    {
        config(['services.wa_webhook.key' => null]);

        $this->postJson(route('api.wa-webhook'), [
            'from' => '628123456789',
            'message' => 'LUPA TOKEN',
        ])->assertStatus(503);
    }

    public function test_wa_webhook_rejects_wrong_key(): void
    {
        config(['services.wa_webhook.key' => 'correct-secret-key']);

        $this->postJson(route('api.wa-webhook'), [
            'from' => '628123456789',
            'message' => 'LUPA TOKEN',
        ], ['X-WA-WEBHOOK-KEY' => 'wrong-key'])->assertStatus(401);
    }

    public function test_wa_webhook_does_not_reset_token_without_valid_key(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'no_wa' => '628123456789',
            'access_token' => 'OLDTOKEN123',
        ]);

        config(['services.wa_webhook.key' => null]);

        $response = $this->postJson(route('api.wa-webhook'), [
            'from' => '628123456789',
            'message' => 'LUPA TOKEN',
        ]);

        $response->assertStatus(503);
        $teacher->refresh();
        $this->assertSame('OLDTOKEN123', $teacher->access_token);
    }

    public function test_student_cannot_save_answer_for_other_mapel(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Cross',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $mapelSesi = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $mapelLain = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'bahasa_indonesia',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'urutan' => 2,
        ]);

        $soalLain = Soal::create([
            'mapel_paket_id' => $mapelLain->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'indikator' => 'Indikator',
            'pertanyaan' => 'Soal mapel lain',
            'bobot' => 1,
        ]);

        $exam = Exam::create([
            'user_id' => $user->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Cross',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'mapel_paket_id' => $mapelSesi->id,
            'nama' => 'Siswa Cross',
            'session_token' => Str::random(40),
            'status' => 'mengerjakan',
            'timer_state' => [
                $mapelSesi->id => [
                    'duration_seconds' => 3600,
                    'remaining_seconds' => 3600,
                    'started_at' => now()->toIso8601String(),
                    'finished_at' => null,
                ],
            ],
        ]);

        $response = $this->withSession([
            'participant_token' => $sesi->session_token,
            '_token' => 'cross-mapel-token',
        ])
            ->withHeader('X-CSRF-TOKEN', 'cross-mapel-token')
            ->postJson(route('siswa.api.save_answer'), [
                'question_id' => $soalLain->id,
                'mapel_paket_id' => $mapelLain->id,
                'tipe_soal' => 'pilihan_ganda',
                'jawaban_pg' => 'A',
                'is_ragu' => false,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('jawaban_siswas', [
            'ujian_sesi_id' => $sesi->id,
            'soal_id' => $soalLain->id,
        ]);
    }

    public function test_admin_login_is_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login'), [
                'email' => 'superadmin@ujion.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('admin.login'), [
            'email' => 'superadmin@ujion.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
