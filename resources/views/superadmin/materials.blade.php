@extends('layouts.superadmin')

@section('title', 'Master Data Materi')

@section('content')
@php
    $contextJenjang = in_array($filter, ['SD', 'SMP', 'SMA'], true) ? $filter : null;
    $contextTitle = match ($contextJenjang) {
        'SD' => 'Materi SD',
        'SMP' => 'Materi SMP',
        'SMA' => 'Materi SMA',
        default => 'Semua Jenjang',
    };
    $templateLabel = $contextJenjang ? "Download Template {$contextJenjang}" : 'Download Template Excel';
    $importTitle = $contextJenjang ? "Import Materi {$contextJenjang}" : 'Import Materi';
    $importCopy = $contextJenjang
        ? "Semua baris tanpa kolom jenjang akan otomatis dibaca sebagai materi {$contextJenjang}."
        : 'Upload file Excel atau CSV untuk menambahkan banyak materi sekaligus.';
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Data Kurikulum & Materi</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola hierarki kurikulum, subelemen, unit, dan sub unit materi global.</p>
    </div>

    <div class="card border-primary/15 bg-primary/5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Konteks Input</div>
                <div class="mt-2 text-lg font-bold text-slate-900 dark:text-white">{{ $contextTitle }}</div>
                <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">
                    @if ($contextJenjang)
                        Form tambah data, template, dan import di halaman ini diselaraskan ke jenjang {{ $contextJenjang }} agar tidak mudah tertukar.
                    @else
                        Pilih submenu `SD`, `SMP`, atau `SMA` dari sidebar jika ingin fokus input materi per jenjang tertentu.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-info">{{ $contextTitle }}</span>
                @if (($filter ?? null) === 'GLOBAL')
                    <span class="badge-warning">Global</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        {{-- ─── Header Row ─── --}}
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="font-bold text-lg">Daftar Materi</div>
                @php
                    $activeFilterCount = collect([$mapel, $curriculum, $subelement, $unit, $subUnit, ($search !== '' ? $search : null)])->filter()->count();
                @endphp
                @if ($activeFilterCount > 0)
                    <span class="badge-info text-xs">{{ $activeFilterCount }} filter aktif</span>
                    <a href="{{ route('superadmin.materials.index', array_filter(['jenjang' => $filter])) }}"
                       class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
                        <i class="fa-solid fa-xmark"></i> Reset
                    </a>
                @endif
            </div>
            <div class="flex flex-col gap-2 sm:flex-row items-center">
                @if(count($materials) > 0)
                <form method="POST" action="{{ route('superadmin.materials.destroyAll') }}" onsubmit="return false;" id="delete-all-materials-form">
                    @csrf
                    <button type="submit" class="btn-danger whitespace-nowrap" data-confirm data-confirm-title="Hapus Semua Materi?" data-confirm="Semua data materi akan dihapus permanen. Lanjutkan?" title="Hapus Semua Materi">
                        <i class="fa-solid fa-trash"></i> Hapus Semua
                    </button>
                </form>
                @endif
                <button type="button" class="btn-secondary whitespace-nowrap" data-open-material-modal="import">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    Import Excel
                </button>
                <button type="button" class="btn-primary whitespace-nowrap" data-open-material-modal="create">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Materi
                </button>
            </div>
        </div>

        {{-- ─── Filter & Search Bar ─── --}}
        <form method="GET" action="{{ route('superadmin.materials.index') }}" id="material-filter-form"
              class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end" data-ssd-autosubmit>
            @if ($filter)
                <input type="hidden" name="jenjang" value="{{ $filter }}">
            @endif

            {{-- Mata Pelajaran --}}
            <div class="flex flex-col gap-1 min-w-[160px]">
                <label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Mata Pelajaran</label>
                <div class="ssd-wrap">
                    <input type="hidden" name="mapel" value="{{ $mapel ?? '' }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label truncate">{{ $mapel ?: 'Semua' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option" data-value="">Semua</div>
                            @foreach ($mapels as $m)
                                <div class="ssd-option{{ $mapel === $m ? ' ssd-selected' : '' }}" data-value="{{ $m }}">{{ $m }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kurikulum --}}
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Kurikulum</label>
                <div class="ssd-wrap">
                    <input type="hidden" name="curriculum" value="{{ $curriculum ?? '' }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label truncate">{{ $curriculum ?: 'Semua' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option" data-value="">Semua</div>
                            @foreach ($curriculums as $c)
                                <div class="ssd-option{{ $curriculum === $c ? ' ssd-selected' : '' }}" data-value="{{ $c }}">{{ $c }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subelemen --}}
            <div class="flex flex-col gap-1 min-w-[180px]">
                <label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Subelemen</label>
                <div class="ssd-wrap">
                    <input type="hidden" name="subelement" value="{{ $subelement ?? '' }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label truncate">{{ $subelement ? Str::limit($subelement, 26) : 'Semua' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari subelemen..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option" data-value="">Semua</div>
                            @foreach ($subelements as $se)
                                <div class="ssd-option{{ $subelement === $se ? ' ssd-selected' : '' }}" data-value="{{ $se }}">{{ $se }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Unit --}}
            <div class="flex flex-col gap-1 min-w-[200px]">
                <label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Unit / Bab</label>
                <div class="ssd-wrap">
                    <input type="hidden" name="unit" value="{{ $unit ?? '' }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label truncate">{{ $unit ? Str::limit($unit, 26) : 'Semua' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari unit..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option" data-value="">Semua</div>
                            @foreach ($units as $u)
                                <div class="ssd-option{{ $unit === $u ? ' ssd-selected' : '' }}" data-value="{{ $u }}">{{ $u }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sub Unit --}}
            <div class="flex flex-col gap-1 min-w-[220px]">
                <label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Sub Unit</label>
                <div class="ssd-wrap">
                    <input type="hidden" name="sub_unit" value="{{ $subUnit ?? '' }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label truncate">{{ $subUnit ? Str::limit($subUnit, 26) : 'Semua' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari sub unit..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option" data-value="">Semua</div>
                            @foreach ($subUnits as $su)
                                <div class="ssd-option{{ $subUnit === $su ? ' ssd-selected' : '' }}" data-value="{{ $su }}">{{ $su }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                <label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Cari Materi</label>
                <div class="relative flex items-center">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 text-muted text-xs pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Mapel, Subelemen, unit..."
                           class="input pl-8 text-sm w-full">
                    <button type="submit" class="ml-2 btn-primary px-4 py-2 text-sm whitespace-nowrap">
                        Cari
                    </button>
                </div>
            </div>
        </form>

        {{-- ─── List ─── --}}
        <div class="grid grid-cols-1 gap-3">
            @if(count($materials) > 0)
            @foreach ($materials as $m)
                <div class="flex flex-col gap-4 rounded-card border border-border bg-white p-4 transition-shadow hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="badge-info text-[10px]">{{ $m->curriculum }}</span>
                            @if($m->mapel)
                                <span class="badge-primary bg-blue-100 text-blue-700 text-[10px]">{{ $m->mapel }}</span>
                            @endif
                            @if($m->jenjang)
                                <span class="badge-warning text-[10px]">{{ $m->jenjang }}</span>
                            @endif
                            <div class="text-xs text-muted">ID: #{{ $m->id }}</div>
                        </div>
                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $m->subelement }}</div>
                        <div class="mt-1 text-sm text-textSecondary dark:text-slate-400">
                            <i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $m->unit }}
                            <i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $m->sub_unit }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('superadmin.materials.destroy', $m) }}">
                        @csrf
                        <button class="btn-danger p-2" type="submit" data-confirm="Hapus materi ini? Data soal yang terikat akan kehilangan referensi materi. Lanjutkan?" title="Hapus">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            @endforeach
            @else
                <div class="text-center py-20 border-2 border-dashed rounded-xl">
                    <i class="fa-solid fa-book-open text-5xl text-slate-100 mb-4 block"></i>
                    @if($activeFilterCount > 0)
                        <span class="text-muted italic">Tidak ada materi yang cocok dengan filter yang dipilih.</span>
                        <div class="mt-3">
                            <a href="{{ route('superadmin.materials.index', array_filter(['jenjang' => $filter])) }}"
                               class="btn-secondary text-sm">
                                <i class="fa-solid fa-rotate-left"></i> Reset Filter
                            </a>
                        </div>
                    @else
                        <span class="text-muted italic">Belum ada kurikulum/materi yang diinput.</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>


