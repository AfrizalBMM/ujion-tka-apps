<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DokuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DokuWebhookReplayTest extends TestCase
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

        // Prevent real HTTP calls during webhook processing.
        Http::preventStrayRequests();
    }

    // --- Unit tests for isTimestampFresh via reflection ---

    public function test_is_timestamp_fresh_rejects_future_timestamp_beyond_five_minutes(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $future = now()->utc()->addMinutes(10)->format('Y-m-d\TH:i:s\Z');

        $this->assertFalse(
            $method->invoke($dokuService, $future),
            'Timestamp 10 minutes in the future should be rejected.'
        );
    }

    public function test_is_timestamp_fresh_rejects_past_timestamp_beyond_five_minutes(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $past = now()->utc()->subMinutes(10)->format('Y-m-d\TH:i:s\Z');

        $this->assertFalse(
            $method->invoke($dokuService, $past),
            'Timestamp 10 minutes in the past should be rejected.'
        );
    }

    public function test_is_timestamp_fresh_accepts_valid_current_timestamp(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $valid = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $this->assertTrue(
            $method->invoke($dokuService, $valid),
            'Current timestamp should be accepted.'
        );
    }

    public function test_is_timestamp_fresh_accepts_timestamp_within_five_minute_window_past(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $withinWindow = now()->utc()->subMinutes(4)->format('Y-m-d\TH:i:s\Z');

        $this->assertTrue(
            $method->invoke($dokuService, $withinWindow),
            'Timestamp 4 minutes in the past should be accepted (within 5-minute window).'
        );
    }

    public function test_is_timestamp_fresh_accepts_future_timestamp_within_five_minute_window(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $withinWindow = now()->utc()->addMinutes(3)->format('Y-m-d\TH:i:s\Z');

        $this->assertTrue(
            $method->invoke($dokuService, $withinWindow),
            'Timestamp 3 minutes in the future should be accepted (within 5-minute window).'
        );
    }

    public function test_is_timestamp_fresh_rejects_empty_timestamp(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $this->assertFalse($method->invoke($dokuService, ''));
    }

    public function test_is_timestamp_fresh_rejects_invalid_timestamp_format(): void
    {
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $this->assertFalse($method->invoke($dokuService, 'not-a-timestamp'));
    }

    public function test_abs_fix_prevents_future_timestamp_bypass(): void
    {
        // Before the abs() fix, diffInMinutes returned a negative value for future
        // timestamps, which always passed the <= 5 check. After abs(), the absolute
        // value is used, so a future timestamp >5 min is correctly rejected.
        $dokuService = app(DokuService::class);
        $method = new \ReflectionMethod(DokuService::class, 'isTimestampFresh');

        $futureBeyondWindow = now()->utc()->addMinutes(6)->format('Y-m-d\TH:i:s\Z');

        $this->assertFalse(
            $method->invoke($dokuService, $futureBeyondWindow),
            'Timestamp 6 minutes in the future must be rejected — this is the bug abs() fixed.'
        );
    }

    // --- Integration tests via webhook endpoint ---

    public function test_webhook_rejects_notification_with_replayed_past_timestamp(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $payload = $this->successPayload();
        $rawBody = json_encode($payload);
        $oldTimestamp = now()->utc()->subMinutes(10)->format('Y-m-d\TH:i:s\Z');

        $response = $this->postJson(
            route('api.payments.doku.notification'),
            $payload,
            $this->notificationHeadersWithTimestamp($rawBody, $oldTimestamp)
        );

        $response->assertStatus(401);
        // Transaction must remain pending — replayed timestamp rejected.
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_webhook_rejects_notification_with_future_timestamp_beyond_five_minutes(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $payload = $this->successPayload();
        $rawBody = json_encode($payload);
        $futureTimestamp = now()->utc()->addMinutes(10)->format('Y-m-d\TH:i:s\Z');

        $response = $this->postJson(
            route('api.payments.doku.notification'),
            $payload,
            $this->notificationHeadersWithTimestamp($rawBody, $futureTimestamp)
        );

        $response->assertStatus(401);
        // Transaction must remain pending — future timestamp rejected.
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_webhook_accepts_notification_with_valid_timestamp(): void
    {
        [$teacher, $transaction] = $this->createPendingDokuTransaction();

        $payload = $this->successPayload();
        $rawBody = json_encode($payload);
        $validTimestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $response = $this->postJson(
            route('api.payments.doku.notification'),
            $payload,
            $this->notificationHeadersWithTimestamp($rawBody, $validTimestamp)
        );

        // Valid signature → 200 response (not 401).
        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Transaction should be marked as success.
        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    private function successPayload(): array
    {
        return [
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

    private function notificationHeadersWithTimestamp(string $rawBody, string $timestamp): array
    {
        $requestId = 'test-request-id-'.bin2hex(random_bytes(4));
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
