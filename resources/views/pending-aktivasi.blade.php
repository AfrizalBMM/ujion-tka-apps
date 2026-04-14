@extends('layouts.guest')

@section('content')
<div class="max-w-xl mx-auto mt-10 p-6 bg-white rounded shadow text-center">
    <h2 class="text-2xl font-bold mb-4 text-blue-700">Registrasi Berhasil!</h2>
    <p class="mb-6 text-gray-600 font-medium">Data Anda telah kami terima dan sedang dalam antrean proses.<br>Silakan lakukan pembayaran sesuai instruksi di bawah ini.</p>
    <div class="mb-6">
        <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center">
            <img src="{{ $qr_url ?? asset('img/qr-placeholder.png') }}" alt="QR Pembayaran" class="w-48 h-48 object-contain mb-4 shadow-sm">
            <div class="text-2xl font-bold text-slate-800">Rp{{ number_format($harga ?? 0, 0, ',', '.') }}</div>
            <div class="text-sm text-slate-500 mt-1 uppercase tracking-wider">Total Pembayaran</div>
        </div>
    </div>
    <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl text-blue-800 text-sm">
        <i class="fa-solid fa-circle-info mr-1"></i>
        Setelah pembayaran diverifikasi, akun Anda akan <strong>diaktivasi oleh Superadmin</strong>. Mohon tunggu informasi <strong>Token Akses</strong> yang akan kami kirimkan melalui <strong>WhatsApp</strong> ke nomor Anda.
    </div>
    <a href="/" class="btn-secondary w-full">Kembali ke Beranda</a>
</div>
@endsection
