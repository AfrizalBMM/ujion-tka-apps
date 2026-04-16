@extends('layouts.guru')

@section('title', 'Edit Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->nama }} &middot; {{ $mapel->nama_label }}</span>
        <h1 class="page-title">Edit soal {{ $soal->nomor_soal }}</h1>
        <p class="page-description">Perbarui detail soal, pasangan, pilihan, dan media pendukungnya.</p>
    </section>

    <section class="card">
        @include('partials.soal-form', [
            'action' => route('guru.soal.update', [$paket, $mapel, $soal]),
            'method' => 'PUT',
            'submitLabel' => 'Perbarui Soal',
            'cancelUrl' => route('guru.soal.index', [$paket, $mapel]),
        ])
    </section>
</div>
@endsection
