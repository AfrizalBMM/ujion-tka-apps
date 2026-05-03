@extends('layouts.guru')
@section('title', 'Materi')
@section('content')
<div class="space-y-6">
  <section class="page-hero">
    <span class="page-kicker">Materi</span>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="page-title">Materi Pembelajaran Interaktif</h1>
        <p class="page-description">Akses materi sesuai jenjang Anda, Anda melihat materi jenjang
          <strong>{{ $jenjangUser ?? '-' }}</strong>, termasuk materi global yang ditetapkan
          untuk jenjang yang sama.
        </p>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="hero-chip">
          <i class="fa-solid fa-book-open-reader"></i>
          Materi terstruktur
        </div>
        <div class="hero-chip">
          <i class="fa-solid fa-star"></i>
          Bookmark materi favorit
        </div>
      </div>
    </div>
    <div class="page-actions">
      <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('guru.personal-questions') }}"
          class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
          <i class="fa-solid fa-database"></i>
          Bank Soal Pribadi
        </a>
        @php
          $nextBookmarked = request()->boolean('bookmarked') ? null : 1;
          $bookmarkUrl = route('guru.materials', array_filter(array_merge(request()->query(), [
            'bookmarked' => $nextBookmarked,
          ]), fn ($v) => $v !== null && $v !== ''));
        @endphp
        <a href="{{ $bookmarkUrl }}"
          class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
          <i class="{{ request()->boolean('bookmarked') ? 'fa-solid' : 'fa-regular' }} fa-bookmark"></i>
          {{ request()->boolean('bookmarked') ? 'Bookmark Saya' : 'Tampilkan Bookmark' }}
          <span class="badge-info">{{ is_array($bookmarks ?? null) ? count($bookmarks) : 0 }}</span>
        </a>
      </div>
    </div>
  </section>
  <form method="GET" action="{{ route('guru.materials') }}"
    class="card p-4 space-y-4 sm:space-y-0 sm:flex sm:items-end sm:gap-4" data-ssd-autosubmit
    data-materials-filter-form>
    @if(request()->boolean('bookmarked'))
      <input type="hidden" name="bookmarked" value="1">
    @endif
    <div class="flex-1 min-w-[150px]">
      <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
      <div class="ssd-wrap mt-1">
        <input type="hidden" name="mapel" value="{{ $filters['mapel'] ?? '' }}">
        <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
          <span class="ssd-label">{{ ($filters['mapel'] ?? '') ?: 'Semua Mapel' }}</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
        </button>
        <div class="ssd-panel">
          <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search"
              placeholder="Cari mapel..."></div>
          <div class="ssd-list">
            <div class="ssd-option" data-value="">Semua Mapel</div>
            @foreach($mapels as $m)
            <div class="ssd-option{{ ($filters['mapel'] ?? '') === $m ? ' ssd-selected' : '' }}" data-value="{{ $m }}">
              {{ $m }}
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
    <div class="flex-1 min-w-[150px]">
      <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
      <div class="ssd-wrap mt-1">
        <input type="hidden" name="curriculum" value="{{ $filters['curriculum'] ?? '' }}">
        <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
          <span class="ssd-label">{{ ($filters['curriculum'] ?? '') ?: 'Semua Kurikulum' }}</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
        </button>
        <div class="ssd-panel">
          <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search"
              placeholder="Cari kurikulum..."></div>
          <div class="ssd-list">
            <div class="ssd-option" data-value="">Semua Kurikulum</div>
            @foreach($curriculums as $c)
            <div class="ssd-option{{ ($filters['curriculum'] ?? '') === $c ? ' ssd-selected' : '' }}"
              data-value="{{ $c }}">{{ $c }}</div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
    <div class="flex-1 min-w-[200px]">
      <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Materi</label>
      <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="input mt-1 w-full"
        placeholder="Cari materi..." data-live-search>
    </div>
    <a href="{{ route('guru.materials') }}" class="btn-secondary h-[42px] flex items-center justify-center">Reset</a>
  </form>

  <div class="space-y-4">
    @foreach($materials as $m)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-border bg-white p-5 shadow-sm hover:shadow-md transition-all dark:border-slate-800 dark:bg-slate-900">
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <span class="badge-info text-[11px]">{{ $m->curriculum }}</span>
          @if($m->mapel)
            <span class="badge-primary bg-blue-100 text-blue-700 text-[11px]">{{ $m->mapel }}</span>
          @endif
          @if($m->jenjang)
            <span class="badge-warning text-[11px]">{{ $m->jenjang }}</span>
          @endif
          @php $bankCount = (int) ($m->bank_question_count ?? 0); @endphp
          @if($bankCount > 0)
            <span class="badge-success text-[11px]">Sudah ada {{ $bankCount }} soal</span>
          @else
            <span class="badge-warning text-[11px]">Belum ada soal</span>
          @endif
          <span class="text-xs text-muted">ID: #{{ $m->id }}</span>
        </div>
        <div class="font-bold text-lg text-slate-800 dark:text-slate-200 mb-1">{{ $m->subelement }}</div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-textSecondary dark:text-slate-400">
          <span><i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $m->unit }}</span>
          <span><i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $m->sub_unit }}</span>
        </div>
      </div>
      <div class="flex flex-row gap-2 shrink-0 items-center justify-end">
        <a href="{{ route('guru.materials.show', $m) }}" class="btn-secondary p-2" title="Detail Materi">
          <i class="fa-solid fa-key"></i>
        </a>
        @if($m->link)
        <a href="{{ $m->link }}" class="btn-secondary p-2" target="_blank" rel="noopener" title="Buka Link">
          <i class="fa-solid fa-link"></i>
        </a>
        @endif
        @if(in_array($m->id, $bookmarks))
        <form method="POST" action="{{ route('guru.materials.unbookmark', $m) }}">@csrf<button class="btn-danger p-2" title="Hapus Bookmark"><i class="fa-solid fa-trash"></i></button></form>
        @else
        <form method="POST" action="{{ route('guru.materials.bookmark', $m) }}">@csrf<button class="btn-secondary p-2" title="Bookmark"><i class="fa-regular fa-bookmark"></i></button></form>
        @endif
      </div>
    </div>
    @endforeach
  </div>
</div>

@endsection
