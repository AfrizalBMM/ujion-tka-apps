<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiswaExamSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_answer_can_be_saved_in_new_schema(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Siswa',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 30,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);

        $soal = Soal::create([
            'mapel_paket_id' => $mapel->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'indikator' => 'Contoh indikator',
            'pertanyaan' => '2 + 2 = ?',
            'bobot' => 1,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $kode) {
            PilihanJawaban::create([
                'soal_id' => $soal->id,
                'kode' => $kode,
                'teks' => 'Pilihan '.$kode,
                'is_benar' => $kode === 'B',
            ]);
        }

        $exam = Exam::create([
            'user_id' => $user->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Paket',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 75,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'nama' => 'Siswa Contoh',
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

        $response = $this->withSession([
                'participant_token' => $sesi->session_token,
                '_token' => 'save-answer-token',
            ])
            ->withHeader('X-CSRF-TOKEN', 'save-answer-token')
            ->postJson(route('siswa.api.save_answer'), [
                'question_id' => $soal->id,
                'mapel_paket_id' => $mapel->id,
                'tipe_soal' => 'pilihan_ganda',
                'jawaban_pg' => 'B',
                'is_ragu' => false,
            ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'remaining_seconds' => 4500,
            'time_expired' => false,
        ]);
        $this->assertDatabaseHas('jawaban_siswas', [
            'ujian_sesi_id' => $sesi->id,
            'soal_id' => $soal->id,
            'jawaban_pg' => 'B',
        ]);
        $sesi->refresh();
        $this->assertSame(4500, data_get($sesi->timer_state, $mapel->id . '.remaining_seconds'));

        Carbon::setTestNow();
    }

    public function test_student_cannot_extend_timer_by_sending_large_remaining_seconds(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        [$soal, $mapel, $sesi] = $this->createExamSessionFixture(now()->subSeconds(120)->toIso8601String(), 300, 999999);

        $response = $this->withSession([
                'participant_token' => $sesi->session_token,
                '_token' => 'extend-timer-token',
            ])
            ->withHeader('X-CSRF-TOKEN', 'extend-timer-token')
            ->postJson(route('siswa.api.save_answer'), [
                'question_id' => $soal->id,
                'mapel_paket_id' => $mapel->id,
                'tipe_soal' => 'pilihan_ganda',
                'jawaban_pg' => 'B',
                'is_ragu' => false,
                'remaining_seconds' => 999999,
            ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'remaining_seconds' => 180,
            'time_expired' => false,
        ]);

        $sesi->refresh();
        $this->assertSame(180, data_get($sesi->timer_state, $mapel->id . '.remaining_seconds'));

        Carbon::setTestNow();
    }

    public function test_student_answer_is_rejected_when_server_timer_has_expired(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        [$soal, $mapel, $sesi] = $this->createExamSessionFixture(now()->subSeconds(301)->toIso8601String(), 300, 10);

        $response = $this->withSession([
                'participant_token' => $sesi->session_token,
                '_token' => 'expired-timer-token',
            ])
            ->withHeader('X-CSRF-TOKEN', 'expired-timer-token')
            ->postJson(route('siswa.api.save_answer'), [
                'question_id' => $soal->id,
                'mapel_paket_id' => $mapel->id,
                'tipe_soal' => 'pilihan_ganda',
                'jawaban_pg' => 'B',
                'is_ragu' => false,
                'remaining_seconds' => 999999,
            ]);

        $response->assertStatus(409)->assertJson([
            'status' => 'expired',
            'remaining_seconds' => 0,
        ]);

        $this->assertDatabaseMissing('jawaban_siswas', [
            'ujian_sesi_id' => $sesi->id,
            'soal_id' => $soal->id,
        ]);

        $sesi->refresh();
        $this->assertSame(0, data_get($sesi->timer_state, $mapel->id . '.remaining_seconds'));
        $this->assertNotNull(data_get($sesi->timer_state, $mapel->id . '.finished_at'));

        Carbon::setTestNow();
    }

    private function createExamSessionFixture(string $startedAt, int $durationSeconds, int $reportedRemaining): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Timer',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 30,
            'durasi_menit' => (int) ceil($durationSeconds / 60),
            'urutan' => 1,
        ]);

        $soal = Soal::create([
            'mapel_paket_id' => $mapel->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'indikator' => 'Contoh indikator',
            'pertanyaan' => '2 + 2 = ?',
            'bobot' => 1,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $kode) {
            PilihanJawaban::create([
                'soal_id' => $soal->id,
                'kode' => $kode,
                'teks' => 'Pilihan '.$kode,
                'is_benar' => $kode === 'B',
            ]);
        }

        $exam = Exam::create([
            'user_id' => $user->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Timer',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 75,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'nama' => 'Siswa Timer',
            'session_token' => Str::random(40),
            'status' => 'mengerjakan',
            'timer_state' => [
                $mapel->id => [
                    'duration_seconds' => $durationSeconds,
                    'remaining_seconds' => $reportedRemaining,
                    'started_at' => $startedAt,
                    'finished_at' => null,
                ],
            ],
        ]);

        return [$soal, $mapel, $sesi];
    }
}
