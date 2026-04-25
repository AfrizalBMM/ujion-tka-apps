<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController
{
    public function __invoke(): Response
    {
        $urls = [];

        $urls[] = [
            'loc' => route('landing'),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        if (Route::has('register.guru.form')) {
            $urls[] = [
                'loc' => route('register.guru.form'),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        $lastmod = now()->toAtomString();

        $xml = view('sitemap.xml', [
            'urls' => $urls,
            'lastmod' => $lastmod,
        ])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
