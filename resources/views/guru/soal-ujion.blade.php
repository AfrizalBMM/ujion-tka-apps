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
    <div id="questions-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      @forelse($questions as $question)
      <div class="card p-4 flex flex-col">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <div class="text-[10px] font-bold text-primary uppercase tracking-wider mb-0.5">{{ $question->material_mapel }}</div>
            <div class="font-bold">{{ $question->material_subelement }}</div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            @php $isBookmarked = in_array($question->id, $bookmarks ?? []); @endphp
            @if($isBookmarked)
              <form method="POST" action="{{ route('guru.soal-ujion.unbookmark', $question) }}">
                @csrf
                <button type="submit" class="icon-button h-9 w-9 rounded-2xl border-danger/30 bg-danger/10 text-danger" title="Hapus Bookmark">
                  <i class="fa-solid fa-bookmark"></i>
                </button>
              </form>
            @else
              <form method="POST" action="{{ route('guru.soal-ujion.bookmark', $question) }}">
                @csrf
                <button type="submit" class="icon-button h-9 w-9 rounded-2xl" title="Bookmark Soal">
                  <i class="fa-regular fa-bookmark"></i>
                </button>
              </form>
            @endif
            <span class="badge-info shrink-0">Soal Ujion</span>
          </div>
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
