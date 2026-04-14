<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Landing Page Settings
    |--------------------------------------------------------------------------
    |
    | Default pricing preview shown on the landing page.
    | If a Superadmin pricing module is added later, it can override these.
    |
    */

    'pricing' => [
        [
            'name' => 'Paket Starter',
            'subtitle' => 'Cocok untuk uji coba 1 kelas',
            'price' => '49.000',
            'original_price' => '99.000',
            'period' => '/bulan',
        ],
        [
            'name' => 'Paket Sekolah',
            'subtitle' => 'Untuk kebutuhan 1 sekolah',
            'price' => '149.000',
            'original_price' => '299.000',
            'period' => '/bulan',
        ],
        [
            'name' => 'Paket Pro',
            'subtitle' => 'Analisis lengkap + bank soal',
            'price' => '299.000',
            'original_price' => '499.000',
            'period' => '/bulan',
        ],
    ],
];
