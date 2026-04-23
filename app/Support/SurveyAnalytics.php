<?php

namespace App\Support;

use App\Models\MapelPaket;
use App\Models\PilihanJawaban;
use App\Models\UjianSesi;
use Illuminate\Support\Collection;

class SurveyAnalytics
{
    public static function categoryFromAverage(?float $average): string
    {
        if ($average === null || $average <= 0) {
            return 'Belum cukup data';
        }

        return match (true) {
            $average >= 3.25 => 'Sangat Positif',
            $average >= 2.50 => 'Positif',
            $average >= 1.75 => 'Perlu Pendampingan',
            default => 'Prioritas Pendampingan',
        };
    }

    public static function sessionProfile(UjianSesi $session): array
    {
        $session->loadMissing([
            'mapelPaket.soals.pilihanJawabans',
            'jawabanSiswas',
        ]);

        $answers = $session->jawabanSiswas->keyBy('soal_id');
        $dimensionStats = [];
        $responseCounts = [];
        $scoreTotal = 0.0;
        $scoreCount = 0;

        foreach ($session->mapelPaket?->soals ?? [] as $soal) {
            $jawaban = $answers->get($soal->id);
            $selected = $soal->pilihanJawabans->firstWhere('kode', $jawaban?->jawaban_pg);

            if (! $selected instanceof PilihanJawaban || $selected->nilai_survey === null) {
                continue;
            }

            $dimension = $soal->dimensi ?: 'Umum';
            $responseLabel = $selected->profil_label ?: $selected->teks ?: $selected->kode;

            $dimensionStats[$dimension] ??= [
                'dimensi' => $dimension,
                'total_nilai' => 0,
                'jumlah_jawaban' => 0,
                'average' => 0.0,
                'score_percent' => 0.0,
                'category' => 'Belum cukup data',
            ];

            $dimensionStats[$dimension]['total_nilai'] += $selected->nilai_survey;
            $dimensionStats[$dimension]['jumlah_jawaban']++;

            $responseCounts[$responseLabel] = ($responseCounts[$responseLabel] ?? 0) + 1;
            $scoreTotal += $selected->nilai_survey;
            $scoreCount++;
        }

        foreach ($dimensionStats as &$item) {
            $item['average'] = $item['jumlah_jawaban'] > 0
                ? round($item['total_nilai'] / $item['jumlah_jawaban'], 2)
                : 0.0;
            $item['score_percent'] = round(($item['average'] / 4) * 100, 2);
            $item['category'] = self::categoryFromAverage($item['average']);
        }
        unset($item);

        $overallAverage = $scoreCount > 0 ? round($scoreTotal / $scoreCount, 2) : 0.0;

        return [
            'score_percent' => round(($overallAverage / 4) * 100, 2),
            'average' => $overallAverage,
            'answered_count' => $scoreCount,
            'overall_category' => self::categoryFromAverage($overallAverage),
            'dimension_stats' => array_values($dimensionStats),
            'response_counts' => $responseCounts,
        ];
    }

    public static function mapelOverview(MapelPaket $mapel, Collection $sessions): array
    {
        $mapel->loadMissing('soals.pilihanJawabans');

        $sessions = $sessions->values();
        $dimensionAggregate = [];
        $questionBreakdown = [];
        $categoryDistribution = [];

        foreach ($sessions as $session) {
            $profile = $session->profil_ringkasan ?: self::sessionProfile($session);
            $categoryDistribution[$profile['overall_category']] = ($categoryDistribution[$profile['overall_category']] ?? 0) + 1;

            foreach ($profile['dimension_stats'] as $dimension) {
                $name = $dimension['dimensi'];
                $dimensionAggregate[$name] ??= [
                    'dimensi' => $name,
                    'score_total' => 0.0,
                    'count' => 0,
                    'average' => 0.0,
                    'score_percent' => 0.0,
                    'category' => 'Belum cukup data',
                ];
                $dimensionAggregate[$name]['score_total'] += $dimension['average'];
                $dimensionAggregate[$name]['count']++;
            }
        }

        foreach ($dimensionAggregate as &$item) {
            $item['average'] = $item['count'] > 0 ? round($item['score_total'] / $item['count'], 2) : 0.0;
            $item['score_percent'] = round(($item['average'] / 4) * 100, 2);
            $item['category'] = self::categoryFromAverage($item['average']);
        }
        unset($item);

        foreach ($mapel->soals as $soal) {
            $distribution = [];

            foreach ($soal->pilihanJawabans as $option) {
                $count = $sessions->filter(function (UjianSesi $session) use ($soal, $option) {
                    $answer = $session->jawabanSiswas->firstWhere('soal_id', $soal->id);
                    return $answer?->jawaban_pg === $option->kode;
                })->count();

                $distribution[] = [
                    'kode' => $option->kode,
                    'label' => $option->profil_label ?: $option->teks,
                    'count' => $count,
                    'percent' => $sessions->count() > 0 ? round(($count / $sessions->count()) * 100, 1) : 0.0,
                ];
            }

            $questionBreakdown[] = [
                'nomor' => $soal->nomor_soal,
                'dimensi' => $soal->dimensi ?: 'Umum',
                'subdimensi' => $soal->subdimensi,
                'distribution' => $distribution,
            ];
        }

        return [
            'dimension_stats' => array_values($dimensionAggregate),
            'question_breakdown' => $questionBreakdown,
            'category_distribution' => $categoryDistribution,
        ];
    }
}
