<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ujion - Registrasi Guru/Operator')</title>
    @yield('head')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    {{-- KaTeX Math Rendering --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

    @include('partials.ssd-style')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); }
    </style>
</head>
@php
    // Otomatis sembunyikan showcase untuk halaman siswa dan latihan materi.
    if (request()->is('siswa*') || request()->is('materi*')) {
        $hideShowcase = true;
    }

    $guestFullscreen = isset($fullscreenGuest) && $fullscreenGuest;
    $hideGuestFooter = isset($hideFooterGuest) && $hideFooterGuest;
    $guestWide = isset($wideGuest) && $wideGuest;

    if (request()->routeIs('materi.dashboard', 'materi.paket.*', 'siswa.practice.dashboard', 'siswa.practice.paket.*')) {
        $guestWide = true;
    }
@endphp
<body class="{{ $guestFullscreen ? 'min-h-screen' : 'min-h-screen flex flex-col items-center justify-center p-4' }}">
    <div class="guest-shell {{ $guestFullscreen ? '!max-w-none !w-full !px-0 !py-0 !min-h-screen flex flex-col' : 'mx-auto' }} {{ isset($hideShowcase) && $hideShowcase ? ($guestWide ? 'w-full max-w-6xl' : 'max-w-lg') : '' }}">
        <div class="guest-panel {{ $guestFullscreen ? '!rounded-none !border-0 !bg-transparent !shadow-none flex-1 min-h-0 md:!grid-cols-[40%_60%]' : '' }} {{ isset($hideShowcase) && $hideShowcase ? '!grid-cols-1' : '' }}">
            @if(!isset($hideShowcase) || !$hideShowcase)
            <section class="guest-showcase {{ $guestFullscreen ? 'md:border-r md:border-white/10' : '' }}">
                <span class="page-kicker">Platform Ujian TKA</span>
                <h1 class="mt-5 text-center text-3xl md:text-4xl">Ruang belajar dan operasional yang terasa lebih modern, ringan, dan jelas.</h1>
                <p class="mx-auto mt-4 max-w-md text-center text-sm leading-6 text-slate-200">
                    Ujion membantu sekolah dan guru mengelola ujian, materi, dan aktivasi akun dalam satu alur yang lebih tertata.
                </p>
                <div class="mt-8 grid grid-cols-1 gap-3 text-center sm:grid-cols-2">
                    <div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                        <i class="fa-solid fa-shield-halved text-white/90"></i>
                        <span class="font-semibold">Aktivasi akun lebih terkontrol</span>
                    </div>
                    <div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                        <i class="fa-solid fa-layer-group text-white/90"></i>
                        <span class="font-semibold">Pengelolaan materi dan soal lebih rapi</span>
                    </div>
                    <div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                        <i class="fa-solid fa-chart-line text-white/90"></i>
                        <span class="font-semibold">Dashboard siap untuk monitoring harian</span>
                    </div>
                    <div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                        <i class="fa-solid fa-clipboard-check text-white/90"></i>
                        <span class="font-semibold">Rekap & hasil ujian lebih cepat</span>
                    </div>
                </div>
            </section>
            @endif

            <section class="guest-content {{ $guestFullscreen ? '!px-5 !py-8 sm:!px-8 sm:!py-10 md:!px-12 md:!py-12 bg-white/85 backdrop-blur-xl dark:bg-slate-950/80' : '' }}">
                @yield('content')
            </section>
        </div>

        @if(!$hideGuestFooter)
            <footer class="{{ $guestFullscreen ? 'px-5 py-6 sm:px-8 md:px-12' : 'pt-5' }} text-center text-xs text-textSecondary dark:text-slate-500">
                &copy; {{ date('Y') }} Ujion. All rights reserved.
            </footer>
        @endif
    </div>
    @include('partials.ssd')
    @stack('scripts')
</body>
</html>
