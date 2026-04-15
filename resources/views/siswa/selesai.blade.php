@extends('layouts.guest')

@section('content')
<div class="flex items-center justify-center">
    <div class="w-full max-w-md rounded-2xl border border-white/70 bg-white/90 p-5 text-center shadow-card sm:p-6">
        <h1 class="text-2xl font-bold mb-4">Ujian Selesai</h1>
        <p class="mb-6">Terima kasih telah mengikuti ujian.<br>Jawaban Anda telah direkam.</p>
        <a href="/" class="inline-block px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Kembali ke Beranda</a>
    </div>
</div>
@endsection
