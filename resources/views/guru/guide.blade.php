@extends('layouts.guru')
@section('title', 'Panduan Guru')
@section('content')
<div class="max-w-3xl space-y-8">
    <h1 class="text-2xl font-bold mb-4">Panduan Penggunaan Guru</h1>
    <div class="card p-6 space-y-4">
        <h2 class="font-semibold">Menu & Fitur</h2>
        <ul class="list-disc ml-6 space-y-1">
            <li><b>Dashboard:</b> Statistik aktivitas, info pengumuman.</li>
            <li><b>Profil:</b> Edit profil, ganti password, avatar.</li>
            <li><b>Materi:</b> Lihat & bookmark materi sesuai jenjang.</li>
            <li><b>Bank Soal Pribadi:</b> CRUD soal, builder fullscreen.</li>
            <li><b>Ujian:</b> Join ujian, lihat hasil & pembahasan.</li>
            <li><b>Live Chat:</b> Chat dengan superadmin/operator.</li>
            <li><b>Log Aktivitas:</b> Riwayat aktivitas pribadi.</li>
        </ul>
    </div>
    <div class="card p-6 space-y-4">
        <h2 class="font-semibold">Tips & FAQ</h2>
        <ul class="list-disc ml-6 space-y-1">
            <li>Gunakan <b>builder soal</b> untuk membuat soal dengan mudah.</li>
            <li>Bookmark materi favorit untuk akses cepat.</li>
            <li>Periksa <b>log aktivitas</b> untuk keamanan akun.</li>
            <li>Manfaatkan <b>dark mode</b> dan pengaturan font untuk kenyamanan.</li>
        </ul>
    </div>
</div>
@endsection
