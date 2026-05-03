@extends('layouts.guru')

@section('title', 'Hasil Siswa — Analisis')

@section('content')
<div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Hasil Siswa</h1>
        <p class="mt-1 text-sm text-textSecondary">Pantau hasil ujian dan latihan materi dalam satu tempat.</p>
    </div>
</div>

<div class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-white/80 bg-white/80 p-2 shadow-sm">
    <a href="{{ route('guru.results.index', ['tab' => 'ujian']) }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'ujian' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
        <i class="fa-solid fa-file-lines"></i>
        Ujian
    </a>
    <a href="{{ route('guru.results.index', ['tab' => 'materi']) }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'materi' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
        <i class="fa-solid fa-book-open"></i>
        Materi
    </a>
</div>

@if($activeTab === 'materi')
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($practiceTokens as $t)
            <div class="group relative flex flex-col overflow-hidden rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-hover">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-sm transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fa-solid fa-chart-column text-lg"></i>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Peserta</span>
                        <span class="text-lg font-bold text-slate-900">{{ $t->sessions_count }}</span>
                    </div>
                </div>

                <h3 class="line-clamp-2 text-lg font-bold text-slate-900">{{ $t->material?->sub_unit ?? 'Materi' }}</h3>
                <p class="mt-1 text-xs text-textSecondary">{{ $t->material?->subelement ?? '-' }} &middot; {{ $t->material?->unit ?? '-' }}</p>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Selesai</div>
                        <div class="mt-1 text-lg font-black text-slate-900">{{ $t->completed_sessions_count }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Rata-rata</div>
                        <div class="mt-1 text-lg font-black text-indigo-600">{{ $t->avg_score !== null ? number_format((float) $t->avg_score, 1) : '-' }}</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <code class="rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-bold text-indigo-700">{{ $t->token }}</code>
                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $t->packages_count }} paket</span>
                </div>

                <div class="mt-auto pt-6">
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
                    <h3 class="text-lg font-bold text-slate-900">Belum ada latihan materi</h3>
                    <p class="mt-1 text-sm text-textSecondary text-balance">Token latihan materi dibuat oleh admin. Jika belum ada, minta admin menyiapkan latihan untuk materi yang Anda butuhkan.</p>
                </div>
            </div>
        @endforelse
    </div>
@else
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

                <h3 class="line-clamp-1 text-lg font-bold text-slate-900">
                    {{ $exam->nama }}
                    @if($exam->creator && $exam->creator->role === 'superadmin')
                        <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Ujion</span>
                    @endif
                </h3>
                <p class="mt-1 text-xs text-textSecondary">Paket: {{ $exam->paketSoal->nama ?? '-' }}</p>

                <div class="mt-6 flex flex-wrap gap-2">
                    @php
                        $tokens = $exam->examMapelTokens()->with('mapelPaket')->get();
                    @endphp
                    @foreach($tokens as $t)
                        <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">
                            {{ $t->mapelPaket->nama_label ?? 'Mapel' }}
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
                    <p class="mt-1 text-sm text-textSecondary text-balance">Terbitkan ujian terlebih dahulu untuk melihat analisis hasil di sini.</p>
                    <a href="{{ route('guru.exams') }}" class="btn-primary mt-6 inline-flex px-6 py-2.5">Mulai Terbitkan</a>
                </div>
            </div>
        @endforelse
    </div>
@endif
@endsection
