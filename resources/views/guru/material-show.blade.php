@extends('layouts.guru')

@section('title', 'Detail Materi')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Materi Detail</span>
        <h1 class="page-title">{{ $material->subelement }}</h1>
        <p class="page-description">{{ $material->unit }} &middot; {{ $material->sub_unit }}</p>
        <div class="page-actions">
            <span class="badge-info border-white/20 bg-white/10 text-white">Materi dari Ujion</span>
            <a href="{{ route('guru.materials') }}" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Kembali</a>
            @if($material->link)
                <a href="{{ $material->link }}" class="btn-primary" target="_blank" rel="noopener">Buka Link</a>
            @endif
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="section-heading mb-4">
                <div>
                    <h2 class="section-title">Ringkasan</h2>
                    <p class="section-description">Informasi materi yang dipakai sebagai referensi pembuatan soal.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Jenjang</div>
                    <div class="mt-2 font-semibold">{{ $material->jenjang ?? 'Semua' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Kurikulum</div>
                    <div class="mt-2 font-semibold">{{ $material->curriculum }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60 sm:col-span-2">
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Unit</div>
                    <div class="mt-2 font-semibold">{{ $material->unit }}</div>
                    <div class="mt-2 text-sm text-textSecondary">Sub unit: {{ $material->sub_unit }}</div>
                </div>
            </div>
        </div>

        <aside class="card">
            <div class="section-heading mb-4">
                <div>
                    <h2 class="section-title">Aksi</h2>
                    <p class="section-description">Bookmark dan statistik singkat.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Bank Soal Global</div>
                <div class="mt-2 text-3xl font-bold">{{ $globalQuestionCount }}</div>
                <div class="mt-2 text-xs text-textSecondary">Snapshot builder ujian: {{ $examSnapshotCount }}</div>
            </div>

            <div class="mt-4">
                @if($isBookmarked)
                    <form method="POST" action="{{ route('guru.materials.unbookmark', $material) }}">
                        @csrf
                        <button class="btn-danger w-full" type="submit">Hapus Bookmark</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('guru.materials.bookmark', $material) }}">
                        @csrf
                        <button class="btn-secondary w-full" type="submit">Bookmark</button>
                    </form>
                @endif
            </div>
        </aside>
    </section>
</div>
@endsection
