@extends('layouts.guest')

@section('title', 'Konfirmasi Selesai — Ujion')

@section('content')
@php
    $totalSoal = $session->mapelPaket?->soals->count() ?? 0;
    $dijawab   = $session->jawabanSiswas->filter(function ($j) {
        if ($j->tipe_soal === 'pilihan_ganda') return !empty($j->jawaban_pg);
        if ($j->tipe_soal === 'menjodohkan') return !empty($j->jawaban_menjodohkan);
        return false;
    })->count();
    $belumDijawab = max($totalSoal - $dijawab, 0);
    $menit = intdiv($remainingSeconds, 60);
    $detik = $remainingSeconds % 60;
@endphp

<div class="w-full max-w-md space-y-5">
    <div class="rounded-3xl border border-white/80 bg-white/90 p-8 text-center shadow-card">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl shadow-inner">
            ⏳
        </div>

        <h1 class="text-2xl font-bold text-slate-900">Selesaikan Ujian?</h1>
        <p class="mt-2 text-sm text-textSecondary">
            Waktu Anda masih berjalan. Setelah selesai, jawaban tidak bisa diubah lagi.
        </p>

        @if($totalSoal > 0)
            <div class="mt-6 grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-2xl bg-emerald-50 p-3">
                    <div class="text-xl font-bold text-emerald-700">{{ $dijawab }}</div>
                    <div class="text-xs text-emerald-600">Dijawab</div>
                </div>
                <div class="rounded-2xl bg-rose-50 p-3">
                    <div class="text-xl font-bold text-rose-700">{{ $belumDijawab }}</div>
                    <div class="text-xs text-rose-600">Belum Dijawab</div>
                </div>
                <div class="rounded-2xl bg-indigo-50 p-3">
                    <div class="text-xl font-bold text-indigo-700">{{ sprintf('%02d:%02d', $menit, $detik) }}</div>
                    <div class="text-xs text-indigo-600">Sisa Waktu</div>
                </div>
            </div>
        @endif

        @if($belumDijawab > 0)
            <div class="mt-5 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-left text-xs text-amber-800">
                <p class="font-semibold">Masih ada {{ $belumDijawab }} soal belum dijawab.</p>
                <p class="mt-1">Soal yang tidak dijawab akan dinilai salah. Anda bisa kembali dan melengkapi dulu.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('siswa.selesai.submit') }}" class="mt-6">
            @csrf
            <button type="submit" class="btn-primary inline-flex w-full px-8 py-3">
                Ya, Selesai &amp; Lihat Hasil
            </button>
        </form>

        <div class="mt-3">
            <a href="{{ route('siswa.ujian') }}" class="text-sm font-semibold text-indigo-600 underline underline-offset-2">Kembali mengerjakan ujian</a>
        </div>
    </div>
</div>
@endsection
