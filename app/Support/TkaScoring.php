<?php

declare(strict_types=1);

namespace App\Support;

/**
 * TKA Scoring — 200-800 scale with penalty and round-half-up.
 *
 * Formula:
 *   raw = correct - (penalty_factor × wrong)
 *   pre_clamp = baseline + (raw / total) × (ceiling − baseline)
 *   scaled = max(baseline, min(ceiling, round_half_up(pre_clamp)))
 */
final class TkaScoring
{
    private int $baseline;

    private int $ceiling;

    private float $penaltyFactor;

    public function __construct(
        ?int $baseline = null,
        ?int $ceiling = null,
        ?float $penaltyFactor = null,
    ) {
        $config = config('ujion.scoring', []);

        $this->baseline = $baseline ?? (int) ($config['baseline'] ?? 200);
        $this->ceiling = $ceiling ?? (int) ($config['ceiling'] ?? 800);
        $this->penaltyFactor = $penaltyFactor ?? (float) ($config['penalty_factor'] ?? 0.25);
    }

    /**
     * Calculate scaled TKA score.
     *
     * @param  int  $correct  Number of correct answers.
     * @param  int  $wrong  Number of wrong answers.
     * @param  int  $total  Total number of items.
     * @param  float|null  $penaltyFactor  Override penalty factor (null = use config default).
     * @return int Scaled score in [baseline, ceiling].
     */
    public function calculate(int $correct, int $wrong, int $total, ?float $penaltyFactor = null): int
    {
        // Division-by-zero guard: return baseline (TKA floor), not 0.
        if ($total <= 0) {
            return $this->baseline;
        }

        $penalty = $penaltyFactor ?? $this->penaltyFactor;

        $raw = $correct - ($penalty * $wrong);
        $range = $this->ceiling - $this->baseline;
        $preClamp = $this->baseline + ($raw / $total) * $range;

        return $this->clamp($this->roundHalfUp($preClamp));
    }

    /**
     * Round half up (towards positive infinity), matching TKA convention.
     * PHP's built-in round() uses banker's rounding (HALF_EVEN) by default,
     * which produces different results at .5 boundaries for odd integers.
     */
    public function roundHalfUp(float $value): int
    {
        return (int) floor($value + 0.5);
    }

    /**
     * Clamp value to [baseline, ceiling].
     */
    public function clamp(int $value): int
    {
        return max($this->baseline, min($this->ceiling, $value));
    }

    /**
     * Get the configured passing grade.
     */
    public function passingGrade(): int
    {
        return (int) (config('ujion.scoring.passing_grade') ?? 500);
    }

    /**
     * Get distribution bins for the 200-800 scale.
     *
     * @return array<string, int>
     */
    public static function distributionBins(): array
    {
        return [
            '700-800' => 0,
            '600-699' => 0,
            '500-599' => 0,
            '400-499' => 0,
            '200-399' => 0,
        ];
    }

    /**
     * Assign a score to a distribution bin.
     */
    public static function assignBin(float $skor): string
    {
        if ($skor >= 700) {
            return '700-800';
        }
        if ($skor >= 600) {
            return '600-699';
        }
        if ($skor >= 500) {
            return '500-599';
        }
        if ($skor >= 400) {
            return '400-499';
        }

        return '200-399';
    }
}
