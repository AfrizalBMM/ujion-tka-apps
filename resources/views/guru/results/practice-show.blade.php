@extends('layouts.guru')

@section('title', 'Detail Latihan Materi — Analisis')

@section('content')
<div class="mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="mb-4 flex items-center gap-2 text-sm font-semibold text-textSecondary">
                <a href="{{ route('guru.results.index', ['tab' => 'materi']) }}" class="hover:text-primary">Hasil Siswa</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-slate-900">Materi</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900">{{ $material->sub_unit }}</h1>
            <p class="mt-1 text-sm text-textSecondary">{{ $material->subelement }} &middot; {{ $material->unit }}</p>
        </div>
        <a href="{{ route('guru.results.index', ['tab' => 'materi']) }}" class="btn-secondary px-5 py-2.5 text-sm font-bold">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

@if(!$token)
    <div class="empty-state py-12">
        <h3 class="text-lg font-bold text-slate-900">Token latihan belum dibuat</h3>
        <p class="mt-1 text-sm text-textSecondary">Belum ada data yang bisa dianalisis untuk materi ini.</p>
    </div>
@else
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-600"><i class="fa-solid fa-users"></i></div>
            <div><div class="metric-label">Peserta</div><div class="metric-value">{{ $stats['total'] }}</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-500"><i class="fa-solid fa-circle-check"></i></div>
            <div><div class="metric-label">Selesai 3 Paket</div><div class="metric-value">{{ $stats['completed'] }}</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-500"><i class="fa-solid fa-star"></i></div>
            <div><div class="metric-label">Rata-rata Skor</div><div class="metric-value">{{ $stats['avg'] }}</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-500"><i class="fa-solid fa-trophy"></i></div>
            <div><div class="metric-label">Skor Tertinggi</div><div class="metric-value text-amber-600">{{ $stats['max'] }}</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-rose-500"><i class="fa-solid fa-circle-down"></i></div>
            <div><div class="metric-label">Skor Terendah</div><div class="metric-value text-rose-600">{{ $stats['min'] }}</div></div>
        </div>
    </div>

    <div class="mb-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Token Latihan</h3>
                <p class="mt-1 text-sm text-textSecondary">Kode masuk siswa dan komposisi paket.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <code class="rounded bg-indigo-50 px-3 py-2 text-lg font-black text-indigo-700">{{ $token->token }}</code>
                <span class="badge-info">{{ $packageStats->count() }} paket</span>
                <span class="{{ $token->is_active ? 'badge-success' : 'badge-warning' }}">{{ $token->is_active ? 'Aktif' : 'Nonaktif' }}</span>
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
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Paket</th>
                                <th class="px-6 py-4 text-center">Rata-rata</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rankings as $s)
                                <tr class="group transition-colors hover:bg-slate-50/50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900">{{ $s->nama }}</div>
                                        <div class="text-[10px] text-textSecondary">{{ $s->nomor_wa ?: '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="badge-secondary">{{ $s->status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-black text-slate-900">{{ $s->packages_done }}</span><span class="text-textSecondary">/3</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            <span class="rounded-xl px-3 py-1 text-sm font-black {{ ($s->avg_score ?? 0) >= 70 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $s->avg_score !== null ? number_format((float) $s->avg_score, 1) : '-' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('guru.results.practice.student', [$material->id, $s->id]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors hover:bg-indigo-600 hover:text-white" title="Lihat Detail">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-textSecondary">Belum ada siswa yang mengerjakan materi ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
                <h3 class="mb-5 text-lg font-bold text-slate-900">Performa Paket</h3>
                <div class="space-y-3">
                    @foreach($packageStats as $pkg)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-slate-900">Paket {{ $pkg['paket_no'] }}</div>
                                <div class="text-sm font-black text-indigo-600">{{ number_format((float) $pkg['avg'], 1) }}</div>
                            </div>
                            <div class="mt-1 text-xs text-textSecondary">{{ $pkg['attempts'] }} pengerjaan &middot; {{ $pkg['total_soal'] }} soal</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
                <h3 class="mb-2 text-lg font-bold text-slate-900">Analisis Butir Paket</h3>
                <p class="mb-5 text-xs text-textSecondary">Persentase benar per soal yang sudah dikerjakan siswa.</p>
                <div class="grid grid-cols-5 gap-2">
                    @forelse($questionStats as $index => $q)
                        <div class="group relative">
                            <div class="flex h-10 w-full items-center justify-center rounded-xl font-bold text-white shadow-sm transition-transform hover:scale-110 {{ $q['percent'] >= 75 ? 'bg-emerald-500' : ($q['percent'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" title="{{ $q['percent'] }}% benar">
                                {{ $index + 1 }}
                            </div>
                            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 w-24 -translate-x-1/2 rounded-lg bg-slate-900 px-2 py-1 text-center text-[9px] font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
                                {{ $q['percent'] }}% Benar
                            </div>
                        </div>
                    @empty
                        <div class="col-span-5 text-sm text-textSecondary">Belum ada jawaban paket.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Analisis Telaah</h3>
        <div class="grid gap-4 md:grid-cols-2">
            @forelse($telaahStats as $index => $q)
                <div class="rounded-2xl bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold text-slate-900">Telaah {{ $index + 1 }}</div>
                        <div class="rounded-xl px-3 py-1 text-sm font-black {{ $q['percent'] >= 75 ? 'bg-emerald-100 text-emerald-700' : ($q['percent'] >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                            {{ $q['percent'] }}%
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-textSecondary">{{ \Illuminate\Support\Str::limit(strip_tags($q['question']?->question_text ?? '-'), 120) }}</div>
                    <div class="mt-2 text-xs text-textSecondary">{{ $q['correct'] }} benar dari {{ $q['total'] }} jawaban</div>
                </div>
            @empty
                <div class="text-sm text-textSecondary">Belum ada jawaban telaah.</div>
            @endforelse
        </div>
    </div>
@endif
@endsection
