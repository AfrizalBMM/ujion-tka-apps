<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppBlast;
use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-testkey';

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::putValue('midtrans_enabled', '1');
        AppSetting::putValue('midtrans_environment', 'sandbox');
        AppSetting::putValue('midtrans_server_key', self::SERVER_KEY);
        AppSetting::putValue('midtrans_client_key', 'SB-Mid-client-testkey');
    }

    public function test_webhook_settlement_marks_success_and_activates_teacher(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        $response = $this->postJson(route('api.payments.midtrans.notification'), [
            'order_id' => 'UJN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => '99000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'gopay',
            'signature_key' => $this->signature('UJN-TEST-0001', '200', '99000.00'),
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $transaction->refresh();
        $teacher->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_METHOD_MIDTRANS, $transaction->payment_method);
        $this->assertSame('settlement', $transaction->midtrans_transaction_status);
        $this->assertSame('gopay', $transaction->midtrans_payment_type);
        $this->assertNotNull($transaction->paid_at);

        $this->assertSame(User::STATUS_ACTIVE, $teacher->account_status);
        $this->assertSame(User::PAYMENT_APPROVED, $teacher->payment_status);
        $this->assertNotNull($teacher->access_token);

        Queue::assertPushed(SendWhatsAppBlast::class);
    }

    public function test_webhook_is_idempotent_for_duplicate_notifications(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        $payload = [
            'order_id' => 'UJN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => '99000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'gopay',
            'signature_key' => $this->signature('UJN-TEST-0001', '200', '99000.00'),
        ];

        $this->postJson(route('api.payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('api.payments.midtrans.notification'), $payload)->assertOk();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
        Queue::assertPushed(SendWhatsAppBlast::class, 1);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        $response = $this->postJson(route('api.payments.midtrans.notification'), [
            'order_id' => 'UJN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => '99000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature',
        ]);

        $response->assertStatus(401);

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
        $this->assertSame(User::STATUS_PENDING, $teacher->fresh()->account_status);
    }

    public function test_webhook_ignores_amount_mismatch(): void
    {
        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        $response = $this->postJson(route('api.payments.midtrans.notification'), [
            'order_id' => 'UJN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => '1000.00',
            'transaction_status' => 'settlement',
            'signature_key' => $this->signature('UJN-TEST-0001', '200', '1000.00'),
        ]);

        $response->assertOk();

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
        $this->assertSame(User::STATUS_PENDING, $teacher->fresh()->account_status);
    }

    public function test_webhook_expire_marks_transaction_failed(): void
    {
        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        $this->postJson(route('api.payments.midtrans.notification'), [
            'order_id' => 'UJN-TEST-0001',
            'status_code' => '202',
            'gross_amount' => '99000.00',
            'transaction_status' => 'expire',
            'payment_type' => 'gopay',
            'signature_key' => $this->signature('UJN-TEST-0001', '202', '99000.00'),
        ])->assertOk();

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $transaction->status);
        $this->assertStringContainsString('expire', (string) $transaction->rejection_reason);
        $this->assertSame(User::STATUS_PENDING, $teacher->fresh()->account_status);
    }

    public function test_webhook_returns_503_when_midtrans_disabled(): void
    {
        AppSetting::putValue('midtrans_enabled', '0');

        $this->postJson(route('api.payments.midtrans.notification'), [
            'order_id' => 'UJN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => '99000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'anything',
        ])->assertStatus(503);
    }

    public function test_start_returns_snap_token_and_records_order(): void
    {
        Http::fake([
            '*/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-abc',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/snap-token-abc',
            ], 201),
        ]);

        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        $response = $this
            ->withSession(['pending_registration' => ['teacher_id' => $teacher->id]])
            ->postJson(route('payments.midtrans.start'));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('snap_token', 'snap-token-abc')
            ->assertJsonPath('order_id', 'UJN-TEST-0001')
            ->assertJsonPath('client_key', 'SB-Mid-client-testkey')
            ->assertJsonPath('is_production', false);

        $transaction->refresh();

        $this->assertSame(Transaction::PAYMENT_METHOD_MIDTRANS, $transaction->payment_method);
        $this->assertSame('UJN-TEST-0001', $transaction->midtrans_order_id);
    }

    public function test_start_creates_transaction_when_none_exists(): void
    {
        Http::fake([
            '*/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-new',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/snap-token-new',
            ], 201),
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => 'SMP',
            'no_wa' => '08123456789',
        ]);

        PricingPlan::create([
            'name' => 'Aktivasi SMP',
            'jenjang' => 'SMP',
            'price' => 99000,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession(['pending_registration' => ['teacher_id' => $teacher->id]])
            ->postJson(route('payments.midtrans.start'));

        $response->assertOk()->assertJsonPath('ok', true);

        $transaction = $teacher->transactions()->first();
        $this->assertNotNull($transaction);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(99000.0, (float) $transaction->amount);
    }

    public function test_start_requires_pending_registration_session(): void
    {
        $this->postJson(route('payments.midtrans.start'))->assertStatus(419);
    }

    public function test_start_returns_409_when_already_paid(): void
    {
        [$teacher, $transaction] = $this->createPendingMidtransTransaction();
        $transaction->update(['status' => Transaction::STATUS_SUCCESS]);

        $this
            ->withSession(['pending_registration' => ['teacher_id' => $teacher->id]])
            ->postJson(route('payments.midtrans.start'))
            ->assertStatus(409);
    }

    public function test_finish_page_polls_midtrans_status_when_still_pending(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        Http::fake([
            '*/v2/*/status' => Http::response([
                'order_id' => 'UJN-TEST-0001',
                'status_code' => '200',
                'gross_amount' => '99000.00',
                'transaction_status' => 'settlement',
                'payment_type' => 'gopay',
            ]),
        ]);

        $response = $this
            ->withSession(['pending_registration' => ['teacher_id' => $teacher->id]])
            ->get(route('payments.midtrans.finish', ['order_id' => 'UJN-TEST-0001']));

        $response->assertOk();
        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $teacher->fresh()->account_status);
        $response->assertSee($teacher->fresh()->access_token);
    }

    public function test_status_endpoint_activates_transaction_via_remote_status(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingMidtransTransaction();

        Http::fake([
            '*/v2/*/status' => Http::response([
                'order_id' => 'UJN-TEST-0001',
                'status_code' => '200',
                'gross_amount' => '99000.00',
                'transaction_status' => 'settlement',
                'payment_type' => 'qris',
            ]),
        ]);

        $response = $this
            ->withSession(['pending_registration' => ['teacher_id' => $teacher->id]])
            ->getJson(route('payments.midtrans.status', ['order_id' => 'UJN-TEST-0001']));

        $response->assertOk()->assertJsonPath('status', Transaction::STATUS_SUCCESS);
        $this->assertNotNull($response->json('token'));
        Queue::assertPushed(SendWhatsAppBlast::class);
    }

    private function createPendingMidtransTransaction(): array
    {
        PricingPlan::create([
            'name' => 'Aktivasi Umum',
            'jenjang' => null,
            'price' => 99000,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'no_wa' => '08123456789',
        ]);

        $transaction = $teacher->transactions()->create([
            'plan_name' => 'Aktivasi Guru SD',
            'reference_code' => 'UJN-TEST-0001',
            'amount' => 99000,
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => Transaction::PAYMENT_METHOD_MIDTRANS,
            'midtrans_order_id' => 'UJN-TEST-0001',
        ]);

        return [$teacher, $transaction];
    }

    private function signature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId.$statusCode.$grossAmount.self::SERVER_KEY);
    }
}
