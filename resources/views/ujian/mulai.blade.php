@extends('layouts.guest')

@section('title', 'Petunjuk Ujian — Ujion')

@section('content')
<div class="w-full max-w-xl space-y-5">
    <div class="rounded-3xl border border-white/80 bg-white/90 p-6 shadow-card">
        {{-- Header --}}
        <div class="mb-6 flex items-start gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-lg font-black text-white shadow">U</div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ $exam->judul }}</p>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $mapel->nama_label }}</h1>
                <p class="mt-1 text-sm text-textSecondary">Peserta: <strong>{{ $session->nama }}</strong></p>
            </div>
        </div>

        {{-- Info Mapel --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 text-center">
                <div class="text-2xl font-black text-indigo-600">{{ $mapel->jumlah_soal }}</div>
                <div class="mt-1 text-xs text-textSecondary">Jumlah Soal</div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 text-center">
                <div class="text-2xl font-black text-indigo-600">{{ $mapel->durasi_menit }}'</div>
                <div class="mt-1 text-xs text-textSecondary">Durasi (menit)</div>
            </div>
        </div>

        {{-- Petunjuk --}}
        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-amber-700">Petunjuk Pengerjaan</p>
            <ul class="space-y-1.5 text-sm text-amber-900">
                <li>• Kerjakan soal sesuai perintah pada tiap butir.</li>
                <li>• Jawaban tersimpan otomatis setiap 30 detik.</li>
                <li>• Waktu berjalan terus, tidak bisa dijeda.</li>
                <li>• Tandai ragu-ragu jika belum yakin, tinjau kembali sebelum selesai.</li>
                <li>• Saat waktu habis, ujian akan otomatis diselesaikan.</li>
            </ul>
        </div>

        {{-- Aksi --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('siswa.ujian') }}" class="btn-primary flex-1 py-3 text-center font-bold">
                Mulai Mengerjakan &rarr;
            </a>
            <a href="{{ route('siswa.login') }}" class="btn-secondary px-5 py-3">Batal</a>
        </div>
    </div>
</div>
@endsection
