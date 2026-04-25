<?php

namespace App\Http\Controllers;

use App\Models\GlobalQuestion;
use App\Models\LandingBranding;
use App\Models\LandingContent;
use App\Models\LandingFaq;
use App\Models\LandingHeroMockup;
use App\Models\Material;
use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    public function index(): View
    {
        $sectionActives = [
            'hero' => true,
            'faq' => true,
            'pricing' => true,
            'stats' => true,
        ];

        if (Schema::hasTable('landing_contents')) {
            $sectionRows = LandingContent::query()
                ->whereIn('section', array_keys($sectionActives))
                ->get()
                ->keyBy('section');

            foreach (array_keys($sectionActives) as $sectionKey) {
                if ($sectionRows->has($sectionKey)) {
                    $sectionActives[$sectionKey] = (bool) $sectionRows[$sectionKey]->is_active;
                }
            }
        }

        $tarifJenjangs = config('landing.pricing', []);

        if (Schema::hasTable('pricing_plans')) {
            $tarifQuery = PricingPlan::query()->where('is_active', true);

            if (Schema::hasColumn('pricing_plans', 'jenjang')) {
                $tarifQuery->orderByRaw("case when jenjang = 'SD' then 1 when jenjang = 'SMP' then 2 when jenjang = 'SMA' then 3 else 4 end");
            }

            $tarifJenjangs = $tarifQuery->get()
                ->map(fn ($plan) => [
                    'id' => $plan->id,
                    'jenjang' => $plan->jenjang ?? null,
                    'name' => $plan->name,
                    'subtitle' => $plan->subtitle,
                    'description' => $plan->description,
                    'price' => $plan->price,
                ])
                ->all();
        }

        // Fetch counts for materials and questions
        $materialStats = Material::query()
            ->select('jenjang', 'mapel', DB::raw('count(*) as count'))
            ->groupBy('jenjang', 'mapel')
            ->get();

        $questionStats = GlobalQuestion::query()
            ->join('jenjangs', 'global_questions.jenjang_id', '=', 'jenjangs.id')
            ->select('jenjangs.kode as jenjang', 'material_mapel as mapel', DB::raw('count(*) as count'))
            ->groupBy('jenjangs.kode', 'material_mapel')
            ->get();

        $stats = [];

        foreach ($materialStats as $m) {
            $jenjang = $m->jenjang;
            $mapel = $m->mapel ?: 'Umum';
            $stats[$jenjang][$mapel]['materials'] = $m->count;
        }

        foreach ($questionStats as $q) {
            $jenjang = $q->jenjang;
            $mapel = $q->mapel ?: 'Umum';
            $stats[$jenjang][$mapel]['questions'] = $q->count;
        }

        $logoUrl = asset('assets/img/logo.png');
        if (Schema::hasTable('landing_brandings')) {
            $branding = LandingBranding::query()
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($branding && $branding->logo_path) {
                $logoUrl = Storage::disk('public')->url($branding->logo_path);
            }
        }

        $hero = [
            'kicker' => 'Website pendamping guru untuk memantau kesiapan siswa menuju Tes Kemampuan Akademik (TKA).',
            'title' => 'Bantu guru memantau, menganalisis, dan menyiapkan siswa agar lebih siap menghadapi TKA.',
            'body' => 'Ujion TKA dirancang untuk guru/operator yang ingin melihat perkembangan akademik siswa dengan lebih jelas. Mulai dari latihan, paket soal, sesi ujian, sampai hasil akhir, semua disusun agar guru lebih mudah membaca kesiapan siswa, menemukan kelemahan belajar, dan mengambil langkah pembinaan sebelum TKA berlangsung.',
            'button_text' => 'Coba Sebagai Guru',
            'button_url' => null,
        ];

        if (Schema::hasTable('landing_contents')) {
            $heroContent = LandingContent::query()
                ->where('section', 'hero')
                ->first();

            if ($heroContent) {
                $hero['kicker'] = $heroContent->kicker ?: $hero['kicker'];
                $hero['title'] = $heroContent->title ?: $hero['title'];
                $hero['body'] = $heroContent->body ?: $hero['body'];
                $hero['button_text'] = $heroContent->button_text ?: $hero['button_text'];
                $hero['button_url'] = $heroContent->button_url ?: null;
            }
        }

        $faqs = [
            [
                'question' => 'Apakah platform ini cocok untuk pelaksanaan TKA?',
                'answer' => 'Ya. Platform ini cocok dipakai untuk membantu guru menyiapkan siswa menuju TKA melalui latihan, ujian, dan pembacaan hasil yang lebih terarah.',
            ],
            [
                'question' => 'Apakah guru bisa mengelola soal sendiri?',
                'answer' => 'Bisa. Guru memiliki workflow untuk bank soal, paket soal, mapel, dan sesi ujian sehingga persiapan siswa bisa dikelola dengan lebih rapi.',
            ],
            [
                'question' => 'Apakah siswa harus punya akun?',
                'answer' => 'Tidak. Siswa bisa masuk dengan token ujian, lalu mengisi identitas dan langsung mengikuti sesi yang tersedia.',
            ],
            [
                'question' => 'Apakah tetap relevan dengan arah resmi TKA?',
                'answer' => 'Ya. Platform ini dapat diposisikan selaras dengan semangat TKA karena membantu sekolah membaca capaian akademik siswa secara lebih terstruktur.',
            ],
            [
                'question' => 'Apakah bisa dipakai di HP siswa?',
                'answer' => 'Bisa. Tampilan dibuat responsif sehingga siswa tetap nyaman mengerjakan dari perangkat mobile, sementara guru tetap mengelola dari dashboard.',
            ],
            [
                'question' => 'Bagaimana jika koneksi internet tidak stabil?',
                'answer' => 'Sistem membantu meminimalkan risiko kehilangan jawaban dengan autosave, sehingga progres siswa tetap tercatat meski koneksi naik turun.',
            ],
        ];

        if (Schema::hasTable('landing_faqs')) {
            $dbFaqs = LandingFaq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($dbFaqs->isNotEmpty()) {
                $faqs = $dbFaqs
                    ->map(fn (LandingFaq $faq) => [
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ])
                    ->all();
            }
        }

        $heroMockups = collect();

        if (Schema::hasTable('landing_hero_mockups')) {
            $heroMockups = LandingHeroMockup::query()
                ->where('is_active', true)
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (LandingHeroMockup $mockup) => [
                    'badge' => $mockup->badge,
                    'title' => $mockup->title,
                    'description' => $mockup->description,
                    'image_url' => $mockup->image_url,
                    'is_featured' => $mockup->is_featured,
                ]);
        }

        $heroCtaUrl = $hero['button_url'] ? url($hero['button_url']) : route('register.guru.form');

        return view('landing', [
            'tarifJenjangs' => $tarifJenjangs,
            'stats' => $stats,
            'logoUrl' => $logoUrl,
            'hero' => $hero,
            'heroCtaUrl' => $heroCtaUrl,
            'heroMockups' => $heroMockups,
            'faqs' => $faqs,
            'sectionActives' => $sectionActives,
        ]);
    }
}
