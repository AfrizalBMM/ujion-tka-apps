@extends('layouts.superadmin')

@section('title', 'Buat Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Paket Baru</span>
        <h1 class="page-title">Buat paket soal dan inisialisasi empat komponen otomatis.</h1>
        <p class="page-description">Matematika, Bahasa Indonesia, Survey Karakter, dan Survey Lingkungan Belajar akan langsung disiapkan dengan konfigurasi awal.</p>
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
