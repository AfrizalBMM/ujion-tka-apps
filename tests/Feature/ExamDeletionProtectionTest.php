<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\PaketSoal;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamDeletionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_with_results_requires_typed_confirmation(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $exam = $this->createExam($superadmin);

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => 'Peserta Penting',
            'session_token' => Str::random(40),
            'status' => 'selesai',
            'skor' => 90,
        ]);

        // Tanpa konfirmasi → ditolak, exam tetap ada.
        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.exams.destroy', $exam));

        $response->assertRedirect();
        $response->assertSessionHasErrors('confirm_text');
        $this->assertDatabaseHas('exams', ['id' => $exam->id]);
        $this->assertSame(1, UjianSesi::count());

        // Konfirmasi salah → ditolak.
        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.exams.destroy', $exam), ['confirm_text' => 'SALAH']);

        $response->assertSessionHasErrors('confirm_text');
        $this->assertDatabaseHas('exams', ['id' => $exam->id]);

        // Konfirmasi benar → terhapus dengan pesan jumlah hasil.
        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.exams.destroy', $exam), ['confirm_text' => 'hapus']);

        $response->assertRedirect();
        $response->assertSessionHas('flash');
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
        $this->assertSame(0, UjianSesi::count());
    }

    public function test_exam_without_results_deletes_without_confirmation(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $exam = $this->createExam($superadmin);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.exams.destroy', $exam));

        $response->assertRedirect();
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    private function createExam(User $superadmin): Exam
    {
        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Delete',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        return Exam::create([
            'user_id' => $superadmin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Delete Guard',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);
    }
}
