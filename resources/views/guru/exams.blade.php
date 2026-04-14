@extends('layouts.guru')
@section('title', 'Daftar Ujian')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Daftar Ujian</h1>
    <form method="POST" action="{{ route('guru.exams.join') }}" class="card p-4 mb-4 flex gap-3 items-end">
        @csrf
        <div class="flex-1">
            <label class="text-xs font-bold">Token Ujian</label>
            <input name="token" class="input w-full" required>
        </div>
        <button class="btn-primary" type="submit">Join Ujian</button>
    </form>
    <div class="card p-4 mb-4">
        <h2 class="font-semibold mb-2">Ujian Tersedia</h2>
        <table class="table-ujion w-full">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($available as $exam)
                <tr>
                    <td>{{ $exam->judul }}</td>
                    <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
                    <td>{{ $exam->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card p-4 mb-4">
        <h2 class="font-semibold mb-2">Histori Ujian</h2>
        <table class="table-ujion w-full">
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
                    <td><a href="#" class="btn-secondary btn-xs">Lihat Hasil</a></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-gray-400">Belum ada histori ujian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection