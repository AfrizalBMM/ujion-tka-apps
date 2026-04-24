@extends('layouts.guru')
@section('title', 'Detail Soal Ujion')
@section('content')
@php
  $type = (string) ($question->question_type ?? 'multiple_choice');
  $typeLabel = match ($type) {
    'multiple_choice' => 'Pilihan Ganda',
    'essay' => 'Uraian',
    'matching' => 'Menjodohkan',
    default => strtoupper(str_replace('_', ' ', $type)),
  };

  $answerKeyRaw = trim((string) ($question->answer_key ?? ''));
  $answerKey = strtoupper($answerKeyRaw);
  $options = is_array($question->options ?? null) ? $question->options : [];

  $correctIndex = null;
  if ($answerKey !== '' && preg_match('/^[A-Z]$/', $answerKey)) {
    $candidate = ord($answerKey) - ord('A');
    if ($candidate >= 0 && $candidate < count($options)) {
      $correctIndex = $candidate;
    }
  } elseif ($answerKey !== '' && preg_match('/^[0-9]+$/', $answerKey)) {
    $candidate = (int) $answerKey - 1; // 1-based fallback
    if ($candidate >= 0 && $candidate < count($options)) {
      $correctIndex = $candidate;
    }
  } elseif ($answerKeyRaw !== '' && count($options)) {
    foreach ($options as $idx => $opt) {
      if (trim((string) $opt) === $answerKeyRaw) {
        $correctIndex = $idx;
        break;
      }
    }
  }
@endphp

