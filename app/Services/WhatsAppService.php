<?php

namespace App\Services;

use App\Models\WhatsAppLog;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppService
{
    public function gatewayUrl(): string
    {
        return rtrim((string) config('services.wa_gateway.url'), '/');
    }

    public function senderId(): string
    {
        return (string) config('services.wa_gateway.sender_id');
    }

    public function normalizeNumber(?string $number): ?string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number) ?? '';
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return null;
        }

        // Common Indonesian formats:
        // 08xxxx => 628xxxx
        // 8xxxx  => 628xxxx
        // 62xxxx => 62xxxx
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        // Fallback: return digits as-is.
        return $digits;
    }

    /**
     * @return array{status: bool, response?: mixed, error?: string}
     */
    public function sendMessage(string $number, string $message): array
    {
        $normalized = $this->normalizeNumber($number);

        if ($normalized === null) {
            return ['status' => false, 'error' => 'Nomor WhatsApp kosong / tidak valid.'];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($this->gatewayUrl().'/send-message', [
                    'sender' => $this->senderId(),
                    'number' => $normalized,
                    'message' => $message,
                ])
                ->throw();

            $payload = (array) $response->json();

            $this->tryLog($normalized, $message, (bool) ($payload['status'] ?? false), $payload);

            return $payload;
        } catch (RequestException $e) {
            Log::warning('WA Gateway request failed (sendMessage)', [
                'url' => $this->gatewayUrl(),
                'sender' => $this->senderId(),
                'number' => $normalized,
                'error' => $e->getMessage(),
                'response' => optional($e->response)->body(),
            ]);

            $this->tryLog($normalized, $message, false, [
                'error' => $e->getMessage(),
                'response' => optional($e->response)->body(),
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('WA Gateway error (sendMessage)', [
                'error' => $e->getMessage(),
            ]);

            $this->tryLog($normalized, $message, false, [
                'error' => $e->getMessage(),
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: bool, response?: mixed, error?: string}
     */
    public function sendMedia(string $number, string $url, string $caption = ''): array
    {
        $normalized = $this->normalizeNumber($number);

        if ($normalized === null) {
            return ['status' => false, 'error' => 'Nomor WhatsApp kosong / tidak valid.'];
        }

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->asJson()
                ->post($this->gatewayUrl().'/send-media', [
                    'sender' => $this->senderId(),
                    'number' => $normalized,
                    'url' => $url,
                    'message' => $caption,
                ])
                ->throw();

            $payload = (array) $response->json();

            $this->tryLog($normalized, $caption !== '' ? $caption : $url, (bool) ($payload['status'] ?? false), $payload);

            return $payload;
        } catch (RequestException $e) {
            Log::warning('WA Gateway request failed (sendMedia)', [
                'url' => $this->gatewayUrl(),
                'sender' => $this->senderId(),
                'number' => $normalized,
                'error' => $e->getMessage(),
                'response' => optional($e->response)->body(),
            ]);

            $this->tryLog($normalized, $caption !== '' ? $caption : $url, false, [
                'error' => $e->getMessage(),
                'response' => optional($e->response)->body(),
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('WA Gateway error (sendMedia)', [
                'error' => $e->getMessage(),
            ]);

            $this->tryLog($normalized, $caption !== '' ? $caption : $url, false, [
                'error' => $e->getMessage(),
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    private function tryLog(string $phone, ?string $message, bool $isSuccess, mixed $responseData = null): void
    {
        try {
            if (! Schema::hasTable('whatsapp_logs')) {
                return;
            }

            WhatsAppLog::create([
                'phone' => $phone,
                'message' => $message,
                'status' => $isSuccess ? 'success' : 'failed',
                'response_data' => $responseData,
            ]);
        } catch (\Throwable) {
            // Intentionally ignore logging failures.
        }
    }
}
