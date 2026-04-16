@extends('layouts.guru')
@section('title', 'Daftar Ujian')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Daftar Ujian</h1>
    <form method="POST" action="{{ route('guru.exams.join') }}" class="card mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label class="text-xs font-bold">Token Ujian</label>
            <input name="token" class="input w-full" required>
        </div>
        <button class="btn-primary w-full sm:w-auto" type="submit">Join Ujian</button>
    </form>
    <div class="card p-4 mb-4">
        <h2 class="font-semibold mb-2">Ujian Tersedia</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[520px]">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Paket</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($available as $exam)
                <tr>
                    <td>{{ $exam->judul }}</td>
                    <td>{{ $exam->paketSoal?->nama ?? '-' }}</td>
                    <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
                    <td>{{ $exam->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="card p-4 mb-4">
        <h2 class="font-semibold mb-2">Histori Ujian</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[620px]">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Skor</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $h)
                <tr>
                    <td>{{ $h['judul'] }}</td>
                    <td>{{ $h['skor'] }}</td>
                    <td>{{ $h['status'] }}</td>
                    <td><a href="{{ route('guru.exams.result', $h['exam_id']) }}" class="btn-secondary">Lihat Hasil</a></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-gray-400">Belum ada histori ujian.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
