<?php

namespace Tests\Feature;

use App\Http\Controllers\Superadmin\TeacherController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_superadmin_can_approve_submitted_payment_and_activate_teacher(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_SUBMITTED,
            'payment_proof_path' => 'payment-proofs/proof.png',
            'access_token' => null,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.teachers.approve-payment', $teacher));

        $response->assertRedirect();
        $teacher->refresh();

        $this->assertSame(User::STATUS_ACTIVE, $teacher->account_status);
        $this->assertSame(User::PAYMENT_APPROVED, $teacher->payment_status);
        $this->assertSame($superadmin->id, $teacher->payment_reviewed_by);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', (string) $teacher->access_token);
    }

    public function test_superadmin_can_reject_submitted_payment_with_reason(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_SUBMITTED,
            'payment_proof_path' => 'payment-proofs/proof.png',
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.teachers.reject-payment', $teacher), [
            'payment_rejection_reason' => 'Bukti transfer tidak menampilkan nominal yang jelas.',
        ]);

        $response->assertRedirect();
        $teacher->refresh();

        $this->assertSame(User::STATUS_PENDING, $teacher->account_status);
        $this->assertSame(User::PAYMENT_REJECTED, $teacher->payment_status);
        $this->assertSame($superadmin->id, $teacher->payment_reviewed_by);
        $this->assertSame('Bukti transfer tidak menampilkan nominal yang jelas.', $teacher->payment_rejection_reason);
    }

    public function test_manual_activate_is_blocked_when_payment_is_waiting_review(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_SUBMITTED,
            'payment_proof_path' => 'payment-proofs/proof.png',
            'access_token' => null,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.teachers.activate', $teacher));

        $response->assertRedirect();
        $teacher->refresh();

        $this->assertNull($teacher->access_token);
        $this->assertSame(User::STATUS_PENDING, $teacher->account_status);
        $response->assertSessionHas('flash', fn (array $flash) => ($flash['type'] ?? null) === 'warning');
    }

    public function test_superadmin_can_filter_teacher_list_by_payment_status(): void
    {
        $submittedTeacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_SUBMITTED,
            'name' => 'Guru Review',
        ]);

        User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'name' => 'Guru Belum Bayar',
        ]);

        $request = Request::create(route('superadmin.teachers.index', [
            'payment_status' => User::PAYMENT_SUBMITTED,
        ]), 'GET');

        $view = app(TeacherController::class)->index($request);
        $teachers = $view->getData()['teachers'];

        $this->assertCount(1, $teachers);
        $this->assertTrue($teachers->contains('id', $submittedTeacher->id));
        $this->assertFalse($teachers->contains('name', 'Guru Belum Bayar'));
    }
}
