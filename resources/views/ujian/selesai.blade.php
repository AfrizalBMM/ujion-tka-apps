@extends('layouts.guest')

@section('title', 'Ujian Selesai')

@section('content')
<div class="flex items-center justify-center">
    <div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/90 p-6 text-center shadow-card">
        <div class="text-xs font-bold uppercase tracking-[0.24em] text-textSecondary">Selesai</div>
        <h1 class="mt-3 text-3xl font-bold">Jawaban berhasil direkam</h1>
        <p class="mt-3 text-sm text-textSecondary">Terima kasih telah mengikuti ujian. {{ $session?->nama ? 'Sesi untuk '.$session->nama.' sudah ditutup.' : '' }}</p>
        @if($session?->skor !== null)
            <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                Estimasi skor: {{ number_format((float) $session->skor, 2) }}
            </div>
        @endif
        <div class="mt-6">
            <a href="/" class="btn-primary inline-flex">Kembali ke Beranda</a>
        </div>
    </div>
</div>
@endsection
