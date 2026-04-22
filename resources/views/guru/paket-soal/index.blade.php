@extends('layouts.guru')

@section('title', 'Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Paket Relevan</span>
        <h1 class="page-title">Paket soal untuk jenjang {{ auth()->user()->jenjang ?? '-' }}</h1>
        <p class="page-description">Setiap paket untuk jenjang Anda sudah memuat 4 bagian baku: Bahasa Indonesia, Matematika, Survey Karakter, dan Sulingjar.</p>
    </section>

    <section class="card">
        @php
            $activeFilterCount = collect([
                ($search ?? '') !== '' ? $search : null,
            ])->filter()->count();
        @endphp
        <div class="mb-5 flex items-center gap-3">
            <div class="font-bold text-lg">Filter Paket</div>
            @if ($activeFilterCount > 0)
                <span class="badge-info text-xs">{{ $activeFilterCount }} filter aktif</span>
                <a href="{{ route('guru.paket-soal.index') }}" class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
                    <i class="fa-solid fa-xmark"></i> Reset
                </a>
            @endif
        </div>
        <form method="GET" action="{{ route('guru.paket-soal.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,2fr)_auto]">
            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Cari Paket</label>
                <input type="text" name="search" class="input" value="{{ $search ?? '' }}" placeholder="Nama paket atau tahun ajaran">
            </div>
            <div class="flex items-end gap-3">
                <button class="btn-primary w-full md:w-auto" type="submit">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari
                </button>
                <a href="{{ route('guru.paket-soal.index') }}" class="btn-secondary w-full md:w-auto text-center">Reset</a>
            </div>
        </form>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        @forelse($paketSoals as $paket)
            <article class="card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="section-title">{{ $paket->nama }}</h2>
                        <p class="section-description">{{ $paket->tahun_ajaran }} &middot; {{ $paket->jenjang?->kode }}</p>
                    </div>
                    @if($paket->is_active)
                        <span class="badge-success">Aktif</span>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($paket->mapelPakets as $mapel)
                        <span class="badge-info">{{ $mapel->nama_label }}</span>
                    @endforeach
                </div>
                <div class="mt-5">
                    <a href="{{ route('guru.paket-soal.show', $paket) }}" class="btn-primary px-4 py-2 text-xs">Lihat Detail</a>
                </div>
            </article>
        @empty
            <div class="empty-state lg:col-span-2">Belum ada paket yang sesuai dengan jenjang Anda.</div>
        @endforelse
    </section>
</div>
@endsection
