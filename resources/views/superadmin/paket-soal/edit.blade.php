@extends('layouts.superadmin')

@section('title', 'Edit Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Edit Metadata</span>
        <h1 class="page-title">{{ $paket->nama }}</h1>
        <p class="page-description">Perbarui jenjang, tahun ajaran, nama paket, dan status aktif.</p>
    </section>

    <section class="card">
        @include('partials.paket-soal-form', [
            'action' => route('superadmin.paket-soal.update', $paket),
            'method' => 'PUT',
            'submitLabel' => 'Perbarui Paket',
            'cancelUrl' => route('superadmin.paket-soal.show', $paket),
        ])
    </section>
</div>
@endsection
