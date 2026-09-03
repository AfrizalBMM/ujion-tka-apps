<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Transaction;
use App\Support\PhoneNumber;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransService
{
    public function isEnabled(): bool
    {
        return AppSetting::getValue('midtrans_enabled') === '1'
            && ! blank($this->serverKey());
    }

    public function serverKey(): ?string
    {
        $key = trim((string) AppSetting::getValue('midtrans_server_key'));

        return $key !== '' ? $key : null;
    }

    public function clientKey(): ?string
    {
        $key = trim((string) AppSetting::getValue('midtrans_client_key'));

        return $key !== '' ? $key : null;
    }

    public function isProduction(): bool
    {
        return AppSetting::getValue('midtrans_environment') === 'production';
    }

    public function baseUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    public function createSnapTransaction(Transaction $transaction): array
    {
        $serverKey = $this->serverKey();
        abort_if(blank($serverKey), 503, 'Midtrans belum dikonfigurasi.');

        $user = $transaction->user;
        $amount = (int) round((float) $transaction->amount);
        $orderId = $transaction->midtrans_order_id ?: $transaction->reference_code;

        $buildPayload = function (string $orderId) use ($transaction, $user, $amount): array {
            return [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => Str::limit($user?->name ?? 'Guru Ujion', 50, ''),
                    'email' => $user?->email,
                    'phone' => PhoneNumber::normalizeIndonesian($user?->no_wa ?? '') ?: null,
                ],
                'item_details' => [
                    [
                        'id' => 'plan-'.$transaction->pricing_plan_id,
                        'price' => $amount,
                        'quantity' => 1,
                        'name' => Str::limit($transaction->plan_name, 50, ''),
                    ],
                ],
                'callbacks' => [
                    'finish' => route('payments.midtrans.finish'),
                ],
            ];
        };

        $response = $this->snapClient()->post(
            $this->baseUrl().'/snap/v1/transactions',
            $buildPayload($orderId),
        );

        if ($response->status() === 409) {
            $orderId = $transaction->reference_code.'-R'.strtoupper(Str::random(4));

            $response = $this->snapClient()->post(
                $this->baseUrl().'/snap/v1/transactions',
                $buildPayload($orderId),
            );
        }

        if (! $response->successful()) {
            Log::error('Midtrans Snap create failed', [
                'reference_code' => $transaction->reference_code,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Gagal membuat transaksi Midtrans. Silakan coba lagi.');
        }

        $snapToken = (string) $response->json('token');
        $redirectUrl = (string) $response->json('redirect_url');

        if ($snapToken === '' || $redirectUrl === '') {
            throw new \RuntimeException('Midtrans tidak mengembalikan token pembayaran.');
        }

        $transaction->update([
            'payment_method' => Transaction::PAYMENT_METHOD_MIDTRANS,
            'midtrans_order_id' => $orderId,
        ]);

        return [
            'token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'order_id' => $orderId,
        ];
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, ?string $signatureKey): bool
    {
        $serverKey = (string) $this->serverKey();
        if ($serverKey === '' || blank($signatureKey)) {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, (string) $signatureKey);
    }

    public function status(string $orderId): ?array
    {
        $serverKey = $this->serverKey();
        if (blank($serverKey)) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->get($this->baseUrl().'/v2/'.rawurlencode($orderId).'/status');
        } catch (\Exception $e) {
            Log::error('Midtrans status check failed: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function snapClient(): PendingRequest
    {
        return Http::withBasicAuth((string) $this->serverKey(), '')
            ->acceptJson()
            ->timeout(20);
    }
}
