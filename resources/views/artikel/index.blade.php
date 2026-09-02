@extends('layouts.public')

@section('title', 'Artikel & Tips TKA — ' . config('app.name', 'Ujion TKA'))
@section('description', 'Kumpulan artikel, panduan, dan tips seputar Tes Kemampuan Akademik (TKA) untuk guru dan sekolah.')
@section('canonical', route('artikel.index'))

@push('jsonld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('landing')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Artikel', 'item' => route('artikel.index')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <nav class="text-sm text-textSecondary dark:text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-slate-900 dark:hover:text-white">Beranda</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900 dark:text-white">Artikel</span>
    </nav>

    <div class="mt-6 max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">Artikel &amp; Tips TKA</h1>
        <p class="mt-3 leading-7 text-textSecondary dark:text-slate-300">
            Panduan, strategi, dan informasi terbaru seputar persiapan Tes Kemampuan Akademik untuk guru dan sekolah.
        </p>
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($posts as $post)
            <a href="{{ route('artikel.show', $post) }}"
                class="group flex flex-col rounded-3xl border border-slate-200/80 bg-white/70 p-6 transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/60">
                <div class="text-xs text-textSecondary dark:text-slate-400">
                    {{ $post->published_at?->translatedFormat('d F Y') }}
                </div>
                <h2 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-primary dark:text-white">{{ $post->title }}</h2>
                @if ($post->excerpt)
                    <p class="mt-2 flex-1 text-sm leading-6 text-textSecondary dark:text-slate-300">{{ $post->excerpt }}</p>
                @endif
                <div class="mt-4 text-sm font-semibold text-primary">Baca artikel &rarr;</div>
            </a>
        @empty
            <p class="text-textSecondary dark:text-slate-300 md:col-span-2 lg:col-span-3">Belum ada artikel yang diterbitkan.</p>
        @endforelse
    </div>

    @if ($posts->hasPages())
        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
@endsection
