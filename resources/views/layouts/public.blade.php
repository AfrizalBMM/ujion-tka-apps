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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

            <nav class="hidden items-center gap-6 lg:flex">
                <a href="{{ route('kisi-kisi.index') }}" class="landing-nav-link">Kisi-Kisi</a>
                <a href="{{ route('artikel.index') }}" class="landing-nav-link">Artikel</a>
                @if(\Illuminate\Support\Facades\Route::has('ujian-online.index'))
                    <a href="{{ route('ujian-online.index') }}" class="landing-nav-link">Ujian Online</a>
                @endif
                <a href="{{ route('landing') }}#faq" class="landing-nav-link">FAQ</a>
            </nav>

            <a href="{{ route('register.guru.form') }}" class="btn-primary hidden sm:inline-flex">Coba Sebagai Guru</a>
        </div>
    </header>

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