<div id="material-import-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Import</div>
                <div class="mt-2 text-xl font-bold">{{ $importTitle }}</div>
                <p class="mt-2 text-sm text-textSecondary">{{ $importCopy }}</p>
            </div>
            <button type="button" class="icon-button" data-close-material-modal>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('superadmin.materials.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            @if ($contextJenjang)
                <input type="hidden" name="default_jenjang" value="{{ $contextJenjang }}">
            @endif
            <div>
                <label class="text-xs font-bold text-textSecondary">File Excel / CSV</label>
                <input class="input mt-1" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
            </div>
            <div class="rounded-2xl border border-blue-200/70 bg-blue-50/70 p-4 text-sm text-blue-900">
                <div class="font-semibold">Template yang disarankan</div>
                <p class="mt-1">
                    @if ($contextJenjang)
                        Template dan import akan mengutamakan jenjang {{ $contextJenjang }}.
                    @else
                        Gunakan kolom `jenjang` di file Excel untuk membedakan materi SD, SMP, dan SMA.
                    @endif
                </p>
                <a class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('superadmin.materials.template', array_filter(['jenjang' => $contextJenjang])) }}">
                    <i class="fa-solid fa-file-excel"></i> {{ $templateLabel }}
                </a>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Import File</button>
                <button class="btn-secondary" type="button" data-close-material-modal>Batal</button>
            </div>
        </form>
    </div>
