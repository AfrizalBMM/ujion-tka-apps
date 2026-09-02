@extends('layouts.public')

@section('title', 'Kisi-Kisi TKA per Jenjang — ' . config('app.name', 'Ujion TKA'))
@section('description', 'Lihat kisi-kisi dan cakupan materi Tes Kemampuan Akademik (TKA) untuk jenjang SD, SMP, dan SMA. Pelajari topik per mata pelajaran sebelum mengikuti ujian.')
@section('canonical', route('kisi-kisi.index'))

@push('jsonld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('landing')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Kisi-Kisi', 'item' => route('kisi-kisi.index')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <nav class="text-sm text-textSecondary dark:text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-slate-900 dark:hover:text-white">Beranda</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900 dark:text-white">Kisi-Kisi</span>
    </nav>

    <div class="mt-6 max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">Kisi-Kisi TKA per Jenjang</h1>
        <p class="mt-3 leading-7 text-textSecondary dark:text-slate-300">
            Jelajahi cakupan materi Tes Kemampuan Akademik (TKA) berdasarkan jenjang pendidikan. Pilih jenjang untuk melihat daftar mata pelajaran dan topik yang perlu dikuasai siswa.
        </p>
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @forelse ($jenjangs as $jenjang)
            @php
                $stat = $stats->get(strtoupper($jenjang->kode));
            @endphp
            <a href="{{ route('kisi-kisi.jenjang', ['jenjang' => strtolower($jenjang->kode)]) }}"
                class="group rounded-3xl border border-slate-200/80 bg-white/70 p-6 transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/60">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary dark:text-slate-400">Jenjang</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 group-hover:text-primary dark:text-white">{{ $jenjang->kode }}</div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">{{ $jenjang->nama }}</div>
                <div class="mt-4 text-sm text-textSecondary dark:text-slate-400">
                    @if ($stat)
                        {{ $stat->mapel_count }} mata pelajaran &middot; {{ $stat->topic_count }} topik
                    @else
                        Materi segera hadir
                    @endif
                </div>
                <div class="mt-4 text-sm font-semibold text-primary">Lihat kisi-kisi &rarr;</div>
            </a>
        @empty
            <p class="text-textSecondary dark:text-slate-300">Data jenjang belum tersedia.</p>
        @endforelse
    </div>
@endsection
