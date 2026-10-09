<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $pageTitle = trim($__env->yieldContent('title')) ?: config('app.name', 'Ujion TKA');
        $pageDescription = trim($__env->yieldContent('description')) ?: 'Platform pendamping guru/operator untuk memantau progres, menganalisis hasil, dan menyiapkan siswa menghadapi TKA.';
        $pageCanonical = trim($__env->yieldContent('canonical')) ?: url()->current();
        $ogImageAbs = route('og.image');
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
    <meta name="robots" content="@yield('robots', 'index,follow')">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name', 'Ujion TKA') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:image" content="{{ $ogImageAbs }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImageAbs }}">

    @vite(['resources/css/app.css', 'resources/js/public.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    @stack('jsonld')
</head>

<body class="landing-body min-h-screen text-textPrimary dark:text-slate-100">
    <header class="landing-header">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <div class="landing-brand-mark overflow-hidden">
                    <img src="{{ $logoUrl ?? asset('assets/img/logo.png') }}" alt="Logo Ujion TKA" class="h-full w-full object-cover">
                </div>
                <div class="leading-tight">
                    <div class="font-bold text-slate-900 dark:text-white">Ujion TKA</div>
                    <div class="text-xs uppercase tracking-[0.22em] text-textSecondary dark:text-slate-400">Rekan Guru</div>
                </div>
            </a>

            <div class="flex items-center gap-2">
                <nav class="hidden items-center gap-6 lg:flex">
                    <a href="{{ route('kisi-kisi.index') }}" class="landing-nav-link">Kisi-Kisi</a>
                    <a href="{{ route('artikel.index') }}" class="landing-nav-link">Artikel</a>
                    @if(\Illuminate\Support\Facades\Route::has('ujian-online.index'))
                        <a href="{{ route('ujian-online.index') }}" class="landing-nav-link">Ujian Online</a>
                    @endif
                    <a href="{{ route('landing') }}#faq" class="landing-nav-link">FAQ</a>
                </nav>

                <a href="{{ route('register.guru.form') }}" class="btn-primary hidden sm:inline-flex">Coba Sebagai Guru</a>

                <button
                    type="button"
                    id="mobile-nav-toggle"
                    class="lg:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                    aria-label="Buka menu navigasi"
                    aria-expanded="false"
                    aria-controls="mobile-nav-drawer"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div id="mobile-nav-drawer" class="fixed inset-0 z-[60] lg:hidden" style="display:none" aria-hidden="true">
        <div id="mobile-nav-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div id="mobile-nav-panel" class="absolute right-0 top-0 h-full w-72 max-w-[80vw] overflow-y-auto bg-white p-6 shadow-2xl dark:bg-slate-900 transition-transform duration-300" role="dialog" aria-modal="true" aria-label="Menu navigasi mobile">
            <div class="mb-6 flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white">Menu</span>
                <button type="button" id="mobile-nav-close" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Tutup menu navigasi">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex flex-col gap-4">
                <a href="{{ route('kisi-kisi.index') }}" class="text-base font-medium text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400">Kisi-Kisi</a>
                <a href="{{ route('artikel.index') }}" class="text-base font-medium text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400">Artikel</a>
                @if(\Illuminate\Support\Facades\Route::has('ujian-online.index'))
                    <a href="{{ route('ujian-online.index') }}" class="text-base font-medium text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400">Ujian Online</a>
                @endif
                <a href="{{ route('landing') }}#faq" class="text-base font-medium text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400">FAQ</a>
                <a href="{{ route('register.guru.form') }}" class="btn-primary mt-2 w-full justify-center">Coba Sebagai Guru</a>
            </nav>
        </div>
    </div>

    <script>
        (function () {
            var drawer = document.getElementById('mobile-nav-drawer');
            var toggle = document.getElementById('mobile-nav-toggle');
            var closeBtn = document.getElementById('mobile-nav-close');
            var backdrop = document.getElementById('mobile-nav-backdrop');

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

    <main class="mx-auto w-full max-w-7xl px-4 py-10">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200/70 py-8 dark:border-slate-800/70">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 text-sm text-textSecondary dark:text-slate-400 sm:flex-row">
            <div>&copy; {{ date('Y') }} {{ config('app.name', 'Ujion TKA') }}. All rights reserved.</div>
            <nav class="flex items-center gap-5">
                <a href="{{ route('kisi-kisi.index') }}" class="hover:text-slate-900 dark:hover:text-white">Kisi-Kisi</a>
                <a href="{{ route('artikel.index') }}" class="hover:text-slate-900 dark:hover:text-white">Artikel</a>
                @if(\Illuminate\Support\Facades\Route::has('ujian-online.index'))
                    <a href="{{ route('ujian-online.index') }}" class="hover:text-slate-900 dark:hover:text-white">Ujian Online</a>
                @endif
                <a href="{{ route('register.guru.form') }}" class="hover:text-slate-900 dark:hover:text-white">Daftar Guru</a>
            </nav>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
