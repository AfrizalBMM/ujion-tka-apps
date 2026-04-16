<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Ujion TKA') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-textPrimary dark:bg-slate-950 dark:text-slate-100">
<header class="sticky top-0 z-40 border-b border-border bg-white/80 backdrop-blur dark:bg-slate-950/80 dark:border-slate-800">
    <div class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">
        <a href="{{ route('landing') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-primary shadow-glow"></div>
            <div class="leading-tight">
                <div class="font-bold">Ujion TKA</div>
                <div class="text-xs text-textSecondary dark:text-slate-300">Ujian Tanpa Ribet</div>
            </div>
        </a>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="btn-secondary px-3"
                data-theme-toggle
                aria-label="Toggle dark mode"
                title="Dark mode"
            >
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
    </div>
</header>

<main>
    <div class="mx-auto max-w-6xl px-4 pt-6">
        @include('components.ui.flash')
    </div>

    <section class="relative overflow-hidden">
        <div class="blur-dot left-[-4rem] top-[-4rem]"></div>
        <div class="blur-dot right-[-4rem] bottom-[-4rem]"></div>

        <div class="mx-auto max-w-6xl px-4 py-14 md:py-20 grid md:grid-cols-2 gap-10 items-center">
            <div class="animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-border bg-white/70 text-xs font-bold text-textSecondary dark:bg-slate-900/60 dark:border-slate-800 dark:text-slate-300">
                    <i class="fa-solid fa-bolt text-primary"></i>
                    Platform ujian online SD & SMP
                </div>

                <h1 class="mt-4 text-4xl md:text-5xl leading-tight">
                    Revolusi Ujian Sekolah Lebih Mudah & Menyenangkan!
                </h1>
                <p class="mt-4 text-textSecondary dark:text-slate-300 text-lg">
                    Platform ujian online Jenjang SD & SMP dengan fitur anti-curang dan analisis otomatis.
                </p>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="#alur-guru" class="btn-primary">
                        Mulai Sekarang (Free Trial/Register)
                        <i class="fa-solid fa-arrow-down"></i>
                    </a>
                </div>

                <p class="mt-4 text-sm text-muted dark:text-slate-400">
                    Ramah untuk guru dan siswa—lebih fokus mengajar, bukan ngurus teknis.
                </p>
            </div>

            <div class="animate-slide-in-right">
                <div class="card">
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <div class="text-sm text-textSecondary dark:text-slate-300 font-bold uppercase">Kenapa Ujion?</div>
                            <div class="mt-2 text-2xl font-bold">Ujian Tanpa Ribet</div>
                            <div class="mt-2 text-textSecondary dark:text-slate-300">Mulai dari bank soal sampai laporan hasil—semua otomatis.</div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-gradient-primary text-white flex items-center justify-center shadow-glow">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                            <div class="text-sm font-bold">Anti-curang</div>
                            <div class="text-sm text-textSecondary dark:text-slate-300 mt-1">Random soal, timer, dan kontrol sesi.</div>
                        </div>
                        <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                            <div class="text-sm font-bold">Analisis otomatis</div>
                            <div class="text-sm text-textSecondary dark:text-slate-300 mt-1">Rekap nilai & insight per materi.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">
        <div class="flex items-end justify-between gap-6">
            <div>
                <h2 class="text-2xl">Fitur untuk Semua Peran</h2>
                <p class="mt-2 text-textSecondary dark:text-slate-300">Tiap peran dapat pengalaman yang pas—simple, cepat, dan rapi.</p>
            </div>
        </div>

        <div class="mt-6 grid md:grid-cols-2 gap-4">
            <div class="card">
                <div class="stat-icon bg-gradient-primary">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="mt-4 font-bold text-lg">Role Siswa</div>
                <div class="mt-2 text-textSecondary dark:text-slate-300">Tampilan UI yang bersih dan fokus—biar siswa nyaman mengerjakan.</div>
                <div class="mt-4">
                    <span class="badge-info"><i class="fa-solid fa-wand-magic-sparkles"></i> Fokus</span>
                </div>
            </div>

            <div class="card">
                <div class="stat-icon bg-gradient-primary">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="mt-4 font-bold text-lg">Role Guru</div>
                <div class="mt-2 text-textSecondary dark:text-slate-300">Dashboard analisis dan bank soal otomatis—hemat waktu setiap minggu.</div>
                <div class="mt-4">
                    <span class="badge-success"><i class="fa-solid fa-circle-check"></i> Otomatis</span>
                </div>
            </div>
        </div>
    </section>

    <section id="alur-guru" class="mx-auto max-w-6xl px-4 py-12 scroll-mt-20">
        <div class="card">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h2 class="text-2xl">Alur Masuk Guru</h2>
                    <p class="mt-2 text-textSecondary dark:text-slate-300">Mulai dalam hitungan menit—tanpa setup ribet.</p>
                </div>
                <span class="badge-info"><i class="fa-solid fa-clock"></i> Cepat</span>
            </div>

            <div class="mt-6 grid md:grid-cols-3 gap-4">
                <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                    <div class="text-sm font-bold">1) Daftar / Login</div>
                    <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Buat akun guru, lalu masuk ke dashboard.</div>
                </div>
                <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                    <div class="text-sm font-bold">2) Siapkan Bank Soal</div>
                    <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Buat soal cepat, kelola per bab/materi.</div>
                </div>
                <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                    <div class="text-sm font-bold">3) Jalankan Ujian</div>
                    <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Bagikan kode ujian dan pantau progres.</div>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                @if (Route::has('register.guru.form'))
                    <a href="{{ route('register.guru.form') }}" class="btn-primary">
                        Mulai Free Trial
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endif
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-secondary">
                        Saya sudah punya akun
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section id="harga" class="mx-auto max-w-6xl px-4 py-12 scroll-mt-20">
        <div>
            <h2 class="text-2xl">Harga yang Masuk Akal</h2>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Preview paket yang tersedia.</p>
        </div>

        <div class="mt-6 grid md:grid-cols-3 gap-4">
            @foreach ($pricingPlans as $plan)
                <div class="card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-bold">{{ $plan['name'] }}</div>
                            @if (!empty($plan['subtitle']))
                                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">{{ $plan['subtitle'] }}</div>
                            @endif
                        </div>
                        <span class="badge-info"><i class="fa-solid fa-percent"></i> Promo</span>
                    </div>

                    <div class="mt-5">
                        @if (!empty($plan['original_price']))
                            <div class="text-sm text-muted dark:text-slate-400 line-through">Rp {{ $plan['original_price'] }}</div>
                        @endif
                        <div class="mt-1 flex items-baseline gap-2">
                            <div class="text-3xl font-bold">Rp {{ $plan['price'] }}</div>
                            <div class="text-sm text-textSecondary dark:text-slate-300">{{ $plan['period'] ?? '' }}</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="#alur-guru" class="btn-primary w-full">
                            Coba Sekarang
                            <i class="fa-solid fa-bolt"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</main>

<footer class="border-t border-border bg-white dark:bg-slate-950 dark:border-slate-800">
    <div class="mx-auto max-w-6xl px-4 py-8 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-sm text-textSecondary dark:text-slate-400">2026 Ujion TKA by Reditech</div>
    </div>
</footer>
</body>
</html>
