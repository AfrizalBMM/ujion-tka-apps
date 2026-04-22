@extends('layouts.guest')

@section('title', 'Selesai — Ujion')

@section('content')
@php
    $totalSoal = 0;
    $dijawab   = 0;
    if (isset($session) && $session && $session->mapelPaket) {
        $totalSoal = $session->mapelPaket->soals->count();
        $dijawab   = $session->jawabanSiswas->filter(function($j) {
            if ($j->tipe_soal === 'pilihan_ganda') return !empty($j->jawaban_pg);
            if ($j->tipe_soal === 'menjodohkan') return !empty($j->jawaban_menjodohkan);
            return false;
        })->count();
    }
@endphp

<div class="w-full max-w-md space-y-5">
    <div class="rounded-3xl border border-white/80 bg-white/90 p-8 text-center shadow-card">
        {{-- Icon --}}
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl shadow-inner">
            ✅
        </div>

        <h1 class="text-2xl font-bold text-slate-900">{{ $session?->mapelPaket?->isSurvey() ? 'Survey Selesai!' : 'Ujian Selesai!' }}</h1>
        <p class="mt-2 text-sm text-textSecondary">
            {{ (isset($session) && $session->nama) ? 'Terima kasih, ' . $session->nama . '.' : 'Terima kasih.' }}
            Jawaban Anda sudah berhasil disimpan.
        </p>

        @if(isset($session) && $session->skor !== null)
            <div class="mt-6">
                <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">{{ $session?->mapelPaket?->isSurvey() ? 'Kelengkapan Respons' : 'Estimasi Skor Mapel' }}</div>
                <div class="mt-2 text-5xl font-black text-indigo-600">
                    {{ number_format((float) $session->skor, 1) }}
                </div>
                <div class="text-sm text-textSecondary">/ 100</div>
            </div>
        @endif

        @if($totalSoal > 0)
            <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl bg-emerald-50 p-3">
                    <div class="text-xl font-bold text-emerald-700">{{ $dijawab }}</div>
                    <div class="text-xs text-emerald-600">Soal Dijawab</div>
                </div>
                <div class="rounded-2xl bg-rose-50 p-3">
                    <div class="text-xl font-bold text-rose-700">{{ $totalSoal - $dijawab }}</div>
                    <div class="text-xs text-rose-600">Tidak Dijawab</div>
                </div>
            </div>
        @endif

        <div class="mt-6 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-left text-xs text-amber-800">
            <p class="font-semibold">Jika ada bagian berikutnya:</p>
            <p class="mt-1">Tunggu token berikutnya dari guru/pengawas, kemudian masukkan token tersebut di halaman masuk ujian.</p>
        </div>

        <div class="mt-6">
            <a href="{{ route('siswa.login') }}" class="btn-primary inline-flex px-8 py-3">
                Masuk Bagian Berikutnya
            </a>
        </div>
        <div class="mt-3">
            <a href="/" class="text-sm text-textSecondary underline underline-offset-2">Kembali ke Beranda</a>
        </div>
    </div>
</div>
@endsection
