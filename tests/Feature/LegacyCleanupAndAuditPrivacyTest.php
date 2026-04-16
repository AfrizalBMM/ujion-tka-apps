<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyCleanupAndAuditPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_superadmin_and_student_artifacts_are_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Superadmin/QuestionController.php'));
        $this->assertFileDoesNotExist(resource_path('views/siswa/ujian.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/siswa/petunjuk.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/siswa/selesai.blade.php'));
    }

    public function test_audit_log_masks_path_ip_and_user_agent_metadata(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($superadmin)
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.45',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/135.0.0.0 Safari/537.36',
            ])
            ->post(route('superadmin.teachers.activate', $teacher));

        $response->assertRedirect();

        $log = AuditLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('superadmin/teachers/{id}/activate', $log->path);
        $this->assertSame('203.0.113.x', $log->ip_address);
        $this->assertMatchesRegularExpression('/^Chrome \[sha1:[A-F0-9]{10}\]$/', (string) $log->user_agent);
    }
}
