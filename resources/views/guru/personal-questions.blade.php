@extends('layouts.guru')
@section('title', 'Bank Soal Pribadi')
@section('content')
@php
$optionLabels = range('A', 'Z');
$imageUrl = static fn ($path) => $path ? route('guru.personal-questions.builder.image', ['path' => $path]) : null;
$resolveAnswerLabel = static function ($question) use ($optionLabels) {
    if ($question->tipe === 'Singkat') {
        return '';
    }

    $raw = strtoupper(trim((string) $question->jawaban_benar));
    if (in_array($raw, array_slice($optionLabels, 0, 5), true)) {
        return $raw;
    }

    foreach (($question->opsi ?? []) as $index => $option) {
        if (trim((string) $option) === trim((string) $question->jawaban_benar)) {
            return $optionLabels[$index] ?? '';
        }
    }

    return '';
};
@endphp
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Bank Soal</span>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="page-title">Bank Soal Pribadi</h1>
                <p class="page-description">Kelola soal-soal pribadi Anda untuk digunakan dalam ujian atau latihan. Tambahkan,
                    filter, dan edit soal dengan mudah.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="hero-chip">
                    <i class="fa-solid fa-database"></i>
                    Soal Pribadi
                </div>
                <div class="hero-chip">
                    <i class="fa-solid fa-layer-group"></i>
                    Kategori & Tipe Soal
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('guru.materials') }}"
                class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                <i class="fa-solid fa-book"></i>
                Materi
            </a>
        </div>
    </section>
    <form method="GET" action="{{ route('guru.personal-questions') }}" class="card p-4 space-y-4 sm:space-y-0 sm:flex sm:items-end sm:gap-4 mb-4" data-personal-questions-filter-form>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Pertanyaan</label>
            <input type="text" name="q" value="{{ request('q') }}" class="input mt-1 w-full" placeholder="Cari pertanyaan..." data-live-search>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kategori</label>
            <div class="ssd-wrap mt-1">
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                    <span class="ssd-label">{{ request('kategori') ?: 'Semua Kategori' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kategori..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option{{ !request('kategori') ? ' ssd-selected' : '' }}" data-value="">Semua Kategori</div>
                        @foreach(($categories ?? collect()) as $kategori)
                            <div class="ssd-option{{ request('kategori') == $kategori ? ' ssd-selected' : '' }}" data-value="{{ $kategori }}">{{ $kategori }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Tipe Soal</label>
            <div class="ssd-wrap mt-1">
                <input type="hidden" name="tipe" value="{{ request('tipe') }}">
                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                    <span class="ssd-label">{{ match(request('tipe')) { 'PG' => 'Pilihan Ganda', 'Checklist' => 'Checklist', 'Singkat' => 'Jawaban Singkat', default => 'Semua Tipe' } }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari tipe..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option{{ !request('tipe') ? ' ssd-selected' : '' }}" data-value="">Semua Tipe</div>
                        <div class="ssd-option{{ request('tipe') === 'PG' ? ' ssd-selected' : '' }}" data-value="PG">Pilihan Ganda</div>
                        <div class="ssd-option{{ request('tipe') === 'Checklist' ? ' ssd-selected' : '' }}" data-value="Checklist">Checklist</div>
                        <div class="ssd-option{{ request('tipe') === 'Singkat' ? ' ssd-selected' : '' }}" data-value="Singkat">Jawaban Singkat</div>
                    </div>
                </div>
            </div>
        </div>
        <a href="{{ route('guru.personal-questions') }}"
            class="btn-secondary h-[42px] flex items-center justify-center">Reset</a>
        <a href="{{ route('guru.personal-questions.builder') }}"
            class="btn-primary h-[42px] flex items-center justify-center">Builder Soal Fullscreen</a>
        <button class="btn-primary h-[42px] flex items-center justify-center" type="button"
            data-modal-open="modal-tambah-soal">
            <i class="fa-solid fa-plus mr-2"></i>Tambah Soal
        </button>
    </form>

    <!-- Modal Tambah Soal -->
    <div id="modal-tambah-soal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="font-bold text-lg">Tambah Soal Pribadi</div>
                    <button class="text-gray-500 hover:text-gray-700" type="button" data-modal-close="modal-tambah-soal">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('guru.personal-questions.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('guru.partials.personal-question-form-fields', [
                        'user' => $user,
                        'optionLabels' => $optionLabels,
                        'modeKey' => 'create',
                        'isEditMode' => false,
                        'questionData' => [
                            'tipe' => 'PG',
                            'kategori' => '',
                            'pertanyaan' => '',
                            'opsi' => ['', '', '', ''],
                            'jawaban_benar' => '',
                            'pembahasan' => '',
                            'status' => 'draft',
                            'image_path' => null,
                        ],
                        'imagePreviewUrl' => null,
                    ])
                    <button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Tambah Soal</button>
                </form>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <a href="{{ route('guru.personal-questions.builder') }}" class="btn-primary mb-4 w-full sm:w-auto">Builder Soal
            Fullscreen</a>
        <div id="personal-questions-table-wrap" class="table-container">
            <table class="table-ujion w-full min-w-[620px]">
                <thead>
                    <tr>
                        <th>Soal</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($questions as $question)
                    <tr>
                        <td class="min-w-[260px]">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge-info">Soal Pribadi</span>
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $question->jenjang }}</span>
                                </div>
                                <div class="text-sm font-semibold leading-6 text-slate-900 dark:text-slate-100">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($question->pertanyaan), 110) }}
                                </div>
                                @if($question->image_path)
                                <div class="text-[11px] text-slate-500">Memiliki lampiran gambar</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge">{{ $question->kategori }}</span>
                        </td>
                        <td>
                            <span class="badge-warning">{{ $question->tipe }}</span>
                        </td>
                        <td>
                            <span class="{{ $question->status === 'terbit' ? 'badge-success' : 'badge-warning' }}">{{ $question->status }}</span>
                        </td>
                        <td class="flex flex-wrap gap-2">
                            <button type="button" class="btn-secondary" data-modal-open="modal-edit-soal-{{ $question->id }}">Edit</button>
                            <form method="POST" action="{{ route('guru.personal-questions.destroy', $question) }}">@csrf<button
                                    class="btn-danger">Hapus</button></form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($questions->hasPages())
            <div class="mt-4">
                {{ $questions->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@foreach($questions as $question)
<div id="modal-edit-soal-{{ $question->id }}" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="font-bold text-lg">Edit Soal Pribadi</div>
                <button class="text-gray-500 hover:text-gray-700" type="button" data-modal-close="modal-edit-soal-{{ $question->id }}">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('guru.personal-questions.update', $question) }}" enctype="multipart/form-data" data-edit-personal-form>
                @csrf
                @include('guru.partials.personal-question-form-fields', [
                    'user' => $user,
                    'optionLabels' => $optionLabels,
                    'modeKey' => 'edit-' . $question->id,
                    'isEditMode' => true,
                    'questionData' => [
                        'tipe' => $question->tipe,
                        'kategori' => $question->kategori,
                        'pertanyaan' => $question->pertanyaan,
                        'opsi' => $question->opsi ?? [],
                        'jawaban_benar' => $resolveAnswerLabel($question),
                        'pembahasan' => $question->pembahasan,
                        'status' => $question->status,
                        'image_path' => $question->image_path,
                    ],
                    'imagePreviewUrl' => $imageUrl($question->image_path),
                ])
                <button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>
@endforeach
<script id="personal-questions-config" type="application/json">@json(['optionLabels' => $optionLabels])</script>
@endsection
