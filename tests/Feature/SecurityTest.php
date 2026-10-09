<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'role' => 'superadmin',
        ]);

        $user->refresh();

        // role column default is 'siswa' — mass-assigned 'superadmin' must be ignored.
        $this->assertNotSame('superadmin', $user->role);
        $this->assertSame('siswa', $user->role);
    }

    public function test_user_account_status_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker2@example.com',
            'password' => 'password',
            'account_status' => 'suspend',
        ]);

        $user->refresh();

        // account_status default is 'active' — 'suspend' must be ignored.
        $this->assertNotSame('suspend', $user->account_status);
    }

    public function test_user_access_token_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker3@example.com',
            'password' => 'password',
            'access_token' => 'stolen-sensitive-token',
        ]);

        $user->refresh();

        // access_token default is NULL — mass-assigned value must be ignored.
        $this->assertNull($user->access_token);
    }

    public function test_user_payment_status_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker4@example.com',
            'password' => 'password',
            'payment_status' => 'approved',
        ]);

        $user->refresh();

        // payment_status default is 'awaiting_payment' — 'approved' must be ignored.
        $this->assertNotSame('approved', $user->payment_status);
    }

    public function test_user_payment_reviewed_by_is_not_mass_assignable(): void
    {
        $reviewer = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'password',
        ]);

        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker5@example.com',
            'password' => 'password',
            'payment_reviewed_by' => $reviewer->id,
        ]);

        $user->refresh();

        $this->assertNull($user->payment_reviewed_by);
    }

    public function test_app_setting_only_allows_key_and_value_mass_assignment(): void
    {
        $setting = AppSetting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'id' => 999,
        ]);

        $setting->refresh();

        $this->assertSame('test_setting', $setting->key);
        $this->assertSame('test_value', $setting->value);
        // id is auto-increment — mass-assigned 999 must be ignored.
        $this->assertNotSame(999, $setting->id);
    }

    public function test_app_setting_fillable_contains_only_key_and_value(): void
    {
        $fillable = (new AppSetting)->getFillable();

        $this->assertSame(['key', 'value'], $fillable);
    }

    public function test_user_fillable_excludes_sensitive_fields(): void
    {
        $fillable = (new User)->getFillable();

        // Sensitive fields must NOT be mass-assignable.
        $this->assertNotContains('role', $fillable);
        $this->assertNotContains('account_status', $fillable);
        $this->assertNotContains('payment_status', $fillable);
        $this->assertNotContains('access_token', $fillable);
        $this->assertNotContains('payment_reviewed_by', $fillable);
        $this->assertNotContains('payment_verified_at', $fillable);

        // Legitimate fields remain fillable.
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }
}
