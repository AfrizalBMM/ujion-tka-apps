<?php

namespace Tests\Feature;

use App\Models\PersonalQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
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

    public function test_login_links_to_guru_token_request_form(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('guru.token-request.form'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Lupa token?')
            ->assertSee('No. WhatsApp')
            ->assertDontSee('Nama Lengkap atau No. WhatsApp')
            ->assertSee(route('guru.token-request.form'), false);

        $this->get(route('guru.token-request.form'))
            ->assertOk()
            ->assertSee('Nama Lengkap')
            ->assertSee('Email / No. WhatsApp Aktif')
            ->assertSee('Jenjang');
    }

    public function test_guru_can_login_using_registered_whatsapp_number(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'no_wa' => '08123456789',
            'access_token' => 'ABC123TOKEN',
        ]);

        $response = $this->withSession(['_token' => 'login-wa-token'])->post(route('login'), [
            '_token' => 'login-wa-token',
            'no_wa' => '0812-3456-789',
            'access_token' => 'abc123token',
        ]);

        $response->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($guru);
    }

    public function test_guru_login_rejects_name_as_identifier(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'name' => 'Siti Rahayu',
            'no_wa' => '08123456789',
            'access_token' => 'ABC123TOKEN',
        ]);

        $response = $this->from(route('login'))
            ->withSession(['_token' => 'login-name-token'])
            ->post(route('login'), [
                '_token' => 'login-name-token',
                'no_wa' => $guru->name,
                'access_token' => 'ABC123TOKEN',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'access_token' => 'No. WA atau Token Akses tidak sesuai.',
        ]);
        $this->assertGuest();
    }

    public function test_guru_token_request_redirects_to_admin_whatsapp(): void
    {
        config(['services.qris.admin_whatsapp' => '62 812-3456-7890']);

        $response = $this->post(route('guru.token-request.send'), [
            'name' => 'Guru Lupa Token',
            'contact' => '0812-2222-3333',
            'jenjang' => 'SMP',
        ]);

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $message = (string) ($query['text'] ?? '');

        $this->assertStringContainsString('Saya lupa token akses guru.', $message);
        $this->assertStringContainsString('Nama lengkap: Guru Lupa Token', $message);
        $this->assertStringContainsString('Email/No. WhatsApp aktif: 0812-2222-3333', $message);
        $this->assertStringContainsString('Jenjang: SMP', $message);
    }

    public function test_guru_token_request_shows_warning_when_admin_whatsapp_is_missing(): void
    {
        config(['services.qris.admin_whatsapp' => '']);

        $response = $this->post(route('guru.token-request.send'), [
            'name' => 'Guru Lupa Token',
            'contact' => 'guru.lupa@example.com',
            'jenjang' => 'SD',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('flash.type', 'warning');
    }

    public function test_guru_registration_creates_pending_account(): void
    {
        $response = $this->withSession(['_token' => 'register-token'])->post(route('register.guru'), [
            '_token' => 'register-token',
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'jenjang' => 'SMP',
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
        config(['services.qris.admin_whatsapp' => '']);

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
        config(['services.qris.admin_whatsapp' => '']);

        $response = $this->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru.persist@example.com',
            'jenjang' => 'SMP',
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

        $response = $this->withSession(['_token' => 'pending-token'])->post(route('register.guru'), [
            '_token' => 'pending-token',
            'name' => 'Guru Daftar Ulang',
            'email' => 'guru.pending@example.com',
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('register.guru.pending'));
        $response->assertSessionHas('pending_registration.teacher_id', $guru->id);
        $response->assertSessionHas('pending_registration');
        $pendingRegistration = session('pending_registration');
        $this->assertIsArray($pendingRegistration);
        $this->assertArrayHasKey('pricing_plan_id', $pendingRegistration);
        $this->assertArrayHasKey('harga', $pendingRegistration);
        $this->assertNull($pendingRegistration['pricing_plan_id']);
        $this->assertNull($pendingRegistration['harga']);
        $response->assertSessionHas('flash.message', 'Kami menemukan data pendaftaran Anda yang masih pending. Silakan lanjutkan dari halaman aktivasi pembayaran.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_resume_pending_accepts_registered_name_without_titles(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'name' => 'Siti Rahayu, S.Pd.',
            'email' => 'siti.pending@example.com',
            'no_wa' => '08123456789',
            'jenjang' => 'SMP',
        ]);

        $response = $this->withSession(['_token' => 'resume-token'])->post(route('register.guru.pending.resume'), [
            '_token' => 'resume-token',
            'name' => 'Siti Rahayu',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('register.guru.pending'));
        $response->assertSessionHas('pending_registration.teacher_id', $guru->id);
    }

    public function test_resume_pending_rejects_partial_name_match_even_with_same_whatsapp(): void
    {
        User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'name' => 'Anastasia Putri',
            'email' => 'anastasia.pending@example.com',
            'no_wa' => '08123456789',
            'jenjang' => 'SMP',
        ]);

        $response = $this->from(route('register.guru.pending'))
            ->withSession(['_token' => 'resume-partial-token'])
            ->post(route('register.guru.pending.resume'), [
                '_token' => 'resume-partial-token',
                'name' => 'Ana',
                'no_wa' => '0812-3456-789',
            ]);

        $response->assertRedirect(route('register.guru.pending'));
        $response->assertSessionHasErrors([
            'resume' => 'Data pending tidak ditemukan. Pastikan nomor WhatsApp sama seperti saat pendaftaran. Nama boleh tanpa gelar.',
        ]);
    }

    public function test_duplicate_active_registration_returns_clear_errors(): void
    {
        User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'email' => 'guru.aktif@example.com',
            'no_wa' => '08123456789',
        ]);

        $response = $this->from(route('register.guru.form'))
            ->withSession(['_token' => 'active-token'])
            ->post(route('register.guru'), [
            '_token' => 'active-token',
            'name' => 'Guru Baru',
            'email' => 'guru.aktif@example.com',
            'jenjang' => 'SMP',
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
            'satuan_pendidikan' => 'SD Lama',
            'no_wa' => '08111',
        ]);

        $response = $this->actingAs($guru)->post(route('guru.profile.update'), [
            'name' => 'Guru Update',
            'email' => 'guru-update@example.com',
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '089999',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $guru->id,
            'name' => 'Guru Update',
            'email' => 'guru-update@example.com',
            'jenjang' => 'SD',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '089999',
        ]);
    }

    public function test_guru_profile_does_not_show_password_form(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($guru)
            ->get(route('guru.profile'))
            ->assertOk()
            ->assertDontSee('Ganti Password')
            ->assertDontSee('password_confirmation')
            ->assertDontSee('name="jenjang"', false);

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('guru.profile.password'));
    }

    public function test_uploaded_guru_avatar_is_used_in_header_dropdown(): void
    {
        Storage::fake('public');

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SD',
            'satuan_pendidikan' => 'SD Lama',
            'no_wa' => '08111',
        ]);

        $response = $this->actingAs($guru)->post(route('guru.profile.update'), [
            'name' => $guru->name,
            'email' => $guru->email,
            'jenjang' => 'SD',
            'satuan_pendidikan' => 'SD Lama',
            'no_wa' => '08111',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertRedirect();
        $guru->refresh();

        $this->assertNotNull($guru->avatar);
        Storage::disk('public')->assertExists($guru->avatar);

        $this->actingAs($guru)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee(\Illuminate\Support\Facades\Storage::url($guru->avatar), false);
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

    public function test_personal_question_builder_routes_are_resolved_before_parameter_routes(): void
    {
        $routes = app('router')->getRoutes();

        $builderRoute = $routes->match(Request::create('/guru/personal-questions/builder', 'GET'));
        $this->assertSame('guru.personal-questions.builder', $builderRoute->getName());

        $saveRoute = $routes->match(Request::create('/guru/personal-questions/builder/save', 'POST'));
        $this->assertSame('guru.personal-questions.builder.save', $saveRoute->getName());

        $updateRoute = $routes->match(Request::create('/guru/personal-questions/123', 'POST'));
        $this->assertSame('guru.personal-questions.update', $updateRoute->getName());
    }
}
