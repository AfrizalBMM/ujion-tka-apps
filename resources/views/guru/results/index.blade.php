@extends('layouts.guru')

@section('title', 'Hasil Ujian — Analisis')

@section('content')
<div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Analisis Hasil Ujian</h1>
        <p class="mt-1 text-sm text-textSecondary">Pantau performa dan statistik seluruh simulasi yang Anda terbitkan.
        </p>
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($exams as $exam)
    <div
        class="group relative flex flex-col overflow-hidden rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-hover">
        <div class="mb-4 flex items-center justify-between">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-sm transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                <i class="fa-solid fa-chart-pie text-lg"></i>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Peserta</span>
                <span class="text-lg font-bold text-slate-900">{{ $exam->total_peserta }}</span>
            </div>
        </div>

        <h3 class="line-clamp-1 text-lg font-bold text-slate-900">
            {{ $exam->nama }}
            @if($exam->creator && $exam->creator->role === 'superadmin')
                <span
                    class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Ujion</span>
            @endif
        </h3>
        <p class="mt-1 text-xs text-textSecondary">Paket: {{ $exam->paketSoal->nama ?? '-' }}</p>

        <div class="mt-6 flex flex-wrap gap-2">
            @php($tokens = $exam->examMapelTokens()->with('mapelPaket')->get())
            @foreach($tokens as $t)
                <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">
                    {{ $t->mapelPaket->nama_label ?? 'Mapel' }}
                </span>
            @endforeach
        </div>

        <div class="mt-auto pt-6">
            <a href="{{ route('guru.results.show', $exam->id) }}"
                class="btn-primary w-full justify-center py-2.5 text-sm font-bold">
                Buka Analisis
                <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
            </a>
        </div>
    </div>
    @empty
    <div class="col-span-full py-12">
        <div class="empty-state">
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <i class="fa-solid fa-folder-open text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Belum ada ujian</h3>
            <p class="mt-1 text-sm text-textSecondary text-balance">Terbitkan ujian terlebih dahulu untuk melihat
                analisis hasil di sini.</p>
            <a href="{{ route('guru.exams') }}" class="btn-primary mt-6 inline-flex px-6 py-2.5">Mulai Terbitkan</a>
        </div>
    </div>
    @endforelse
</div>
@endsection