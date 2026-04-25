<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Utama')
            ->assertSee(route('superadmin.profile'), false);

        $this->actingAs($superadmin)
            ->get(route('superadmin.profile'))
            ->assertOk()
            ->assertSee('Profil Superadmin')
            ->assertSee('Data Profil')
            ->assertSee('Ganti Password');
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

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee(Storage::url($superadmin->avatar), false)
            ->assertSee('Admin Baru');
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
}
