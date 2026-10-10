<?php

return [
    'jenjangs' => ['SD', 'SMP', 'SMA'],

    'audit_enabled' => env('UJION_AUDIT_ENABLED', true),

    /*
    |-------------------------------------------------------------
    | TKA Scoring Configuration (200-800 scale)
    |-------------------------------------------------------------
    | Formula: scaled = max(baseline, min(ceiling,
    |         round_half_up(baseline + ((correct - penalty_factor * wrong) / total) * (ceiling - baseline))))
    */
    'scoring' => [
        'baseline' => (int) env('TKA_SCORE_BASELINE', 200),
        'ceiling' => (int) env('TKA_SCORE_CEILING', 800),
        'penalty_factor' => (float) env('TKA_PENALTY_FACTOR', 0.25),
        'passing_grade' => (int) env('TKA_PASSING_GRADE', 500),
    ],
];
