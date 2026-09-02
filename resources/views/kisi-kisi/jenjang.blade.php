@extends('layouts.public')

@section('title', 'Kisi-Kisi TKA ' . $jenjang->kode . ' — ' . config('app.name', 'Ujion TKA'))
@section('description', 'Daftar mata pelajaran dan cakupan materi TKA jenjang ' . $jenjang->nama . ' (' . $jenjang->kode . '). Lihat topik per mata pelajaran untuk persiapan Tes Kemampuan Akademik.')
@section('canonical', route('kisi-kisi.jenjang', ['jenjang' => strtolower($jenjang->kode)]))

@push('jsonld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('landing')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Kisi-Kisi', 'item' => route('kisi-kisi.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $jenjang->kode, 'item' => route('kisi-kisi.jenjang', ['jenjang' => strtolower($jenjang->kode)])],
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
        <span class="text-slate-900 dark:text-white">{{ $jenjang->kode }}</span>
    </nav>

    <div class="mt-6 max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">Kisi-Kisi TKA {{ $jenjang->kode }}</h1>
        <p class="mt-3 leading-7 text-textSecondary dark:text-slate-300">
            Cakupan materi Tes Kemampuan Akademik untuk jenjang {{ $jenjang->nama }}. Pilih mata pelajaran untuk melihat rincian topik.
        </p>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($mapels as $mapel)
            <a href="{{ route('kisi-kisi.mapel', ['jenjang' => strtolower($jenjang->kode), 'mapel' => $mapel['slug']]) }}"
                class="group rounded-2xl border border-slate-200/80 bg-white/70 p-5 transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/60">
                <div class="text-lg font-bold text-slate-900 group-hover:text-primary dark:text-white">{{ $mapel['name'] }}</div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-400">{{ $mapel['topic_count'] }} topik</div>
            </a>
        @empty
            <p class="text-textSecondary dark:text-slate-300">Materi untuk jenjang ini belum tersedia.</p>
        @endforelse
    </div>
@endsection