<div class="space-y-6">
  <section class="page-hero">
    <span class="page-kicker">Soal dari Ujion</span>
    <h1 class="page-title">Detail Soal</h1>
    <p class="page-description">Soal ini hanya dapat dilihat. Untuk menggunakan, silakan pilih saat membuat paket soal.</p>
  </section>

  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('guru.soal-ujion.index') }}" class="btn-secondary inline-flex items-center gap-2">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali
      </a>
      @if(($isBookmarked ?? false))
        <form method="POST" action="{{ route('guru.soal-ujion.unbookmark', $question) }}">
          @csrf
          <button type="submit" class="btn-secondary inline-flex items-center gap-2 border-danger/30 bg-danger/10 text-danger hover:bg-danger/15" title="Hapus Bookmark">
            <i class="fa-solid fa-bookmark"></i>
            Tersimpan
          </button>
        </form>
      @else
        <form method="POST" action="{{ route('guru.soal-ujion.bookmark', $question) }}">
          @csrf
          <button type="submit" class="btn-secondary inline-flex items-center gap-2" title="Bookmark Soal">
            <i class="fa-regular fa-bookmark"></i>
            Bookmark
          </button>
        </form>
      @endif
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="{{ $question->is_active ? 'badge-success' : 'badge-danger' }}">
        <i class="fa-solid {{ $question->is_active ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
        {{ $question->is_active ? 'Aktif' : 'Nonaktif' }}
      </span>
      <span class="badge-info">
        <i class="fa-solid fa-database"></i>
        Soal Ujion
      </span>
      <span class="badge-warning">
        <i class="fa-solid fa-list-check"></i>
        {{ $typeLabel }}
      </span>
      @if($question->jenjang?->nama)
        <span class="badge">
          <i class="fa-solid fa-graduation-cap"></i>
          {{ $question->jenjang->nama }}
        </span>
      @endif
      @if($question->material_mapel)
        <span class="badge">
          <i class="fa-solid fa-book"></i>
          {{ $question->material_mapel }}
        </span>
      @endif
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
      @if($question->reading_passage)
        <div class="card p-5 sm:p-6">
          <div class="flex items-center justify-between gap-3">
            <div>
              <div class="text-xs font-bold uppercase tracking-wider text-primary">Teks Bacaan</div>
              <div class="mt-1 text-sm text-textSecondary">Jika soal mengacu pada bacaan, lihat bagian ini terlebih dahulu.</div>
            </div>
            <span class="badge-info">
              <i class="fa-solid fa-scroll"></i>
              Bacaan
            </span>
          </div>
          <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 leading-7 text-slate-800 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-100">
            {!! $question->reading_passage !!}
          </div>
        </div>
      @endif

      <div class="card p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-xs font-bold uppercase tracking-wider text-primary">Pertanyaan</div>
            <div class="mt-1 text-sm text-textSecondary">ID: <span class="font-mono">#{{ $question->id }}</span></div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            @if($question->material_curriculum)
              <span class="badge">
                <i class="fa-solid fa-layer-group"></i>
                {{ $question->material_curriculum }}
              </span>
            @endif
          </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 leading-7 text-slate-800 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-100">
          {!! $question->question_text !!}
        </div>

        @if(count($options))
          <div class="mt-6">
            <div class="flex items-center justify-between gap-3">
              <div class="font-bold text-slate-900 dark:text-slate-100">Pilihan Jawaban</div>
              <div class="text-xs text-textSecondary">Klik untuk menyeleksi saat membuat paket soal (di menu paket soal).</div>
            </div>
            <div class="mt-3 space-y-2">
              @foreach($options as $idx => $opt)
                @php
                  $letter = chr(ord('A') + (int) $idx);
                  $isCorrect = $correctIndex !== null && (int) $idx === (int) $correctIndex;
                @endphp
                <div class="flex gap-3 rounded-2xl border p-4 {{ $isCorrect ? 'border-success/40 bg-success/10 dark:bg-success/15' : 'border-slate-200/80 bg-white/70 dark:border-slate-800 dark:bg-slate-950/30' }}">
                  <div class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border text-xs font-bold {{ $isCorrect ? 'border-success/40 bg-white text-success dark:bg-slate-950 dark:text-success' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200' }}">
                    {{ $letter }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="whitespace-pre-wrap text-sm leading-6 text-slate-800 dark:text-slate-100">{{ $opt }}</div>
                    @if($isCorrect)
                      <div class="mt-2 inline-flex items-center gap-2 text-xs font-bold text-success">
                        <i class="fa-solid fa-circle-check"></i>
                        Kunci Jawaban
                      </div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </div>

    <div class="space-y-6">
      <div class="card p-5 sm:p-6">
        <div class="font-bold text-slate-900 dark:text-slate-100">Ringkasan</div>
        <div class="mt-1 text-sm text-textSecondary">Informasi cepat untuk memudahkan guru memahami konteks soal.</div>

        <div class="mt-4 space-y-3 text-sm">
          <div class="flex items-start justify-between gap-3">
            <div class="text-textSecondary">Jenjang</div>
            <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $question->jenjang?->nama ?? '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-3">
            <div class="text-textSecondary">Mapel</div>
            <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $question->material_mapel ?? '-' }}</div>
          </div>
          <div class="flex items-start justify-between gap-3">
            <div class="text-textSecondary">Kurikulum</div>
            <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $question->material_curriculum ?? '-' }}</div>
          </div>
          @if($question->material_subelement)
            <div class="flex items-start justify-between gap-3">
              <div class="text-textSecondary">Subelemen</div>
              <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $question->material_subelement }}</div>
            </div>
          @endif
          @if($question->material_unit)
            <div class="flex items-start justify-between gap-3">
              <div class="text-textSecondary">Unit</div>
              <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $question->material_unit }}</div>
            </div>
          @endif
          @if($question->material_sub_unit)
            <div class="flex items-start justify-between gap-3">
              <div class="text-textSecondary">Sub Unit</div>
              <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $question->material_sub_unit }}</div>
            </div>
          @endif
          <div class="flex items-start justify-between gap-3">
            <div class="text-textSecondary">Tipe</div>
            <div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $typeLabel }}</div>
          </div>
        </div>
      </div>

      <div class="card p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
          <div class="font-bold text-slate-900 dark:text-slate-100">Kunci & Pembahasan</div>
          @if($question->answer_key)
            <span class="badge-success">
              <i class="fa-solid fa-key"></i>
              @if($correctIndex !== null)
                {{ chr(ord('A') + (int) $correctIndex) }}
              @else
                {{ \Illuminate\Support\Str::limit((string) $question->answer_key, 18) }}
              @endif
            </span>
          @endif
        </div>

        @if(! $question->answer_key)
          <div class="mt-3 rounded-2xl border border-dashed border-slate-200/80 bg-slate-50/80 p-4 text-sm text-textSecondary dark:border-slate-800 dark:bg-slate-950/40">
            Kunci jawaban belum tersedia pada soal ini.
          </div>
        @endif

        @if($question->explanation)
          <div class="mt-4 text-sm leading-6 text-slate-800 dark:text-slate-100">
            {!! nl2br(e($question->explanation)) !!}
          </div>
        @else
          <div class="mt-4 text-sm text-textSecondary">Pembahasan belum tersedia.</div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
