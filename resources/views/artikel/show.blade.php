@extends('layouts.public')

@section('title', ($post->meta_title ?: $post->title) . ' — ' . config('app.name', 'Ujion TKA'))
@section('description', $description)
@section('canonical', route('artikel.show', $post))
@section('og_type', 'article')

@push('jsonld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $description,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'mainEntityOfPage' => route('artikel.show', $post),
            'author' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Ujion TKA'),
                'url' => route('landing'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Ujion TKA'),
                'url' => route('landing'),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('landing')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Artikel', 'item' => route('artikel.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => route('artikel.show', $post)],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <article class="mx-auto max-w-3xl">
        <nav class="text-sm text-textSecondary dark:text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}" class="hover:text-slate-900 dark:hover:text-white">Beranda</a>
            <span class="mx-2">/</span>
            <a href="{{ route('artikel.index') }}" class="hover:text-slate-900 dark:hover:text-white">Artikel</a>
            <span class="mx-2">/</span>
            <span class="text-slate-900 dark:text-white">{{ Str::limit($post->title, 40) }}</span>
        </nav>

        <header class="mt-6">
            <div class="text-sm text-textSecondary dark:text-slate-400">
                {{ $post->published_at?->translatedFormat('d F Y') }}
            </div>
            <h1 class="mt-2 text-3xl font-bold leading-tight text-slate-900 dark:text-white sm:text-4xl">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="mt-3 text-lg leading-8 text-textSecondary dark:text-slate-300">{{ $post->excerpt }}</p>
            @endif
        </header>

        <div class="article-content mt-8">
            {!! Str::markdown($post->content) !!}
        </div>
    </article>

    @if ($relatedPosts->isNotEmpty())
        <section class="mx-auto mt-14 max-w-3xl">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Artikel Terkait</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                @foreach ($relatedPosts as $related)
                    <a href="{{ route('artikel.show', $related) }}"
                        class="group rounded-2xl border border-slate-200/80 bg-white/70 p-5 transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/60">
                        <div class="text-xs text-textSecondary dark:text-slate-400">{{ $related->published_at?->translatedFormat('d F Y') }}</div>
                        <div class="mt-1 font-bold text-slate-900 group-hover:text-primary dark:text-white">{{ $related->title }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mx-auto mt-12 max-w-3xl rounded-3xl bg-gradient-primary p-8 text-center text-white">
        <h2 class="text-2xl font-bold">Pantau kesiapan siswa menghadapi TKA</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm text-white/85">Kelola paket soal, sesi ujian, dan analisis hasil dari satu dashboard guru.</p>
        <a href="{{ route('register.guru.form') }}" class="mt-5 inline-flex rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-900">Coba Sebagai Guru</a>
    </div>
@endsection
