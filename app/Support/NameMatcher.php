<?php

namespace App\Support;

class NameMatcher
{
    public static function matches(?string $storedName, ?string $inputName): bool
    {
        $stored = self::normalize($storedName);
        $input = self::normalize($inputName);

        if ($stored === '' || $input === '') {
            return false;
        }

        return $stored === $input;
    }

    public static function normalize(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));

        if ($name === '') {
            return '';
        }

        $name = str_replace(['.', ','], ' ', $name);
        $segments = preg_split('/[^\p{L}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $segments = array_values(array_filter($segments, fn (string $segment): bool => ! self::isIgnorableSegment($segment)));

        return implode('', $segments);
    }

    private static function isIgnorableSegment(string $segment): bool
    {
        return in_array($segment, [
            'dr', 'dra', 'drs', 'ir', 'h', 'hj', 'ust', 'ustadz', 'ustaz',
            's', 'sd', 'smp', 'sma', 'smk',
            'pd', 'kom', 't',
            'spd', 'spdsi', 'ssi', 'skom', 'st', 'se', 'sh', 'si', 'sn',
            'mkom', 'mt', 'mpd', 'ma', 'msi', 'mh', 'mm', 'mhum',
            'phd', 'prof',
        ], true);
    }
}
