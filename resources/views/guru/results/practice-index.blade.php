@extends('layouts.guru')

@section('title', 'Hasil Latihan Materi — Analisis')

@section('content')
<div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Analisis Latihan Materi</h1>
        <p class="mt-1 text-sm text-textSecondary">Pantau hasil latihan (telaah + paket 1-3) berbasis token per materi.</p>
    </div>
    <div>
        <a href="{{ route('guru.results.index') }}" class="btn-secondary">Kembali</a>
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($tokens as $t)
        <div class="group relative flex flex-col overflow-hidden rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-hover">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-sm transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                    <i class="fa-solid fa-chart-column text-lg"></i>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Peserta</span>
                    <span class="text-lg font-bold text-slate-900">{{ $t->sessions_count }}</span>
                </div>
            </div>

            <h3 class="line-clamp-2 text-lg font-bold text-slate-900">{{ $t->material?->sub_unit ?? 'Materi' }}</h3>
            <p class="mt-1 text-xs text-textSecondary">Token: <span class="font-mono font-bold">{{ $t->token }}</span></p>

            <div class="mt-6">
                <a href="{{ route('guru.results.practice.show', $t->material_id) }}" class="btn-primary w-full justify-center py-2.5 text-sm font-bold">
                    Buka Analisis
                    <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-full py-12">
            <div class="empty-state">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="fa-solid fa-folder-open text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Belum ada token latihan</h3>
                <p class="mt-1 text-sm text-textSecondary text-balance">Token latihan materi dibuat oleh admin. Jika belum ada, minta admin menyiapkan latihan untuk materi yang Anda butuhkan.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
