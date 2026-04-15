@extends('layouts.guest')

@section('content')
<div class="flex flex-col items-center justify-center">
    <div class="w-full max-w-lg space-y-6 rounded-2xl border border-white/70 bg-white/90 p-5 shadow-card sm:p-6">
        <h2 class="text-2xl font-bold text-center mb-4">Petunjuk Ujian</h2>
        <div class="mb-6 text-gray-700">
            <ul class="list-disc pl-6 space-y-2">
                <li>Baca setiap soal dengan seksama.</li>
                <li>Kerjakan soal sesuai waktu yang tersedia.</li>
                <li>Jangan menutup atau berpindah tab selama ujian berlangsung.</li>
                <li>Gunakan tombol <b>Ragu-ragu</b> jika belum yakin dengan jawaban.</li>
                <li>Tekan <b>Selesaikan</b> jika sudah selesai mengerjakan semua soal.</li>
            </ul>
        </div>
        <form method="POST" action="{{ route('siswa.mulai') }}">
            @csrf
            <button type="submit" class="w-full py-2 font-semibold text-white bg-green-600 rounded hover:bg-green-700">Mulai Ujian</button>
        </form>
    </div>
</div>
@endsection
