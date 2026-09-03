<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController
{
    private const DISALLOWED_PATHS = [
        '/guru',
        '/superadmin',
        '/ngadumin',
        '/siswa',
        '/materi',
        '/payments',
        '/login',
        '/lupa-token',
        '/api',
        '/ujian-online/pending',
        '/ujian-online/start',
        '/ujian-online/result',
        '/ujian-online/pay',
    ];

    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
