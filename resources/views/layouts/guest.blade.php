<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ujion - Registrasi Guru/Operator')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    {{-- KaTeX Math Rendering --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" onload="renderMathInElement(document.body, {
        delimiters: [
            {left: '$$', right: '$$', display: true},
            {left: '$', right: '$', display: false},
            {left: '\\(', right: '\\)', display: false},
            {left: '\\[', right: '\\]', display: true}
        ],
        throwOnError: false
    });"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); }
    </style>
</head>
@php
    // Otomatis sembunyikan showcase untuk semua halaman siswa
    if (request()->is('siswa*')) {
        $hideShowcase = true;
    }
@endphp
<body class="min-h-screen">
    <div class="guest-shell mx-auto {{ isset($hideShowcase) && $hideShowcase ? 'max-w-xl' : '' }}">
        <div class="guest-panel {{ isset($hideShowcase) && $hideShowcase ? '!grid-cols-1' : '' }}">
            @if(!isset($hideShowcase) || !$hideShowcase)
            <section class="guest-showcase">
                <span class="page-kicker">Platform Ujian TKA</span>
                <h1 class="mt-5 text-3xl md:text-4xl">Ruang belajar dan operasional yang terasa lebih modern, ringan, dan jelas.</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-slate-200">
                    Ujion membantu sekolah dan guru mengelola ujian, materi, dan aktivasi akun dalam satu alur yang lebih tertata.
                </p>
                <div class="mt-8 grid gap-3">
                    <div class="hero-chip">
                        <i class="fa-solid fa-shield-halved"></i>
                        Aktivasi akun lebih terkontrol
                    </div>
                    <div class="hero-chip">
                        <i class="fa-solid fa-layer-group"></i>
                        Pengelolaan materi dan soal lebih rapi
                    </div>
                    <div class="hero-chip">
                        <i class="fa-solid fa-chart-line"></i>
                        Dashboard siap untuk monitoring harian
                    </div>
                </div>
            </section>
            @endif

            <section class="guest-content">
                @yield('content')
            </section>
        </div>

        <footer class="pt-5 text-center text-xs text-textSecondary dark:text-slate-500">
            &copy; {{ date('Y') }} Ujion. All rights reserved.
        </footer>
    </div>
</body>
</html>
