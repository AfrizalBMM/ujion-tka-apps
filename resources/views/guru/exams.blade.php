@extends('layouts.guru')
@section('title', 'Simulasi Ujian')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Simulasi Ujian</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">
            Gunakan halaman ini untuk mencoba alur ujian dari sisi siswa, mengecek kesiapan paket soal, dan melihat pembahasan
            sebagai bahan evaluasi sebelum dipakai untuk pembinaan siswa.
        </p>
    </div>
    <form method="POST" action="{{ route('guru.exams.join') }}" class="card mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label class="text-xs font-bold uppercase tracking-wide text-muted">Token simulasi</label>
            <input name="token" class="input w-full" placeholder="Masukkan token ujian untuk mencoba alur siswa" required>
        </div>
        <button class="btn-primary w-full sm:w-auto" type="submit">
            <i class="fa-solid fa-play"></i>
            Mulai Simulasi
        </button>
    </form>
    <div class="card p-4 mb-4">
        <div class="mb-2">
            <h2 class="font-semibold">Ujian yang Bisa Dicoba</h2>
            <p class="mt-1 text-sm text-textSecondary dark:text-slate-400">Daftar ujian aktif yang bisa guru buka untuk melihat pengalaman siswa secara langsung.</p>
        </div>
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
        <div class="mb-2">
            <h2 class="font-semibold">Riwayat Simulasi</h2>
            <p class="mt-1 text-sm text-textSecondary dark:text-slate-400">Simulasi yang sudah pernah Anda kerjakan beserta hasil dan pembahasannya.</p>
        </div>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[620px]">
            <thead>
                <tr>
                    <th>Judul Simulasi</th>
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
                <tr><td colspan="4" class="text-gray-400">Belum ada riwayat simulasi.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
