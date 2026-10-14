<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PricingHardeningQaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::putValue('doku_client_id', 'BRN-test-client-id');
        AppSetting::putValue('doku_secret_key', 'SK-test-secret-key');

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 99000,
            'is_active' => true,
        ]);
    }

    public function test_guest_with_doku_off_gets_401_not_503(): void
    {
        AppSetting::putValue('doku_enabled', '0');

        $this->postJson(route('payments.doku.start'))->assertStatus(401);
    }

    public function test_guest_with_doku_on_still_gets_401_not_503(): void
    {
        AppSetting::putValue('doku_enabled', '1');

        $this->postJson(route('payments.doku.start'))->assertStatus(401);
    }

    public function test_guest_cannot_distinguish_doku_status(): void
    {
        AppSetting::putValue('doku_enabled', '0');
        $off = $this->postJson(route('payments.doku.start'));

        AppSetting::putValue('doku_enabled', '1');
        $on = $this->postJson(route('payments.doku.start'));

        $this->assertSame(401, $off->status());
        $this->assertSame(401, $on->status());
        $this->assertSame(
            $off->json('message'),
            $on->json('message'),
        );
    }

    public function test_authenticated_guru_with_doku_off_gets_503(): void
    {
        AppSetting::putValue('doku_enabled', '0');
        $guru = $this->createGuru(User::STATUS_PENDING);

        $this->actingAs($guru)->postJson(route('payments.doku.start'))->assertStatus(503);
    }

    public function test_regression_guru_with_doku_on_creates_transaction_and_returns_https_url(): void
    {
        AppSetting::putValue('doku_enabled', '1');
        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => [
                    'payment' => [
                        'url' => 'https://checkout.doku.com/payment-qa-abc',
                    ],
                ],
            ], 201),
        ]);

        $guru = $this->createGuru(User::STATUS_PENDING);

        $res = $this->actingAs($guru)->postJson(route('payments.doku.start'));

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('payment_url', 'https://checkout.doku.com/payment-qa-abc');

        $this->assertSame(1, $guru->transactions()->count());
        $t = $guru->transactions()->first();
        $this->assertSame(Transaction::STATUS_PENDING, $t->status);
        $this->assertSame('UJN-TEST-QA-0001', $t->doku_invoice_number);
    }

    private function createGuru(string $accountStatus): User
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => $accountStatus,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
            'no_wa' => '08123456789',
        ]);

        $guru->transactions()->create([
            'plan_name' => 'Aktivasi SMP',
            'reference_code' => 'UJN-TEST-QA-0001',
            'amount' => 99000,
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => Transaction::PAYMENT_METHOD_DOKU,
            'doku_invoice_number' => 'UJN-TEST-QA-0001',
        ]);

        return $guru;
    }
}
