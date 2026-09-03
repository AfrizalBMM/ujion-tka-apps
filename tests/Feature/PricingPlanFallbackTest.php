<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PricingPlanFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::putValue('midtrans_enabled', '1');
        AppSetting::putValue('midtrans_environment', 'sandbox');
        AppSetting::putValue('midtrans_server_key', 'SB-Mid-server-testkey');
        AppSetting::putValue('midtrans_client_key', 'SB-Mid-client-testkey');

        Http::fake([
            '*/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-plan',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/snap-token-plan',
            ], 201),
        ]);
    }

    public function test_teacher_is_not_charged_plan_from_other_jenjang(): void
    {
        PricingPlan::create([
            'name' => 'Aktivasi SD',
            'jenjang' => 'SD',
            'price' => 50000,
            'is_active' => true,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMA',
        ]);

        $response = $this->withSession([
            'pending_registration' => ['teacher_id' => $guru->id],
        ])->postJson(route('payments.midtrans.start'));

        $response->assertStatus(422);
        $this->assertSame(0, $guru->transactions()->count());
    }

    public function test_teacher_uses_own_jenjang_plan(): void
    {
        PricingPlan::create([
            'name' => 'Aktivasi SD',
            'jenjang' => 'SD',
            'price' => 50000,
            'is_active' => true,
        ]);

        $smaPlan = PricingPlan::create([
            'name' => 'Aktivasi SMA',
            'jenjang' => 'SMA',
            'price' => 150000,
            'is_active' => true,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMA',
        ]);

        $response = $this->withSession([
            'pending_registration' => ['teacher_id' => $guru->id],
        ])->postJson(route('payments.midtrans.start'));

        $response->assertOk()->assertJsonPath('ok', true);
        $transaction = $guru->transactions()->firstOrFail();
        $this->assertSame($smaPlan->id, $transaction->pricing_plan_id);
        $this->assertSame(150000.0, (float) $transaction->amount);
    }

    public function test_teacher_falls_back_to_global_plan(): void
    {
        $globalPlan = PricingPlan::create([
            'name' => 'Aktivasi Umum',
            'jenjang' => null,
            'price' => 75000,
            'is_active' => true,
        ]);

        PricingPlan::create([
            'name' => 'Aktivasi SD',
            'jenjang' => 'SD',
            'price' => 50000,
            'is_active' => true,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);

        $response = $this->withSession([
            'pending_registration' => ['teacher_id' => $guru->id],
        ])->postJson(route('payments.midtrans.start'));

        $response->assertOk()->assertJsonPath('ok', true);
        $transaction = $guru->transactions()->firstOrFail();
        $this->assertSame($globalPlan->id, $transaction->pricing_plan_id);
    }

    public function test_start_payment_rejected_when_no_plan_available(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMA',
        ]);

        $response = $this->withSession([
            'pending_registration' => ['teacher_id' => $guru->id],
        ])->postJson(route('payments.midtrans.start'));

        $response->assertStatus(422);
        $guru->refresh();
        $this->assertSame(User::PAYMENT_AWAITING, $guru->payment_status);
        $this->assertSame(0, $guru->transactions()->count());
    }

    public function test_stale_pending_transactions_are_cancelled_when_price_changes(): void
    {
        $plan = PricingPlan::create([
            'name' => 'Aktivasi SMA',
            'jenjang' => 'SMA',
            'price' => 100000,
            'is_active' => true,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMA',
        ]);

        $stale = $guru->transactions()->create([
            'pricing_plan_id' => $plan->id,
            'reference_code' => 'UJN-260101-OLDDDDDD',
            'plan_name' => $plan->name,
            'amount' => '100000',
            'status' => 'pending',
        ]);

        $plan->update(['price' => 125000]);

        $this->withSession([
            'pending_registration' => ['teacher_id' => $guru->id],
        ])->postJson(route('payments.midtrans.start'));

        $stale->refresh();
        $this->assertSame('failed', $stale->status);
        $this->assertSame('Dibatalkan otomatis karena tarif berubah.', $stale->rejection_reason);

        $newTransaction = $guru->transactions()->where('status', 'pending')->firstOrFail();
        $this->assertSame(125000.0, (float) $newTransaction->amount);
        $this->assertSame(2, $guru->transactions()->count());
    }
}
