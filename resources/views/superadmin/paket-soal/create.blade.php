@extends('layouts.superadmin')

@section('title', 'Buat Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Paket Baru</span>
        <h1 class="page-title">Buat paket lengkap per jenjang dan inisialisasi 4 bagian otomatis.</h1>
        <p class="page-description">Setiap paket baru akan langsung berisi Bahasa Indonesia, Matematika, Survey Karakter, dan Sulingjar.</p>
    </section>

    <section class="card">
        @include('partials.paket-soal-form', [
            'action' => route('superadmin.paket-soal.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Paket',
            'cancelUrl' => route('superadmin.paket-soal.index'),
        ])
    </section>
</div>
@endsection
