<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PersonalQuestion;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Tests\TestCase;

class GuruProfileAndRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::putValue('doku_enabled', '1');
        AppSetting::putValue('doku_client_id', 'BRN-test-client-id');
        AppSetting::putValue('doku_secret_key', 'SK-test-secret-key');
    }

    public function test_landing_points_to_guru_registration_route(): void
    {
        $this->assertTrue(Route::has('register.guru.form'));
        $this->assertStringContainsString(
            "route('register.guru.form')",
            file_get_contents(resource_path('views/landing.blade.php'))
        );
    }

    public function test_guest_pages_have_theme_toggle(): void
    {
        $loginUrl = route('login');
        $login = $this->get($loginUrl, $this->inertiaHeaders($loginUrl));

        $login->assertOk();
        $this->assertSame('Auth/Login', $login->json('component'));

        $this->get(route('register.guru.form'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false);
    }

    public function test_login_links_to_guru_token_request_form(): void
    {
        $this->assertTrue(Route::has('guru.token-request.form'));

        $loginUrl = route('login');
        $login = $this->get($loginUrl, $this->inertiaHeaders($loginUrl));

        $login->assertOk();
        $this->assertSame('Auth/Login', $login->json('component'));

        $tokenUrl = route('guru.token-request.form');
        $tokenForm = $this->get($tokenUrl, $this->inertiaHeaders($tokenUrl));

        $tokenForm->assertOk();
        $this->assertSame('Auth/ForgotToken', $tokenForm->json('component'));
        $this->assertNotEmpty($tokenForm->json('props.jenjangs'));
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
        config(['services.admin.whatsapp' => '62 812-3456-7890']);

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
        config(['services.admin.whatsapp' => '']);

        $response = $this->post(route('guru.token-request.send'), [
            'name' => 'Guru Lupa Token',
            'contact' => 'guru.lupa@example.com',
            'jenjang' => 'SD',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('flash.type', 'warning');
    }

    public function test_guru_registration_creates_pending_account_and_logs_in(): void
    {
        $response = $this->withSession(['_token' => 'register-token'])->post(route('register.guru'), [
            '_token' => 'register-token',
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('guru.dashboard'));
        $this->assertDatabaseHas('users', [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'no_wa' => '08123456789',
        ]);

        $user = User::where('email', 'guru.baru@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_guru_can_start_payment_from_dashboard(): void
    {
        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => [
                    'payment' => [
                        'url' => 'https://checkout.doku.com/payment-url-123',
                    ],
                ],
            ], 201),
        ]);

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 100000,
            'is_active' => true,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);

        $response = $this->actingAs($guru)->postJson(route('payments.doku.start'));

        $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('payment_url', 'https://checkout.doku.com/payment-url-123');

        $guru->refresh();
        $transaction = $guru->transactions()->first();

        $this->assertNotNull($transaction);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(100000.0, (float) $transaction->amount);
    }

    public function test_registration_auto_login_allows_starting_payment_directly(): void
    {
        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => [
                    'payment' => [
                        'url' => 'https://checkout.doku.com/payment-url-persist',
                    ],
                ],
            ], 201),
        ]);

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 100000,
            'is_active' => true,
        ]);

        $response = $this->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru.persist@example.com',
            'jenjang' => 'SMP',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-7777-9999',
        ]);

        $response->assertRedirect(route('guru.dashboard'));

        $paymentResponse = $this->postJson(route('payments.doku.start'));

        $paymentResponse->assertOk()->assertJsonPath('ok', true);

        $guru = User::where('email', 'guru.persist@example.com')->firstOrFail();
        $transaction = $guru->transactions()->first();

        $this->assertNotNull($transaction);
    }

    public function test_duplicate_pending_registration_logs_user_back_in(): void
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

        $response->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($guru);
        $response->assertSessionHas('flash.message', 'Kami menemukan data pendaftaran Anda yang masih pending. Selesaikan pembayaran dari dashboard untuk mengaktifkan akun.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_pending_guru_can_access_dashboard_profile_and_chat_but_not_other_features(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);

        $this->actingAs($guru)->get(route('guru.dashboard'))->assertOk();
        $this->actingAs($guru)->get(route('guru.profile'))->assertOk();
        $this->actingAs($guru)->get(route('guru.chat'))->assertOk();

        $blocked = $this->actingAs($guru)->get(route('guru.materials'));
        $blocked->assertRedirect(route('guru.dashboard'));
        $blocked->assertSessionHas('flash.message', 'Fitur ini terbuka setelah pembayaran aktivasi berhasil. Silakan selesaikan pembayaran dari dashboard Anda.');
        $this->assertAuthenticatedAs($guru);

        $this->actingAs($guru)->get(route('guru.exams'))->assertRedirect(route('guru.dashboard'));
        $this->actingAs($guru)->get(route('guru.paket-soal.index'))->assertRedirect(route('guru.dashboard'));
    }

    public function test_dashboard_shows_payment_banner_with_locked_menus_for_pending_guru(): void
    {
        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 99000,
            'is_active' => true,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);

        $url = route('guru.dashboard');
        $response = $this->actingAs($guru)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame('Guru/Dashboard', $response->json('component'));
        $this->assertSame('Aktivasi SMP', $response->json('props.paymentBanner.planName'));
        $this->assertTrue($response->json('props.guruLayout.paymentLocked'));
        $this->assertNotNull($response->json('props.guruLayout.dokuConfig'));
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

    public function test_suspended_guru_is_logged_out_when_trying_to_open_guru_area(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_SUSPEND,
        ]);

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('flash.message', 'Akun Anda sedang ditangguhkan. Silakan hubungi admin.');
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

    public function test_guru_profile_shows_optional_password_form(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $url = route('guru.profile');
        $response = $this->actingAs($guru)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame('Guru/Profile', $response->json('component'));
        $this->assertSame($guru->name, $response->json('props.user.name'));
        $this->assertSame($guru->email, $response->json('props.user.email'));

        $this->assertTrue(Route::has('guru.profile.password'));
    }

    public function test_guru_can_set_password(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($guru)
            ->post(route('guru.profile.password'), [
                'password' => 'rahasia-baru-123',
                'password_confirmation' => 'rahasia-baru-123',
            ]);

        $response->assertRedirect();
        $guru->refresh();
        $this->assertTrue(Hash::check('rahasia-baru-123', $guru->password));
    }

    public function test_guru_password_requires_confirmation(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $oldHash = $guru->password;

        $this->actingAs($guru)
            ->post(route('guru.profile.password'), [
                'password' => 'rahasia-baru-123',
                'password_confirmation' => 'beda-sekali',
            ])->assertSessionHasErrors('password');

        $guru->refresh();
        $this->assertSame($oldHash, $guru->password);
    }

    public function test_active_guru_sidebar_shows_access_token_with_copy_button(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'no_wa' => '08123456789',
            'access_token' => 'ABC123TOKEN',
        ]);

        $url = route('guru.dashboard');
        $response = $this->actingAs($guru)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame('Guru/Dashboard', $response->json('component'));
        $this->assertSame('ABC123TOKEN', $response->json('props.auth.user.access_token'));
    }

    public function test_pending_guru_sidebar_does_not_show_access_token(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'access_token' => null,
        ]);

        $url = route('guru.dashboard');
        $response = $this->actingAs($guru)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertNull($response->json('props.auth.user.access_token'));
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

        $url = route('guru.dashboard');
        $response = $this->actingAs($guru)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame(Storage::url($guru->avatar), $response->json('props.auth.user.avatar_url'));
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

    private function inertiaHeaders(string $url): array
    {
        $this->get($url);

        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ];
    }
}
