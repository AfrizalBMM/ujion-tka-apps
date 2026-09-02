<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogleUser(array $attributes = []): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->id = $attributes['id'] ?? 'google-123';
        $user->name = $attributes['name'] ?? 'Siti Aisyah';
        $user->email = $attributes['email'] ?? 'siti@gmail.com';
        $user->avatar = $attributes['avatar'] ?? 'https://lh3.googleusercontent.com/avatar.png';

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($user);

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        return $user;
    }

    public function test_redirect_to_google(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_callback_new_user_redirects_to_complete_data(): void
    {
        $this->mockGoogleUser();

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('auth.google.complete'));
        $response->assertSessionHas('google_registration', function ($value) {
            return $value['email'] === 'siti@gmail.com'
                && $value['google_id'] === 'google-123';
        });

        $this->assertDatabaseMissing('users', ['email' => 'siti@gmail.com']);
    }

    public function test_callback_existing_active_user_logs_in(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'email' => 'siti@gmail.com',
        ]);

        $this->mockGoogleUser();

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
    }

    public function test_callback_existing_pending_user_resumes_payment(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'email' => 'siti@gmail.com',
        ]);

        $this->mockGoogleUser();

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('register.guru.pending'));
        $response->assertSessionHas('pending_registration', function ($value) use ($user) {
            return (int) $value['teacher_id'] === $user->id;
        });
    }

    public function test_complete_data_creates_pending_teacher(): void
    {
        $this->withSession([
            'google_registration' => [
                'google_id' => 'google-123',
                'name' => 'Siti Aisyah',
                'email' => 'siti@gmail.com',
                'avatar' => 'https://lh3.googleusercontent.com/avatar.png',
            ],
        ]);

        $response = $this->post(route('auth.google.complete.store'), [
            'jenjang' => 'SD',
            'satuan_pendidikan' => 'SDN 01 Jakarta',
            'no_wa' => '08123456789',
        ]);

        $response->assertRedirect(route('register.guru.pending'));

        $this->assertDatabaseHas('users', [
            'email' => 'siti@gmail.com',
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SD',
            'google_id' => 'google-123',
        ]);
    }

    public function test_complete_data_requires_session(): void
    {
        $this->post(route('auth.google.complete.store'), [
            'jenjang' => 'SD',
            'satuan_pendidikan' => 'SDN 01',
            'no_wa' => '08123456789',
        ])->assertRedirect(route('register.guru.form'));
    }

    public function test_complete_data_rejects_duplicate_whatsapp(): void
    {
        User::factory()->create([
            'role' => User::ROLE_GURU,
            'no_wa' => '08123456789',
        ]);

        $this->withSession([
            'google_registration' => [
                'google_id' => 'google-123',
                'name' => 'Siti Aisyah',
                'email' => 'siti@gmail.com',
            ],
        ]);

        $response = $this->post(route('auth.google.complete.store'), [
            'jenjang' => 'SD',
            'satuan_pendidikan' => 'SDN 01 Jakarta',
            'no_wa' => '08123456789',
        ]);

        $response->assertSessionHasErrors('no_wa');
        $this->assertDatabaseMissing('users', ['email' => 'siti@gmail.com']);
    }

    public function test_profile_connect_links_google_account(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'email' => 'guru@example.com',
        ]);

        $this->actingAs($user);
        $this->withSession(['google_connect' => true]);

        $this->mockGoogleUser([
            'id' => 'google-link-1',
            'email' => 'google@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/link.png',
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('guru.profile'));

        $fresh = $user->fresh();
        $this->assertSame('google-link-1', $fresh->google_id);
        $this->assertSame('https://lh3.googleusercontent.com/link.png', $fresh->google_avatar);
        $this->assertSame('google@example.com', $fresh->email);
    }

    public function test_profile_connect_keeps_email_when_google_email_taken_by_other(): void
    {
        User::factory()->create([
            'role' => User::ROLE_GURU,
            'email' => 'conflict@example.com',
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'email' => 'guru@example.com',
        ]);

        $this->actingAs($user);
        $this->withSession(['google_connect' => true]);

        $this->mockGoogleUser([
            'id' => 'google-link-2',
            'email' => 'conflict@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('guru.profile'));

        $fresh = $user->fresh();
        $this->assertNull($fresh->google_id);
        $this->assertSame('guru@example.com', $fresh->email);
    }

    public function test_profile_connect_rejects_google_id_taken_by_other(): void
    {
        $other = User::factory()->create([
            'role' => User::ROLE_GURU,
            'google_id' => 'google-taken',
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->withSession(['google_connect' => true]);

        $this->mockGoogleUser(['id' => 'google-taken']);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('guru.profile'));

        $this->assertNull($user->fresh()->google_id);
        $this->assertSame('google-taken', $other->fresh()->google_id);
    }

    public function test_disconnect_clears_google_account(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'google_id' => 'google-123',
            'google_avatar' => 'https://lh3.googleusercontent.com/avatar.png',
        ]);

        $this->actingAs($user)
            ->post(route('guru.profile.google.disconnect'))
            ->assertRedirect(route('guru.profile'));

        $fresh = $user->fresh();
        $this->assertNull($fresh->google_id);
        $this->assertNull($fresh->google_avatar);
    }
}
