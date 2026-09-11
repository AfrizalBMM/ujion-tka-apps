<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\LandingExamOrder;
use App\Models\Transaction;
use App\Support\PhoneNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuService
{
    public const PAYMENT_PATH = '/checkout/v1/payment';

    public const STATUS_PATH_PREFIX = '/orders/v1/status/';

    public const PAYMENT_DUE_MINUTES = 60;

    public function isEnabled(): bool
    {
        return AppSetting::getValue('doku_enabled') === '1'
            && ! blank($this->clientId())
            && ! blank($this->secretKey());
    }

    public function clientId(): ?string
    {
        $key = trim((string) AppSetting::getValue('doku_client_id'));

        return $key !== '' ? $key : null;
    }

    public function secretKey(): ?string
    {
        $key = trim((string) AppSetting::getValue('doku_secret_key'));

        return $key !== '' ? $key : null;
    }

    public function baseUrl(): string
    {
        return 'https://api.doku.com';
    }

    public function createCheckoutPayment(Transaction $transaction): array
    {
        $this->assertConfigured();

        $user = $transaction->user;
        $amount = (int) round((float) $transaction->amount);
        $invoice = $transaction->doku_invoice_number ?: $transaction->reference_code;

        $buildPayload = function (string $invoiceNumber) use ($transaction, $user, $amount): array {
            return [
                'order' => [
                    'amount' => $amount,
                    'invoice_number' => $invoiceNumber,
                    'currency' => 'IDR',
                    'callback_url' => route('payments.doku.finish', ['order_id' => $invoiceNumber]),
                    'callback_url_cancel' => route('payments.doku.cancel'),
                ],
                'payment' => [
                    'payment_due_date' => self::PAYMENT_DUE_MINUTES,
                ],
                'customer' => [
                    'id' => 'guru-'.$transaction->user_id,
                    'name' => Str::limit($user?->name ?? 'Guru Ujion', 50, ''),
                    'phone' => PhoneNumber::normalizeIndonesian($user?->no_wa ?? '') ?: null,
                    'country' => 'ID',
                ],
            ];
        };

        $response = $this->sendPaymentRequest($buildPayload($invoice));

        if (! $response->successful()) {
            $invoice = $transaction->reference_code.'-R'.strtoupper(Str::random(4));
            $response = $this->sendPaymentRequest($buildPayload($invoice));
        }

        if (! $response->successful()) {
            Log::error('Doku checkout create failed', [
                'reference_code' => $transaction->reference_code,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Gagal membuat transaksi Doku. Silakan coba lagi.');
        }

        $paymentUrl = (string) ($response->json('response.payment.url')
            ?? $response->json('payment.url')
            ?? '');

        if ($paymentUrl === '') {
            throw new \RuntimeException('Doku tidak mengembalikan URL pembayaran.');
        }

        $transaction->update([
            'payment_method' => Transaction::PAYMENT_METHOD_DOKU,
            'doku_invoice_number' => $invoice,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'invoice_number' => $invoice,
        ];
    }

    public function createCheckoutPaymentForOrder(LandingExamOrder $order): array
    {
        $this->assertConfigured();

        $mapel = $order->landingExamMapel;
        $landingExam = $mapel?->landingExam;
        $exam = $landingExam?->exam;
        $mapelPaket = $mapel?->mapelPaket;

        $amount = (int) round((float) $order->amount);
        $invoice = $order->doku_invoice_number ?: $this->generatePublicExamReferenceCode();

        $buildPayload = function (string $invoiceNumber) use ($order, $amount): array {
            return [
                'order' => [
                    'amount' => $amount,
                    'invoice_number' => $invoiceNumber,
                    'currency' => 'IDR',
                    'callback_url' => route('ujian-online.pay.finish', ['order_id' => $invoiceNumber]),
                    'callback_url_cancel' => route('ujian-online.pending', $order->session_token),
                ],
                'payment' => [
                    'payment_due_date' => self::PAYMENT_DUE_MINUTES,
                ],
                'customer' => [
                    'id' => 'public-exam-'.$order->id,
                    'name' => Str::limit($order->nama, 50, ''),
                    'phone' => PhoneNumber::normalizeIndonesian($order->nomor_wa) ?: null,
                    'country' => 'ID',
                ],
            ];
        };

        $response = $this->sendPaymentRequest($buildPayload($invoice));

        if (! $response->successful()) {
            $invoice = $this->generatePublicExamReferenceCode().'-R'.strtoupper(Str::random(4));
            $response = $this->sendPaymentRequest($buildPayload($invoice));
        }

        if (! $response->successful()) {
            Log::error('Doku checkout create failed (public exam)', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Gagal membuat transaksi Doku. Silakan coba lagi.');
        }

        $paymentUrl = (string) ($response->json('response.payment.url')
            ?? $response->json('payment.url')
            ?? '');

        if ($paymentUrl === '') {
            throw new \RuntimeException('Doku tidak mengembalikan URL pembayaran.');
        }

        $order->update(['doku_invoice_number' => $invoice]);

        return [
            'payment_url' => $paymentUrl,
            'invoice_number' => $invoice,
        ];
    }

    public function checkStatus(string $invoiceNumber): ?array
    {
        if (blank($this->clientId()) || blank($this->secretKey()) || $invoiceNumber === '') {
            return null;
        }

        $requestTarget = self::STATUS_PATH_PREFIX.rawurlencode($invoiceNumber);

        try {
            $response = $this->signedRequest('get', $requestTarget);
        } catch (\Exception $e) {
            Log::error('Doku status check failed: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function verifyNotificationSignature(Request $request): bool
    {
        $clientId = (string) $this->clientId();
        $secretKey = (string) $this->secretKey();
        $signature = trim((string) $request->header('Signature'));

        if ($clientId === '' || $secretKey === '' || $signature === '') {
            return false;
        }

        if (! hash_equals($clientId, trim((string) $request->header('Client-Id')))) {
            return false;
        }

        if (! $this->isTimestampFresh((string) $request->header('Request-Timestamp'))) {
            return false;
        }

        $digest = base64_encode(hash('sha256', (string) $request->getContent(), true));

        $expected = $this->buildSignature(
            $clientId,
            trim((string) $request->header('Request-Id')),
            trim((string) $request->header('Request-Timestamp')),
            $request->getPathInfo(),
            $digest,
            $secretKey,
        );

        return hash_equals($expected, $signature);
    }

    public function findOrder(string $invoiceNumber): ?LandingExamOrder
    {
        if ($invoiceNumber === '') {
            return null;
        }

        return LandingExamOrder::query()
            ->where('doku_invoice_number', $invoiceNumber)
            ->first();
    }

    public function generatePublicExamReferenceCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'PUJ-'.now()->format('ymd').'-'.strtoupper(Str::random(8));

            if (! LandingExamOrder::query()->where('doku_invoice_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Gagal generate reference code.');
    }

    private function assertConfigured(): void
    {
        abort_if(blank($this->clientId()) || blank($this->secretKey()), 503, 'Doku belum dikonfigurasi.');
    }

    private function sendPaymentRequest(array $payload): Response
    {
        $body = json_encode($payload) ?: '{}';

        return $this->signedRequest('post', self::PAYMENT_PATH, $body);
    }

    private function signedRequest(string $method, string $requestTarget, ?string $rawBody = null): Response
    {
        $clientId = (string) $this->clientId();
        $secretKey = (string) $this->secretKey();
        $requestId = (string) Str::uuid();
        $timestamp = $this->currentTimestamp();
        $digest = $rawBody !== null
            ? base64_encode(hash('sha256', $rawBody, true))
            : null;

        $signature = $this->buildSignature($clientId, $requestId, $timestamp, $requestTarget, $digest, $secretKey);

        $client = Http::withHeaders([
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $signature,
        ])
            ->acceptJson()
            ->timeout(20);

        if ($rawBody !== null) {
            $client = $client->withBody($rawBody, 'application/json');
        }

        return $client->{$method}($this->baseUrl().$requestTarget);
    }

    private function buildSignature(
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        ?string $digest,
        string $secretKey,
    ): string {
        $components = 'Client-Id:'.$clientId."\n"
            .'Request-Id:'.$requestId."\n"
            .'Request-Timestamp:'.$timestamp."\n"
            .'Request-Target:'.$requestTarget;

        if ($digest !== null) {
            $components .= "\n".'Digest:'.$digest;
        }

        return 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, $secretKey, true));
    }

    private function currentTimestamp(): string
    {
        return now()->utc()->format('Y-m-d\TH:i:s').'Z';
    }

    private function isTimestampFresh(string $timestamp): bool
    {
        if ($timestamp === '') {
            return false;
        }

        try {
            $parsed = Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return false;
        }

        return $parsed->diffInMinutes(now()) <= 5;
    }
}
