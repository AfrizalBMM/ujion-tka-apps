@extends('layouts.guru')

@section('title', 'Tambah Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->nama }} &middot; {{ $mapel->nama_label }}</span>
        <h1 class="page-title">Tambah soal</h1>
        <p class="page-description">Gunakan form ini untuk menyusun butir soal sesuai revisi paket per jenjang.</p>
    </section>

    <section class="card">
        @include('partials.soal-form', [
            'action' => route('guru.soal.store', [$paket, $mapel]),
            'method' => 'POST',
            'submitLabel' => 'Simpan Soal',
            'cancelUrl' => route('guru.soal.index', [$paket, $mapel]),
        ])
    </section>
</div>
@endsection
