<?php

return [
    'jenjangs' => ['SD', 'SMP', 'SMA'],

    'assessment_types' => [
        'paket_lengkap' => [
            'label' => 'Paket Lengkap',
            'description' => 'Paket ujian lengkap per jenjang',
            'default_mapels' => [
                'bahasa_indonesia',
                'matematika',
                'survey_karakter',
                'sulingjar',
            ],
        ],
        'tka' => [
            'label' => 'TKA',
            'description' => 'Tes Kemampuan Akademik',
            'default_mapels' => [
                'matematika',
                'bahasa_indonesia',
            ],
        ],
        'survey_karakter' => [
            'label' => 'Survey Karakter',
            'description' => 'Survey karakter siswa',
            'default_mapels' => [
                'karakter_pribadi',
                'karakter_sosial',
            ],
        ],
        'sulingjar' => [
            'label' => 'Sulingjar',
            'description' => 'Survey Lingkungan Belajar',
            'default_mapels' => [
                'lingkungan_belajar',
                'iklim_sekolah',
            ],
        ],
    ],

    'mapel_labels' => [
        'matematika' => 'Matematika',
        'bahasa_indonesia' => 'Bahasa Indonesia',
        'survey_karakter' => 'Survey Karakter',
        'sulingjar' => 'Sulingjar',
        'karakter_pribadi' => 'Survey Karakter',
        'karakter_sosial' => 'Survey Karakter',
        'lingkungan_belajar' => 'Sulingjar',
        'iklim_sekolah' => 'Sulingjar',
    ],

    'mapel_assessment_types' => [
        'bahasa_indonesia' => 'tka',
        'matematika' => 'tka',
        'survey_karakter' => 'survey_karakter',
        'sulingjar' => 'sulingjar',
        'karakter_pribadi' => 'survey_karakter',
        'karakter_sosial' => 'survey_karakter',
        'lingkungan_belajar' => 'sulingjar',
        'iklim_sekolah' => 'sulingjar',
    ],
];
