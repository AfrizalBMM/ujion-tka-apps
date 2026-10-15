<?php

namespace App\Support;

/**
 * Petakan answer_key bank soal ke indeks opsi (0-based).
 * Bank menyimpan kunci dalam dua bentuk: kode huruf ('A'..'E') atau teks opsi apa adanya ('22 cm').
 * Tabel pilihan_jawabans hanya menampung kode enum A-D, jadi indeks >= 4 = soal tak bisa disimpan utuh.
 */
class AnswerKeyResolver
{
    public const MAX_INDEX = 3; // enum kode A-D

    public static function resolve(mixed $answerKey, array $options): ?int
    {
        $raw = trim((string) $answerKey);

        if ($raw === '') {
            return null;
        }

        $labelIndex = array_search(strtoupper($raw), range('A', 'Z'), true);

        if ($labelIndex !== false && array_key_exists($labelIndex, $options)) {
            return $labelIndex;
        }

        foreach ($options as $idx => $optionText) {
            if (trim((string) $optionText) === $raw) {
                return (int) $idx;
            }
        }

        return null;
    }

    /**
     * Indeks kunci yang aman disimpan (0-3) atau null bila tak bisa dipetakan.
     */
    public static function resolveStorable(mixed $answerKey, array $options): ?int
    {
        $index = self::resolve($answerKey, $options);

        if ($index === null || $index > self::MAX_INDEX) {
            return null;
        }

        return $index;
    }
}
