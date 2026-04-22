@extends('layouts.guru')

@section('title', 'Hasil Ujian - Analisis')

@section('content')
<div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Analisis Hasil</h1>
        <p class="mt-1 text-sm text-textSecondary">Pantau performa ujian dan kelengkapan bagian survey dari seluruh sesi yang bisa Anda akses.</p>
    </div>
</div>

<section class="card mb-8 p-4">
    @php
        $activeFilterCount = collect([
            ($search ?? '') !== '' ? $search : null,
        ])->filter()->count();
    @endphp
    <div class="mb-5 flex items-center gap-3">
        <div class="font-bold text-lg">Filter Hasil</div>
        @if ($activeFilterCount > 0)
            <span class="badge-info text-xs">{{ $activeFilterCount }} filter aktif</span>
            <a href="{{ route('guru.results.index') }}" class="flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
        @endif
    </div>
    <form method="GET" action="{{ route('guru.results.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,2fr)_auto]">
        <div>
            <label class="text-xs font-bold text-textSecondary">Cari Ujian / Paket</label>
            <input type="text" name="search" class="input mt-1 w-full" value="{{ $search ?? '' }}" placeholder="Judul ujian atau nama paket">
        </div>
        <div class="flex items-end gap-3">
            <button class="btn-primary w-full md:w-auto" type="submit">
                <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari
            </button>
            <a href="{{ route('guru.results.index') }}" class="btn-secondary w-full text-center md:w-auto">Reset</a>
        </div>
    </form>
</section>

<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($exams as $exam)
    <div class="group relative flex flex-col overflow-hidden rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-hover">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-sm transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                <i class="fa-solid fa-chart-pie text-lg"></i>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Peserta</span>
                <span class="text-lg font-bold text-slate-900">{{ $exam->total_peserta }}</span>
            </div>
        </div>

        <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-primary">{{ $exam->assessment_label }}</div>
        <h3 class="mt-2 line-clamp-1 text-lg font-bold text-slate-900">
            {{ $exam->judul }}
            @if($exam->creator && $exam->creator->role === 'superadmin')
                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Ujion</span>
            @endif
        </h3>
        <p class="mt-1 text-xs text-textSecondary">Paket: {{ $exam->paketSoal->nama ?? '-' }}</p>

        <div class="mt-6 flex flex-wrap gap-2">
            @php($tokens = $exam->examMapelTokens()->with('mapelPaket')->get())
            @foreach($tokens as $t)
                <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">
                    {{ $t->mapelPaket->nama_label ?? 'Bagian' }}
                </span>
            @endforeach
        </div>

        <div class="mt-auto pt-6">
            <a href="{{ route('guru.results.show', $exam->id) }}" class="btn-primary w-full justify-center py-2.5 text-sm font-bold">
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
            <h3 class="text-lg font-bold text-slate-900">Belum ada ujian</h3>
            <p class="mt-1 text-sm text-textSecondary text-balance">Terbitkan sesi terlebih dahulu untuk melihat analisis hasil di sini.</p>
            <a href="{{ route('guru.exams') }}" class="btn-primary mt-6 inline-flex px-6 py-2.5">Mulai Terbitkan</a>
        </div>
    </div>
    @endforelse
</div>
@endsection
