<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Ujion TKA') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-body min-h-screen text-textPrimary dark:text-slate-100">
@php
    $navItems = [
        ['label' => 'Solusi', 'href' => '#solusi'],
        ['label' => 'Flow', 'href' => '#flow'],
        ['label' => 'Harga', 'href' => '#harga'],
        ['label' => 'FAQ', 'href' => '#faq'],
    ];
@endphp

<header class="landing-header">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4">
        <a href="{{ route('landing') }}" class="flex items-center gap-3">
            <div class="landing-brand-mark">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="leading-tight">
                <div class="font-bold text-slate-900 dark:text-white">Ujion TKA</div>
                <div class="text-xs uppercase tracking-[0.22em] text-textSecondary dark:text-slate-400">Exam Platform</div>
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
                class="btn-secondary px-3"
                data-theme-toggle
                aria-label="Toggle dark mode"
                title="Dark mode"
            >
                <i class="fa-solid fa-moon"></i>
            </button>
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="btn-secondary hidden sm:inline-flex">
                    Login Guru
                </a>
            @endif
            @if (Route::has('register.guru.form'))
                <a href="{{ route('register.guru.form') }}" class="btn-primary">
                    Mulai Trial
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

    <section class="relative overflow-hidden">
        <div class="landing-orb left-[-4rem] top-16"></div>
        <div class="landing-orb right-[-5rem] top-24"></div>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:py-16">
            <div class="animate-fade-in-up">
                <div class="landing-kicker">
                    <i class="fa-solid fa-sparkles text-warning"></i>
                    Website pendamping guru untuk memantau kesiapan siswa menuju TKA
                </div>

                <h1 class="landing-hero-title">
                    Bantu guru memantau, menganalisis, dan menyiapkan siswa agar lebih siap menghadapi TKA.
                </h1>

                <p class="landing-hero-copy">
                    Ujion TKA dirancang untuk guru yang ingin melihat perkembangan akademik siswa dengan lebih jelas.
                    Mulai dari latihan, paket soal, sesi ujian, sampai hasil akhir, semua disusun agar guru lebih mudah membaca kesiapan siswa,
                    menemukan kelemahan belajar, dan mengambil langkah pembinaan sebelum TKA berlangsung.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @if (Route::has('register.guru.form'))
                        <a href="{{ route('register.guru.form') }}" class="btn-primary">
                            Coba Sebagai Guru
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endif
                    <a href="#harga" class="btn-secondary">
                        Lihat Paket Harga
                        <i class="fa-solid fa-layer-group"></i>
                    </a>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="landing-mini-stat">
                        <div class="landing-mini-value">Pantau progres</div>
                        <div class="landing-mini-label">Guru bisa melihat perkembangan siswa dari latihan sampai hasil ujian.</div>
                    </div>
                    <div class="landing-mini-stat">
                        <div class="landing-mini-value">Baca kelemahan</div>
                        <div class="landing-mini-label">Hasil ujian membantu guru menemukan materi yang masih perlu diperkuat.</div>
                    </div>
                    <div class="landing-mini-stat">
                        <div class="landing-mini-value">Siapkan TKA</div>
                        <div class="landing-mini-label">Sekolah dapat menyiapkan siswa dengan alur yang lebih tertata dan terukur.</div>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap gap-3 text-sm text-textSecondary dark:text-slate-400">
                    <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Paket soal untuk persiapan TKA</span>
                    <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Hasil ujian lebih mudah dianalisis</span>
                    <span class="landing-proof-chip"><i class="fa-solid fa-circle-check text-success"></i> Flow siswa sederhana dan fokus</span>
                </div>
            </div>

            <div class="animate-slide-in-right">
                <div class="landing-showcase-shell">
                    <div class="landing-showcase-header">
                        <div>
                            <div class="landing-showcase-title">Ruang mockup produk</div>
                            <div class="landing-showcase-copy">Placeholder ini sengaja disiapkan supaya nanti kamu bisa ganti dengan PNG final.</div>
                        </div>
                        <span class="badge-info"><i class="fa-solid fa-image"></i> PNG ready</span>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <div class="landing-mockup-card">
                            <div class="landing-mockup-badge">Mockup 1</div>
                            <div class="landing-mockup-title">Dashboard guru / analytics</div>
                            <div class="landing-mockup-copy">Disarankan PNG landscape rasio 16:10 atau 4:3.</div>
                            <div class="landing-mockup-dropzone">
                                <i class="fa-regular fa-image text-2xl"></i>
                                <div class="mt-3 font-semibold">Tempel PNG dashboard guru di sini</div>
                                <div class="mt-1 text-xs text-textSecondary dark:text-slate-400">Contoh referensi: online exam dashboard, analytics dashboard, teacher workspace UI.</div>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="landing-mockup-card compact">
                                <div class="landing-mockup-badge">Mockup 2</div>
                                <div class="landing-mockup-title">Tampilan ujian siswa</div>
                                <div class="landing-mockup-dropzone compact">
                                    <i class="fa-solid fa-laptop-code text-xl"></i>
                                    <div class="mt-2 text-sm font-semibold">PNG screen siswa</div>
                                </div>
                            </div>
                            <div class="landing-insight-card">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white">Arah mockup yang saya cari</div>
                                <ul class="mt-3 space-y-2 text-sm text-textSecondary dark:text-slate-300">
                                    <li>Dashboard ujian online bernuansa SaaS edukasi</li>
                                    <li>Screen pengerjaan ujian yang fokus dan bersih</li>
                                    <li>Komposisi putih, navy, dan aksen biru atau orange</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8">
        <div class="landing-trust-grid">
            <div class="landing-trust-card">
                <div class="landing-trust-value">Guru bisa memantau</div>
                <div class="landing-trust-label">Setiap sesi latihan dan ujian membantu guru membaca sejauh mana kesiapan siswa menuju TKA.</div>
            </div>
            <div class="landing-trust-card">
                <div class="landing-trust-value">Guru bisa menganalisis</div>
                <div class="landing-trust-label">Hasil yang tersusun rapi membantu guru melihat area lemah siswa dan menentukan tindak lanjut belajar.</div>
            </div>
            <div class="landing-trust-card">
                <div class="landing-trust-value">Siswa bisa dipersiapkan</div>
                <div class="landing-trust-label">Sekolah tidak hanya menjalankan ujian, tetapi juga membangun kesiapan siswa secara bertahap sebelum TKA.</div>
            </div>
        </div>
    </section>

    <section id="solusi" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
        <div class="section-heading">
            <div>
                <div class="landing-section-kicker">Kenapa ini penting</div>
                <h2 class="landing-section-title">Fokus utama website ini adalah membantu guru membaca kesiapan siswa.</h2>
                <p class="landing-section-copy">Jadi nilai jual utamanya bukan hanya ujian online, tetapi alat kerja guru untuk memantau progres, menganalisis hasil, dan menyiapkan strategi belajar sebelum TKA.</p>
            </div>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-3">
            <article class="landing-solution-card">
                <div class="landing-solution-icon bg-slate-900">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <h3 class="landing-solution-title">Guru lebih mudah memantau kesiapan</h3>
                <p class="landing-solution-copy">Guru dapat melihat bagaimana siswa berkembang dari sesi ke sesi, bukan hanya melihat nilai akhir sekali saja.</p>
            </article>
            <article class="landing-solution-card">
                <div class="landing-solution-icon bg-gradient-primary">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <h3 class="landing-solution-title">Guru lebih cepat menemukan kelemahan belajar</h3>
                <p class="landing-solution-copy">Dari hasil ujian yang rapi, guru bisa melihat materi mana yang masih lemah dan perlu dibina ulang sebelum TKA.</p>
            </article>
            <article class="landing-solution-card">
                <div class="landing-solution-icon bg-emerald-500">
                    <i class="fa-solid fa-chart-column"></i>
                </div>
                <h3 class="landing-solution-title">Sekolah bisa menyiapkan siswa dengan lebih terarah</h3>
                <p class="landing-solution-copy">Platform ini membantu sekolah mengubah latihan dan ujian menjadi bahan evaluasi nyata untuk persiapan TKA.</p>
            </article>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <div class="landing-flow-panel">
                <div class="landing-section-kicker">Kenapa memilih Ujion</div>
                <h2 class="landing-section-title">Ini adalah website yang dibutuhkan guru saat ingin menyiapkan siswa menuju TKA.</h2>
                <p class="landing-section-copy">Pengunjung harus langsung merasa bahwa platform ini membantu pekerjaan nyata di sekolah, bukan sekadar menampilkan ujian digital biasa.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="landing-flow-tile">
                    <div class="landing-flow-tile-title">Kalau saya guru</div>
                    <div class="landing-flow-tile-copy">Saya butuh sistem yang membantu saya melihat kesiapan siswa, bukan hanya memberi soal lalu selesai.</div>
                </div>
                <div class="landing-flow-tile">
                    <div class="landing-flow-tile-title">Kalau saya sekolah</div>
                    <div class="landing-flow-tile-copy">Saya butuh platform yang membantu guru membina siswa secara lebih terarah sebelum menghadapi TKA.</div>
                </div>
                <div class="landing-flow-tile">
                    <div class="landing-flow-tile-title">Kalau saya siswa</div>
                    <div class="landing-flow-tile-copy">Saya butuh tampilan yang jelas, mudah diikuti, dan membantu saya fokus saat latihan atau ujian.</div>
                </div>
                <div class="landing-flow-tile">
                    <div class="landing-flow-tile-title">Kalau saya operator</div>
                    <div class="landing-flow-tile-copy">Saya butuh data yang rapi agar hasil ujian bisa dibaca guru dan sekolah untuk tindak lanjut akademik.</div>
                </div>
            </div>
        </div>
    </section>

    <section id="flow" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
        <div class="section-heading">
            <div>
                <div class="landing-section-kicker">Flow produk</div>
                <h2 class="landing-section-title">Alur guru dan siswa ditampilkan lebih jelas agar calon user cepat paham.</h2>
                <p class="landing-section-copy">Section ini dibuat langsung berdasarkan flow yang memang hidup di codebase saat ini.</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <div class="landing-flow-panel">
                <div class="landing-flow-head">
                    <div>
                        <div class="landing-flow-kicker">Untuk Guru</div>
                        <h3 class="landing-flow-title">Dari menyiapkan soal sampai membaca kesiapan siswa</h3>
                    </div>
                    <span class="badge-success"><i class="fa-solid fa-circle-check"></i> Flow utama</span>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="landing-step">
                        <div class="landing-step-number">1</div>
                        <div>
                            <div class="landing-step-title">Daftar dan tunggu aktivasi</div>
                            <p class="landing-step-copy">Guru mendaftar, upload bukti pembayaran, lalu akun direview superadmin sampai aktif.</p>
                        </div>
                    </div>
                    <div class="landing-step">
                        <div class="landing-step-number">2</div>
                        <div>
                            <div class="landing-step-title">Susun mapel sesuai jenjang</div>
                            <p class="landing-step-copy">Guru atau operator menyusun paket soal dan mapel yang dipakai untuk membina kesiapan siswa sesuai target belajar.</p>
                        </div>
                    </div>
                    <div class="landing-step">
                        <div class="landing-step-number">3</div>
                        <div>
                            <div class="landing-step-title">Jalankan latihan dan ujian secara teratur</div>
                            <p class="landing-step-copy">Bank soal, teks bacaan, dan sesi ujian membantu guru membangun ritme latihan yang lebih tertata menjelang TKA.</p>
                        </div>
                    </div>
                    <div class="landing-step">
                        <div class="landing-step-number">4</div>
                        <div>
                            <div class="landing-step-title">Baca hasil dan ambil tindakan pembinaan</div>
                            <p class="landing-step-copy">Setelah sesi selesai, guru dapat membaca hasil siswa dengan lebih mudah lalu menentukan materi yang perlu diperkuat.</p>
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
                    <span class="badge-info"><i class="fa-solid fa-bolt"></i> Paling utuh</span>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="landing-step landing-step-student">
                        <div class="landing-step-number landing-step-number-student">1</div>
                        <div>
                            <div class="landing-step-title">Masuk dengan token ujian</div>
                            <p class="landing-step-copy">Siswa login memakai token exam yang aktif tanpa perlu alur masuk yang membingungkan.</p>
                        </div>
                    </div>
                    <div class="landing-step landing-step-student">
                        <div class="landing-step-number landing-step-number-student">2</div>
                        <div>
                            <div class="landing-step-title">Isi identitas dengan cepat</div>
                            <p class="landing-step-copy">Nama diisi di awal, lalu sistem membentuk sesi ujian agar siswa bisa langsung lanjut ke pengerjaan.</p>
                        </div>
                    </div>
                    <div class="landing-step landing-step-student">
                        <div class="landing-step-number landing-step-number-student">3</div>
                        <div>
                            <div class="landing-step-title">Kerjakan soal dengan fokus</div>
                            <p class="landing-step-copy">Siswa mengerjakan soal dengan timer, autosave, dan navigasi yang membantu mereka tetap fokus selama sesi berjalan.</p>
                        </div>
                    </div>
                    <div class="landing-step landing-step-student">
                        <div class="landing-step-number landing-step-number-student">4</div>
                        <div>
                            <div class="landing-step-title">Hasil membantu guru membaca kesiapan</div>
                            <p class="landing-step-copy">Setelah sesi selesai, hasil tersusun rapi sehingga guru lebih mudah melihat progres dan kebutuhan belajar siswa.</p>
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
                <h2 class="landing-section-title max-w-2xl text-white">Fitur yang membantu guru melihat progres dan kesiapan siswa.</h2>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="landing-feature-chip"><i class="fa-solid fa-layer-group"></i> Paket soal persiapan TKA</div>
                <div class="landing-feature-chip"><i class="fa-solid fa-key"></i> Token ujian siswa</div>
                <div class="landing-feature-chip"><i class="fa-solid fa-floppy-disk"></i> Autosave jawaban</div>
                <div class="landing-feature-chip"><i class="fa-solid fa-chart-line"></i> Hasil untuk analisis guru</div>
            </div>
        </div>
    </section>

    <section id="harga" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
        <div class="section-heading">
            <div>
                <div class="landing-section-kicker">Pricing</div>
                <h2 class="landing-section-title">Harga yang mudah dipahami dan siap dipakai untuk closing.</h2>
                <p class="landing-section-copy">Cocok untuk sekolah atau guru yang ingin menyiapkan siswa menghadapi TKA dengan proses yang lebih terukur dan mudah dianalisis.</p>
            </div>
        </div>

        <div class="mt-8 grid gap-5 xl:grid-cols-3">
            @foreach ($pricingPlans as $index => $plan)
                @php
                    $highlighted = $index === 1;
                @endphp
                <article class="landing-price-card {{ $highlighted ? 'featured' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xl font-bold text-slate-900 dark:text-white">{{ $plan['name'] }}</div>
                            @if (!empty($plan['subtitle']))
                                <div class="mt-2 text-sm text-textSecondary dark:text-slate-300">{{ $plan['subtitle'] }}</div>
                            @endif
                        </div>
                        @if ($highlighted)
                            <span class="badge-success"><i class="fa-solid fa-fire"></i> Paling diminati</span>
                        @elseif (!empty($plan['original_price']))
                            <span class="badge-info"><i class="fa-solid fa-percent"></i> Promo</span>
                        @endif
                    </div>

                    <div class="mt-6">
                        @if (!empty($plan['original_price']))
                            <div class="text-sm text-muted line-through dark:text-slate-400">Rp {{ $plan['original_price'] }}</div>
                        @endif
                        <div class="mt-2 flex items-end gap-2">
                            <div class="text-4xl font-bold text-slate-900 dark:text-white">Rp {{ $plan['price'] }}</div>
                            <div class="pb-1 text-sm text-textSecondary dark:text-slate-300">{{ $plan['period'] ?? '' }}</div>
                        </div>
                    </div>

                    <ul class="mt-6 space-y-3 text-sm text-textSecondary dark:text-slate-300">
                        <li><i class="fa-solid fa-circle-check mr-2 text-success"></i>Siap untuk guru dan operator sekolah</li>
                        <li><i class="fa-solid fa-circle-check mr-2 text-success"></i>Membantu membaca kesiapan siswa</li>
                        <li><i class="fa-solid fa-circle-check mr-2 text-success"></i>Flow siswa tetap sederhana dan fokus</li>
                    </ul>

                    <div class="mt-8">
                        @if (Route::has('register.guru.form'))
                            <a href="{{ route('register.guru.form') }}" class="btn-primary w-full">
                                Pilih Paket Ini
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="faq" class="mx-auto max-w-7xl px-4 py-12 scroll-mt-24">
        <div class="section-heading">
            <div>
                <div class="landing-section-kicker">FAQ</div>
                <h2 class="landing-section-title">Pertanyaan yang biasanya muncul sebelum orang mencoba.</h2>
            </div>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-2">
            <div class="landing-faq-card">
                <h3 class="landing-faq-title">Apakah platform ini cocok untuk pelaksanaan TKA?</h3>
                <p class="landing-faq-copy">Ya. Platform ini cocok dipakai untuk membantu guru menyiapkan siswa menuju TKA melalui latihan, ujian, dan pembacaan hasil yang lebih terarah.</p>
            </div>
            <div class="landing-faq-card">
                <h3 class="landing-faq-title">Apakah guru bisa mengelola soal sendiri?</h3>
                <p class="landing-faq-copy">Bisa. Guru memiliki workflow untuk bank soal, paket soal, mapel, dan sesi ujian sehingga persiapan siswa bisa dikelola dengan lebih rapi.</p>
            </div>
            <div class="landing-faq-card">
                <h3 class="landing-faq-title">Apakah siswa harus punya akun?</h3>
                <p class="landing-faq-copy">Tidak. Siswa bisa masuk dengan token ujian, lalu mengisi identitas dan langsung mengikuti sesi yang tersedia.</p>
            </div>
            <div class="landing-faq-card">
                <h3 class="landing-faq-title">Apakah tetap relevan dengan arah resmi TKA?</h3>
                <p class="landing-faq-copy">Ya. Platform ini dapat diposisikan selaras dengan semangat TKA karena membantu sekolah membaca capaian akademik siswa secara lebih terstruktur.</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-6">
        <div class="landing-cta-panel">
            <div>
                <div class="landing-section-kicker">Siap dipakai</div>
                <h2 class="landing-section-title">Sekarang pesannya lebih jelas: ini website untuk guru yang ingin menyiapkan siswa lebih matang menuju TKA.</h2>
                <p class="landing-section-copy">Manfaat utamanya sudah diarahkan ke pemantauan progres, analisis hasil, dan pengambilan tindakan pembinaan yang lebih tepat di sekolah.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                @if (Route::has('register.guru.form'))
                    <a href="{{ route('register.guru.form') }}" class="btn-primary">
                        Mulai Trial Guru
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endif
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-secondary">
                        Login Guru
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </a>
                @endif
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-white/70 bg-white/65 dark:border-slate-800/80 dark:bg-slate-950/60">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-8 text-center sm:flex-row sm:text-left">
        <div class="text-sm text-textSecondary dark:text-slate-400">2026 Ujion TKA by Reditech</div>
        <div class="text-sm text-textSecondary dark:text-slate-400">Modern exam platform for school operations</div>
    </div>
</footer>
</body>
</html>
