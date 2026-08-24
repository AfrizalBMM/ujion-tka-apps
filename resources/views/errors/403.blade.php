@extends('errors.minimal')

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('message', 'Anda tidak memiliki izin untuk membuka halaman ini. Jika menurut Anda ini keliru, silakan hubungi admin atau login ulang dengan akun yang sesuai.')

@section('actions')
    @php
        $user = auth()->user();
        $dashboardRoute = $user?->isSuperadmin() ? route('superadmin.dashboard') : ($user?->isGuru() ? route('guru.dashboard') : null);
    @endphp
    @if($dashboardRoute)
        <a class="btn btn-primary" href="{{ $dashboardRoute }}">Ke Dashboard Saya</a>
    @else
        <a class="btn btn-primary" href="{{ route('landing') }}">Ke Beranda</a>
    @endif
    <button class="btn" type="button" onclick="history.back()">Kembali</button>
@endsection
