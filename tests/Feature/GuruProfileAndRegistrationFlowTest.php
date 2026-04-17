<?php

namespace Tests\Feature;

use App\Models\PersonalQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruProfileAndRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_points_to_guru_registration_route(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('register.guru.form'));
        $this->assertStringContainsString(
            "route('register.guru.form')",
            file_get_contents(resource_path('views/landing.blade.php'))
        );
    }

    public function test_guru_registration_creates_pending_account(): void
    {
        $response = $this->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('register.guru.pending'));
        $this->assertDatabaseHas('users', [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'no_wa' => '08123456789',
        ]);

        $user = User::where('email', 'guru.baru@example.com')->firstOrFail();
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_guru_can_upload_payment_proof_from_pending_page(): void
    {
        Storage::fake('public');

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
        ]);

        $response = $this->withSession([
            'pending_registration' => ['teacher_id' => $guru->id],
        ])->post(route('register.guru.payment-proof'), [
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('pending_registration');
        $guru->refresh();

        $this->assertSame(User::PAYMENT_SUBMITTED, $guru->payment_status);
        $this->assertNotNull($guru->payment_proof_path);
        $this->assertNotNull($guru->payment_submitted_at);
        Storage::disk('public')->assertExists($guru->payment_proof_path);
    }

    public function test_pending_registration_session_persists_for_upload_after_pending_page_is_opened(): void
    {
        Storage::fake('public');

        $response = $this->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru.persist@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-7777-9999',
        ]);

        $response->assertRedirect(route('register.guru.pending'));

        $this->get(route('register.guru.pending'))->assertOk();

        $uploadResponse = $this->post(route('register.guru.payment-proof'), [
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
        ]);

        $uploadResponse->assertRedirect(route('login'));

        $guru = User::where('email', 'guru.persist@example.com')->firstOrFail();
        $this->assertSame(User::PAYMENT_SUBMITTED, $guru->payment_status);
        $this->assertNotNull($guru->payment_proof_path);
        Storage::disk('public')->assertExists($guru->payment_proof_path);
    }

    public function test_duplicate_pending_registration_redirects_back_to_pending_page(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'email' => 'guru.pending@example.com',
            'no_wa' => '08123456789',
        ]);

        $response = $this->post(route('register.guru'), [
            'name' => 'Guru Daftar Ulang',
            'email' => 'guru.pending@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('register.guru.pending'));
        $response->assertSessionHas('pending_registration', [
            'teacher_id' => $guru->id,
            'harga' => null,
            'qr_url' => null,
        ]);
        $response->assertSessionHas('flash.message', 'Kami menemukan data pendaftaran Anda yang masih pending. Silakan lanjutkan dari halaman aktivasi pembayaran.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_active_registration_returns_clear_errors(): void
    {
        User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'email' => 'guru.aktif@example.com',
            'no_wa' => '08123456789',
        ]);

        $response = $this->from(route('register.guru.form'))->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru.aktif@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('register.guru.form'));
        $response->assertSessionHasErrors([
            'email' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau login bila akun Anda sudah aktif.',
            'no_wa' => 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau lanjutkan pendaftaran sebelumnya.',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_pending_guru_is_redirected_to_login_when_trying_to_open_guru_area(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
        ]);

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('flash.message', 'Akun Anda masih menunggu verifikasi pembayaran. Silakan tunggu token akses dari admin.');
        $this->assertGuest();
    }

    public function test_guru_can_update_complete_profile_fields(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SD',
            'tingkat' => '4',
            'satuan_pendidikan' => 'SD Lama',
            'no_wa' => '08111',
        ]);

        $response = $this->actingAs($guru)->post(route('guru.profile.update'), [
            'name' => 'Guru Update',
            'email' => 'guru-update@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '8',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '089999',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $guru->id,
            'name' => 'Guru Update',
            'email' => 'guru-update@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '8',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '089999',
        ]);
    }

    public function test_guru_cannot_delete_other_users_personal_question(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $otherGuru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $question = PersonalQuestion::create([
            'user_id' => $owner->id,
            'jenjang' => 'SMP',
            'kategori' => 'Sedang',
            'tipe' => 'PG',
            'pertanyaan' => 'Soal milik owner',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'A',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($otherGuru)->post(route('guru.personal-questions.destroy', $question));

        $response->assertNotFound();
        $this->assertDatabaseHas('personal_questions', ['id' => $question->id]);
    }

    public function test_personal_question_builder_only_replaces_current_guru_questions(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $otherGuru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        PersonalQuestion::create([
            'user_id' => $guru->id,
            'jenjang' => 'SMP',
            'kategori' => 'Sedang',
            'tipe' => 'PG',
            'pertanyaan' => 'Soal lama guru A',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'A',
            'status' => 'draft',
        ]);

        $otherQuestion = PersonalQuestion::create([
            'user_id' => $otherGuru->id,
            'jenjang' => 'SD',
            'kategori' => 'Mudah',
            'tipe' => 'PG',
            'pertanyaan' => 'Soal guru B',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'B',
            'status' => 'terbit',
        ]);

        $response = $this->actingAs($guru)->post(route('guru.personal-questions.builder.save'), [
            'questions' => [
                [
                    'jenjang' => 'SMP',
                    'kategori' => 'Sulit',
                    'tipe' => 'PG',
                    'pertanyaan' => 'Soal baru guru A',
                    'opsi' => ['1', '2', '3', '4'],
                    'jawaban_benar' => '4',
                    'pembahasan' => 'Pembahasan',
                    'image' => null,
                    'status' => 'draft',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('personal_questions', [
            'user_id' => $guru->id,
            'pertanyaan' => 'Soal lama guru A',
        ]);
        $this->assertDatabaseHas('personal_questions', [
            'user_id' => $guru->id,
            'pertanyaan' => 'Soal baru guru A',
        ]);
        $this->assertDatabaseHas('personal_questions', [
            'id' => $otherQuestion->id,
            'user_id' => $otherGuru->id,
            'pertanyaan' => 'Soal guru B',
        ]);
    }
}
