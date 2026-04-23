@extends('layouts.guru')
@section('title', 'Soal dari Ujion')
@section('content')
<div class="space-y-6">
  <section class="page-hero">
    <span class="page-kicker">Soal dari Ujion</span>
    <h1 class="page-title">Bank Soal Global Ujion</h1>
    <p class="page-description">Soal-soal ini disusun oleh tim Ujion dan hanya dapat dilihat. Pilih soal dari sini saat membuat paket soal.</p>
  </section>
  <div class="card p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end mb-4">
      <div class="flex-1 min-w-[150px]">
        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
        <input type="text" name="search" value="{{ request('search') }}" class="input mt-1 w-full" placeholder="Kata kunci pertanyaan...">
      </div>
      <div class="flex-1 min-w-[150px]">
        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
        <select name="mapel" class="input mt-1 w-full">
          <option value="">Semua Mapel</option>
          @foreach($mapels as $m)
          <option value="{{ $m }}" @selected(request('mapel')==$m)> {{ $m }} </option>
          @endforeach
        </select>
      </div>
      <div class="flex-1 min-w-[150px]">
        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
        <select name="curriculum" class="input mt-1 w-full">
          <option value="">Semua Kurikulum</option>
          @foreach($curriculums as $c)
          <option value="{{ $c }}" @selected(request('curriculum')==$c)> {{ $c }} </option>
          @endforeach
        </select>
      </div>
      <button class="btn-secondary h-[42px] flex items-center justify-center" type="submit">Filter</button>
    </form>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      @forelse($questions as $question)
      <div class="card p-4 flex flex-col">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <div class="text-[10px] font-bold text-primary uppercase tracking-wider mb-0.5">{{ $question->material_mapel }}</div>
            <div class="font-bold">{{ $question->material_subelement }}</div>
          </div>
          <span class="badge-info shrink-0">Soal Ujion</span>
        </div>
        <div class="mt-1 text-sm text-textSecondary">{!! Str::limit(strip_tags($question->question_text), 120) !!}</div>
        <div class="mt-2 text-xs text-textSecondary mb-3 flex flex-wrap gap-x-3 gap-y-1">
          <span>Kurikulum: {{ $question->material_curriculum }}</span>
          <span>|</span>
          <span>Jenjang: {{ $question->jenjang?->nama ?? '-' }}</span>
        </div>
        <a href="{{ route('guru.soal-ujion.show', $question) }}" class="btn-primary w-full text-center">Lihat Detail</a>
      </div>
      @empty
      <div class="col-span-full text-center text-textSecondary py-8">Tidak ada soal ditemukan.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection