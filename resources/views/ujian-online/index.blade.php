@extends('layouts.public')

@section('title', 'Ujian Online — Ujion TKA')
@section('description', 'Ikuti ujian dan tryout online langsung. Pilih jenjang SD, SMP, atau SMA.')

@section('content')
<section class="text-center">
    <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.24em] text-indigo-600">Ujian Online Langsung</span>
    <h1 class="mt-4 text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">Pilih Jenjang Ujian</h1>
    <p class="mx-auto mt-3 max-w-2xl text-textSecondary">Daftar, bayar online, dan kerjakan ujian langsung — tanpa perlu akun guru. Hasil dan pembahasan tersedia setelah selesai.</p>
</section>

<div class="mt-10 grid gap-6 sm:grid-cols-3">
    @foreach($jenjangs as $j)
        <a href="{{ route('ujian-online.jenjang', strtolower($j)) }}" class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm transition hover:border-indigo-300 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-4xl font-black text-slate-900 dark:text-white">{{ $j }}</div>
                    <div class="mt-1 text-sm text-textSecondary">{{ $counts[$j] ?? 0 }} ujian tersedia</div>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 text-2xl text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-900/40 dark:text-indigo-300">
                    @if($j === 'SD')<i class="fa-solid fa-1"></i>@elseif($j === 'SMP')<i class="fa-solid fa-2"></i>@else<i class="fa-solid fa-3"></i>@endif
                </div>
            </div>
            <div class="mt-4 text-sm font-semibold text-indigo-600">Lihat ujian <i class="fa-solid fa-arrow-right ml-1 transition group-hover:translate-x-1"></i></div>
        </a>
    @endforeach
</div>

<div class="mt-12 rounded-3xl border border-indigo-100 bg-indigo-50/50 p-8 dark:border-indigo-900/30 dark:bg-indigo-950/20">
    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Cara Kerja</h2>
    <div class="mt-4 grid gap-6 sm:grid-cols-4">
        <div class="text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-white font-bold">1</div>
            <p class="mt-2 text-sm text-textSecondary">Pilih ujian & mapel</p>
        </div>
        <div class="text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-white font-bold">2</div>
            <p class="mt-2 text-sm text-textSecondary">Isi nama & nomor WA</p>
        </div>
        <div class="text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-white font-bold">3</div>
            <p class="mt-2 text-sm text-textSecondary">Bayar via Midtrans</p>
        </div>
        <div class="text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-white font-bold">4</div>
            <p class="mt-2 text-sm text-textSecondary">Kerjakan & lihat hasil</p>
        </div>
    </div>
</div>
@endsection
