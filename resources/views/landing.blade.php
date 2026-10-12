<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $sectionsData = $sections ?? [];
        $itemsData = $items ?? [];

        $heroPoints = $itemsData['hero_point'] ?? [];
        $reasons = $itemsData['reason'] ?? [];
        $audience = $itemsData['audience'] ?? [];
        $stepGuru = $itemsData['step_guru'] ?? [];
        $stepSiswa = $itemsData['step_siswa'] ?? [];
        $features = $itemsData['feature'] ?? [];

        $reasonsHead = $sectionsData['reasons_head'] ?? [];
        $bankSoal = $sectionsData['bank_soal'] ?? [];
        $audienceHead = $sectionsData['audience_head'] ?? [];
        $stepsHead = $sectionsData['steps_head'] ?? [];
        $featuresHead = $sectionsData['features_head'] ?? [];
        $faqHead = $sectionsData['faq_head'] ?? [];
        $footerSection = $sectionsData['footer'] ?? [];

        $seoTitle = $hero['seo_title'] ?? null;
        if (empty($seoTitle)) {
            $seoTitle = config('app.name', 'Ujion TKA');
            if (!empty($hero['title'] ?? null)) {
                $seoTitle = $seoTitle . ' — ' . $hero['title'];
            }
        }

        $seoDescription = $hero['seo_description']
            ?? $hero['kicker']
            ?? 'Ujion TKA adalah situs latihan dan persiapan TKA untuk bimbel dan guru. Guru membuat ujian sendiri, siswa mengerjakan lewat HP.';

        $canonicalUrl = route('landing');

        $ogImageAbs = route('og.image');

        $faqJsonLd = [];
        if (($sectionActives['faq'] ?? true) === true) {
            $faqJsonLd = collect($faqs ?? [])
                ->map(fn($faq) => [
                    '@type' => 'Question',
                    'name' => (string) ($faq['question'] ?? ''),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => (string) ($faq['answer'] ?? ''),
                    ],
                ])
                ->filter(fn($item) => trim((string) ($item['name'] ?? '')) !== '' && trim((string) ($item['acceptedAnswer']['text'] ?? '')) !== '')
                ->values()
                ->all();
        }
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'Ujion TKA') }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImageAbs }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $ogImageAbs }}">

    @vite(['resources/css/app.css', 'resources/js/public.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <script type="application/ld+json">
        {!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => config('app.name', 'Ujion TKA'),
    'url' => $canonicalUrl,
    'logo' => $ogImageAbs,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name', 'Ujion TKA'),
    'url' => $canonicalUrl,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>

    @if (!empty($faqJsonLd))
        <script type="application/ld+json">
                        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqJsonLd,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
                    </script>
    @endif
</head>

<body class="landing-body min-h-screen text-textPrimary dark:text-slate-100">
    @php
        $navItems = [
            ['label' => 'Keunggulan', 'href' => '#solusi'],
            ['label' => 'Cara Kerja', 'href' => '#flow'],
            ['label' => 'Kisi-Kisi TKA', 'href' => route('kisi-kisi.index')],
            ['label' => 'Artikel', 'href' => route('artikel.index')],
        ];

        if (\Illuminate\Support\Facades\Route::has('ujian-online.index')) {
            $navItems[] = ['label' => 'Ujian Online', 'href' => route('ujian-online.index')];
        }

        if (($sectionActives['faq'] ?? true) === true) {
            $navItems[] = ['label' => 'Tanya Jawab', 'href' => '#tanya-jawab'];
        }
    @endphp

    <header class="landing-header">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-3" data-track="landing_brand_header">
                <div class="landing-brand-mark overflow-hidden">
                    <img src="{{ $logoUrl ?? asset('assets/img/logo.png') }}" alt="Logo Ujion TKA"
                        class="h-full w-full object-cover">
                </div>
                <div class="leading-tight">
                    <div class="font-bold text-slate-900 dark:text-white">Ujion TKA</div>
                    <div class="text-xs uppercase tracking-[0.22em] text-textSecondary dark:text-slate-400">TKA-First Platform
                    </div>
                </div>
            </a>

            <nav class="hidden items-center gap-6 lg:flex">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="landing-nav-link">{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    id="landing-mobile-nav-toggle"
                    class="lg:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                    aria-label="Buka menu navigasi"
                    aria-expanded="false"
                    aria-controls="landing-mobile-nav-drawer"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <a href="https://whatsapp.com/channel/0029VbCcQxX5fM5fp2n5fM36" target="_blank"
                    class="btn-success hidden sm:inline-flex" data-track="landing_wa_header">
                    <i class="fa-brands fa-whatsapp text-lg"></i>
                    Gabung Saluran
                </a>
                <button type="button" class="btn-secondary px-3" data-theme-toggle aria-label="Toggle dark mode"
                    title="Dark mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-secondary"
                        data-track="landing_login_header">
                        Masuk
                    </a>
                @endif
                @if (Route::has('register.guru.form'))
                    <a href="{{ route('register.guru.form') }}" class="btn-primary hidden sm:inline-flex" data-track="landing_register_header">
                        Daftar (Guru)
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <div id="landing-mobile-nav-drawer" class="fixed inset-0 z-[60] lg:hidden" style="display:none" aria-hidden="true">
        <div id="landing-mobile-nav-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div id="landing-mobile-nav-panel" class="absolute right-0 top-0 h-full w-72 max-w-[80vw] overflow-y-auto bg-white p-6 shadow-2xl dark:bg-slate-900 transition-transform duration-300" role="dialog" aria-modal="true" aria-label="Menu navigasi mobile">
            <div class="mb-6 flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white">Menu</span>
                <button type="button" id="landing-mobile-nav-close" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Tutup menu navigasi">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex flex-col gap-4">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="text-base font-medium text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400">{{ $item['label'] }}</a>
                @endforeach
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="text-base font-medium text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400">Masuk</a>
                @endif
                @if (Route::has('register.guru.form'))
                    <a href="{{ route('register.guru.form') }}" class="btn-primary mt-2 w-full justify-center">Daftar (Guru)</a>
                @endif
            </nav>
        </div>
    </div>

    <script>
        (function () {
            var drawer = document.getElementById('landing-mobile-nav-drawer');
            var toggle = document.getElementById('landing-mobile-nav-toggle');
            var closeBtn = document.getElementById('landing-mobile-nav-close');
            var backdrop = document.getElementById('landing-mobile-nav-backdrop');

            function openDrawer() {
                drawer.style.display = '';
                drawer.setAttribute('aria-hidden', 'false');
                toggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }
            function closeDrawer() {
                drawer.style.display = 'none';
                drawer.setAttribute('aria-hidden', 'true');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }

            toggle.addEventListener('click', openDrawer);
            closeBtn.addEventListener('click', closeDrawer);
            backdrop.addEventListener('click', closeDrawer);
            drawer.querySelectorAll('nav a').forEach(function (link) {
                link.addEventListener('click', closeDrawer);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') {
                    closeDrawer();
                }
            });
        })();
    </script>

    <main class="pb-16">
        <div class="mx-auto max-w-7xl px-4 pt-6">
            @include('components.ui.flash')
        </div>

        @if (($sectionActives['hero'] ?? true) === true)
            <section class="relative overflow-hidden">
                <div class="landing-orb left-[-4rem] top-16"></div>
                <div class="landing-orb right-[-5rem] top-24"></div>

                <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start lg:py-16">
                    <div class="animate-fade-in-up">
                        <div class="landing-kicker">
                            <i class="fa-solid fa-bullseye text-warning"></i>
                            {{ $hero['kicker'] ?? 'Latihan TKA online untuk bimbel dan guru' }}
                        </div>

                        <h1 class="landing-hero-title">
                            {{ $hero['title'] ?? 'Latih siswa untuk TKA dengan soal dan skor yang mirip ujian aslinya' }}
                        </h1>

                        <p class="landing-hero-copy">
                            {{ $hero['body'] ?? 'Ujion TKA adalah situs ujian online khusus Tes Kemampuan Akademik (TKA). Guru dan bimbel bisa membuat ujian sendiri, siswa mengerjakannya lewat HP, dan hasilnya langsung bisa dibaca. Skor memakai skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK.' }}
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @if (Route::has('register.guru.form'))
                                <a href="{{ $heroCtaUrl ?? route('register.guru.form') }}" class="btn-primary"
                                    data-track="landing_register_hero">
                                    {{ $hero['button_text'] ?? 'Coba Sebagai Guru' }}
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            @endif

                        </div>

                        @if (count($heroPoints))
                            <div class="mt-8 flex flex-wrap gap-3 text-sm text-textSecondary dark:text-slate-400">
                                @foreach ($heroPoints as $point)
                                    <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-primary"></i> {{ $point['title'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="animate-slide-in-right">
                        <div class="landing-showcase-shell">


                            <div class="mt-5 grid gap-4">
                                @if (($heroMockups ?? collect())->isNotEmpty())
                                    @php
                                        $featuredMockup = $heroMockups->first();
                                        $secondaryMockups = $heroMockups->skip(1)->take(2);
                                    @endphp

                                    <div class="overflow-hidden">
                                        @if ($featuredMockup['badge'])
                                            <div class="landing-mockup-badge">{{ $featuredMockup['badge'] }}</div>
                                        @endif
                                        <div class="landing-mockup-title">{{ $featuredMockup['title'] }}</div>
                                        @if ($featuredMockup['description'])
                                            <div class="landing-mockup-copy">{{ $featuredMockup['description'] }}</div>
                                        @endif
                                        <img src="{{ $featuredMockup['image_url'] }}" alt="{{ $featuredMockup['title'] }}"
                                            class="mt-4 w-full rounded-2xl object-cover">
                                    </div>

                                    @if ($secondaryMockups->isNotEmpty())
                                        <div class="grid gap-4">
                                            @foreach ($secondaryMockups as $mockup)
                                                <div class="overflow-hidden">
                                                    @if ($mockup['badge'])
                                                        <div class="landing-mockup-badge">{{ $mockup['badge'] }}</div>
                                                    @endif
                                                    <div class="landing-mockup-title">{{ $mockup['title'] }}</div>
                                                    @if ($mockup['description'])
                                                        <div class="landing-mockup-copy">{{ $mockup['description'] }}</div>
                                                    @endif
                                                    <img src="{{ $mockup['image_url'] }}" alt="{{ $mockup['title'] }}"
                                                        class="mt-3 h-36 w-full rounded-xl object-cover">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div>
                                        <div class="landing-mockup-badge">Tampilan guru</div>
                                        <div class="landing-mockup-title">Menyusun paket soal dan membaca hasil siswa</div>
                                        <div class="landing-mockup-dropzone">
                                            <i class="fa-regular fa-image text-2xl"></i>
                                            <div class="mt-3 font-semibold">Tampilan dashboard guru tampil di sini</div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="landing-mockup-badge">Tampilan siswa</div>
                                        <div class="landing-mockup-title">Mengerjakan soal dari HP dengan timer dan navigasi soal</div>
                                        <div class="landing-mockup-dropzone compact">
                                            <i class="fa-solid fa-mobile-screen-button text-xl"></i>
                                            <div class="mt-2 text-sm font-semibold">Tampilan pengerjaan ujian siswa tampil di sini</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section id="solusi" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
            <div class="mb-8 text-center">
                <div class="landing-section-kicker mb-3">{{ $reasonsHead['kicker'] ?? '3 alasan Ujion berbeda dari ujian online biasa' }}</div>
                <h2 class="landing-section-title max-w-2xl mx-auto">{{ $reasonsHead['title'] ?? 'Dibuat khusus untuk TKA, bukan ujian online serba guna.' }}</h2>
            </div>
            <div class="landing-trust-grid">
                @php
                    $reasonStyles = [
                        ['icon' => 'fa-bullseye', 'box' => 'bg-indigo-600 shadow-indigo-200', 'card' => 'border-indigo-100 bg-indigo-50/30 hover:border-indigo-300'],
                        ['icon' => 'fa-store', 'box' => 'bg-amber-500 shadow-amber-200', 'card' => 'border-amber-100 bg-amber-50/30 hover:border-amber-300'],
                        ['icon' => 'fa-mobile-screen-button', 'box' => 'bg-emerald-500 shadow-emerald-200', 'card' => 'border-emerald-100 bg-emerald-50/30 hover:border-emerald-300'],
                    ];
                @endphp
                @foreach ($reasons as $reasonIndex => $reason)
                    @php $reasonStyle = $reasonStyles[$reasonIndex % count($reasonStyles)]; @endphp
                    <div class="landing-trust-card group {{ $reasonStyle['card'] }} transition-all">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $reasonStyle['box'] }} text-white shadow-lg transition-transform group-hover:scale-110">
                            <i class="fa-solid {{ $reasonStyle['icon'] }}"></i>
                        </div>
                        <div class="mt-5 text-xl font-bold text-slate-900 dark:text-white">{{ $reason['title'] }}</div>
                        <div class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $reason['body'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        @if (($sectionActives['stats'] ?? true) === true)
            <section class="mx-auto max-w-7xl px-4 py-12 sm:py-20">
                <div class="mb-10 text-center">
                    <div class="landing-section-kicker mb-3">{{ $bankSoal['kicker'] ?? 'Bank soal dan materi' }}</div>
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white">{{ $bankSoal['title'] ?? 'Soal dan materi mengikuti aturan resmi TKA' }}</h2>

                    <div class="mx-auto mt-8 max-w-4xl animate-fade-in-up">
                        <div
                            class="relative overflow-hidden rounded-3xl border border-primary/10 bg-white/50 p-6 shadow-sm backdrop-blur-sm transition-all hover:shadow-md md:p-8">
                            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary/5 blur-3xl"></div>
                            <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-primary/5 blur-3xl"></div>

                            <div class="relative flex flex-col items-center gap-6 md:flex-row md:text-left">
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-indigo-600 text-2xl text-white shadow-lg shadow-primary/20">
                                    <i class="fa-solid fa-file-shield"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="mb-3 text-sm font-bold uppercase tracking-[0.2em] text-primary">Aturan resmi TKA</p>
                                    <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                                        {{ $bankSoal['body'] ?? 'Semua soal dan materi disusun berdasarkan Peraturan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan (BSKAP) Kemendikdasmen Nomor 47 Tahun 2025 tentang Kerangka Asesmen TKA untuk jenjang SD/MI dan SMP/MTs, serta Peraturan BSKAP Nomor 45 Tahun 2025 untuk jenjang SMA/MA/SMK.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table-ujion min-w-full">
                        <thead>
                            <tr>
                                <th>Jenjang</th>
                                <th>Materi</th>
                                <th>Soal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stats as $jenjang => $data)
                                <tr>
                                    <td class="font-semibold">{{ $jenjang }}</td>
                                    <td>{{ $data['materi'] ?? 0 }}</td>
                                    <td>
                                        @if ($data['coming_soon'] ?? false)
                                            <span class="badge-warning">Segera hadir</span>
                                        @else
                                            {{ $data['soal'] ?? 0 }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-10 text-center text-muted">Data soal sedang disiapkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p class="mt-6 text-center text-sm text-textSecondary dark:text-slate-400">
                    Ujion adalah situs latihan dan persiapan TKA, bukan penyelenggara ujian TKA resmi.
                </p>
            </section>
        @endif

        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <div class="landing-flow-panel">
                    <div class="landing-section-kicker">{{ $audienceHead['kicker'] ?? 'Untuk siapa Ujion' }}</div>
                    <h2 class="landing-section-title">{{ $audienceHead['title'] ?? 'Cocok untuk yang menyiapkan siswa menghadapi TKA' }}</h2>
                    <p class="landing-section-copy">{{ $audienceHead['body'] ?? 'Ujion fokus pada satu hal: latihan dan ujian TKA. Ujion bukan aplikasi ujian umum dan bukan sistem sekolah yang lengkap, jadi lebih ringan dan mudah dipakai.' }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($audience as $entry)
                        <div class="landing-flow-tile">
                            <div class="landing-flow-tile-title">{{ $entry['title'] }}</div>
                            <div class="landing-flow-tile-copy">{{ $entry['body'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="flow" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
            <div class="mb-8 text-center">
                <div class="landing-section-kicker mb-3">{{ $stepsHead['kicker'] ?? 'Cara kerja' }}</div>
                <h2 class="landing-section-title max-w-2xl mx-auto">{{ $stepsHead['title'] ?? 'Cara kerja untuk guru dan siswa' }}</h2>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="landing-flow-panel">
                    <div class="landing-flow-head">
                        <div>
                            <div class="landing-flow-kicker">Untuk Guru</div>
                            <h3 class="landing-flow-title">Dari menyiapkan soal sampai membaca kesiapan siswa</h3>
                        </div>
                        <span class="badge-success"><i class="fa-solid fa-circle-check"></i>Langkah Guru</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        @foreach ($stepGuru as $stepIndex => $step)
                            <div class="landing-step">
                                <div class="landing-step-number">{{ $stepIndex + 1 }}</div>
                                <div>
                                    <div class="landing-step-title">{{ $step['title'] }}</div>
                                    <p class="landing-step-copy">{{ $step['body'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="landing-flow-panel landing-flow-panel-student">
                    <div class="landing-flow-head">
                        <div>
                            <div class="landing-flow-kicker">Untuk Siswa</div>
                            <h3 class="landing-flow-title">Masuk, kerjakan, dan bantu guru melihat kesiapan belajar</h3>
                        </div>
                        <span class="badge-info"><i class="fa-solid fa-bolt"></i> Langkah Siswa</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        @foreach ($stepSiswa as $stepIndex => $step)
                            <div class="landing-step landing-step-student">
                                <div class="landing-step-number landing-step-number-student">{{ $stepIndex + 1 }}</div>
                                <div>
                                    <div class="landing-step-title">{{ $step['title'] }}</div>
                                    <p class="landing-step-copy">{{ $step['body'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="landing-feature-banner">
                <div>
                    <div class="landing-section-kicker text-white/70">{{ $featuresHead['kicker'] ?? 'Fitur utama' }}</div>
                    <h2 class="landing-section-title max-w-2xl text-white">{{ $featuresHead['title'] ?? 'Yang membuat Ujion cocok untuk persiapan TKA' }}</h2>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($features as $feature)
                        <div class="landing-feature-chip"><i class="fa-solid fa-circle-check"></i> {{ $feature['title'] }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        @if (($testimonials ?? collect())->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-12">
                <div class="section-heading">
                    <div>
                        <div class="landing-section-kicker">Testimoni</div>
                        <h2 class="landing-section-title">Mereka sudah mencoba</h2>
                        <p class="landing-section-copy">Cerita dan pengalaman dari guru serta operator yang memakai Ujion TKA dalam keseharian mereka.</p>
                    </div>
                </div>

                <div class="mt-8" data-testimonial-slider>
                    <div class="overflow-hidden">
                        <div class="flex transition-transform duration-500 ease-out" data-testimonial-track>
                            @foreach ($testimonials as $testimonial)
                                <div class="w-full shrink-0 px-1" data-testimonial-slide>
                                    <div class="landing-faq-card mx-auto flex h-full max-w-3xl flex-col items-center gap-5 p-8 text-center md:p-10">
                                        <div class="flex gap-1 text-lg text-amber-400">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="fa-solid fa-star {{ $i <= $testimonial->rating ? '' : 'opacity-25' }}"></i>
                                            @endfor
                                        </div>
                                        <p class="text-base leading-8 text-textSecondary dark:text-slate-300">
                                            &ldquo;{{ $testimonial->content }}&rdquo;
                                        </p>
                                        <div class="flex items-center gap-3">
                                            @if ($testimonial->photo_path)
                                                <img src="{{ $testimonial->photo_url }}" alt="{{ $testimonial->name }}"
                                                    class="h-14 w-14 rounded-full border-2 border-primary/20 object-cover">
                                            @else
                                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-primary text-lg text-white">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>
                                            @endif
                                            <div class="text-left">
                                                <div class="font-bold text-slate-900 dark:text-white">{{ $testimonial->name }}</div>
                                                @if (!blank($testimonial->role))
                                                    <div class="text-sm text-textSecondary dark:text-slate-400">{{ $testimonial->role }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-6 flex justify-center gap-2" data-testimonial-dots></div>
                </div>
            </section>
        @endif

        @if (($sectionActives['faq'] ?? true) === true)
            <section id="tanya-jawab" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
                <div class="section-heading">
                    <div>
                        <div class="landing-section-kicker">{{ $faqHead['kicker'] ?? 'Tanya jawab' }}</div>
                        <h2 class="landing-section-title">{{ $faqHead['title'] ?? 'Pertanyaan yang sering diajukan' }}</h2>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-2">
                    @foreach (($faqs ?? []) as $faq)
                        <div class="landing-faq-card">
                            <h3 class="landing-faq-title">{{ $faq['question'] ?? '' }}</h3>
                            <p class="landing-faq-copy">{{ $faq['answer'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif


    </main>

    <footer class="border-t border-white/70 bg-white/65 dark:border-slate-800/80 dark:bg-slate-950/60">
        <div class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-3"
                        data-track="landing_brand_footer">
                        <div
                            class="h-12 w-12 overflow-hidden rounded-2xl border border-white/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <img src="{{ $logoUrl ?? asset('assets/img/logo.png') }}" alt="Logo Ujion TKA"
                                class="h-full w-full object-cover">
                        </div>
                        <div class="leading-tight">
                            <div class="text-base font-bold text-slate-900 dark:text-white">Ujion TKA</div>
                            <div class="text-xs uppercase tracking-[0.22em] text-textSecondary dark:text-slate-400">
                                TKA-First Platform</div>
                        </div>
                    </a>

                    <p class="mt-4 max-w-xl text-sm leading-7 text-textSecondary dark:text-slate-300">
                        {{ $footerSection['kicker'] ?? 'Ujion TKA adalah situs latihan dan ujian TKA untuk bimbel dan guru. Skor 0-100 untuk SD/SMP dan 200-800 untuk SMA/SMK, paket soal disusun sendiri, siswa cukup pakai HP.' }}
                    </p>

                    <p class="mt-3 max-w-xl text-sm leading-7 text-textSecondary dark:text-slate-400">
                        {{ $footerSection['body'] ?? 'Ujion adalah situs latihan dan persiapan TKA, bukan penyelenggara ujian TKA resmi.' }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @if (Route::has('register.guru.form'))
                            <a href="{{ route('register.guru.form') }}" class="btn-primary"
                                data-track="landing_register_footer">
                                {{ $footerSection['button_text'] ?? 'Daftar' }}
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn-secondary" data-track="landing_login_footer">
                                {{ $footerSection['title'] ?? 'Masuk' }}
                            </a>
                        @endif
                        <a href="https://whatsapp.com/channel/0029VbCcQxX5fM5fp2n5fM36" target="_blank"
                            class="btn-success" data-track="landing_wa_footer">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                            {{ $footerSection['note'] ?? 'Ikuti Saluran WhatsApp' }}
                        </a>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-muted">Navigasi</div>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="#solusi"
                                class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Keunggulan</a>
                        </li>
                        <li><a href="#flow"
                                class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Cara Kerja</a>
                        </li>
                        @if (($sectionActives['faq'] ?? true) === true)
                            <li><a href="#tanya-jawab"
                                    class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Tanya Jawab</a>
                            </li>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('ujian-online.index'))
                            <li><a href="{{ route('ujian-online.index') }}"
                                    class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Ujian
                                    Online</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-2 border-t border-white/70 pt-6 text-sm text-textSecondary dark:border-slate-800/80 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                <div>© {{ date('Y') }} Ujion TKA oleh Reditech</div>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const endpoint = @json(url('/api/landing-click'));

            function send(payload) {
                try {
                    if (navigator.sendBeacon) {
                        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                        navigator.sendBeacon(endpoint, blob);
                        return;
                    }
                } catch (e) {
                    // ignore
                }

                try {
                    fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        keepalive: true,
                        credentials: 'same-origin'
                    });
                } catch (e) {
                    // ignore
                }
            }

            document.addEventListener('click', function (event) {
                const element = event.target && event.target.closest ? event.target.closest('[data-track]') : null;
                if (!element) return;

                const name = element.getAttribute('data-track');
                if (!name) return;

                const href = element.getAttribute('href');

                send({
                    event: name,
                    href: href || null,
                    path: location.pathname,
                    referrer: document.referrer || null,
                });
            }, { capture: true });

            // Track landing access (page view) once per day per browser.
            try {
                const today = new Date().toISOString().slice(0, 10);
                const key = 'ujion_landing_view_' + today;
                if (!localStorage.getItem(key)) {
                    localStorage.setItem(key, '1');
                    send({
                        event: 'landing_view',
                        href: null,
                        path: location.pathname,
                        referrer: document.referrer || null,
                    });
                }
            } catch (e) {
                // ignore
            }
        })();
    </script>
</body>

</html>