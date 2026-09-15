<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Tests\TestCase;

class SuperadminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_header_dropdown_links_to_profile_page(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
            'name' => 'Admin Utama',
        ]);

        $dashboardUrl = route('superadmin.dashboard');
        $dashboard = $this->actingAs($superadmin)->get($dashboardUrl, $this->inertiaHeaders($dashboardUrl));

        $dashboard->assertOk();
        $this->assertSame('Superadmin/Dashboard', $dashboard->json('component'));
        $this->assertSame('Admin Utama', $dashboard->json('props.auth.user.name'));

        $profileUrl = route('superadmin.profile');
        $profile = $this->actingAs($superadmin)->get($profileUrl, $this->inertiaHeaders($profileUrl));

        $profile->assertOk();
        $this->assertSame('Superadmin/Profile', $profile->json('component'));
        $this->assertSame('Admin Utama', $profile->json('props.user.name'));
    }

    public function test_superadmin_can_update_profile_and_avatar(): void
    {
        Storage::fake('public');

        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
            'name' => 'Admin Lama',
            'email' => 'admin-lama@example.com',
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.profile.update'), [
            'name' => 'Admin Baru',
            'email' => 'admin-baru@example.com',
            'avatar' => UploadedFile::fake()->image('admin-avatar.jpg'),
        ]);

        $response->assertRedirect();
        $superadmin->refresh();

        $this->assertSame('Admin Baru', $superadmin->name);
        $this->assertSame('admin-baru@example.com', $superadmin->email);
        $this->assertNotNull($superadmin->avatar);
        Storage::disk('public')->assertExists($superadmin->avatar);

        $url = route('superadmin.dashboard');
        $response = $this->actingAs($superadmin)->get($url, $this->inertiaHeaders($url));

        $response->assertOk();
        $this->assertSame(Storage::url($superadmin->avatar), $response->json('props.auth.user.avatar_url'));
        $this->assertSame('Admin Baru', $response->json('props.auth.user.name'));
    }

    public function test_superadmin_can_change_password(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
            'password' => Hash::make('password-lama'),
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.profile.password'), [
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertRedirect();
        $superadmin->refresh();

        $this->assertTrue(Hash::check('password-baru', $superadmin->password));
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
