@extends('errors.minimal')

@section('title', 'Terlalu Banyak Percobaan')
@section('code', '429')
@section('message', 'Anda melakukan terlalu banyak percobaan dalam waktu singkat. Demi keamanan akun, mohon tunggu sebentar (maksimal 1 menit) sebelum mencoba lagi.')

@section('actions')
    <button class="btn btn-primary" type="button" onclick="setTimeout(() => location.reload(), 60000); this.disabled = true; this.textContent = 'Menunggu 60 detik...'">Coba Lagi dalam 1 Menit</button>
    <button class="btn" type="button" onclick="history.back()">Kembali</button>
@endsection
