@extends('errors.minimal')

@section('title', 'Sesi Anda Berakhir')
@section('code', '419')
@section('message', 'Sesi login Anda sudah berakhir karena tidak aktif terlalu lama. Data yang belum tersimpan tidak hilang — silakan login ulang, lalu ulangi aksi terakhir Anda.')

@section('actions')
    @php
        $user = auth()->user();
        $dashboardRoute = $user?->isSuperadmin() ? route('superadmin.dashboard') : ($user?->isGuru() ? route('guru.dashboard') : null);
    @endphp
    <a class="btn btn-primary" href="{{ route('landing') }}">Login Ulang</a>
    @if($dashboardRoute)
        <a class="btn" href="{{ $dashboardRoute }}">Ke Dashboard Saya</a>
    @endif
    <button class="btn" type="button" onclick="history.back()">Kembali</button>
@endsection
