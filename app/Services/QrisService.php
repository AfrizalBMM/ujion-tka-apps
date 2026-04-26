<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class QrisService
{
    public function generateFixedAmountPayload(int|float|string $amount, ?string $masterPayload = null): string
    {
        $basePayload = trim((string) ($masterPayload ?? \App\Models\AppSetting::getValue('qris_master_payload', config('services.qris.master_payload'))));

        if ($basePayload === '') {
            throw new RuntimeException('GOPAY_MASTER_PAYLOAD belum dikonfigurasi.');
        }

        $sanitizedAmount = $this->sanitizeAmount($amount);
        $payloadWithoutCrc = $this->stripCrc($basePayload);
        $segments = $this->parseTlv($payloadWithoutCrc);
        $tag54Value = $sanitizedAmount;
        $tag54Segment = '54' . str_pad((string) strlen($tag54Value), 2, '0', STR_PAD_LEFT) . $tag54Value;

        $rebuilt = '';
        $inserted = false;

        foreach ($segments as $segment) {
            if ($segment['id'] === '54') {
                $rebuilt .= $tag54Segment;
                $inserted = true;

                continue;
            }

            if (! $inserted && $segment['id'] === '58') {
                $rebuilt .= $tag54Segment;
                $inserted = true;
            }

            $rebuilt .= $segment['raw'];
        }

        if (! $inserted) {
            $rebuilt .= $tag54Segment;
        }

        return $rebuilt . '6304' . $this->calculateCrc($rebuilt . '6304');
    }

    public function sanitizeAmount(int|float|string $amount): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $amount) ?? '';

        if ($normalized === '') {
            throw new InvalidArgumentException('Nominal QRIS tidak valid.');
        }

        return ltrim($normalized, '0') !== '' ? ltrim($normalized, '0') : '0';
    }

    public function calculateCrc(string $payload): string
    {
        $crc = 0xFFFF;

        foreach (str_split($payload) as $character) {
            $crc ^= ord($character) << 8;

            for ($i = 0; $i < 8; $i++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * @return array<int, array{id: string, length: int, value: string, raw: string}>
     */
    private function parseTlv(string $payload): array
    {
        $segments = [];
        $offset = 0;
        $max = strlen($payload);

        while ($offset + 4 <= $max) {
            $id = substr($payload, $offset, 2);
            $length = (int) substr($payload, $offset + 2, 2);
            $value = substr($payload, $offset + 4, $length);

            $segments[] = [
                'id' => $id,
                'length' => $length,
                'value' => $value,
                'raw' => $id . substr($payload, $offset + 2, 2) . $value,
            ];

            $offset += 4 + $length;
        }

        return $segments;
    }

    private function stripCrc(string $payload): string
    {
        if (strlen($payload) >= 8 && substr($payload, -8, 4) === '6304') {
            return substr($payload, 0, -8);
        }

        return $payload;
    }
}
