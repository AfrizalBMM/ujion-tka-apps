@extends('layouts.guest')

@section('title', 'Mulai Ujian')

@section('content')
<div class="space-y-6">
    <div class="rounded-[28px] border border-white/80 bg-white/90 p-6 shadow-card">
        <div class="text-xs font-bold uppercase tracking-[0.24em] text-textSecondary">Konfirmasi Ujian</div>
        <h1 class="mt-3 text-2xl font-bold">{{ $exam->judul }}</h1>
        <p class="mt-2 text-sm text-textSecondary">Peserta: {{ $session->nama }} &middot; Paket: {{ $paket->nama }}</p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach($paket->mapelPakets as $mapel)
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
                    <div class="font-semibold">{{ $mapel->nama_label }}</div>
                    <div class="mt-2 text-sm text-textSecondary">{{ $mapel->jumlah_soal }} soal &middot; {{ $mapel->durasi_menit }} menit</div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            Waktu disimpan per mapel. Saat sisa waktu tinggal 5 menit, sistem akan memberi peringatan dan jawaban akan tersimpan otomatis saat waktu habis.
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('siswa.ujian') }}" class="btn-primary">Mulai Mengerjakan</a>
            <a href="{{ route('siswa.login') }}" class="btn-secondary">Batal</a>
        </div>
    </div>
</div>
@endsection
