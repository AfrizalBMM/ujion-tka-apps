@extends('layouts.guru')

@section('title', 'Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Paket Relevan</span>
        <h1 class="page-title">Paket soal untuk jenjang {{ auth()->user()->jenjang ?? '-' }}</h1>
        <p class="page-description">Guru hanya melihat dan mengelola paket yang sesuai dengan jenjang yang diajar.</p>
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
