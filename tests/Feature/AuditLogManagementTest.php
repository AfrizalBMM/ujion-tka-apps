<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_audit_logs_with_summary(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'name' => 'Guru Rajin',
        ]);

        AuditLog::create([
            'user_id' => $guru->id,
            'method' => 'GET',
            'path' => 'guru/dashboard',
            'route_name' => 'guru.dashboard',
            'ip_address' => '192.168.1.x',
            'user_agent' => 'Chrome [sha1:ABC]',
        ]);

        AuditLog::create([
            'user_id' => $superadmin->id,
            'method' => 'POST',
            'path' => 'superadmin/exams',
            'route_name' => 'superadmin.exams.store',
            'ip_address' => '10.0.0.x',
            'user_agent' => 'Edge [sha1:DEF]',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Guru Rajin', false);
        $response->assertSee('guru.dashboard', false);
        $response->assertSee('superadmin.exams.store', false);
    }

    public function test_audit_log_filters_apply(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        AuditLog::create([
            'user_id' => $superadmin->id,
            'method' => 'GET',
            'path' => 'superadmin/dashboard',
            'route_name' => 'superadmin.dashboard',
        ]);

        AuditLog::create([
            'user_id' => $superadmin->id,
            'method' => 'POST',
            'path' => 'superadmin/exams',
            'route_name' => 'superadmin.exams.store',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.audit-logs.index', ['method' => 'POST']));

        $response->assertOk();
        $response->assertSee('superadmin.exams.store', false);
        $content = $response->getContent();
        $this->assertStringNotContainsString('superadmin/dashboard</td>', $content);
    }

    public function test_superadmin_can_cleanup_old_logs_only(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $old = AuditLog::create([
            'method' => 'GET',
            'path' => 'superadmin/old',
        ]);
        $old->update(['created_at' => now()->subDays(45)]);

        $recent = AuditLog::create([
            'method' => 'GET',
            'path' => 'superadmin/recent',
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.audit-logs.cleanup'), [
            'mode' => 'older_than_30d',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
    }

    public function test_superadmin_can_cleanup_all_logs(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $logA = AuditLog::create(['method' => 'GET', 'path' => 'a']);
        $logB = AuditLog::create(['method' => 'POST', 'path' => 'b']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.audit-logs.cleanup'), [
            'mode' => 'all',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('audit_logs', ['id' => $logA->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $logB->id]);
    }

    public function test_guest_cannot_access_audit_logs(): void
    {
        $this->get(route('superadmin.audit-logs.index'))->assertRedirect(route('login'));
    }
}
