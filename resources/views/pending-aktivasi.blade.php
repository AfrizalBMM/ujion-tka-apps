@extends('layouts.guest')

@section('content')
<div class="card mx-auto max-w-xl text-center">
    <h2 class="text-2xl font-bold mb-4 text-blue-700">Registrasi Berhasil!</h2>
    <p class="mb-6 text-gray-600 font-medium">Data Anda telah kami terima dan sedang dalam antrean proses.<br>Silakan lakukan pembayaran sesuai instruksi di bawah ini.</p>
    <div class="mb-6">
        <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 sm:p-6">
            <img src="{{ $qr_url ?? asset('img/qr-placeholder.png') }}" alt="QR Pembayaran" class="mb-4 h-40 w-40 object-contain shadow-sm sm:h-48 sm:w-48">
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
