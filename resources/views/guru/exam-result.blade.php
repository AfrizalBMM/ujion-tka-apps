@extends('layouts.guru')
@section('title', 'Hasil Simulasi')
@section('content')
<div class="max-w-3xl space-y-6">
    <h1 class="text-2xl font-bold mb-4">Hasil Simulasi: {{ $exam->judul }}</h1>
    <div class="card p-4 mb-4">
        <div class="font-bold">Skor simulasi Anda:</div>
        <div class="text-3xl text-blue-700 font-bold">{{ $result ? $result['skor'] : '-' }}</div>
        @if($result['waktu_selesai'] ?? null)
            <div class="mt-2 text-sm text-gray-500">Selesai pada {{ $result['waktu_selesai']->format('d M Y H:i') }}</div>
        @endif
    </div>
    <div class="card p-4">
        <h2 class="font-semibold mb-2">Pembahasan untuk Evaluasi Guru</h2>
        <ul class="space-y-2">
            @forelse ($pembahasan as $p)
                <li class="border-b pb-2">
                    <div class="text-xs font-bold uppercase text-blue-600">{{ $p['mapel'] }}</div>
                    <div class="font-bold">{{ $p['pertanyaan'] }}</div>
                    <div class="text-slate-700">Jawaban Anda: {{ $p['jawaban_user'] }}</div>
                    <div class="text-green-700">Jawaban Benar: {{ $p['jawaban_benar'] }}</div>
                    <div class="text-gray-700">Pembahasan: {{ $p['pembahasan'] }}</div>
                </li>
            @empty
                <li class="text-gray-400">Belum ada pembahasan.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
