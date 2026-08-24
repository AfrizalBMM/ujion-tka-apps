<?php

namespace App\Support;

class MatchingKey
{
    public static function rowKey(int $pairId, string $seed): string
    {
        return substr(sha1('row:'.$pairId.':'.$seed), 0, 16);
    }

    public static function optKey(int $pairId, string $seed): string
    {
        return substr(sha1('opt:'.$pairId.':'.$seed), 0, 16);
    }

    /**
     * @param  iterable<int, array{id: int}>|object  $pairs
     * @return array<string, int>
     */
    public static function rowKeyLookup($pairs, string $seed): array
    {
        $lookup = [];

        foreach ($pairs as $pair) {
            $lookup[self::rowKey((int) $pair->id, $seed)] = (int) $pair->id;
        }

        return $lookup;
    }

    /**
     * @param  iterable<int, array{id: int}>|object  $pairs
     * @return array<string, int>
     */
    public static function optKeyLookup($pairs, string $seed): array
    {
        $lookup = [];

        foreach ($pairs as $pair) {
            $lookup[self::optKey((int) $pair->id, $seed)] = (int) $pair->id;
        }

        return $lookup;
    }
}
