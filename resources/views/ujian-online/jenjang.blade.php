@extends('layouts.public')

@section('title', 'Ujian Online ' . $jenjang . ' — Ujion TKA')
@section('description', 'Daftar ujian dan tryout online jenjang ' . $jenjang . ' di Ujion TKA.')

@section('content')
<div class="flex items-center justify-between">
    <div>
        <span class="page-kicker">Ujian Online</span>
        <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">Jenjang {{ $jenjang }}</h1>
    </div>
    <a href="{{ route('ujian-online.index') }}" class="btn-secondary text-sm">Semua Jenjang</a>
</div>

@if($landingExams->isEmpty())
    <div class="mt-10 rounded-3xl border border-slate-200/80 bg-white p-12 text-center dark:border-slate-700/60 dark:bg-slate-900">
        <i class="fa-solid fa-folder-open text-4xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
        <p class="text-textSecondary">Belum ada ujian untuk jenjang {{ $jenjang }}.</p>
    </div>
@else
    <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($landingExams as $le)
            <a href="{{ route('ujian-online.show', [strtolower($le->jenjang), $le->slug]) }}" class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900">
                <div class="p-6 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $le->jenjang }}</span>
                        <span class="text-xs text-textSecondary">{{ $le->mapels->count() }} mapel</span>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900 dark:text-white">{{ $le->exam?->judul ?? '—' }}</h3>
                    <p class="mt-2 text-sm text-textSecondary line-clamp-2">{{ $le->short_description ?? 'Klik untuk melihat detail ujian.' }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($le->mapels->where('is_active', true) as $mapel)
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $mapel->mapelPaket?->nama_label ?? '—' }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-700/60">
                    <span class="text-sm font-semibold text-indigo-600">Mulai dari Rp{{ number_format((float) $le->mapels->where('is_active', true)->min('price') ?? 0, 0, ',', '.') }}
                        <i class="fa-solid fa-arrow-right ml-1 transition group-hover:translate-x-1"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
