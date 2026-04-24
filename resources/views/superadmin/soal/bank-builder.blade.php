@extends('layouts.superadmin')

@section('title', 'Bank Builder — ' . $mapel->nama_label)

@section('content')
@php
    $optionLabels = range('A', 'Z');
    $soalCount    = $mapel->soals()->count();
    $maxSoal      = $mapel->jumlah_soal;
    $slotSisa     = max(0, $maxSoal - $soalCount);
@endphp

<div class="space-y-6">

    {{-- Hero --}}
    <section class="page-hero">
        <div class="flex flex-wrap items-center gap-2 text-sm text-white/70">
            <a href="{{ route('superadmin.paket-soal.index') }}" class="hover:text-white">Paket Soal</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <a href="{{ route('superadmin.paket-soal.show', $paket) }}" class="hover:text-white">{{ $paket->nama }}</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <span class="text-white">{{ $mapel->nama_label }}</span>
        </div>
        <span class="page-kicker mt-2">Import dari Bank Soal</span>
        <h1 class="page-title">Pilih soal untuk <span class="text-white/80">{{ $mapel->nama_label }}</span></h1>
        <p class="page-description">
            Paket: <strong class="text-white">{{ $paket->nama }}</strong> &middot;
            {{ $mapel->soals()->count() }}/{{ $mapel->jumlah_soal }} soal &middot;
            Jenjang {{ $paket->jenjang?->kode }}
        </p>
    </section>

    {{-- Filter bar --}}
    <section class="card">
        <form method="GET" action="{{ route('superadmin.soal.bank-builder', [$paket, $mapel]) }}"
              class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">

            <div class="sm:col-span-2 lg:col-span-5">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Cari Soal / Bacaan</label>
                <input class="input mt-1" type="text" name="search"
                       value="{{ $filters['search'] }}"
                       placeholder="Ketik kata kunci pertanyaan, teks bacaan, atau materi...">
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tipe Soal</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="question_type" value="{{ $filters['question_type'] }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ match($filters['question_type']) { 'multiple_choice' => 'Pilihan Ganda', 'matching' => 'Menjodohkan', 'short_answer' => 'Jawaban Singkat', default => 'Semua Tipe' } }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ $filters['question_type'] === '' ? ' ssd-selected' : '' }}" data-value="">Semua Tipe</div>
                            <div class="ssd-option{{ $filters['question_type'] === 'multiple_choice' ? ' ssd-selected' : '' }}" data-value="multiple_choice">Pilihan Ganda</div>
                            <div class="ssd-option{{ $filters['question_type'] === 'matching' ? ' ssd-selected' : '' }}" data-value="matching">Menjodohkan</div>
                            <div class="ssd-option{{ $filters['question_type'] === 'short_answer' ? ' ssd-selected' : '' }}" data-value="short_answer">Jawaban Singkat</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kurikulum</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="material_curriculum" value="{{ $filters['material_curriculum'] }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ $filters['material_curriculum'] ?: 'Semua Kurikulum' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ $filters['material_curriculum'] === '' ? ' ssd-selected' : '' }}" data-value="">Semua Kurikulum</div>
                            @foreach($curriculums as $c)
                                <div class="ssd-option{{ $filters['material_curriculum'] === $c ? ' ssd-selected' : '' }}" data-value="{{ $c }}">{{ $c }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Sub Unit Materi</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="material_sub_unit" value="{{ $filters['material_sub_unit'] }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ $filters['material_sub_unit'] ? Str::limit($filters['material_sub_unit'], 26) : 'Semua Sub Unit' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari sub unit..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ $filters['material_sub_unit'] === '' ? ' ssd-selected' : '' }}" data-value="">Semua Sub Unit</div>
                            @foreach($subUnits as $su)
                                <div class="ssd-option{{ $filters['material_sub_unit'] === $su ? ' ssd-selected' : '' }}" data-value="{{ $su }}">{{ $su }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Mata Pelajaran</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="material_mapel" value="{{ $filters['material_mapel'] }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ $filters['material_mapel'] ?: 'Semua Mapel' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ $filters['material_mapel'] === '' ? ' ssd-selected' : '' }}" data-value="">Semua Mapel</div>
                            @foreach($mapels as $m)
                                <div class="ssd-option{{ $filters['material_mapel'] === $m ? ' ssd-selected' : '' }}" data-value="{{ $m }}">{{ $m }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="jenjang_id" value="{{ $filters['jenjang_id'] }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        @php $selJenjang = $jenjangs->firstWhere('id', $filters['jenjang_id']) @endphp
                        <span class="ssd-label">{{ $selJenjang ? $selJenjang->nama : 'Semua Jenjang' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ $filters['jenjang_id'] === '' ? ' ssd-selected' : '' }}" data-value="">Semua Jenjang</div>
                            @foreach($jenjangs as $j)
                                <div class="ssd-option{{ $filters['jenjang_id'] == $j->id ? ' ssd-selected' : '' }}" data-value="{{ $j->id }}">{{ $j->nama }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3 sm:col-span-2 lg:col-span-5">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-filter mr-2"></i> Terapkan Filter
                </button>
                <a class="btn-secondary" href="{{ route('superadmin.soal.bank-builder', [$paket, $mapel]) }}">
                    Reset
                </a>
                <span class="ml-auto text-sm text-textSecondary">
                    <span id="selected-count" class="font-bold text-blue-600">0</span> soal dipilih dari {{ count($bankSoals) }} hasil
                </span>
            </div>
        </form>
    </section>

    {{-- Form import --}}
    <form id="import-bank-form"
          method="POST"
          action="{{ route('superadmin.soal.import-from-bank', [$paket, $mapel]) }}">
        @csrf

        {{-- Daftar soal --}}
        <section class="card min-h-[400px]">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-bold">Bank Soal Global</h2>
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium">
                    <input type="checkbox" id="select-all-checkbox" class="h-4 w-4 rounded">
                    <span>Pilih Semua</span>
                </label>
            </div>

            <div class="space-y-4">
                @forelse($bankSoals as $gq)
                    @php
                        $isPG       = $gq->question_type === 'multiple_choice';
                        $isMatch    = $gq->question_type === 'matching';
                        $isSingkat  = $gq->question_type === 'short_answer';
                        $typeBadge  = match($gq->question_type) {
                            'multiple_choice' => ['label' => 'Pilihan Ganda', 'class' => 'badge-info'],
                            'matching'        => ['label' => 'Menjodohkan',   'class' => 'badge-warning'],
                            default           => ['label' => 'Jawaban Singkat','class' => 'badge-success'],
                        };
                        $cardId = 'gq-' . $gq->id;
                    @endphp

                    <div class="soal-card group rounded-[20px] border border-border bg-white transition-all
                                hover:border-blue-300 hover:shadow-md
                                dark:border-slate-800 dark:bg-slate-900"
                         data-card-id="{{ $gq->id }}">

                        {{-- Header card --}}
                        <label class="flex cursor-pointer items-start gap-4 p-4 pb-3" for="{{ $cardId }}">
                            <input type="checkbox"
                                   id="{{ $cardId }}"
                                   name="global_question_ids[]"
                                   value="{{ $gq->id }}"
                                   class="soal-checkbox mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                            <div class="flex-1 min-w-0">
                                {{-- Badges --}}
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="{{ $typeBadge['class'] }} text-[10px] font-bold uppercase tracking-wider">
                                        {{ $typeBadge['label'] }}
                                    </span>
                                    @if($gq->material_curriculum)
                                        <span class="text-[10px] text-textSecondary">
                                            <i class="fa-solid fa-graduation-cap text-[8px]"></i>
                                            {{ $gq->material_curriculum }}
                                        </span>
                                    @endif
                                    @if($gq->material_sub_unit)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $gq->material_sub_unit }}
                                        </span>
                                    @endif
                                    @if($gq->answer_key && $isPG)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            Kunci: {{ \Illuminate\Support\Str::limit($gq->answer_key, 30) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Teks soal --}}
                                <p class="font-medium leading-relaxed text-slate-800 dark:text-slate-200">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($gq->question_text), 180) }}
                                </p>
                            </div>
                        </label>

                        {{-- Teks Bacaan (accordion) --}}
                        @if($gq->reading_passage)
                            <div class="border-t border-border px-4 pb-1 dark:border-slate-800">
                                <button type="button"
                                        class="toggle-bacaan flex w-full items-center justify-between py-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
                                        data-target="bacaan-{{ $gq->id }}">
                                    <span><i class="fa-solid fa-book-open mr-2"></i>Teks Bacaan</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-chevron></i>
                                </button>
                                <div id="bacaan-{{ $gq->id }}" class="hidden pb-3">
                                    <div class="rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $gq->reading_passage }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Pilihan Ganda --}}
                        @if($isPG && !empty($gq->options))
                            <div class="border-t border-border px-4 py-3 dark:border-slate-800">
                                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                    @foreach($gq->options as $idx => $opt)
                                        @php
                                            $label   = $optionLabels[$idx] ?? 'O' . ($idx + 1);
                                            $isBenar = $gq->answer_key == $opt;
                                        @endphp
                                        <div class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs
                                            {{ $isBenar
                                                ? 'bg-emerald-50 font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20'
                                                : 'text-slate-600 dark:text-slate-400' }}">
                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                                                {{ $isBenar ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}
                                                text-[10px] font-bold">{{ $label }}</span>
                                            {{ $opt }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Menjodohkan --}}
                        @if($isMatch && !empty($gq->options))
                            <div class="border-t border-border px-4 py-3 dark:border-slate-800">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-textSecondary">
                                            <th class="pb-1 pr-4 font-semibold">Item Kiri</th>
                                            <th class="pb-1 font-semibold">Item Kanan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border dark:divide-slate-800">
                                        @foreach($gq->options as $pair)
                                            @if(is_array($pair) && isset($pair['left'], $pair['right']))
                                                <tr>
                                                    <td class="py-1 pr-4 text-slate-700 dark:text-slate-300">{{ $pair['left'] }}</td>
                                                    <td class="py-1 font-medium text-blue-700 dark:text-blue-300">{{ $pair['right'] }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Info materi lengkap --}}
                        @if($gq->material_subelement || $gq->material_unit)
                            <div class="border-t border-border px-4 py-2 dark:border-slate-800">
                                <p class="text-[11px] text-textSecondary">
                                    @if($gq->material_subelement)
                                        <span class="font-semibold">Sub-elemen:</span> {{ $gq->material_subelement }}
                                    @endif
                                    @if($gq->material_unit)
                                        &nbsp;&middot;&nbsp;
                                        <span class="font-semibold">Unit:</span> {{ $gq->material_unit }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center py-24 text-center">
                        <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800">
                            <i class="fa-solid fa-database text-3xl text-slate-200"></i>
                        </div>
                        <p class="italic text-muted">Tidak ada soal aktif di bank soal global yang cocok dengan filter.</p>
                        <a href="{{ route('superadmin.global-questions.index') }}" class="mt-4 text-sm font-medium text-blue-600 hover:underline">
                            Tambah soal ke bank soal →
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Sticky Footer --}}
        <div id="import-footer"
             class="sticky bottom-0 z-40 mt-4 hidden rounded-[20px] border border-blue-200 bg-blue-600 px-6 py-4 shadow-2xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="text-white">
                    <span class="text-lg font-bold" id="footer-count">0</span>
                    <span class="ml-1 text-blue-100">soal dipilih</span>
                    <span class="ml-3 text-xs text-blue-200">(sisa slot: <span id="footer-slot">{{ $slotSisa }}</span>)</span>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="deselect-all-btn"
                            class="rounded-xl border border-white/30 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                        Batalkan Pilihan
                    </button>
                    <button type="button" id="preview-btn"
                            class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-blue-700 shadow-md transition hover:bg-blue-50">
                        <i class="fa-solid fa-eye mr-2"></i>
                        Preview & Masukkan
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- ── Preview Modal (Checkout) ── --}}
    <div id="preview-modal"
         class="fixed inset-0 z-[500] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-2xl rounded-[24px] bg-white dark:bg-slate-900 shadow-2xl flex flex-col max-h-[90vh]">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                <div>
                    <h3 class="text-lg font-bold">Konfirmasi Import Soal</h3>
                    <p class="text-xs text-textSecondary mt-0.5">
                        Mapel: <strong>{{ $mapel->nama_label }}</strong> &middot;
                        Paket: <strong>{{ $paket->nama }}</strong>
                    </p>
                </div>
                <button type="button" id="close-preview-btn"
                        class="text-muted hover:text-slate-800 dark:hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            {{-- Summary bar --}}
            <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-border flex items-center justify-between gap-4 text-sm">
                <div class="flex items-center gap-4">
                    <span class="font-semibold text-blue-700 dark:text-blue-300">
                        <i class="fa-solid fa-list-check mr-1.5"></i>
                        <span id="preview-count">0</span> soal akan ditambahkan
                    </span>
                    <span class="text-textSecondary">
                        Slot tersisa sebelum import: <strong>{{ $slotSisa }}</strong>
                    </span>
                </div>
                <span id="quota-warning"
                      class="hidden text-xs font-semibold text-amber-600 dark:text-amber-400">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Melebihi kuota! Sebagian soal akan dilewati.
                </span>
            </div>

            {{-- Daftar soal yang dipilih --}}
            <div id="preview-list" class="overflow-y-auto flex-1 px-6 py-4 space-y-3">
                {{-- diisi oleh JS --}}
            </div>

            {{-- Footer action --}}
            <div class="px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                <button type="button" id="cancel-preview-btn"
                        class="btn-secondary px-5 py-2.5 text-sm">
                    Kembali Pilih
                </button>
                <button type="button" id="confirm-import-btn"
                        class="btn-primary px-5 py-2.5 text-sm">
                    <i class="fa-solid fa-file-import mr-2"></i>
                    Konfirmasi & Import
                </button>
            </div>
        </div>
    </div>
</div>

<script id="superadmin-bank-builder-config" type="application/json">
    @json([
        'slotSisa' => $slotSisa,
        'emptySelectionMessage' => 'Pilih minimal satu soal terlebih dahulu.',
        'processingImportHtml' => '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...',
    ])
</script>
@endsection
