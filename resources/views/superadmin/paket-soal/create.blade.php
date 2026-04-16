@extends('layouts.superadmin')

@section('title', 'Buat Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Paket Baru</span>
        <h1 class="page-title">Buat paket soal dan inisialisasi dua mapel otomatis.</h1>
        <p class="page-description">Matematika dan Bahasa Indonesia akan langsung disiapkan dengan konfigurasi default 30 soal dan 75 menit.</p>
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
