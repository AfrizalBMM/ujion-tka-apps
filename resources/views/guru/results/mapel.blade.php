@extends('layouts.guru')

@section('title', $mapel->nama_label . ' - Analisis Hasil')

@section('content')
<div class="mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="mb-4 flex items-center gap-2 text-sm font-semibold text-textSecondary">
                <a href="{{ route('guru.results.index') }}" class="hover:text-primary">Hasil</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <a href="{{ route('guru.results.show', $exam->id) }}" class="hover:text-primary">{{ $exam->judul }}</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-slate-900">{{ $mapel->nama_label }}</span>
            </nav>
            <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ $exam->assessment_label }}</div>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $mapel->nama_label }}</h1>
            <p class="mt-1 text-sm text-textSecondary">
                {{ $isSurvey ? 'Analisis kelengkapan respons peserta untuk bagian survey ini.' : 'Informasi analisis mendalam untuk pengerjaan bagian ini.' }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('guru.results.export', [$exam->id, $mapel->id]) }}" class="btn-secondary px-5 py-2.5 text-sm font-bold">
                <i class="fa-solid fa-file-export mr-2"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="stat-card">
        <div class="stat-icon bg-indigo-600">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="metric-label">Total Peserta</div>
            <div class="metric-value">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-emerald-500">
            <i class="fa-solid fa-chart-line text-white"></i>
        </div>
        <div>
            <div class="metric-label">{{ $isSurvey ? 'Rata-rata Kelengkapan' : 'Rata-rata Skor' }}</div>
            <div class="metric-value">{{ $stats['avg'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber-500">
            <i class="fa-solid fa-trophy"></i>
        </div>
        <div>
            <div class="metric-label">{{ $isSurvey ? 'Kelengkapan Tertinggi' : 'Skor Tertinggi' }}</div>
            <div class="metric-value text-amber-600">{{ $stats['max'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-rose-500">
            <i class="fa-solid fa-circle-down"></i>
        </div>
        <div>
            <div class="metric-label">{{ $isSurvey ? 'Kelengkapan Terendah' : 'Skor Terendah' }}</div>
            <div class="metric-value text-rose-600">{{ $stats['min'] }}</div>
        </div>
    </div>
</div>

<div class="grid gap-8 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="rounded-[32px] border border-white/80 bg-white/80 overflow-hidden shadow-card">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-bold text-slate-900">Daftar Hasil Peserta</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/50 text-[10px] font-bold uppercase tracking-[0.15em] text-textSecondary">
                            <th class="px-6 py-4">Nama Siswa</th>
                            <th class="px-6 py-4">Waktu</th>
                            <th class="px-6 py-4 text-center">{{ $isSurvey ? 'Kelengkapan' : 'Skor' }}</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sessions as $s)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $s->nama }}</div>
                                <div class="text-[10px] text-textSecondary">{{ $s->nomor_wa ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-slate-700">{{ $s->waktu_mulai?->format('H:i') ?? '-' }} - {{ $s->waktu_selesai?->format('H:i') ?? '-' }}</div>
                                <div class="text-[10px] text-textSecondary">{{ $s->waktu_selesai?->diffInMinutes($s->waktu_mulai) ?? 0 }} Menit</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center">
                                    <span class="rounded-xl px-3 py-1 text-sm font-black {{ $s->skor >= ($isSurvey ? 100 : 70) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ number_format((float) $s->skor, 1) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('guru.results.student', $s->id) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors hover:bg-indigo-600 hover:text-white" title="Lihat Detail Jawaban">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-textSecondary">Belum ada siswa yang menyelesaikan bagian ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
            <h3 class="mb-5 text-lg font-bold text-slate-900">Top 5 Peserta</h3>
            <div class="space-y-4">
                @foreach($sessions->take(5) as $index => $s)
                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-black {{ $index === 0 ? 'bg-amber-100 text-amber-700' : ($index === 1 ? 'bg-slate-200 text-slate-600' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-white text-slate-400')) }}">
                        {{ $index + 1 }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-slate-900">{{ $s->nama }}</div>
                        <div class="text-[10px] text-textSecondary">{{ number_format((float)$s->skor, 1) }} {{ $isSurvey ? '% lengkap' : 'poin' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
            <h3 class="mb-2 text-lg font-bold text-slate-900">{{ $isSurvey ? 'Analisis Respons Butir' : 'Analisis Butir Soal' }}</h3>
            <p class="mb-5 text-xs text-textSecondary">{{ $isSurvey ? 'Tingkat keterisian jawaban peserta untuk setiap nomor.' : 'Tingkat akurasi jawaban siswa untuk setiap nomor.' }}</p>
            <div class="grid grid-cols-5 gap-2">
                @foreach($questionStats as $q)
                <div class="group relative">
                    <div class="flex h-10 w-full items-center justify-center rounded-xl font-bold text-white shadow-sm transition-transform hover:scale-110 {{ $q['percent'] >= 75 ? 'bg-emerald-500' : ($q['percent'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" title="Nomor {{ $q['nomor'] }}: {{ $q['percent'] }}%">
                        {{ $q['nomor'] }}
                    </div>
                    <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 w-24 -translate-x-1/2 rounded-lg bg-slate-900 px-2 py-1 text-center text-[9px] font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
                        {{ $q['percent'] }}% {{ $isSurvey ? 'terjawab' : 'benar' }}
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-6 flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-textSecondary">
                <div class="flex items-center gap-1.5"><div class="h-2 w-2 rounded-full bg-emerald-500"></div> {{ $isSurvey ? 'Paling terisi' : 'Sering benar' }}</div>
                <div class="flex items-center gap-1.5"><div class="h-2 w-2 rounded-full bg-rose-500"></div> {{ $isSurvey ? 'Paling kosong' : 'Sering salah' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
