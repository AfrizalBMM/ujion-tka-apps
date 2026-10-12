<?php

namespace App\Http\Controllers;

use App\Models\LandingBranding;
use App\Models\LandingContent;
use App\Models\LandingContentItem;
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
    private const SINGLETON_SECTIONS = [
        'hero',
        'bank_soal',
        'footer',
        'reasons_head',
        'audience_head',
        'steps_head',
        'features_head',
        'faq_head',
    ];

    private const ITEM_SECTIONS = [
        'hero_point',
        'reason',
        'audience',
        'step_guru',
        'step_siswa',
        'feature',
    ];

    public function index(): View
    {
        $sectionActives = array_fill_keys([...self::SINGLETON_SECTIONS, 'faq', 'stats'], true);

        $sectionRows = collect();
        if (Schema::hasTable('landing_contents')) {
            $sectionRows = LandingContent::query()
                ->whereIn('section', array_keys($sectionActives))
                ->get()
                ->keyBy('section');

            foreach ($sectionActives as $sectionKey => $default) {
                if ($sectionRows->has($sectionKey)) {
                    $sectionActives[$sectionKey] = (bool) $sectionRows[$sectionKey]->is_active;
                }
            }
        }

        $stats = $this->jenjangStats();

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

        $sections = [];
        foreach (self::SINGLETON_SECTIONS as $sectionKey) {
            $data = $this->sectionDefaults($sectionKey);

            $row = $sectionRows->get($sectionKey);
            if ($row) {
                foreach (array_keys($data) as $field) {
                    if ($field === 'is_active') {
                        continue;
                    }
                    $data[$field] = $row->{$field} ?: $data[$field];
                }
                $data['is_active'] = (bool) $row->is_active;
            }

            $sections[$sectionKey] = $data;
        }

        $hero = $sections['hero'];

        $faqs = $this->faqDefaults();

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

        $dbItems = collect();
        if (Schema::hasTable('landing_content_items')) {
            $dbItems = LandingContentItem::query()
                ->active()
                ->whereIn('section', self::ITEM_SECTIONS)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('section');
        }

        $items = [];
        foreach (self::ITEM_SECTIONS as $sectionKey) {
            $rows = $dbItems->get($sectionKey);

            if ($rows && $rows->isNotEmpty()) {
                $items[$sectionKey] = $rows
                    ->map(fn (LandingContentItem $item) => [
                        'id' => $item->id,
                        'kicker' => $item->kicker,
                        'title' => $item->title,
                        'body' => $item->body,
                        'caption' => $item->caption,
                        'image_path' => $item->image_path,
                        'image_url' => $item->image_path
                            ? Storage::disk('public')->url($item->image_path)
                            : null,
                        'sort_order' => $item->sort_order,
                        'is_active' => $item->is_active,
                    ])
                    ->values()
                    ->all();

                continue;
            }

            $items[$sectionKey] = $this->itemDefaults($sectionKey);
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
            'sections' => $sections,
            'items' => $items,
            'faqs' => $faqs,
            'sectionActives' => $sectionActives,
            'testimonials' => $testimonials,
        ]);
    }

    private function jenjangStats(): array
    {
        return Cache::remember(
            'landing.stats.jenjang.v1',
            now()->addMinutes(10),
            function () {
                $order = Schema::hasTable('jenjangs')
                    ? DB::table('jenjangs')->orderBy('urutan')->orderBy('id')->pluck('kode')->all()
                    : [];

                if ($order === []) {
                    $order = config('ujion.jenjangs', []);
                }

                $materiCounts = collect();
                if (Schema::hasTable('materials')) {
                    $materiCounts = Material::query()
                        ->whereNull('deleted_at')
                        ->select('jenjang', DB::raw('count(*) as total'))
                        ->groupBy('jenjang')
                        ->pluck('total', 'jenjang');
                }

                $soalCounts = collect();
                if (Schema::hasTable('questions')) {
                    $soalCounts = DB::table('questions')
                        ->where('is_active', 1)
                        ->select('jenjang', DB::raw('count(*) as total'))
                        ->groupBy('jenjang')
                        ->pluck('total', 'jenjang');
                }

                $stats = [];
                foreach ($order as $kode) {
                    $soal = (int) ($soalCounts[$kode] ?? 0);

                    $stats[$kode] = [
                        'materi' => (int) ($materiCounts[$kode] ?? 0),
                        'soal' => $soal,
                        'coming_soon' => $soal === 0,
                    ];
                }

                return $stats;
            },
        );
    }

    private function sectionDefaults(string $section): array
    {
        $texts = [
            'hero' => [
                'kicker' => 'Latihan TKA online untuk bimbel dan guru',
                'title' => 'Latih siswa untuk TKA dengan soal dan skor yang mirip ujian aslinya',
                'body' => 'Ujion TKA adalah situs ujian online khusus Tes Kemampuan Akademik (TKA). Guru dan bimbel bisa membuat ujian sendiri, siswa mengerjakannya lewat HP, dan hasilnya langsung bisa dibaca. Skor memakai skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK.',
                'button_text' => 'Coba Sebagai Guru',
                'seo_title' => 'Ujion TKA - Latihan TKA online untuk bimbel dan guru',
                'seo_description' => 'Ujion TKA adalah situs latihan dan persiapan TKA untuk bimbel dan guru. Guru membuat ujian sendiri, siswa mengerjakan lewat HP.',
            ],
            'bank_soal' => [
                'kicker' => 'Bank soal dan materi',
                'title' => 'Soal dan materi mengikuti aturan resmi TKA',
                'body' => 'Semua soal dan materi disusun berdasarkan Peraturan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan (BSKAP) Kemendikdasmen Nomor 47 Tahun 2025 tentang Kerangka Asesmen TKA untuk jenjang SD/MI dan SMP/MTs, serta Peraturan BSKAP Nomor 45 Tahun 2025 untuk jenjang SMA/MA/SMK.',
            ],
            'footer' => [
                'kicker' => 'Ujion TKA adalah situs latihan dan ujian TKA untuk bimbel dan guru. Skor 0-100 untuk SD/SMP dan 200-800 untuk SMA/SMK, paket soal disusun sendiri, siswa cukup pakai HP.',
                'title' => 'Masuk',
                'body' => 'Ujion adalah situs latihan dan persiapan TKA, bukan penyelenggara ujian TKA resmi.',
                'button_text' => 'Daftar',
                'note' => 'Ikuti Saluran WhatsApp',
            ],
            'reasons_head' => [
                'kicker' => '3 alasan Ujion berbeda dari ujian online biasa',
                'title' => 'Dibuat khusus untuk TKA, bukan ujian online serba guna.',
            ],
            'audience_head' => [
                'kicker' => 'Untuk siapa Ujion',
                'title' => 'Cocok untuk yang menyiapkan siswa menghadapi TKA',
                'body' => 'Ujion fokus pada satu hal: latihan dan ujian TKA. Ujion bukan aplikasi ujian umum dan bukan sistem sekolah yang lengkap, jadi lebih ringan dan mudah dipakai.',
            ],
            'steps_head' => [
                'kicker' => 'Cara kerja',
                'title' => 'Cara kerja untuk guru dan siswa',
            ],
            'features_head' => [
                'kicker' => 'Fitur utama',
                'title' => 'Yang membuat Ujion cocok untuk persiapan TKA',
            ],
            'faq_head' => [
                'kicker' => 'Tanya jawab',
                'title' => 'Pertanyaan yang sering diajukan',
            ],
        ];

        return array_merge([
            'kicker' => null,
            'title' => null,
            'body' => null,
            'subtitle' => null,
            'button_text' => null,
            'button_url' => null,
            'cta_text' => null,
            'cta_url' => null,
            'note' => null,
            'seo_title' => null,
            'seo_description' => null,
            'is_active' => true,
        ], $texts[$section] ?? []);
    }

    private function itemDefaults(string $section): array
    {
        $items = [
            'hero_point' => [
                ['title' => 'Skor memakai skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK'],
                ['title' => 'Susun paket soal sendiri dengan mudah'],
                ['title' => 'Siswa cukup pakai HP dan kode ujian'],
            ],
            'reason' => [
                [
                    'title' => 'Skor dan soal mirip TKA',
                    'body' => 'Hasil ujian tampil dengan skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK. Soalnya mengikuti kerangka asesmen TKA terbaru, jadi siswa sudah terbiasa sebelum ujian sebenarnya.',
                ],
                [
                    'title' => 'Atur soal sendiri dengan mudah',
                    'body' => 'Guru dan bimbel dapat menyusun paket soal dan mata pelajaran sendiri, lalu membagikan ujian ke siswa lewat satu kode. Hasil tiap siswa langsung terkumpul di satu tempat.',
                ],
                [
                    'title' => 'Praktis untuk guru dan siswa',
                    'body' => 'Siswa mengerjakan lewat HP tanpa membuat akun, cukup memasukkan kode ujian dari guru. Jawaban tersimpan otomatis, dan tidak perlu lab komputer.',
                ],
            ],
            'audience' => [
                [
                    'title' => 'Bimbel',
                    'body' => 'Susun paket soal sendiri, jalankan ujian dengan satu kode, dan pantau hasil semua siswa dari satu tampilan.',
                ],
                [
                    'title' => 'Guru',
                    'body' => 'Latih siswa dengan soal berbentuk TKA dan skor sesuai jenjang, lalu lihat siapa yang perlu dibantu.',
                ],
                [
                    'title' => 'Siswa',
                    'body' => 'Kerjakan ujian dari HP. Tidak perlu daftar akun, cukup masukkan kode dari guru. Jawaban tersimpan otomatis kalau sinyal putus.',
                ],
                [
                    'title' => 'Orang tua',
                    'body' => 'Anak bisa berlatih soal berbentuk TKA langsung dari HP, tanpa perlu ke lab komputer.',
                ],
            ],
            'step_guru' => [
                [
                    'title' => 'Daftar akun guru',
                    'body' => 'Akun akan diperiksa dulu sebelum aktif.',
                ],
                [
                    'title' => 'Siapkan paket soal',
                    'body' => 'Pilih mata pelajaran sesuai jenjang siswa Anda.',
                ],
                [
                    'title' => 'Adakan latihan dan ujian secara rutin',
                    'body' => 'Gunakan bank soal, teks bacaan, dan sesi ujian agar siswa terbiasa menjelang TKA.',
                ],
                [
                    'title' => 'Baca hasilnya',
                    'body' => 'Lihat nilai tiap siswa, lalu tentukan materi mana yang perlu diulang.',
                ],
            ],
            'step_siswa' => [
                [
                    'title' => 'Masukkan kode ujian',
                    'body' => 'Kode ujian dibagikan guru, lalu ujian bisa langsung dikerjakan.',
                ],
                [
                    'title' => 'Tulis nama Anda',
                    'body' => 'Cukup satu langkah identitas, tanpa membuat akun.',
                ],
                [
                    'title' => 'Kerjakan soal',
                    'body' => 'Ada timer dan penanda nomor soal. Jawaban tersimpan otomatis.',
                ],
                [
                    'title' => 'Selesai',
                    'body' => 'Hasil tersimpan rapi dan bisa dibaca guru untuk membantu belajar berikutnya.',
                ],
            ],
            'feature' => [
                ['title' => 'Skor memakai skala resmi TKA: 0-100 untuk SD/SMP, 200-800 untuk SMA/SMK'],
                ['title' => 'Susun paket soal sendiri'],
                ['title' => 'Cukup HP, siswa masuk dengan kode ujian'],
                ['title' => 'Jawaban siswa tersimpan otomatis'],
            ],
        ];

        return collect($items[$section] ?? [])
            ->map(fn (array $item, int $index) => array_merge([
                'id' => null,
                'kicker' => null,
                'title' => null,
                'body' => null,
                'caption' => null,
                'image_path' => null,
                'image_url' => null,
                'sort_order' => $index,
                'is_active' => true,
            ], $item))
            ->values()
            ->all();
    }

    private function faqDefaults(): array
    {
        return [
            [
                'question' => 'Apa bedanya Ujion dengan aplikasi ujian online biasa?',
                'answer' => 'Ujion dibuat khusus untuk TKA. Nilainya memakai skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK, dan soalnya mengikuti kerangka asesmen TKA dalam Peraturan BSKAP Nomor 47 Tahun 2025 (SD/MI dan SMP/MTs) serta Nomor 45 Tahun 2025 (SMA/MA/SMK). Aplikasi ujian online biasa umumnya hanya memberi nilai 0-100.',
            ],
            [
                'question' => 'Apakah Ujion cocok untuk bimbel?',
                'answer' => 'Cocok. Bimbel bisa menyusun paket soal dan mata pelajaran sendiri, menjalankan ujian untuk banyak siswa, dan membaca hasilnya dari satu tampilan. Tidak harus memakai bank soal dari pihak lain.',
            ],
            [
                'question' => 'Bagaimana cara siswa masuk ke ujian?',
                'answer' => 'Guru membagikan kode ujian. Siswa memasukkan kode itu, menulis nama, lalu langsung mengerjakan. Tidak perlu membuat akun.',
            ],
            [
                'question' => 'Bisakah siswa mengerjakan dari HP?',
                'answer' => 'Bisa. Tampilan menyesuaikan layar HP, jadi tidak perlu lab komputer.',
            ],
            [
                'question' => 'Apakah soalnya sama dengan TKA resmi?',
                'answer' => 'Soal dibuat mengikuti kerangka asesmen TKA dalam Peraturan BSKAP Nomor 47 Tahun 2025 (SD/MI dan SMP/MTs) dan Nomor 45 Tahun 2025 (SMA/MA/SMK). Dengan begitu siswa berlatih dengan bentuk soal yang mereka hadapi nanti.',
            ],
            [
                'question' => 'Bagaimana kalau internet putus-nyambung?',
                'answer' => 'Jawaban siswa disimpan otomatis secara berkala, jadi tidak hilang. Siswa bisa melanjutkan mengerjakan saat sinyal kembali.',
            ],
            [
                'question' => 'Apakah Ujion penyelenggara ujian TKA resmi?',
                'answer' => 'Bukan. Ujion adalah situs latihan dan persiapan TKA, bukan penyelenggara ujian TKA resmi.',
            ],
        ];
    }
}
