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

class DokuPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'BRN-test-client-id';

    private const SECRET_KEY = 'SK-test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::putValue('doku_enabled', '1');
        AppSetting::putValue('doku_client_id', self::CLIENT_ID);
        AppSetting::putValue('doku_secret_key', self::SECRET_KEY);
    }

    public function test_webhook_success_marks_success_and_activates_teacher(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $response = $this->postDokuNotification([
            'order' => [
                'invoice_number' => 'UJN-TEST-0001',
                'amount' => 99000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
            'payment' => [
                'method' => 'QRIS',
                'channel' => 'QRIS',
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $transaction->refresh();
        $teacher->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame(Transaction::PAYMENT_METHOD_DOKU, $transaction->payment_method);
        $this->assertSame('SUCCESS', $transaction->doku_transaction_status);
        $this->assertSame('QRIS', $transaction->doku_payment_channel);
        $this->assertNotNull($transaction->paid_at);

        $this->assertSame(User::STATUS_ACTIVE, $teacher->account_status);
        $this->assertSame(User::PAYMENT_APPROVED, $teacher->payment_status);
        $this->assertNotNull($teacher->access_token);

        Queue::assertPushed(SendWhatsAppBlast::class);
    }

    public function test_webhook_is_idempotent_for_duplicate_notifications(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $payload = [
            'order' => [
                'invoice_number' => 'UJN-TEST-0001',
                'amount' => 99000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
            'payment' => [
                'method' => 'QRIS',
                'channel' => 'QRIS',
            ],
        ];

        $this->postDokuNotification($payload)->assertOk();
        $this->postDokuNotification($payload)->assertOk();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
        Queue::assertPushed(SendWhatsAppBlast::class, 1);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $payload = [
            'order' => [
                'invoice_number' => 'UJN-TEST-0001',
                'amount' => 99000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
        ];

        $rawBody = json_encode($payload);
        $headers = $this->notificationHeaders($rawBody);
        $headers['Signature'] = 'HMACSHA256='.base64_encode('invalid-signature');

        $response = $this->postJson(route('api.payments.doku.notification'), $payload, $headers);

        $response->assertStatus(401);

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
        $this->assertSame(User::STATUS_PENDING, $teacher->fresh()->account_status);
    }

    public function test_webhook_ignores_amount_mismatch(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $response = $this->postDokuNotification([
            'order' => [
                'invoice_number' => 'UJN-TEST-0001',
                'amount' => 1000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
        ]);

        $response->assertOk();

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
        $this->assertSame(User::STATUS_PENDING, $teacher->fresh()->account_status);
    }

    public function test_webhook_expired_marks_transaction_failed(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $this->postDokuNotification([
            'order' => [
                'invoice_number' => 'UJN-TEST-0001',
                'amount' => 99000,
            ],
            'transaction' => [
                'status' => 'EXPIRED',
            ],
            'payment' => [
                'method' => 'VIRTUAL_ACCOUNT_BCA',
                'channel' => 'BCA',
            ],
        ])->assertOk();

        $transaction->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $transaction->status);
        $this->assertStringContainsString('expired', (string) $transaction->rejection_reason);
        $this->assertSame(User::STATUS_PENDING, $teacher->fresh()->account_status);
    }

    public function test_webhook_returns_503_when_doku_disabled(): void
    {
        AppSetting::putValue('doku_enabled', '0');

        $payload = [
            'order' => [
                'invoice_number' => 'UJN-TEST-0001',
                'amount' => 99000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
        ];

        $this->postJson(route('api.payments.doku.notification'), $payload, $this->notificationHeaders(json_encode($payload)))
            ->assertStatus(503);
    }

    public function test_start_returns_payment_url_and_records_invoice(): void
    {
        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => [
                    'payment' => [
                        'url' => 'https://checkout.doku.com/payment-url-abc',
                    ],
                ],
            ], 201),
        ]);

        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $response = $this
            ->actingAs($teacher)
            ->postJson(route('payments.doku.start'));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('payment_url', 'https://checkout.doku.com/payment-url-abc')
            ->assertJsonPath('order_id', 'UJN-TEST-0001');

        $transaction->refresh();

        $this->assertSame(Transaction::PAYMENT_METHOD_DOKU, $transaction->payment_method);
        $this->assertSame('UJN-TEST-0001', $transaction->doku_invoice_number);
    }

    public function test_start_creates_transaction_when_none_exists(): void
    {
        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => [
                    'payment' => [
                        'url' => 'https://checkout.doku.com/payment-url-new',
                    ],
                ],
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
            ->actingAs($teacher)
            ->postJson(route('payments.doku.start'));

        $response->assertOk()->assertJsonPath('ok', true);

        $transaction = $teacher->transactions()->first();
        $this->assertNotNull($transaction);
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(99000.0, (float) $transaction->amount);
    }

    public function test_start_requires_authentication(): void
    {
        $this->postJson(route('payments.doku.start'))->assertStatus(401);
    }

    public function test_start_returns_409_when_already_paid(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();
        $transaction->update(['status' => Transaction::STATUS_SUCCESS]);

        $this
            ->actingAs($teacher)
            ->postJson(route('payments.doku.start'))
            ->assertStatus(409);
    }

    public function test_cancel_closes_popup_for_logged_in_guru(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $this->actingAs($teacher)
            ->get(route('payments.doku.cancel'))
            ->assertOk()
            ->assertSee('Menutup jendela pembayaran');
    }

    public function test_cancel_redirects_guest_to_login(): void
    {
        $this->get(route('payments.doku.cancel'))->assertRedirect(route('login'));
    }

    public function test_finish_page_polls_doku_status_when_still_pending(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        Http::fake([
            '*/orders/v1/status/*' => Http::response([
                'order' => [
                    'invoice_number' => 'UJN-TEST-0001',
                    'amount' => 99000,
                ],
                'transaction' => [
                    'status' => 'SUCCESS',
                ],
                'payment' => [
                    'method' => 'QRIS',
                    'channel' => 'QRIS',
                ],
            ]),
        ]);

        $response = $this
            ->actingAs($teacher)
            ->get(route('payments.doku.finish', ['order_id' => 'UJN-TEST-0001']));

        $response->assertOk();
        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $teacher->fresh()->account_status);
        $response->assertSee($teacher->fresh()->access_token);
    }

    public function test_status_endpoint_activates_transaction_via_remote_status(): void
    {
        Queue::fake();

        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        Http::fake([
            '*/orders/v1/status/*' => Http::response([
                'order' => [
                    'invoice_number' => 'UJN-TEST-0001',
                    'amount' => 99000,
                ],
                'transaction' => [
                    'status' => 'SUCCESS',
                ],
                'payment' => [
                    'method' => 'VIRTUAL_ACCOUNT_BCA',
                    'channel' => 'BCA',
                ],
            ]),
        ]);

        $response = $this
            ->actingAs($teacher)
            ->getJson(route('payments.doku.status', ['order_id' => 'UJN-TEST-0001']));

        $response->assertOk()->assertJsonPath('status', Transaction::STATUS_SUCCESS);
        $this->assertNotNull($response->json('token'));
        Queue::assertPushed(SendWhatsAppBlast::class);
    }

    private function createPendingDokuTransaction(): array
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
            'payment_method' => Transaction::PAYMENT_METHOD_DOKU,
            'doku_invoice_number' => 'UJN-TEST-0001',
        ]);

        return [$teacher, $transaction];
    }

    private function postDokuNotification(array $payload)
    {
        $rawBody = json_encode($payload);

        return $this->postJson(
            route('api.payments.doku.notification'),
            $payload,
            $this->notificationHeaders($rawBody),
        );
    }

    private function notificationHeaders(string $rawBody): array
    {
        $requestId = 'test-request-id-'.bin2hex(random_bytes(4));
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s').'Z';
        $digest = base64_encode(hash('sha256', $rawBody, true));

        $components = 'Client-Id:'.self::CLIENT_ID."\n"
            .'Request-Id:'.$requestId."\n"
            .'Request-Timestamp:'.$timestamp."\n"
            .'Request-Target:/api/payments/doku/notification'
            ."\n".'Digest:'.$digest;

        $signature = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, self::SECRET_KEY, true));

        return [
            'Client-Id' => self::CLIENT_ID,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $signature,
        ];
    }
}
