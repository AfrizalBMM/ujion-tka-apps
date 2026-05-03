@extends('layouts.guru')
@section('title', 'Soal dari Ujion')
@section('content')
<div class="space-y-6">
  <section class="page-hero">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <span class="page-kicker">Soal dari Ujion</span>
        <h1 class="page-title">Bank Soal Global Ujion</h1>
        <p class="page-description">Soal-soal ini disusun oleh tim Ujion dan hanya dapat dilihat. Pilih soal dari sini saat membuat paket soal.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        @php
          $nextBookmarked = request()->boolean('bookmarked') ? null : 1;
          $bookmarkUrl = route('guru.soal-ujion.index', array_filter(array_merge(request()->query(), [
            'bookmarked' => $nextBookmarked,
          ]), fn ($v) => $v !== null && $v !== ''));
        @endphp
        <a href="{{ $bookmarkUrl }}" class="btn-secondary inline-flex items-center gap-2 bg-white/95">
          <i class="{{ request()->boolean('bookmarked') ? 'fa-solid' : 'fa-regular' }} fa-bookmark"></i>
          {{ request()->boolean('bookmarked') ? 'Bookmark Saya' : 'Tampilkan Bookmark' }}
          <span class="badge-info">{{ is_array($bookmarks ?? null) ? count($bookmarks) : 0 }}</span>
        </a>
      </div>
    </div>
  </section>
  <div class="card p-4">
    <form method="GET" action="{{ route('guru.soal-ujion.index') }}" class="flex flex-wrap gap-3 items-end mb-4" data-soal-ujion-filter-form>
      @if(request()->boolean('bookmarked'))
        <input type="hidden" name="bookmarked" value="1">
      @endif
      <div class="flex-1 min-w-[150px]">
        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
        <input type="text" name="search" value="{{ request('search') }}" class="input mt-1 w-full" placeholder="Kata kunci pertanyaan..." data-live-search>
      </div>
      <div class="flex-1 min-w-[150px]">
        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
        <div class="ssd-wrap mt-1">
          <input type="hidden" name="mapel" value="{{ request('mapel') }}">
          <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
            <span class="ssd-label">{{ request('mapel') ?: 'Semua Mapel' }}</span>
            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
          </button>
          <div class="ssd-panel">
            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
            <div class="ssd-list">
              <div class="ssd-option{{ !request('mapel') ? ' ssd-selected' : '' }}" data-value="">Semua Mapel</div>
              @foreach($mapels as $m)
                <div class="ssd-option{{ request('mapel') == $m ? ' ssd-selected' : '' }}" data-value="{{ $m }}">{{ $m }}</div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      <div class="flex-1 min-w-[150px]">
        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
        <div class="ssd-wrap mt-1">
          <input type="hidden" name="curriculum" value="{{ request('curriculum') }}">
          <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
            <span class="ssd-label">{{ request('curriculum') ?: 'Semua Kurikulum' }}</span>
            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
          </button>
          <div class="ssd-panel">
            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
            <div class="ssd-list">
              <div class="ssd-option{{ !request('curriculum') ? ' ssd-selected' : '' }}" data-value="">Semua Kurikulum</div>
              @foreach($curriculums as $c)
                <div class="ssd-option{{ request('curriculum') == $c ? ' ssd-selected' : '' }}" data-value="{{ $c }}">{{ $c }}</div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      <div class="w-full text-[11px] text-textSecondary dark:text-slate-400">Filter otomatis: ketik untuk mencari, atau pilih dropdown untuk menyaring.</div>
    </form>
    <div class="space-y-4">
      @forelse($questions as $question)
      @php $isBookmarked = in_array($question->id, $bookmarks ?? []); @endphp
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-border bg-white p-5 shadow-sm hover:shadow-md transition-all dark:border-slate-800 dark:bg-slate-900">
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2 mb-1">
            <span class="badge-info text-[11px]">{{ $question->material_curriculum }}</span>
            @if($question->material_mapel)
              <span class="badge-primary bg-blue-100 text-blue-700 text-[11px]">{{ $question->material_mapel }}</span>
            @endif
            @if($question->jenjang?->nama)
              <span class="badge-warning text-[11px]">{{ $question->jenjang->nama }}</span>
            @endif
            <span class="badge-info text-[11px]">Soal Ujion</span>
            <span class="text-xs text-muted">ID: #{{ $question->id }}</span>
          </div>
          <div class="font-bold text-lg text-slate-800 dark:text-slate-200 mb-1">{{ $question->material_subelement }}</div>
          <div class="flex flex-wrap items-center gap-2 text-sm text-textSecondary dark:text-slate-400">
            <span><i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {!! Str::limit(strip_tags($question->question_text), 80) !!}</span>
          </div>
        </div>
        <div class="flex flex-row gap-2 shrink-0 items-center justify-end">
          <a href="{{ route('guru.soal-ujion.show', $question) }}" class="btn-secondary p-2" title="Lihat Detail">
            <i class="fa-solid fa-eye"></i>
          </a>
          @if($isBookmarked)
          <form method="POST" action="{{ route('guru.soal-ujion.unbookmark', $question) }}">@csrf<button class="btn-danger p-2" title="Hapus Bookmark"><i class="fa-solid fa-bookmark"></i></button></form>
          @else
          <form method="POST" action="{{ route('guru.soal-ujion.bookmark', $question) }}">@csrf<button class="btn-secondary p-2" title="Bookmark"><i class="fa-regular fa-bookmark"></i></button></form>
          @endif
        </div>
      </div>
      @empty
      <div class="text-center text-textSecondary py-8">Tidak ada soal ditemukan.</div>
      @endforelse
    </div>
  </div>
</div>

@endsection