</div>

<div id="material-create-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-2xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Tambah Data</div>
                <div class="mt-2 text-xl font-bold">Tambah Materi Baru</div>
                <p class="mt-2 text-sm text-textSecondary">Tambahkan materi baru sesuai jenjang yang sedang aktif agar referensinya tetap rapi.</p>
            </div>
            <button type="button" class="icon-button" data-close-material-modal>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form class="mt-5 space-y-4" method="POST" action="{{ route('superadmin.materials.store') }}">
            @csrf
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang (opsional)</label>
                <select class="input mt-1" name="jenjang">
                    <option value="" @selected(! $contextJenjang)>Semua Jenjang</option>
                    <option value="SD" @selected($contextJenjang === 'SD')>SD</option>
                    <option value="SMP" @selected($contextJenjang === 'SMP')>SMP</option>
                    <option value="SMA" @selected($contextJenjang === 'SMA')>SMA</option>
                </select>
                <p class="mt-1 text-[10px] text-muted italic">
                    @if ($contextJenjang)
                        Default mengikuti submenu {{ $contextJenjang }} yang sedang dibuka.
                    @else
                        Pilih jenjang dengan jelas agar materi tidak tercampur.
                    @endif
                </p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
                    <select class="input mt-1" name="curriculum" required>
                        <option value="Merdeka">Kurikulum Merdeka</option>
                        <option value="K-13">K-13 (Masa Transisi)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
                    <input class="input mt-1" name="mapel" required placeholder="E.g: Bahasa Indonesia / Matematika">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subelemen</label>
                    <input class="input mt-1" name="subelement" required placeholder="E.g: Literasi">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Unit / Bab</label>
                    <input class="input mt-1" name="unit" required placeholder="E.g: Rukun Iman">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Sub Unit / Sub Bab</label>
                    <input class="input mt-1" name="sub_unit" required placeholder="E.g: Mengenal Malaikat">
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Link Materi (opsional)</label>
                <input class="input mt-1" name="link" placeholder="https://...">
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Tambah Materi</button>
                <button class="btn-secondary" type="button" data-close-material-modal>Batal</button>
            </div>
            <p class="text-[10px] text-muted italic">Materi yang ditambahkan akan tersedia sebagai referensi saat pembuatan bank soal oleh semua guru.</p>
        </form>
    </div>
</div>
@endsection
