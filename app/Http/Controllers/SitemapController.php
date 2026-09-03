<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Jenjang;
use App\Models\LandingContent;
use App\Models\LandingExam;
use App\Models\LandingFaq;
use App\Models\LandingHeroMockup;
use App\Models\Material;
use App\Models\PricingPlan;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SitemapController
{
    public function __invoke(): Response
    {
        $urls = [];

        $urls[] = [
            'loc' => route('landing'),
            'lastmod' => $this->latestLandingUpdate()?->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        if (Route::has('register.guru.form')) {
            $urls[] = [
                'loc' => route('register.guru.form'),
                'lastmod' => null,
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        foreach ($this->kisiKisiUrls() as $url) {
            $urls[] = $url;
        }

        foreach ($this->articleUrls() as $url) {
            $urls[] = $url;
        }

        foreach ($this->publicExamUrls() as $url) {
            $urls[] = $url;
        }

        $xml = view('sitemap.xml', [
            'urls' => $urls,
        ])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function kisiKisiUrls(): array
    {
        $materialsLastmod = null;

        if (Schema::hasTable('materials')) {
            $materialsLastmod = Material::query()->max('updated_at');
        }

        $lastmod = $materialsLastmod ? Carbon::parse($materialsLastmod)->toAtomString() : null;

        $urls = [
            [
                'loc' => route('kisi-kisi.index'),
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
        ];

        if (! Schema::hasTable('jenjangs') || ! Schema::hasTable('materials')) {
            return $urls;
        }

        $jenjangCodes = Jenjang::query()->pluck('kode');

        $mapelsByJenjang = Material::query()
            ->selectRaw('jenjang, mapel')
            ->groupBy('jenjang', 'mapel')
            ->get()
            ->groupBy(fn ($row) => strtoupper((string) $row->jenjang));

        foreach ($jenjangCodes as $kode) {
            $urls[] = [
                'loc' => route('kisi-kisi.jenjang', ['jenjang' => strtolower($kode)]),
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];

            foreach ($mapelsByJenjang->get(strtoupper($kode), collect()) as $row) {
                $urls[] = [
                    'loc' => route('kisi-kisi.mapel', [
                        'jenjang' => strtolower($kode),
                        'mapel' => Str::slug((string) $row->mapel),
                    ]),
                    'lastmod' => $lastmod,
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        }

        return $urls;
    }

    private function articleUrls(): array
    {
        if (! Schema::hasTable('blog_posts')) {
            return [];
        }

        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at']);

        $urls = [
            [
                'loc' => route('artikel.index'),
                'lastmod' => $posts->first()?->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
        ];

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('artikel.show', ['post' => $post->slug]),
                'lastmod' => $post->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }

    private function publicExamUrls(): array
    {
        $urls = [];

        if (Route::has('ujian-online.index')) {
            $urls[] = [
                'loc' => route('ujian-online.index'),
                'lastmod' => null,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        if (! Schema::hasTable('landing_exams')) {
            return $urls;
        }

        foreach (['sd', 'smp', 'sma'] as $jenjang) {
            if (Route::has('ujian-online.jenjang')) {
                $urls[] = [
                    'loc' => route('ujian-online.jenjang', ['jenjang' => $jenjang]),
                    'lastmod' => null,
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        }

        $exams = LandingExam::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'jenjang', 'updated_at']);

        foreach ($exams as $exam) {
            $urls[] = [
                'loc' => route('ujian-online.show', [
                    'jenjang' => strtolower($exam->jenjang),
                    'landingExam' => $exam->slug,
                ]),
                'lastmod' => $exam->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }

    private function latestLandingUpdate(): ?Carbon
    {
        $tables = [
            'landing_contents' => LandingContent::class,
            'landing_faqs' => LandingFaq::class,
            'landing_hero_mockups' => LandingHeroMockup::class,
            'pricing_plans' => PricingPlan::class,
        ];

        $latest = null;

        foreach ($tables as $table => $model) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $updatedAt = $model::query()->max('updated_at');

            if ($updatedAt) {
                $date = Carbon::parse($updatedAt);
                if (! $latest || $date->greaterThan($latest)) {
                    $latest = $date;
                }
            }
        }

        return $latest;
    }
}
