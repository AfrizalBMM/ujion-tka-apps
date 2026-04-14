@extends('layouts.guru')
@section('title', 'Hasil Ujian')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold mb-4">Hasil Ujian: {{ $exam->judul }}</h1>
    <div class="card p-4 mb-4">
        <div class="font-bold">Skor Anda:</div>
        <div class="text-3xl text-blue-700 font-bold">{{ $result ? $result['skor'] : '-' }}</div>
    </div>
    <div class="card p-4">
        <h2 class="font-semibold mb-2">Pembahasan Soal</h2>
        <ul class="space-y-2">
            @forelse ($pembahasan as $p)
                <li class="border-b pb-2">
                    <div class="font-bold">{{ $p['pertanyaan'] }}</div>
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