<?php

namespace App\Support;

class PhoneNumber
{
    public static function digitsOnly(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }

    public static function normalizeIndonesian(?string $phone): string
    {
        $digits = self::digitsOnly($phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '620')) {
            return '62' . ltrim(substr($digits, 2), '0');
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }

    public static function variants(?string $phone): array
    {
        $digits = self::digitsOnly($phone);
        $normalized = self::normalizeIndonesian($phone);
        $local = self::toLocalFormat($normalized);

        return array_values(array_unique(array_filter([
            $digits,
            $normalized,
            $local,
        ])));
    }

    public static function toLocalFormat(?string $phone): string
    {
        $digits = self::digitsOnly($phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        return $digits;
    }
}
