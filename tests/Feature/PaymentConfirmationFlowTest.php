<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentConfirmationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_approve_handles_missing_teacher_account_gracefully(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        Schema::disableForeignKeyConstraints();
        DB::table('transactions')->insert([
            'user_id' => 999999,
            'pricing_plan_id' => null,
            'reference_code' => 'UJN-TEST-ORPHAN',
            'plan_name' => 'Aktivasi Guru',
            'amount' => 150000,
            'status' => Transaction::STATUS_PENDING,
            'payment_proof_path' => 'payment-proofs/orphan-proof.png',
            'payment_submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::enableForeignKeyConstraints();

        $transaction = Transaction::query()->where('reference_code', 'UJN-TEST-ORPHAN')->firstOrFail();

        $response = $this->from(route('superadmin.payment-confirmations.index'))
            ->actingAs($superadmin)
            ->withSession(['_token' => 'approve-orphan-token'])
            ->post(route('superadmin.payment-confirmations.approve', $transaction), [
                '_token' => 'approve-orphan-token',
            ]);

        $response->assertRedirect(route('superadmin.payment-confirmations.index'));
        $response->assertSessionHas('flash', fn (array $flash) =>
            ($flash['type'] ?? null) === 'warning'
            && ($flash['title'] ?? null) === 'Akun guru tidak ditemukan'
        );

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_PENDING,
        ]);
    }
}
