@extends('layouts.guru')

@section('title', 'Detail Latihan Materi — Analisis')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Analisis Latihan Materi</span>
        <h1 class="page-title">{{ $material->sub_unit }}</h1>
        <p class="page-description">{{ $material->subelement }} &middot; {{ $material->unit }}</p>
        <div class="page-actions">
            <a href="{{ route('guru.results.practice.index') }}" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Kembali</a>
        </div>
    </section>

    <section class="card">
        <div class="section-heading mb-4">
            <div>
                <h2 class="section-title">Token</h2>
                <p class="section-description">Token digunakan siswa untuk masuk ke latihan.</p>
            </div>
        </div>

        @if($token)
            <div class="flex flex-wrap items-center gap-2">
                <code class="rounded bg-indigo-50 px-3 py-2 text-lg font-black text-indigo-700">{{ $token->token }}</code>
                <span class="badge-info">Soal/paket snapshot</span>
                <span class="badge-success">Aktif</span>
            </div>
        @else
            <div class="text-sm text-textSecondary">Token latihan belum dibuat untuk materi ini.</div>
        @endif
    </section>

    <section class="card">
        <div class="section-heading mb-4">
            <div>
                <h2 class="section-title">Daftar Peserta</h2>
                <p class="section-description">Ringkasan progres paket 1-3 per siswa.</p>
            </div>
        </div>

        @if(!$token)
            <div class="text-sm text-textSecondary">Tidak ada data peserta.</div>
        @elseif($sessions->isEmpty())
            <div class="text-sm text-textSecondary">Belum ada siswa yang mengerjakan.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold uppercase tracking-wider text-textSecondary">
                            <th class="py-2 pr-4">Nama</th>
                            <th class="py-2 pr-4">WA</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Paket Selesai</th>
                            <th class="py-2 pr-4">Skor Rata-rata</th>
                            <th class="py-2">Detail Paket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-slate-800">
                        @foreach($sessions as $s)
                            <tr>
                                <td class="py-3 pr-4 font-semibold text-slate-900 dark:text-slate-100">{{ $s->nama }}</td>
                                <td class="py-3 pr-4 text-textSecondary">{{ $s->nomor_wa ?: '-' }}</td>
                                <td class="py-3 pr-4">
                                    <span class="badge-secondary">{{ $s->status }}</span>
                                </td>
                                <td class="py-3 pr-4">{{ (int)($s->packages_done_count ?? 0) }}/3</td>
                                <td class="py-3 pr-4">{{ $s->skor_avg !== null ? number_format((float)$s->skor_avg, 2) : '-' }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($s->packageAttempts as $a)
                                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                Paket {{ $a->package?->paket_no }}: {{ $a->status === 'selesai' ? ($a->skor ?? '-') : '...' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
