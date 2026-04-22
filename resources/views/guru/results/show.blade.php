@extends('layouts.guru')

@section('title', $exam->judul . ' - Ringkasan')

@section('content')
<div class="mb-8">
    <a href="{{ route('guru.results.index') }}" class="mb-4 inline-flex items-center text-sm font-semibold text-textSecondary hover:text-primary">
        <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Daftar
    </a>
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ $exam->assessment_label }}</div>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $exam->judul }}</h1>
            <p class="mt-1 text-sm text-textSecondary">Pilih bagian assessment untuk melihat analisis detil dan daftar hasil.</p>
        </div>
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2">
    @php($tokens = $exam->examMapelTokens()->with('mapelPaket')->get())
    @foreach($tokens as $t)
    @php($isSurvey = $t->mapelPaket?->isSurvey())
    @php($sessionCount = $exam->ujianSesis()->where('mapel_paket_id', $t->mapel_paket_id)->count())
    <div class="metric-card group flex flex-col p-6">
        <div class="mb-6 flex items-start justify-between">
            <div class="flex h-14 w-14 items-center justify-center rounded-[20px] bg-slate-900 text-white shadow-lg transition-transform duration-300 group-hover:scale-110">
                <i class="fa-solid fa-book-open text-xl"></i>
            </div>
            <div class="text-right">
                <span class="metric-label">Token Aktif</span>
                <div class="mt-1 flex items-center gap-2">
                    <code class="rounded bg-indigo-50 px-2 py-1 text-sm font-bold text-indigo-600">{{ $t->token }}</code>
                </div>
            </div>
        </div>

        <h3 class="text-xl font-bold text-slate-900">{{ $t->mapelPaket->nama_label ?? 'Bagian' }}</h3>
        <p class="mt-1 text-sm text-textSecondary">{{ $isSurvey ? 'Bagian survey' : 'Bagian akademik' }} &middot; {{ $t->mapelPaket->nama_mapel ?? 'Bagian Assessment' }}</p>

        <div class="mt-6 flex items-center justify-between rounded-2xl bg-slate-50/50 p-4">
            <div class="text-center">
                <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Siswa Ikut</div>
                <div class="mt-1 text-xl font-black text-slate-900">{{ $sessionCount }}</div>
            </div>
            <div class="h-8 w-px bg-slate-200"></div>
            <div class="text-center">
                <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">{{ $isSurvey ? 'Kelengkapan' : 'Rata-rata' }}</div>
                @php($avg = round($exam->ujianSesis()->where('mapel_paket_id', $t->mapel_paket_id)->where('status', 'selesai')->avg('skor') ?? 0, 1))
                <div class="mt-1 text-xl font-black text-indigo-600">{{ $avg }}</div>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('guru.results.mapel', [$exam->id, $t->mapel_paket_id]) }}" class="btn-primary w-full justify-center py-3 font-bold shadow-md">
                Buka Dashboard Bagian
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection
