@extends('layouts.superadmin')
@section('title', 'Analisis Ujian')
@section('content')
<div class="max-w-4xl space-y-8">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card p-6">
            <div class="text-sm font-bold text-slate-500">Peserta Selesai</div>
            <div class="mt-2 text-3xl font-bold text-slate-900">{{ $participantsCount }}</div>
        </div>
        <div class="card p-6">
            <div class="text-sm font-bold text-slate-500">Rata-rata Skor</div>
            <div class="mt-2 text-3xl font-bold text-blue-700">{{ number_format($averageScore, 2) }}</div>
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
                    <th>Skor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ranking as $i => $p)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $p['name'] }}</td>
                        <td>{{ $p['score'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-gray-400">Belum ada peserta yang menyelesaikan ujian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Distribusi Nilai</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[420px]">
            <thead>
                <tr>
                    <th>Rentang Nilai</th>
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
