<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_generates_consistent_teacher_token_and_flashes_it(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'access_token' => null,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.teachers.activate', $teacher));

        $response->assertRedirect();
        $teacher->refresh();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', (string) $teacher->access_token);
        $response->assertSessionHas('flash', fn (array $flash) => ($flash['token'] ?? null) === $teacher->access_token);
    }

    public function test_refresh_token_uses_same_format_and_replaces_old_value(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'access_token' => 'ABCDEF1234',
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.teachers.refresh-token', $teacher));

        $response->assertRedirect();
        $teacher->refresh();

        $this->assertNotSame('ABCDEF1234', $teacher->access_token);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', (string) $teacher->access_token);
        $response->assertSessionHas('flash', fn (array $flash) => ($flash['token'] ?? null) === $teacher->access_token);
    }
}
