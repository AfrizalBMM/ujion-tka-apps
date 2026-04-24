@extends('layouts.superadmin')

@section('title', 'Global Bank Soal')

@section('content')
@php
    $optionLabels = range('A', 'Z');
    $materialOptions = $materials->map(fn ($material) => [
        'mapel' => $material->mapel,
        'curriculum' => $material->curriculum,
        'subelement' => $material->subelement,
        'unit' => $material->unit,
        'sub_unit' => $material->sub_unit,
    ])->values();
    $curriculumFilters = $materials->pluck('curriculum')->filter()->unique()->values();
    $mapelFilters = $materials->pluck('mapel')->filter()->unique()->values();
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Bank Soal Global</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola kumpulan soal yang dapat diakses oleh seluruh guru di platform Ujion.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @if (empty($filters['jenjang_id']))
                <div class="relative" id="import-dropdown-wrapper">
                    <button class="btn-secondary" type="button" id="import-dropdown-btn">
                        <i class="fa-solid fa-file-import mr-2"></i> Import Soal
                        <i class="fa-solid fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div id="import-dropdown-menu"
                         class="absolute right-0 top-full z-30 mt-2 hidden w-52 rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                        <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                                data-open-modal="import-pg-modal">
                            <i class="fa-solid fa-list-check w-4 text-blue-500"></i> Pilihan Ganda
                        </button>
                        <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                                data-open-modal="import-menjodohkan-modal">
                            <i class="fa-solid fa-shuffle w-4 text-amber-500"></i> Menjodohkan
                        </button>
                    </div>
                </div>
            @endif
            <button class="btn-primary" type="button" data-open-modal="create-question-modal">
                <i class="fa-solid fa-plus mr-2"></i> Input Soal Baru
            </button>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.global-questions.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
                <input class="input mt-1" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari pertanyaan, kunci, pembahasan, atau materi...">
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="question_type" value="{{ $filters['question_type'] ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ match($filters['question_type'] ?? '') { 'multiple_choice' => 'Pilihan Ganda', 'matching' => 'Menjodohkan', 'short_answer' => 'Jawaban Singkat', default => 'Semua Jenis' } }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($filters['question_type'] ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua Jenis</div>
                            <div class="ssd-option{{ ($filters['question_type'] ?? '') === 'multiple_choice' ? ' ssd-selected' : '' }}" data-value="multiple_choice">Pilihan Ganda</div>
                            <div class="ssd-option{{ ($filters['question_type'] ?? '') === 'matching' ? ' ssd-selected' : '' }}" data-value="matching">Menjodohkan</div>
                            <div class="ssd-option{{ ($filters['question_type'] ?? '') === 'short_answer' ? ' ssd-selected' : '' }}" data-value="short_answer">Jawaban Singkat</div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ match($filters['status'] ?? '') { 'active' => 'Aktif', 'draft' => 'Draft', default => 'Semua Status' } }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($filters['status'] ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua Status</div>
                            <div class="ssd-option{{ ($filters['status'] ?? '') === 'active' ? ' ssd-selected' : '' }}" data-value="active">Aktif</div>
                            <div class="ssd-option{{ ($filters['status'] ?? '') === 'draft' ? ' ssd-selected' : '' }}" data-value="draft">Draft</div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mapel</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="material_mapel" value="{{ $filters['material_mapel'] ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ ($filters['material_mapel'] ?? '') ?: 'Semua Mapel' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($filters['material_mapel'] ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua Mapel</div>
                            @foreach ($mapelFilters as $m)
                                <div class="ssd-option{{ ($filters['material_mapel'] ?? '') === $m ? ' ssd-selected' : '' }}" data-value="{{ $m }}">{{ $m }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="material_curriculum" value="{{ $filters['material_curriculum'] ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ ($filters['material_curriculum'] ?? '') ?: 'Semua Kurikulum' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($filters['material_curriculum'] ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua Kurikulum</div>
                            @foreach ($curriculumFilters as $curriculum)
                                <div class="ssd-option{{ ($filters['material_curriculum'] ?? '') === $curriculum ? ' ssd-selected' : '' }}" data-value="{{ $curriculum }}">{{ $curriculum }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="jenjang_id" value="{{ $filters['jenjang_id'] ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        @php $selectedJenjang = $jenjangs->firstWhere('id', $filters['jenjang_id'] ?? '') @endphp
                        <span class="ssd-label">{{ $selectedJenjang ? $selectedJenjang->nama : 'Semua Jenjang' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($filters['jenjang_id'] ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua Jenjang</div>
                            @foreach ($jenjangs as $jenjang)
                                <div class="ssd-option{{ ($filters['jenjang_id'] ?? '') == $jenjang->id ? ' ssd-selected' : '' }}" data-value="{{ $jenjang->id }}">{{ $jenjang->nama }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-end gap-3 lg:col-span-6">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-filter mr-2"></i> Terapkan Filter
                </button>
                <a class="btn-secondary" href="{{ route('superadmin.global-questions.index') }}">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card min-h-[400px]">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="font-bold text-lg">Daftar Soal Global ({{ count($globalQuestions) }})</div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if (count($globalQuestions) > 0)
                    <form method="POST" action="{{ route('superadmin.global-questions.destroyAll') }}" onsubmit="return false;" id="delete-all-global-questions-form">
                        @csrf
                        <button
                            type="submit"
                            class="btn-danger whitespace-nowrap"
                            data-confirm
                            data-confirm-title="Hapus Semua Bank Soal?"
                            data-confirm="Semua bank soal global akan dihapus permanen. Tindakan ini tidak bisa dibatalkan. Lanjutkan?"
                            title="Hapus Semua Bank Soal"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($globalQuestions as $q)
                <div class="rounded-card border border-border bg-white p-4 transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1">
                            <div class="mb-2 flex flex-wrap items-center gap-3">
                                <span class="badge-info text-[10px] font-bold uppercase tracking-wider">
                                    {{ $q->question_type === 'matching' ? 'Menjodohkan' : str_replace('_', ' ', $q->question_type) }}
                                </span>
                                @if ($q->jenjang)
                                    <span class="badge-primary bg-indigo-100 text-indigo-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full">
                                        {{ $q->jenjang->nama }}
                                    </span>
                                @endif
                                @if ($q->material)
                                    <span class="flex items-center gap-1 text-[10px] text-muted">
                                        <i class="fa-solid fa-book text-[8px]"></i> {{ $q->material->mapel }} | {{ $q->material->sub_unit }}
                                    </span>
                                @elseif($q->material_sub_unit)
                                    <span class="flex items-center gap-1 text-[10px] text-muted">
                                        <i class="fa-solid fa-book text-[8px]"></i> {{ $q->material_mapel }} | {{ $q->material_sub_unit }}
                                    </span>
                                @endif
                                @if ($q->is_active)
                                    <span class="text-[10px] font-bold text-green-500">ACTIVE</span>
                                @else
                                    <span class="text-[10px] font-bold text-slate-400">DRAFT</span>
                                @endif
                            </div>

                            <div class="font-medium leading-relaxed text-slate-800 dark:text-slate-200">
                                {{ $q->question_text }}
                            </div>

                            {{-- Teks Bacaan --}}
                            @if ($q->reading_passage)
                                <div class="mt-2">
                                    <button type="button"
                                            class="toggle-reading-passage flex items-center gap-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
                                            data-target="rp-{{ $q->id }}">
                                        <i class="fa-solid fa-book-open"></i> Lihat Teks Bacaan
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-rp-chevron></i>
                                    </button>
                                    <div id="rp-{{ $q->id }}" class="hidden mt-2">
                                        <div class="rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $q->reading_passage }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($q->options)
                                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($q->options as $idx => $opt)
                                        @php
                                            $optionLabel = $optionLabels[$idx] ?? 'O' . ($idx + 1);
                                            $isCorrectOption = $q->answer_key == $opt;
                                        @endphp
                                        <div class="rounded border p-2 text-xs dark:border-slate-700 {{ $isCorrectOption ? 'border-emerald-200 bg-emerald-100/60 font-bold text-emerald-900 ring-1 ring-emerald-300 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-slate-100 bg-slate-50 dark:bg-slate-800' }}">
                                            <span class="mr-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/80 text-[10px] font-bold text-slate-700 dark:bg-slate-900/80 dark:text-slate-200">{{ $optionLabel }}</span>
                                            {{ $opt }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($q->answer_key)
                                <div class="mt-3 text-xs">
                                    <span class="font-bold text-blue-600">Kunci:</span> {{ $q->answer_key }}
                                </div>
                            @endif

                            {{-- Pembahasan --}}
                            @if ($q->explanation)
                                <div class="mt-2 text-xs">
                                    <button type="button"
                                            class="toggle-explanation flex items-center gap-2 font-semibold text-emerald-600 hover:text-emerald-700"
                                            data-target="ex-{{ $q->id }}">
                                        <i class="fa-solid fa-lightbulb"></i> Lihat Pembahasan
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-ex-chevron></i>
                                    </button>
                                    <div id="ex-{{ $q->id }}" class="hidden mt-2">
                                        <div class="rounded-xl bg-emerald-50/70 p-3 italic leading-relaxed text-slate-700 dark:bg-emerald-900/10 dark:text-slate-300">
                                            {{ $q->explanation }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-row gap-2 lg:flex-col">
                            <form method="POST" action="{{ route('superadmin.global-questions.destroy', $q) }}">
                                @csrf
                                <button class="btn-danger p-2" type="submit" data-confirm="Hapus soal ini dari bank soal?" title="Hapus">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>

                            @php
                                $editPayload = [
                                    'id'                  => $q->id,
                                    'question_type'       => $q->question_type,
                                    'reading_passage'     => $q->reading_passage,
                                    'question_text'       => $q->question_text,
                                    'material_mapel'      => $q->material_mapel ?? $q->material?->mapel,
                                    'material_curriculum' => $q->material_curriculum ?? $q->material?->curriculum,
                                    'material_subelement' => $q->material_subelement ?? $q->material?->subelement,
                                    'material_unit'       => $q->material_unit ?? $q->material?->unit,
                                    'material_sub_unit'   => $q->material_sub_unit ?? $q->material?->sub_unit,
                                    'options'             => $q->options ?? [],
                                    'answer_key'          => $q->answer_key,
                                    'explanation'         => $q->explanation,
                                    'is_active'           => $q->is_active ? '1' : '0',
                                    'jenjang_id'          => $q->jenjang_id,
                                ];
                            @endphp

                            <button
                                class="btn-secondary p-2"
                                type="button"
                                title="Edit"
                                data-edit-question='@json($editPayload)'
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center py-20 text-center">
                    <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50">
                        <i class="fa-solid fa-database text-3xl text-slate-200"></i>
                    </div>
                    <span class="italic text-muted dark:text-slate-400">Belum ada soal global yang tersedia. Mulai dengan membuat soal pertama atau import Excel/CSV.</span>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div id="create-question-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4">
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Baru</div>
                <div class="mt-2 text-xl font-bold">Input Soal Global</div>
            </div>
            <button type="button" class="icon-button" data-close-modal="create-question-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="mt-5 flex-1 space-y-4 overflow-y-auto pr-2" method="POST" action="{{ route('superadmin.global-questions.store') }}">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
                    <select class="input mt-1" name="jenjang_id" id="create-jenjang-id" required>
                        <option value="" disabled selected>Pilih Jenjang</option>
                        @foreach($jenjangs as $jenjang)
                            <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                    <select class="input mt-1" name="question_type" id="create-question-type" required>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="short_answer">Jawaban Singkat</option>
                        <option value="matching">Menjodohkan</option>
                    </select>
                </div>
            </div>

            {{-- Teks Bacaan (hanya untuk PG) --}}
            <div id="create-reading-passage-wrapper">
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">
                    Teks Bacaan <span class="font-normal italic text-muted">(opsional, khusus Pilihan Ganda)</span>
                </label>
                <textarea class="input mt-1" name="reading_passage" rows="3"
                          placeholder="Isi teks/wacana bacaan yang menjadi konteks soal ini. Kosongkan jika tidak ada."></textarea>
            </div>
            <div class="space-y-3" data-material-picker="create">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative" data-material-field="mapel">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mapel</label>
                        <input type="hidden" name="material_mapel" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih mapel</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari mapel..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                    <div class="relative" data-material-field="curriculum">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Curriculum</label>
                        <input type="hidden" name="material_curriculum" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih kurikulum</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari kurikulum..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative" data-material-field="subelement">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Subelement</label>
                        <input type="hidden" name="material_subelement" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih subelement</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari subelement..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                    <div class="relative" data-material-field="unit">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Unit</label>
                        <input type="hidden" name="material_unit" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih unit</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari unit..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative" data-material-field="sub_unit">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Sub Unit</label>
                        <input type="hidden" name="material_sub_unit" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih sub unit</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari sub unit..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] italic text-muted">Pilih materi bertahap dari kurikulum sampai sub unit. Opsi akan otomatis mengerucut sesuai pilihan sebelumnya.</p>
                    <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" data-material-reset>Kosongkan Materi</button>
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
                <textarea class="input mt-1" name="question_text" rows="4" required placeholder="Apa rukun islam yang kedua?"></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban</label>
                    <button type="button" class="btn-secondary px-3 py-2 text-xs" data-option-add="create">
                        <i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
                    </button>
                </div>
                <div class="mt-2 space-y-2" data-option-list="create"></div>
                <p class="mt-1 text-[10px] italic text-muted">Untuk pilihan ganda, isi opsi satu per baris input. Kunci jawaban bisa diisi huruf seperti `A` atau isi jawabannya.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
                    <input class="input mt-1" name="answer_key" placeholder="A atau isi jawaban benar">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
                    <select class="input mt-1" name="is_active">
                        <option value="1">Aktif (Publik)</option>
                        <option value="0">Draft (Sembunyi)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
                <textarea class="input mt-1" name="explanation" rows="2" placeholder="Shalat adalah tiang agama..."></textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-cloud-upload mr-2"></i> Simpan ke Bank Soal
                </button>
                <button class="btn-secondary" type="button" data-close-modal="create-question-modal">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ================================================ --}}
{{--   Modal Import Pilihan Ganda                      --}}
{{-- ================================================ --}}
<div id="import-pg-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-500"></i>
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Import</div>
                </div>
                <div class="mt-2 text-xl font-bold">Upload Soal Pilihan Ganda</div>
                <p class="mt-2 text-sm text-textSecondary">Gunakan Excel atau CSV. Kolom <code>reading_passage</code> untuk teks bacaan (boleh kosong).</p>
            </div>
            <button type="button" class="icon-button" data-close-modal="import-pg-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="mt-5 space-y-4" method="POST" action="{{ route('superadmin.global-questions.import-pg') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
                <select class="input mt-1" name="jenjang_id" required>
                    <option value="" disabled selected>Pilih Jenjang Tujuan</option>
                    @foreach($jenjangs as $jenjang)
                        <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary">File Import</label>
                <input class="input mt-1 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2"
                       type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
            </div>
            <div class="rounded-2xl border border-blue-200/70 bg-blue-50/70 p-3 text-sm text-blue-900">
                <strong>Kolom wajib:</strong> question_text. Kolom opsional: reading_passage, option_a–e, answer_key, material_*, explanation, is_active.
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-file-import mr-2"></i> Import Sekarang
                </button>
                <a class="btn-secondary" href="{{ route('superadmin.global-questions.template-pg') }}">
                    <i class="fa-solid fa-file-excel mr-2"></i> Template PG
                </a>
                <button class="btn-secondary" type="button" data-close-modal="import-pg-modal">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ================================================ --}}
{{--   Modal Import Menjodohkan                        --}}
{{-- ================================================ --}}
<div id="import-menjodohkan-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-shuffle text-amber-500"></i>
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Import</div>
                </div>
                <div class="mt-2 text-xl font-bold">Upload Soal Menjodohkan</div>
                <p class="mt-2 text-sm text-textSecondary">Format: kolom <code>pair_1_left</code>, <code>pair_1_right</code>, dst. hingga pair_8.</p>
            </div>
            <button type="button" class="icon-button" data-close-modal="import-menjodohkan-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="mt-5 space-y-4" method="POST" action="{{ route('superadmin.global-questions.import-menjodohkan') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
                <select class="input mt-1" name="jenjang_id" required>
                    <option value="" disabled selected>Pilih Jenjang Tujuan</option>
                    @foreach($jenjangs as $jenjang)
                        <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary">File Import</label>
                <input class="input mt-1 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2"
                       type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
            </div>
            <div class="rounded-2xl border border-amber-200/70 bg-amber-50/70 p-3 text-sm text-amber-900">
                <strong>Kolom wajib:</strong> question_text + minimal pair_1_left & pair_1_right. Maksimal 8 pasangan (pair_1 s/d pair_8).
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-file-import mr-2"></i> Import Sekarang
                </button>
                <a class="btn-secondary" href="{{ route('superadmin.global-questions.template-menjodohkan') }}">
                    <i class="fa-solid fa-file-excel mr-2"></i> Template Menjodohkan
                </a>
                <button class="btn-secondary" type="button" data-close-modal="import-menjodohkan-modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-question-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4">
    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
                <div class="mt-2 text-xl font-bold">Soal Global</div>
            </div>
            <button type="button" class="icon-button" data-close-modal="edit-question-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="edit-question-form" method="POST" class="mt-5 flex-1 space-y-4 overflow-y-auto pr-2">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
                    <select class="input mt-1" name="jenjang_id" id="edit-jenjang-id" required>
                        <option value="" disabled selected>Pilih Jenjang</option>
                        @foreach($jenjangs as $jenjang)
                            <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                    <select class="input mt-1" name="question_type" id="edit-question-type" required>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="short_answer">Jawaban Singkat</option>
                        <option value="matching">Menjodohkan</option>
                    </select>
                </div>
            </div>

            {{-- Teks Bacaan (edit) --}}
            <div id="edit-reading-passage-wrapper">
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">
                    Teks Bacaan <span class="font-normal italic text-muted">(opsional, khusus Pilihan Ganda)</span>
                </label>
                <textarea class="input mt-1" name="reading_passage" id="edit-reading-passage" rows="3"
                          placeholder="Teks bacaan konteks soal..."></textarea>
            </div>
            <div class="space-y-3" data-material-picker="edit">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative" data-material-field="mapel">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mapel</label>
                        <input type="hidden" name="material_mapel" id="edit-material-mapel" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih mapel</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari mapel..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                    <div class="relative" data-material-field="curriculum">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Curriculum</label>
                        <input type="hidden" name="material_curriculum" id="edit-material-curriculum" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih kurikulum</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari kurikulum..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative" data-material-field="subelement">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Subelement</label>
                        <input type="hidden" name="material_subelement" id="edit-material-subelement" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih subelement</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari subelement..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                    <div class="relative" data-material-field="unit">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Unit</label>
                        <input type="hidden" name="material_unit" id="edit-material-unit" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih unit</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari unit..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative" data-material-field="sub_unit">
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Sub Unit</label>
                        <input type="hidden" name="material_sub_unit" id="edit-material-sub-unit" data-material-value>
                        <button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
                            <span data-material-label>Pilih sub unit</span>
                            <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                        </button>
                        <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
                            <input type="text" class="input mb-2" placeholder="Cari sub unit..." data-material-search>
                            <div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] italic text-muted">Pilih materi bertahap dari kurikulum sampai sub unit. Opsi akan otomatis mengerucut sesuai pilihan sebelumnya.</p>
                    <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" data-material-reset>Kosongkan Materi</button>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
                <textarea class="input mt-1" name="question_text" id="edit-question-text" rows="4" required></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban</label>
                    <button type="button" class="btn-secondary px-3 py-2 text-xs" data-option-add="edit">
                        <i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
                    </button>
                </div>
                <div class="mt-2 space-y-2" data-option-list="edit"></div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
                    <input class="input mt-1" name="answer_key" id="edit-answer-key" placeholder="A atau isi jawaban benar">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
                    <select class="input mt-1" name="is_active" id="edit-is-active">
                        <option value="1">Aktif (Publik)</option>
                        <option value="0">Draft (Sembunyi)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
                <textarea class="input mt-1" name="explanation" id="edit-explanation" rows="2"></textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Simpan Perubahan</button>
                <button class="btn-secondary" type="button" data-close-modal="edit-question-modal">Batal</button>
            </div>
        </form>
    </div>
</div>


<script id="superadmin-questions-config" type="application/json">
    @json([
        'materialOptions' => $materialOptions,
        'optionLabels' => $optionLabels,
        'updateRouteTemplate' => route('superadmin.global-questions.update', ['globalQuestion' => '__ID__']),
    ])
</script>
@endsection
