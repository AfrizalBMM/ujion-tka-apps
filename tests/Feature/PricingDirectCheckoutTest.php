<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PricingDirectCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::putValue('doku_enabled', '1');
        AppSetting::putValue('doku_client_id', 'BRN-test-client-id');
        AppSetting::putValue('doku_secret_key', 'SK-test-secret-key');
    }

    public function test_guest_gets_401_without_creating_transaction(): void
    {
        $this->postJson(route('payments.doku.start'))->assertStatus(401);

        $this->assertSame(0, Transaction::count());
    }

    public function test_suspend_teacher_gets_403_without_creating_transaction(): void
    {
        $guru = $this->createGuru(User::STATUS_SUSPEND);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(403);

        $this->assertSame(0, $guru->transactions()->count());
    }

    public function test_teacher_with_success_transaction_gets_409_with_success_status(): void
    {
        $guru = $this->createGuru(User::STATUS_PENDING);
        $guru->transactions()->create([
            'reference_code' => 'UJN-QA-0001',
            'plan_name' => 'Aktivasi Guru',
            'amount' => 99000,
            'status' => Transaction::STATUS_SUCCESS,
        ]);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))
            ->assertStatus(409)
            ->assertJsonPath('status', 'success');

        $this->assertSame(1, $guru->transactions()->count());
    }

    public function test_active_teacher_without_transaction_gets_409_with_success_status(): void
    {
        $guru = $this->createGuru(User::STATUS_ACTIVE);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))
            ->assertStatus(409)
            ->assertJsonPath('status', 'success');

        $this->assertSame(0, $guru->transactions()->count());
    }

    public function test_missing_plan_returns_422_without_creating_transaction(): void
    {
        $guru = $this->createGuru(User::STATUS_PENDING);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(422);

        $this->assertSame(0, $guru->transactions()->count());
    }

    public function test_disabled_doku_guest_gets_401_not_503(): void
    {
        AppSetting::putValue('doku_enabled', '0');

        $this->postJson(route('payments.doku.start'))->assertStatus(401);
    }

    public function test_disabled_doku_authenticated_guru_gets_503(): void
    {
        AppSetting::putValue('doku_enabled', '0');
        $guru = $this->createGuru(User::STATUS_PENDING);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(503);
    }

    public function test_doku_failure_returns_502_and_keeps_pending_transaction(): void
    {
        Http::fake(['*/checkout/v1/payment' => Http::response('error', 500)]);

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 99000,
            'is_active' => true,
        ]);
        $guru = $this->createGuru(User::STATUS_PENDING);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(502);

        $transaction = $guru->transactions()->firstOrFail();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
    }

    public function test_throttle_blocks_eleventh_request_within_a_minute(): void
    {
        Http::fake(['*/checkout/v1/payment' => Http::response('error', 500)]);

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 99000,
            'is_active' => true,
        ]);
        $guru = $this->createGuru(User::STATUS_PENDING);

        foreach (range(1, 10) as $i) {
            $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(502);
        }

        $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(429);
    }

    public function test_route_uses_throttle_10_per_minute(): void
    {
        $route = collect(app('router')->getRoutes()->getByName('payments.doku.start')->gatherMiddleware())
            ->first(fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'));

        $this->assertSame('throttle:10,1', $route);
    }

    private function createGuru(string $accountStatus): User
    {
        return User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => $accountStatus,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
        ]);
    }
}
