@extends('layouts.superadmin')
@section('title', 'Analisis Ujian')
@section('content')
<div class="max-w-4xl space-y-8">
    <div>
        <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ $exam->assessment_label }}</div>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $exam->judul }}</h1>
        <p class="mt-1 text-sm text-textSecondary">Ringkasan hasil lintas bagian untuk ujian atau survey ini.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card p-6">
            <div class="text-sm font-bold text-slate-500">Peserta Selesai</div>
            <div class="mt-2 text-3xl font-bold text-slate-900">{{ $participantsCount }}</div>
        </div>
        <div class="card p-6">
            <div class="text-sm font-bold text-slate-500">{{ $scoreMetricLabel }}</div>
            <div class="mt-2 text-3xl font-bold text-blue-700">{{ number_format($averageScore, 2) }}</div>
        </div>
    </div>
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Ringkasan per Bagian</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($mapelSummaries as $summary)
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
                    <div class="font-bold text-slate-900">{{ $summary['label'] }}</div>
                    <div class="mt-2 text-sm text-textSecondary">{{ $summary['participants'] }} peserta selesai</div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span>{{ $summary['metric_label'] }}</span>
                        <strong>{{ number_format($summary['average'], 2) }}</strong>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-sm">
                        <span>{{ $summary['highest_label'] }}</span>
                        <strong>{{ number_format($summary['highest'], 2) }}</strong>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Ranking Peserta</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[420px]">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama</th>
                    <th>Bagian</th>
                    <th>Nilai / Kelengkapan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ranking as $i => $p)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $p['name'] }}</td>
                        <td>{{ $p['mapel'] }}</td>
                        <td>{{ $p['score'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-400">Belum ada peserta yang menyelesaikan ujian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">{{ $distributionTitle }}</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[420px]">
            <thead>
                <tr>
                    <th>Rentang Hasil</th>
                    <th>Jumlah Peserta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($distribution as $range => $count)
                <tr>
                    <td>{{ $range }}</td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="flex flex-col justify-end gap-2 sm:flex-row">
        <a href="{{ route('superadmin.exams.analysis.export-csv', $exam) }}" class="btn-primary w-full sm:w-auto">Export CSV</a>
        <a href="{{ route('superadmin.exams.analysis.print', $exam) }}" target="_blank" rel="noopener" class="btn-secondary w-full sm:w-auto">Versi Cetak</a>
    </div>
</div>
@endsection
