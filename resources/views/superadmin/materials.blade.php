@extends('layouts.superadmin')

@section('title', 'Master Data Materi')

@section('content')
@php
    $contextJenjang = in_array($filter, ['SD', 'SMP'], true) ? $filter : null;
    $contextTitle = match ($contextJenjang) {
        'SD' => 'Materi SD',
        'SMP' => 'Materi SMP',
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
                        Pilih submenu `SD` atau `SMP` dari sidebar jika ingin fokus input materi per jenjang tertentu.
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
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="font-bold text-lg">Daftar Materi</div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row items-center">
                <!-- Hapus Semua Materi Button -->
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

        <div class="grid grid-cols-1 gap-3">
            @if(count($materials) > 0)
            @foreach ($materials as $m)
                <div class="flex flex-col gap-4 rounded-card border border-border bg-white p-4 transition-shadow hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="badge-info text-[10px]">{{ $m->curriculum }}</span>
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
                    <span class="text-muted italic">Belum ada kurikulum/materi yang diinput.</span>
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
                        Gunakan kolom `jenjang` di file Excel untuk membedakan materi SD dan SMP.
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
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subelemen</label>
                    <input class="input mt-1" name="subelement" required placeholder="E.g: Akidah Akhlak">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modals = {
        import: document.getElementById('material-import-modal'),
        create: document.getElementById('material-create-modal'),
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('hidden');
    };

    document.querySelectorAll('[data-open-material-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(modals[button.dataset.openMaterialModal]);
        });
    });

    document.querySelectorAll('[data-close-material-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('#material-import-modal, #material-create-modal');
            closeModal(modal);
        });
    });

    Object.values(modals).forEach((modal) => {
        if (!modal) return;

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });
});
</script>
@endsection
