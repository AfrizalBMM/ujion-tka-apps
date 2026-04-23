@extends('layouts.guru')
@section('title', 'Detail Soal Ujion')
@section('content')
<div class="space-y-6">
  <section class="page-hero">
    <span class="page-kicker">Soal dari Ujion</span>
    <h1 class="page-title">Detail Soal</h1>
    <p class="page-description">Soal ini hanya dapat dilihat. Untuk menggunakan, silakan pilih saat membuat paket soal.</p>
  </section>
  <div class="card p-6">
    <div class="mb-3">
      <span class="badge-info">{{ $question->material_mapel }}</span>
      <span class="ml-2 text-xs text-textSecondary">Kurikulum: {{ $question->material_curriculum }}</span>
      <span class="ml-2 text-xs text-textSecondary">Jenjang: {{ $question->jenjang?->nama ?? '-' }}</span>
    </div>
    <div class="mb-4">
      <div class="font-bold mb-2">Pertanyaan:</div>
      <div>{!! $question->question_text !!}</div>
    </div>
    @if($question->reading_passage)
    <div class="mb-4">
      <div class="font-bold mb-2">Teks Bacaan:</div>
      <div>{!! $question->reading_passage !!}</div>
    </div>
    @endif
    @if($question->options)
    <div class="mb-4">
      <div class="font-bold mb-2">Pilihan Jawaban:</div>
      <ul class="list-disc ml-6">
        @foreach($question->options as $opt)
        <li>{{ $opt }}</li>
        @endforeach
      </ul>
    </div>
    @endif
    @if($question->answer_key)
    <div class="mb-4">
      <div class="font-bold mb-2">Kunci Jawaban:</div>
      <div>{{ $question->answer_key }}</div>
    </div>
    @endif
    @if($question->explanation)
    <div class="mb-4">
      <div class="font-bold mb-2">Pembahasan:</div>
      <div>{{ $question->explanation }}</div>
    </div>
    @endif
    <a href="{{ route('guru.soal-ujion.index') }}" class="btn-secondary mt-4">Kembali ke Daftar Soal</a>
  </div>
</div>
@endsection