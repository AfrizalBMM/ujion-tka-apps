@extends('layouts.superadmin')

@section('title', 'Tambah Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->nama }} &middot; {{ $mapel->nama_label }}</span>
        <h1 class="page-title">Tambah soal baru</h1>
        <p class="page-description">Form ini mendukung pilihan ganda dan menjodohkan dalam satu alur kerja.</p>
    </section>

    <section class="card">
        @include('partials.soal-form', [
            'action' => route('superadmin.soal.store', [$paket, $mapel]),
            'method' => 'POST',
            'submitLabel' => 'Simpan Soal',
            'cancelUrl' => route('superadmin.soal.index', [$paket, $mapel]),
        ])
    </section>
</div>
@endsection
