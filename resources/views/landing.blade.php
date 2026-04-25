<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $seoTitle = config('app.name', 'Ujion TKA');
        if (!empty($hero['title'] ?? null)) {
            $seoTitle = $seoTitle . ' — ' . $hero['title'];
        }

        $seoDescription = $hero['kicker']
            ?? 'Platform pendamping guru/operator untuk memantau progres, menganalisis hasil, dan menyiapkan siswa menghadapi TKA.';

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <script type="application/ld+json">
        {!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => config('app.name', 'Ujion TKA'),
    'url' => $canonicalUrl,
    'logo' => $ogImageAbs,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name', 'Ujion TKA'),
    'url' => $canonicalUrl,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @if (!empty($faqJsonLd))
        <script type="application/ld+json">
                        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqJsonLd,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
                    </script>
    @endif
</head>

<body class="landing-body min-h-screen text-textPrimary dark:text-slate-100">
    @php
        $navItems = [
            ['label' => 'Solusi', 'href' => '#solusi'],
            ['label' => 'Flow', 'href' => '#flow'],
        ];

        if (($sectionActives['faq'] ?? true) === true) {
            $navItems[] = ['label' => 'FAQ', 'href' => '#faq'];
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
                    <div class="text-xs uppercase tracking-[0.22em] text-textSecondary dark:text-slate-400">Rekan Guru
                    </div>
                </div>
            </a>

            <nav class="hidden items-center gap-6 lg:flex">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="landing-nav-link">{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
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
                    <a href="{{ route('login') }}" class="btn-secondary hidden sm:inline-flex"
                        data-track="landing_login_header">
                        Masuk
                    </a>
                @endif
                @if (Route::has('register.guru.form'))
                    <a href="{{ route('register.guru.form') }}" class="btn-primary" data-track="landing_register_header">
                        Daftar Gratis
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="pb-16">
        <div class="mx-auto max-w-7xl px-4 pt-6">
            @include('components.ui.flash')
        </div>

        @if (($sectionActives['hero'] ?? true) === true)
            <section class="relative overflow-hidden">
                <div class="landing-orb left-[-4rem] top-16"></div>
                <div class="landing-orb right-[-5rem] top-24"></div>

                <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:py-16">
                    <div class="animate-fade-in-up">
                        <div class="landing-kicker">
                            <i class="fa-solid fa-sparkles text-warning"></i>
                            {{ $hero['kicker'] ?? 'Website pendamping guru untuk memantau kesiapan siswa menuju Tes Kemampuan Akademik (TKA).' }}
                        </div>

                        <h1 class="landing-hero-title">
                            {{ $hero['title'] ?? 'Bantu guru memantau, menganalisis, dan menyiapkan siswa agar lebih siap menghadapi TKA.' }}
                        </h1>

                        <p class="landing-hero-copy">
                            {{ $hero['body'] ?? 'Ujion TKA dirancang untuk guru/operator yang ingin melihat perkembangan akademik siswa dengan lebih jelas. Mulai dari latihan, paket soal, sesi ujian, sampai hasil akhir, semua disusun agar guru lebih mudah membaca kesiapan siswa, menemukan kelemahan belajar, dan mengambil langkah pembinaan sebelum TKA berlangsung.' }}
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

                        <div class="mt-8 grid gap-3 sm:grid-cols-3">
                            <div class="landing-mini-stat">
                                <div class="landing-mini-value">Pantau progres</div>
                                <div class="landing-mini-label">Guru bisa melihat perkembangan siswa dari latihan sampai
                                    hasil ujian.</div>
                            </div>
                            <div class="landing-mini-stat">
                                <div class="landing-mini-value">Baca kelemahan</div>
                                <div class="landing-mini-label">Hasil ujian membantu guru menemukan materi yang masih perlu
                                    diperkuat.</div>
                            </div>
                            <div class="landing-mini-stat">
                                <div class="landing-mini-value">Siapkan TKA</div>
                                <div class="landing-mini-label">Sekolah dapat menyiapkan siswa dengan alur yang lebih
                                    tertata dan terukur.</div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3 text-sm text-textSecondary dark:text-slate-400">
                            <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Paket
                                soal untuk persiapan TKA</span>
                            <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Hasil
                                ujian lebih mudah dianalisis</span>
                            <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Flow
                                siswa sederhana dan fokus</span>
                            <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Token
                                ujian tanpa akun siswa</span>
                        </div>
                    </div>

                    <div class="animate-slide-in-right">
                        <div class="landing-showcase-shell">


                            <div class="mt-5 grid gap-4">
                                @if (($heroMockups ?? collect())->isNotEmpty())
                                    @php
                                        $featuredMockup = $heroMockups->first();
                                        $secondaryMockups = $heroMockups->skip(1)->take(2);
                                    @endphp

                                    <div class="landing-mockup-card overflow-hidden">
                                        @if ($featuredMockup['badge'])
                                            <div class="landing-mockup-badge">{{ $featuredMockup['badge'] }}</div>
                                        @endif
                                        <div class="landing-mockup-title">{{ $featuredMockup['title'] }}</div>
                                        @if ($featuredMockup['description'])
                                            <div class="landing-mockup-copy">{{ $featuredMockup['description'] }}</div>
                                        @endif
                                        <img src="{{ $featuredMockup['image_url'] }}" alt="{{ $featuredMockup['title'] }}"
                                            class="mt-4 w-full rounded-2xl border border-slate-200 object-cover shadow-sm dark:border-slate-700">
                                    </div>

                                    @if ($secondaryMockups->isNotEmpty())
                                        <div class="grid gap-4">
                                            @foreach ($secondaryMockups as $mockup)
                                                <div class="landing-mockup-card compact overflow-hidden">
                                                    @if ($mockup['badge'])
                                                        <div class="landing-mockup-badge">{{ $mockup['badge'] }}</div>
                                                    @endif
                                                    <div class="landing-mockup-title">{{ $mockup['title'] }}</div>
                                                    <img src="{{ $mockup['image_url'] }}" alt="{{ $mockup['title'] }}"
                                                        class="mt-3 h-36 w-full rounded-xl border border-slate-200 object-cover dark:border-slate-700">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div class="landing-mockup-card">
                                        <div class="landing-mockup-badge">Mockup 1</div>
                                        <div class="landing-mockup-title">Dashboard guru / analytics</div>
                                        <div class="landing-mockup-copy">Disarankan PNG landscape rasio 16:10 atau 4:3.</div>
                                        <div class="landing-mockup-dropzone">
                                            <i class="fa-regular fa-image text-2xl"></i>
                                            <div class="mt-3 font-semibold">Tempel PNG dashboard guru di sini</div>
                                            <div class="mt-1 text-xs text-textSecondary dark:text-slate-400">Contoh referensi:
                                                online exam dashboard, analytics dashboard, teacher workspace UI.</div>
                                        </div>
                                    </div>

                                    <div class="grid gap-4">
                                        <div class="landing-mockup-card compact">
                                            <div class="landing-mockup-badge">Mockup 2</div>
                                            <div class="landing-mockup-title">Tampilan ujian siswa</div>
                                            <div class="landing-mockup-dropzone compact">
                                                <i class="fa-solid fa-laptop-code text-xl"></i>
                                                <div class="mt-2 text-sm font-semibold">PNG screen siswa</div>
                                            </div>
                                        </div>
                                        <div class="landing-insight-card">
                                            <div class="text-sm font-semibold text-slate-900 dark:text-white">Arah mockup yang
                                                saya cari</div>
                                            <ul class="mt-3 space-y-2 text-sm text-textSecondary dark:text-slate-300">
                                                <li>Dashboard ujian online bernuansa SaaS edukasi</li>
                                                <li>Screen pengerjaan ujian yang fokus dan bersih</li>
                                                <li>Komposisi putih, navy, dan aksen biru atau orange</li>
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="mx-auto max-w-7xl px-4 py-8">
            <div class="landing-trust-grid">
                <div
                    class="landing-trust-card group border-indigo-100 bg-indigo-50/30 transition-all hover:border-indigo-300">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                    <div class="mt-5 text-xl font-bold text-slate-900">Guru bisa memantau</div>
                    <div class="mt-3 text-sm leading-7 text-slate-600">Setiap sesi latihan dan ujian membantu guru
                        membaca
                        sejauh mana kesiapan siswa menuju TKA.</div>
                </div>

                <div
                    class="landing-trust-card group border-amber-100 bg-amber-50/30 transition-all hover:border-amber-300">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-200 transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="mt-5 text-xl font-bold text-slate-900">Guru bisa menganalisis</div>
                    <div class="mt-3 text-sm leading-7 text-slate-600">Hasil yang tersusun rapi membantu guru melihat
                        area
                        lemah siswa dan menentukan tindak lanjut belajar.</div>
                </div>

                <div
                    class="landing-trust-card group border-emerald-100 bg-emerald-50/30 transition-all hover:border-emerald-300">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-200 transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div class="mt-5 text-xl font-bold text-slate-900">Siswa bisa dipersiapkan</div>
                    <div class="mt-3 text-sm leading-7 text-slate-600">Sekolah tidak hanya menjalankan ujian, tetapi
                        juga
                        membangun kesiapan siswa secara bertahap sebelum TKA.</div>
                </div>
            </div>
        </section>

        @if (($sectionActives['stats'] ?? true) === true)
            <section class="mx-auto max-w-7xl px-4 py-12 sm:py-20">
                <div class="mb-10 text-center">
                    <div class="landing-section-kicker mb-3">Data Terkini</div>
                    <h2 class="text-3xl font-bold text-slate-900">Bank Soal & Materi</h2>

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
                                    <p class="mb-3 text-sm font-bold uppercase tracking-[0.2em] text-primary">Standar
                                        Kurikulum
                                        Nasional</p>
                                    <p class="text-base leading-relaxed text-slate-600">
                                        Seluruh soal dan materi telah disesuaikan dengan kerangka asesmen berdasarkan:
                                        <span class="block mt-2 font-bold text-slate-900">
                                            Peraturan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerian
                                            Pendidikan Dasar dan Menengah Republik Indonesia Nomor 047/H/AN/2025
                                        </span>
                                        <span class="mt-1 block text-sm italic text-slate-500">
                                            Tentang Kerangka Asesmen Tes Kemampuan Akademik Jenjang SD/MI/Sederajat,
                                            SMP/MTs/Sederajat dan SMA/MA/SMK/Sederajat
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-16">
                    @foreach ($stats as $jenjang => $mapels)
                        <div class="animate-fade-in">
                            <h3 class="mb-8 flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-900 px-4 py-1 text-xs font-bold uppercase tracking-widest text-white shadow-lg">{{ $jenjang }}</span>
                                <span class="text-xl font-bold text-slate-800">Materi & Soal {{ $jenjang }}</span>
                            </h3>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($mapels as $mapel => $data)
                                    <div class="landing-trust-card group overflow-hidden transition-all hover:border-primary/30">
                                        <div class="mb-5 flex items-center justify-between">
                                            <h4 class="font-bold leading-tight text-slate-800">{{ $mapel }}</h4>
                                            <div
                                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-50 transition-colors group-hover:bg-primary/10">
                                                <i class="fa-solid fa-book-bookmark text-slate-400 group-hover:text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div
                                                class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 text-center transition-colors group-hover:bg-white">
                                                <div class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Materi
                                                </div>
                                                <div class="mt-1 text-2xl font-bold text-primary">
                                                    {{ $data['materials'] ?? 0 }}
                                                </div>
                                            </div>
                                            <div
                                                class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 text-center transition-colors group-hover:bg-white">
                                                <div class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Soal
                                                </div>
                                                <div class="mt-1 text-2xl font-bold text-primary">
                                                    {{ $data['questions'] ?? 0 }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section id="solusi" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
            <div class="section-heading">
                <div>
                    <div class="landing-section-kicker">Kenapa Ujion TKA ?</div>
                    <h2 class="landing-section-title">Website ini adalah membantu guru membaca kesiapan
                        siswa.</h2>
                    <p class="landing-section-copy">Jadi nilai jual utamanya bukan hanya ujian online, tetapi alat kerja
                        guru untuk memantau progres, menganalisis hasil, dan menyiapkan strategi belajar sebelum TKA.
                    </p>
                </div>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-3">
                <article class="landing-solution-card">
                    <div class="landing-solution-icon bg-slate-900">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <h3 class="landing-solution-title">Guru lebih mudah memantau kesiapan</h3>
                    <p class="landing-solution-copy">Guru dapat melihat bagaimana siswa berkembang dari sesi ke sesi,
                        bukan hanya melihat nilai akhir sekali saja.</p>
                </article>
                <article class="landing-solution-card">
                    <div class="landing-solution-icon bg-gradient-primary">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <h3 class="landing-solution-title">Guru lebih cepat menemukan kelemahan belajar</h3>
                    <p class="landing-solution-copy">Dari hasil ujian yang rapi, guru bisa melihat materi mana yang
                        masih lemah dan perlu dibina ulang sebelum TKA.</p>
                </article>
                <article class="landing-solution-card">
                    <div class="landing-solution-icon bg-emerald-500">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>
                    <h3 class="landing-solution-title">Sekolah bisa menyiapkan siswa dengan lebih terarah</h3>
                    <p class="landing-solution-copy">Platform ini membantu sekolah mengubah latihan dan ujian menjadi
                        bahan evaluasi nyata untuk persiapan TKA.</p>
                </article>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <div class="landing-flow-panel">
                    <div class="landing-section-kicker">Kenapa memilih Ujion</div>
                    <h2 class="landing-section-title">Ini adalah website yang dibutuhkan guru saat ingin menyiapkan
                        siswa menuju TKA.</h2>
                    <p class="landing-section-copy">Pengunjung harus langsung merasa bahwa platform ini membantu
                        pekerjaan nyata di sekolah, bukan sekadar menampilkan ujian digital biasa.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="landing-flow-tile">
                        <div class="landing-flow-tile-title">Kalau saya guru</div>
                        <div class="landing-flow-tile-copy">Saya butuh sistem yang membantu saya melihat kesiapan siswa,
                            bukan hanya memberi soal lalu selesai.</div>
                    </div>
                    <div class="landing-flow-tile">
                        <div class="landing-flow-tile-title">Kalau saya sekolah</div>
                        <div class="landing-flow-tile-copy">Saya butuh platform yang membantu guru membina siswa secara
                            lebih terarah sebelum menghadapi TKA.</div>
                    </div>
                    <div class="landing-flow-tile">
                        <div class="landing-flow-tile-title">Kalau saya siswa</div>
                        <div class="landing-flow-tile-copy">Saya butuh tampilan yang jelas, mudah diikuti, dan membantu
                            saya fokus saat latihan atau ujian.</div>
                    </div>
                    <div class="landing-flow-tile">
                        <div class="landing-flow-tile-title">Kalau saya operator</div>
                        <div class="landing-flow-tile-copy">Saya butuh data yang rapi agar hasil ujian bisa dibaca guru
                            dan sekolah untuk tindak lanjut akademik.</div>
                    </div>
                </div>
            </div>
        </section>

        @if (($sectionActives['pricing'] ?? true) === true && !empty($tarifJenjangs))
            <section class="mx-auto max-w-7xl px-4 py-12">
                <div class="section-heading">
                    <div>
                        <div class="landing-section-kicker">Biaya Aktivasi</div>
                        <h2 class="landing-section-title">Pilih jenjang, lalu lanjutkan ke flow daftar guru dan pembayaran
                            QRIS sesuai nominalnya.</h2>
                        <p class="landing-section-copy">Nominal aktivasi sekarang mengikuti jenjang SD, SMP, atau SMA agar
                            alurnya tetap satu dan mudah dipahami admin maupun guru.</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-3">
                    @foreach ($tarifJenjangs as $tarifJenjang)
                        <article class="landing-solution-card">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="landing-solution-title">{{ $tarifJenjang['name'] }}</h3>
                                <span class="badge-info">{{ $tarifJenjang['jenjang'] ?: 'Jenjang' }}</span>
                            </div>
                            @if (!empty($tarifJenjang['description']))
                                <p class="landing-solution-copy">{{ $tarifJenjang['description'] }}</p>
                            @elseif (!empty($tarifJenjang['subtitle']))
                                <p class="landing-solution-copy">{{ $tarifJenjang['subtitle'] }}</p>
                            @endif

                            <div class="mt-5">
                                <div class="text-3xl font-bold text-slate-900">
                                    Rp{{ number_format((int) $tarifJenjang['price'], 0, ',', '.') }}</div>
                                <div class="mt-1 text-sm text-textSecondary dark:text-slate-400">Aktivasi
                                    {{ $tarifJenjang['jenjang'] ?: 'guru' }}
                                </div>
                            </div>

                            <div class="mt-6">
                                <a href="{{ route('register.guru.form', ['jenjang' => $tarifJenjang['jenjang']]) }}"
                                    class="btn-primary w-full justify-center" data-track="landing_register_pricing">
                                    Daftar {{ $tarifJenjang['jenjang'] ?: 'Sekarang' }}
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section id="flow" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">

            <div class="mt-8 grid gap-6 xl:grid-cols-2">
                <div class="landing-flow-panel">
                    <div class="landing-flow-head">
                        <div>
                            <div class="landing-flow-kicker">Untuk Guru</div>
                            <h3 class="landing-flow-title">Dari menyiapkan soal sampai membaca kesiapan siswa</h3>
                        </div>
                        <span class="badge-success"><i class="fa-solid fa-circle-check"></i>Alur Guru</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="landing-step">
                            <div class="landing-step-number">1</div>
                            <div>
                                <div class="landing-step-title">Daftar dan tunggu aktivasi</div>
                                <p class="landing-step-copy">Guru / operator melakukan pendaftaran, lalu akun direview
                                    sampai aktif.</p>
                            </div>
                        </div>
                        <div class="landing-step">
                            <div class="landing-step-number">2</div>
                            <div>
                                <div class="landing-step-title">Susun mapel sesuai jenjang</div>
                                <p class="landing-step-copy">Guru atau operator menyusun paket soal dan mapel yang
                                    dipakai untuk membina kesiapan siswa sesuai target belajar.</p>
                            </div>
                        </div>
                        <div class="landing-step">
                            <div class="landing-step-number">3</div>
                            <div>
                                <div class="landing-step-title">Jalankan latihan dan ujian secara teratur</div>
                                <p class="landing-step-copy">Bank soal, teks bacaan, dan sesi ujian membantu guru
                                    membangun ritme latihan yang lebih tertata menjelang TKA.</p>
                            </div>
                        </div>
                        <div class="landing-step">
                            <div class="landing-step-number">4</div>
                            <div>
                                <div class="landing-step-title">Baca hasil dan ambil tindakan pembinaan</div>
                                <p class="landing-step-copy">Setelah sesi selesai, guru dapat membaca hasil siswa dengan
                                    lebih mudah lalu menentukan materi yang perlu diperkuat.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="landing-flow-panel landing-flow-panel-student">
                    <div class="landing-flow-head">
                        <div>
                            <div class="landing-flow-kicker">Untuk Siswa</div>
                            <h3 class="landing-flow-title">Masuk, kerjakan, dan bantu guru melihat kesiapan belajar</h3>
                        </div>
                        <span class="badge-info"><i class="fa-solid fa-bolt"></i> Paling Butuh</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="landing-step landing-step-student">
                            <div class="landing-step-number landing-step-number-student">1</div>
                            <div>
                                <div class="landing-step-title">Masuk dengan token ujian</div>
                                <p class="landing-step-copy">Siswa login memakai token exam yang aktif tanpa perlu alur
                                    masuk yang membingungkan.</p>
                            </div>
                        </div>
                        <div class="landing-step landing-step-student">
                            <div class="landing-step-number landing-step-number-student">2</div>
                            <div>
                                <div class="landing-step-title">Isi identitas dengan cepat</div>
                                <p class="landing-step-copy">Nama diisi di awal, lalu sistem membentuk sesi ujian agar
                                    siswa bisa langsung lanjut ke pengerjaan.</p>
                            </div>
                        </div>
                        <div class="landing-step landing-step-student">
                            <div class="landing-step-number landing-step-number-student">3</div>
                            <div>
                                <div class="landing-step-title">Kerjakan soal dengan fokus</div>
                                <p class="landing-step-copy">Siswa mengerjakan soal dengan timer, autosave, dan navigasi
                                    yang membantu mereka tetap fokus selama sesi berjalan.</p>
                            </div>
                        </div>
                        <div class="landing-step landing-step-student">
                            <div class="landing-step-number landing-step-number-student">4</div>
                            <div>
                                <div class="landing-step-title">Hasil membantu guru membaca kesiapan</div>
                                <p class="landing-step-copy">Setelah sesi selesai, hasil tersusun rapi sehingga guru
                                    lebih mudah melihat progres dan kebutuhan belajar siswa.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12">
            <div class="landing-feature-banner">
                <div>
                    <div class="landing-section-kicker text-white/70">Keunggulan inti</div>
                    <h2 class="landing-section-title max-w-2xl text-white">Fitur yang membantu guru melihat progres dan
                        kesiapan siswa.</h2>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="landing-feature-chip"><i class="fa-solid fa-layer-group"></i> Paket soal persiapan TKA
                    </div>
                    <div class="landing-feature-chip"><i class="fa-solid fa-key"></i> Token ujian siswa</div>
                    <div class="landing-feature-chip"><i class="fa-solid fa-floppy-disk"></i> Autosave jawaban</div>
                    <div class="landing-feature-chip"><i class="fa-solid fa-chart-line"></i> Hasil untuk analisis guru
                    </div>
                </div>
            </div>
        </section>

        @if (($sectionActives['faq'] ?? true) === true)
            <section id="faq" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
                <div class="section-heading">
                    <div>
                        <div class="landing-section-kicker">FAQ</div>
                        <h2 class="landing-section-title">Pertanyaan yang biasanya muncul.</h2>
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
                                Rekan Guru</div>
                        </div>
                    </a>

                    <p class="mt-4 max-w-xl text-sm leading-7 text-textSecondary dark:text-slate-300">
                        Platform pendamping guru/operator untuk memantau progres, menganalisis hasil, dan menyiapkan
                        siswa lebih siap menghadapi TKA.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @if (Route::has('register.guru.form'))
                            <a href="{{ route('register.guru.form') }}" class="btn-primary"
                                data-track="landing_register_footer">
                                Daftar Gratis
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn-secondary" data-track="landing_login_footer">
                                Masuk
                            </a>
                        @endif
                        <a href="https://whatsapp.com/channel/0029VbCcQxX5fM5fp2n5fM36" target="_blank"
                            class="btn-success" data-track="landing_wa_footer">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                            Gabung Saluran
                        </a>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-[0.22em] text-muted">Navigasi</div>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li><a href="#solusi"
                                    class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Solusi</a>
                            </li>
                            <li><a href="#flow"
                                    class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Flow</a>
                            </li>
                            @if (($sectionActives['faq'] ?? true) === true)
                                <li><a href="#faq"
                                        class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">FAQ</a>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-[0.22em] text-muted">Akun</div>
                        <ul class="mt-4 space-y-2 text-sm">
                            @if (Route::has('login'))
                                <li><a href="{{ route('login') }}"
                                        class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Masuk</a>
                                </li>
                            @endif
                            @if (Route::has('register.guru.form'))
                                <li><a href="{{ route('register.guru.form') }}"
                                        class="text-textSecondary hover:text-primary dark:text-slate-300 dark:hover:text-white">Daftar
                                        guru/operator</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-2 border-t border-white/70 pt-6 text-sm text-textSecondary dark:border-slate-800/80 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                <div>© {{ date('Y') }} Ujion TKA by Reditech</div>
                <div>Simulai mengelola pembelajaran siswa</div>
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