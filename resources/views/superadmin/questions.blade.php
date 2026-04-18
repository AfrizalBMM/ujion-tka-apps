@extends('layouts.superadmin')

@section('title', 'Global Bank Soal')

@section('content')
@php
    $optionLabels = range('A', 'Z');
    $materialOptions = $materials->map(fn ($material) => [
        'curriculum' => $material->curriculum,
        'subelement' => $material->subelement,
        'unit' => $material->unit,
        'sub_unit' => $material->sub_unit,
    ])->values();
    $curriculumFilters = $materials->pluck('curriculum')->filter()->unique()->values();
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Bank Soal Global</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola kumpulan soal yang dapat diakses oleh seluruh guru di platform Ujion.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="btn-secondary" type="button" data-open-modal="import-question-modal">
                <i class="fa-solid fa-file-import mr-2"></i> Import Soal
            </button>
            <button class="btn-primary" type="button" data-open-modal="create-question-modal">
                <i class="fa-solid fa-plus mr-2"></i> Input Soal Baru
            </button>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.global-questions.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
                <input class="input mt-1" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari pertanyaan, kunci, pembahasan, atau materi...">
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                <select class="input mt-1" name="question_type">
                    <option value="">Semua Jenis</option>
                    <option value="multiple_choice" @selected(($filters['question_type'] ?? '') === 'multiple_choice')>Pilihan Ganda</option>
                    <option value="short_answer" @selected(($filters['question_type'] ?? '') === 'short_answer')>Jawaban Singkat</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
                <select class="input mt-1" name="status">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                    <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
                <select class="input mt-1" name="material_curriculum">
                    <option value="">Semua Kurikulum</option>
                    @foreach ($curriculumFilters as $curriculum)
                        <option value="{{ $curriculum }}" @selected(($filters['material_curriculum'] ?? '') === $curriculum)>{{ $curriculum }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-end gap-3 lg:col-span-5">
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
                                <span class="badge-info text-[10px] font-bold uppercase tracking-wider">{{ str_replace('_', ' ', $q->question_type) }}</span>
                                @if ($q->material)
                                    <span class="flex items-center gap-1 text-[10px] text-muted">
                                        <i class="fa-solid fa-book text-[8px]"></i> {{ $q->material->sub_unit }}
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
                                    'id' => $q->id,
                                    'question_type' => $q->question_type,
                                    'question_text' => $q->question_text,
                                    'material_curriculum' => $q->material_curriculum ?? $q->material?->curriculum,
                                    'material_subelement' => $q->material_subelement ?? $q->material?->subelement,
                                    'material_unit' => $q->material_unit ?? $q->material?->unit,
                                    'material_sub_unit' => $q->material_sub_unit ?? $q->material?->sub_unit,
                                    'options' => $q->options ?? [],
                                    'answer_key' => $q->answer_key,
                                    'explanation' => $q->explanation,
                                    'is_active' => $q->is_active ? '1' : '0',
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
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                    <select class="input mt-1" name="question_type" required>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="short_answer">Jawaban Singkat</option>
                    </select>
                </div>
            </div>
            <div class="space-y-3" data-material-picker="create">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

<div id="import-question-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Import</div>
                <div class="mt-2 text-xl font-bold">Upload Soal Global</div>
                <p class="mt-2 text-sm text-textSecondary">Gunakan Excel atau CSV untuk upload soal global dalam jumlah besar.</p>
            </div>
            <button type="button" class="icon-button" data-close-modal="import-question-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="mt-5 space-y-4" method="POST" action="{{ route('superadmin.global-questions.import') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">File Import</label>
                <input class="input mt-1 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
            </div>

            <div class="rounded-2xl border border-blue-200/70 bg-blue-50/70 p-4 text-sm text-blue-900">
                <div class="font-semibold">jangan merubah nama kolom agar import berjalan dengan baik</div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-file-import mr-2"></i> Import Sekarang
                </button>
                <a class="btn-secondary" href="{{ route('superadmin.global-questions.template') }}">
                    <i class="fa-solid fa-file-excel mr-2"></i> Template Excel
                </a>
                <button class="btn-secondary" type="button" data-close-modal="import-question-modal">Batal</button>
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
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                    <select class="input mt-1" name="question_type" id="edit-question-type" required>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="short_answer">Jawaban Singkat</option>
                    </select>
                </div>
            </div>
            <div class="space-y-3" data-material-picker="edit">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const materialData = @json($materialOptions);
    const materialFieldOrder = ['curriculum', 'subelement', 'unit', 'sub_unit'];
    const editModal = document.getElementById('edit-question-modal');
    const form = document.getElementById('edit-question-form');
    const optionLabels = @json($optionLabels);
    const createOptionList = document.querySelector('[data-option-list="create"]');
    const editOptionList = document.querySelector('[data-option-list="edit"]');
    const modals = Array.from(document.querySelectorAll('[id$="-modal"]'));
    const materialPickers = new Map();
    const fields = {
        questionType: document.getElementById('edit-question-type'),
        materialCurriculum: document.getElementById('edit-material-curriculum'),
        materialSubelement: document.getElementById('edit-material-subelement'),
        materialUnit: document.getElementById('edit-material-unit'),
        materialSubUnit: document.getElementById('edit-material-sub-unit'),
        questionText: document.getElementById('edit-question-text'),
        answerKey: document.getElementById('edit-answer-key'),
        isActive: document.getElementById('edit-is-active'),
        explanation: document.getElementById('edit-explanation'),
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const closeAllMaterialDropdowns = () => {
        materialPickers.forEach((picker) => {
            Object.values(picker.fields).forEach((field) => field.dropdown.classList.add('hidden'));
        });
    };

    const buildMaterialPicker = (container) => {
        const fieldMap = {};

        materialFieldOrder.forEach((name) => {
            const root = container.querySelector(`[data-material-field="${name}"]`);
            if (!root) return;

            fieldMap[name] = {
                root,
                valueInput: root.querySelector('[data-material-value]'),
                trigger: root.querySelector('[data-material-trigger]'),
                label: root.querySelector('[data-material-label]'),
                dropdown: root.querySelector('[data-material-dropdown]'),
                search: root.querySelector('[data-material-search]'),
                options: root.querySelector('[data-material-options]'),
            };
        });

        const picker = {
            container,
            fields: fieldMap,
            state: {
                curriculum: '',
                subelement: '',
                unit: '',
                sub_unit: '',
            },
        };

        const getFilteredDataset = (fieldName) => {
            const fieldIndex = materialFieldOrder.indexOf(fieldName);

            return materialData.filter((item) => {
                return materialFieldOrder.slice(0, fieldIndex).every((key) => {
                    return !picker.state[key] || item[key] === picker.state[key];
                });
            });
        };

        const getOptions = (fieldName) => {
            const searchTerm = picker.fields[fieldName].search.value.trim().toLowerCase();
            const options = [...new Set(getFilteredDataset(fieldName).map((item) => item[fieldName]).filter(Boolean))];

            return options.filter((option) => option.toLowerCase().includes(searchTerm));
        };

        const updateFieldUI = (fieldName) => {
            const field = picker.fields[fieldName];
            const options = getOptions(fieldName);
            const selectedValue = picker.state[fieldName];
            const placeholderMap = {
                curriculum: 'Pilih kurikulum',
                subelement: 'Pilih subelement',
                unit: 'Pilih unit',
                sub_unit: 'Pilih sub unit',
            };

            field.valueInput.value = selectedValue || '';
            field.label.textContent = selectedValue || placeholderMap[fieldName];
            field.trigger.disabled = options.length === 0 && !selectedValue;
            field.trigger.classList.toggle('cursor-not-allowed', field.trigger.disabled);
            field.trigger.classList.toggle('opacity-60', field.trigger.disabled);

            field.options.innerHTML = '';

            if (options.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'rounded-xl px-3 py-2 text-sm text-muted';
                emptyState.textContent = 'Tidak ada data yang cocok.';
                field.options.appendChild(emptyState);
                return;
            }

            options.forEach((option) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800 ${option === selectedValue ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200'}`;
                button.innerHTML = `<span>${option}</span>${option === selectedValue ? '<i class="fa-solid fa-check text-xs"></i>' : ''}`;
                button.addEventListener('click', () => {
                    picker.state[fieldName] = option;

                    const currentIndex = materialFieldOrder.indexOf(fieldName);
                    materialFieldOrder.slice(currentIndex + 1).forEach((key) => {
                        picker.state[key] = '';
                        picker.fields[key].search.value = '';
                    });

                    refreshPicker();

                    const nextFieldName = materialFieldOrder[currentIndex + 1];
                    if (nextFieldName) {
                        openFieldDropdown(nextFieldName);
                    } else {
                        closeAllMaterialDropdowns();
                    }
                });
                field.options.appendChild(button);
            });
        };

        const refreshPicker = () => {
            materialFieldOrder.forEach((name) => updateFieldUI(name));
        };

        const openFieldDropdown = (fieldName) => {
            closeAllMaterialDropdowns();
            const field = picker.fields[fieldName];
            if (!field || field.trigger.disabled) return;
            field.dropdown.classList.remove('hidden');
            field.search.focus();
            field.search.select();
        };

        materialFieldOrder.forEach((fieldName) => {
            const field = picker.fields[fieldName];

            field.trigger.addEventListener('click', () => {
                if (field.dropdown.classList.contains('hidden')) {
                    openFieldDropdown(fieldName);
                } else {
                    field.dropdown.classList.add('hidden');
                }
            });

            field.search.addEventListener('input', () => updateFieldUI(fieldName));
        });

        container.querySelector('[data-material-reset]')?.addEventListener('click', () => {
            materialFieldOrder.forEach((name) => {
                picker.state[name] = '';
                picker.fields[name].search.value = '';
            });
            refreshPicker();
            closeAllMaterialDropdowns();
        });

        refreshPicker();

        picker.setValues = (values = {}) => {
            materialFieldOrder.forEach((name) => {
                picker.state[name] = values[name] || '';
                picker.fields[name].search.value = '';
            });
            refreshPicker();
        };

        return picker;
    };

    const renderOptionFields = (container, values = []) => {
        const entries = values.length ? values : ['', '', '', ''];
        container.innerHTML = '';

        entries.forEach((value, index) => {
            const label = optionLabels[index] ?? `O${index + 1}`;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
            row.innerHTML = `
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
                <input class="input border-0 bg-transparent px-0" name="options[]" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
            `;
            container.appendChild(row);
        });
    };

    const appendOptionField = (container) => {
        const values = Array.from(container.querySelectorAll('input[name="options[]"]')).map((input) => input.value);
        values.push('');
        renderOptionFields(container, values);
    };

    renderOptionFields(createOptionList);
    document.querySelectorAll('[data-material-picker]').forEach((container) => {
        const picker = buildMaterialPicker(container);
        materialPickers.set(container.dataset.materialPicker, picker);
    });

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.openModal === 'create-question-modal') {
                materialPickers.get('create')?.setValues();
            }
            openModal(document.getElementById(button.dataset.openModal));
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            closeAllMaterialDropdowns();
            closeModal(document.getElementById(button.dataset.closeModal));
        });
    });

    modals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAllMaterialDropdowns();
                closeModal(modal);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element) || event.target.closest('[data-material-field]')) {
            return;
        }

        closeAllMaterialDropdowns();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllMaterialDropdowns();
            modals.forEach((modal) => {
                if (!modal.classList.contains('hidden')) {
                    closeModal(modal);
                }
            });
        }
    });

    document.querySelectorAll('[data-edit-question]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editQuestion);
            form.action = "{{ route('superadmin.global-questions.update', ['globalQuestion' => '__ID__']) }}".replace('__ID__', data.id);
            fields.questionType.value = data.question_type ?? 'multiple_choice';
            materialPickers.get('edit')?.setValues({
                curriculum: data.material_curriculum ?? '',
                subelement: data.material_subelement ?? '',
                unit: data.material_unit ?? '',
                sub_unit: data.material_sub_unit ?? '',
            });
            fields.questionText.value = data.question_text ?? '';
            renderOptionFields(editOptionList, data.options ?? []);
            fields.answerKey.value = data.answer_key ?? '';
            fields.isActive.value = data.is_active ?? '1';
            fields.explanation.value = data.explanation ?? '';
            openModal(editModal);
            fields.questionText.focus();
        });
    });

    document.querySelectorAll('[data-option-add]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.optionAdd === 'edit' ? editOptionList : createOptionList;
            appendOptionField(target);
        });
    });
});
</script>
@endsection
