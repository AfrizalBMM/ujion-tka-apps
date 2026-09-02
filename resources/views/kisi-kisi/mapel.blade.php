@extends('layouts.public')

@section('title', 'Kisi-Kisi TKA ' . $jenjang->kode . ' ' . $mapelName . ' — ' . config('app.name', 'Ujion TKA'))
@section('description', 'Rincian topik dan referensi kisi-kisi TKA ' . $jenjang->kode . ' mata pelajaran ' . $mapelName . '. Panduan cakupan materi untuk persiapan Tes Kemampuan Akademik.')
@section('canonical', route('kisi-kisi.mapel', ['jenjang' => strtolower($jenjang->kode), 'mapel' => $mapelSlug]))

@push('jsonld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('landing')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Kisi-Kisi', 'item' => route('kisi-kisi.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $jenjang->kode, 'item' => route('kisi-kisi.jenjang', ['jenjang' => strtolower($jenjang->kode)])],
                ['@type' => 'ListItem', 'position' => 4, 'name' => $mapelName, 'item' => route('kisi-kisi.mapel', ['jenjang' => strtolower($jenjang->kode), 'mapel' => $mapelSlug])],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <nav class="text-sm text-textSecondary dark:text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-slate-900 dark:hover:text-white">Beranda</a>
        <span class="mx-2">/</span>
        <a href="{{ route('kisi-kisi.index') }}" class="hover:text-slate-900 dark:hover:text-white">Kisi-Kisi</a>
        <span class="mx-2">/</span>
        <a href="{{ route('kisi-kisi.jenjang', ['jenjang' => strtolower($jenjang->kode)]) }}" class="hover:text-slate-900 dark:hover:text-white">{{ $jenjang->kode }}</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900 dark:text-white">{{ $mapelName }}</span>
    </nav>

    <div class="mt-6 max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">Kisi-Kisi TKA {{ $jenjang->kode }} — {{ $mapelName }}</h1>
        <p class="mt-3 leading-7 text-textSecondary dark:text-slate-300">
            Rincian topik {{ $mapelName }} jenjang {{ $jenjang->nama }} beserta referensi belajar untuk persiapan Tes Kemampuan Akademik.
        </p>
    </div>

    <div class="mt-10 space-y-8">
        @forelse ($materials->groupBy('unit') as $unit => $items)
            <section class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/60 dark:bg-slate-900/60">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $unit ?: 'Umum' }}</h2>
                @php
                    $first = $items->first();
                @endphp
                @if ($first && ($first->curriculum || $first->subelement))
                    <div class="mt-1 text-xs uppercase tracking-wide text-textSecondary dark:text-slate-400">
                        {{ collect([$first->curriculum, $first->subelement])->filter()->implode(' · ') }}
                    </div>
                @endif
                <ul class="mt-4 space-y-3">
                    @foreach ($items as $material)
                        <li class="flex flex-wrap items-start justify-between gap-3 border-t border-slate-200/60 pt-3 dark:border-slate-700/60">
                            <span class="text-slate-800 dark:text-slate-200">{{ $material->sub_unit ?: $material->unit }}</span>
                            @if ($material->link)
                                <a href="{{ $material->link }}" target="_blank" rel="noopener nofollow"
                                    class="text-sm font-semibold text-primary hover:underline">Referensi &nearr;</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="text-textSecondary dark:text-slate-300">Rincian topik untuk mata pelajaran ini belum tersedia.</p>
        @endforelse
    </div>

    <div class="mt-12 rounded-3xl bg-gradient-primary p-8 text-center text-white">
        <h2 class="text-2xl font-bold">Siap uji kemampuan siswa pada materi ini?</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm text-white/85">Buat paket soal dan sesi ujian dari topik-topik di atas, lalu pantau kesiapan siswa dari dashboard guru.</p>
        <a href="{{ route('register.guru.form') }}" class="mt-5 inline-flex rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-900">Coba Sebagai Guru</a>
    </div>
@endsection
