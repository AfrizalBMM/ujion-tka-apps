<?php

namespace App\Http\Controllers;

use App\Models\GlobalQuestion;
use App\Models\LandingBranding;
use App\Models\LandingContent;
use App\Models\LandingFaq;
use App\Models\LandingHeroMockup;
use App\Models\Material;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
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
            'stats' => true,
        ];

        if (Schema::hasTable('landing_contents')) {
            $sectionRows = LandingContent::query()
                ->whereIn('section', array_keys($sectionActives))
                ->get()
                ->keyBy('section');

            foreach (array_keys($sectionActives) as $sectionKey) {
                if ($sectionRows->has($sectionKey)) {
                    $sectionActives[$sectionKey] =
                        (bool) $sectionRows[$sectionKey]->is_active;
                }
            }
        }

        // Fetch counts for materials and questions (cached 10 minutes — aggregate queries)
        $stats = Cache::remember(
            'landing.stats.v1',
            now()->addMinutes(10),
            function () {
                $materialStats = Material::query()
                    ->select('jenjang', 'mapel', DB::raw('count(*) as count'))
                    ->groupBy('jenjang', 'mapel')
                    ->get();

                $questionStats = GlobalQuestion::query()
                    ->join(
                        'jenjangs',
                        'global_questions.jenjang_id',
                        '=',
                        'jenjangs.id',
                    )
                    ->select(
                        'jenjangs.kode as jenjang',
                        'material_mapel as mapel',
                        DB::raw('count(*) as count'),
                    )
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

                return $stats;
            },
        );

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
            'kicker' => 'Platform TKA-first untuk bimbel & guru — bukan CBT biasa.',
            'title' => 'Siapkan siswa TKA dengan platform yang benar-benar dirancang untuk format ujian nasional terbaru.',
            'body' => 'Ujion TKA adalah platform ujian yang spesifik dibangun untuk Tes Kemampuan Akademik. Skor 200-800 sesuai standar Kemendikdasmen, format soal aligned dengan kerangka asesmen terbaru, dan alur kerja yang pas untuk bimbel maupun guru independen. Mulai gratis, jalankan dari HP.',
            'button_text' => 'Coba Sebagai Guru',
            'button_url' => null,
            'seo_title' => null,
            'seo_description' => null,
        ];

        if (Schema::hasTable('landing_contents')) {
            $heroContent = LandingContent::query()
                ->where('section', 'hero')
                ->first();

            if ($heroContent) {
                $hero['kicker'] = $heroContent->kicker ?: $hero['kicker'];
                $hero['title'] = $heroContent->title ?: $hero['title'];
                $hero['body'] = $heroContent->body ?: $hero['body'];
                $hero['button_text'] =
                    $heroContent->button_text ?: $hero['button_text'];
                $hero['button_url'] = $heroContent->button_url ?: null;
                $hero['seo_title'] = $heroContent->seo_title ?: null;
                $hero['seo_description'] =
                    $heroContent->seo_description ?: null;
            }
        }

        $faqs = [
            [
                'question' => 'Apa beda Ujion dengan CBT biasa?',
                'answer' => 'Ujion dibangun khusus untuk TKA: skor 200-800 sesuai standar Kemendikdasmen, format soal aligned dengan kerangka asesmen terbaru (Peraturan BSKAP No. 047/H/AN/2025). CBT biasa hanya menampilkan nilai 0-100 tanpa konteks TKA.',
            ],
            [
                'question' => 'Apakah cocok untuk bimbel, bukan sekolah?',
                'answer' => 'Ya. Ujion mendukung white-label branding, upload soal sendiri, dan pengelolaan paket soal independen. Bimbel bisa langsung pakai tanpa terikat bank soal global. Bukan LMS sekolah yang rigid.',
            ],
            [
                'question' => 'Berapa harga? Apakah ada paket gratis?',
                'answer' => 'Mulai gratis, tanpa kartu kredit. Model freemium: fitur dasar tersedia gratis, upgrade untuk fitur premium. Siswa tidak perlu akun berbayar — cukup token dari guru.',
            ],
            [
                'question' => 'Apakah siswa bisa kerjakan dari HP?',
                'answer' => 'Bisa. Tampilan responsif, siswa masuk pakai token tanpa akun, dan autosave menjaga jawaban tetap tersimpan meski koneksi naik turun. Tidak perlu lab komputer.',
            ],
            [
                'question' => 'Apakah format soal sesuai dengan TKA resmi?',
                'answer' => 'Ya. Format soal mengikuti kerangka asesmen Peraturan BSKAP No. 047/H/AN/2025 dengan skor 200-800. Siswa berlatih dengan format yang sama seperti ujian sebenarnya.',
            ],
            [
                'question' => 'Bagaimana jika koneksi internet tidak stabil?',
                'answer' => 'Sistem autosave menjawaban siswa secara berkala, sehingga progres tetap tercatat meski koneksi putus-nyambung. Siswa bisa lanjut mengerjakan tanpa khawatir kehilangan jawaban.',
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
                    ->map(
                        fn (LandingFaq $faq) => [
                            'question' => $faq->question,
                            'answer' => $faq->answer,
                        ],
                    )
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
                ->map(
                    fn (LandingHeroMockup $mockup) => [
                        'badge' => $mockup->badge,
                        'title' => $mockup->title,
                        'description' => $mockup->description,
                        'image_url' => $mockup->image_url,
                        'is_featured' => $mockup->is_featured,
                    ],
                );
        }

        $heroCtaUrl = $hero['button_url']
            ? url($hero['button_url'])
            : route('register.guru.form');

        $testimonials = collect();

        if (Schema::hasTable('testimonials')) {
            $testimonials = Testimonial::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view('landing', [
            'stats' => $stats,
            'logoUrl' => $logoUrl,
            'hero' => $hero,
            'heroCtaUrl' => $heroCtaUrl,
            'heroMockups' => $heroMockups,
            'faqs' => $faqs,
            'sectionActives' => $sectionActives,
            'testimonials' => $testimonials,
        ]);
    }
}
