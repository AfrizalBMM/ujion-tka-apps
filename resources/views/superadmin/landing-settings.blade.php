@extends('layouts.superadmin')

@section('title', 'Pengaturan Landing')

@section('content')
@php
    $tabs = [
        'hero' => ['label' => 'Hero', 'icon' => 'fa-wand-magic-sparkles'],
        'content' => ['label' => 'Konten Landing', 'icon' => 'fa-pen-to-square'],
        'branding' => ['label' => 'Logo', 'icon' => 'fa-image'],
        'faq' => ['label' => 'FAQ', 'icon' => 'fa-circle-question'],
        'pricing' => ['label' => 'Pricing', 'icon' => 'fa-tags'],
        'stats' => ['label' => 'Statistik', 'icon' => 'fa-chart-column'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Pengaturan Landing</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Satu menu dengan beberapa tab untuk mengelola bagian landing page.</p>
        </div>
        <a href="{{ route('landing') }}" target="_blank" class="btn-secondary whitespace-nowrap">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            Buka Landing
        </a>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach ($tabs as $key => $item)
            <a
                href="{{ route('superadmin.landing-settings.index', ['tab' => $key]) }}"
                class="{{ $tab === $key ? 'btn-primary' : 'btn-secondary' }}"
            >
                <i class="fa-solid {{ $item['icon'] }}"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    @if ($tab === 'hero')
        <div class="grid gap-6">
            <div class="card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold">{{ $editHeroMockup ? 'Edit Mockup Hero' : 'Tambah Mockup Hero' }}</h2>
                        <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Upload PNG final untuk mengganti placeholder ruang mockup produk.</p>
                    </div>
                    @if ($editHeroMockup)
                        <a href="{{ route('superadmin.landing-settings.index', ['tab' => 'hero']) }}" class="btn-secondary px-3" title="Batal edit">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <div class="font-bold">Upload belum berhasil</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $editHeroMockup ? route('superadmin.landing-settings.hero-mockups.update', $editHeroMockup) : route('superadmin.landing-settings.hero-mockups.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Badge</label>
                        <input class="input mt-1 w-full" name="badge" value="{{ old('badge', $editHeroMockup->badge ?? '') }}" placeholder="Contoh: Mockup 1">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
                        <input class="input mt-1 w-full" name="title" value="{{ old('title', $editHeroMockup->title ?? '') }}" placeholder="Contoh: Dashboard guru / analytics" required>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Deskripsi</label>
                        <textarea class="input mt-1 min-h-24 w-full" name="description" placeholder="Keterangan singkat mockup">{{ old('description', $editHeroMockup->description ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Gambar PNG</label>
                        <input class="input mt-1 w-full" type="file" name="image" accept="image/png,image/jpeg,image/webp" {{ $editHeroMockup ? '' : 'required' }}>
                        <div class="mt-1 text-xs text-muted">Disarankan PNG landscape rasio 16:10 atau 4:3, maksimal 10MB.</div>
                        @if ($editHeroMockup && $editHeroMockup->image_path)
                            <img src="{{ $editHeroMockup->image_url }}" alt="{{ $editHeroMockup->title }}" class="mt-3 h-32 w-full rounded-2xl border border-slate-200 object-cover dark:border-slate-700">
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
                            <input class="input mt-1 w-full" type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', $editHeroMockup->sort_order ?? 0) }}">
                        </div>
                        <div class="space-y-3 pt-1">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $editHeroMockup->is_featured ?? false))>
                                Jadikan mockup utama
                            </label>
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editHeroMockup->is_active ?? true))>
                                Aktif tampil di landing
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            {{ $editHeroMockup ? 'Simpan Perubahan' : 'Tambah Mockup' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold">Daftar Mockup Hero</h2>
                        <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Mockup aktif akan menggantikan kartu placeholder di landing page.</p>
                    </div>
                    <span class="badge-info">{{ number_format($heroMockups->count()) }} item</span>
                </div>

                <div class="mt-5 table-container">
                    <table class="table-ujion min-w-[900px]">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Konten</th>
                                <th>Urutan</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($heroMockups as $mockup)
                                <tr>
                                    <td>
                                        <img src="{{ $mockup->image_url }}" alt="{{ $mockup->title }}" class="h-20 w-32 rounded-xl object-cover">
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($mockup->badge)
                                                <span class="badge-info">{{ $mockup->badge }}</span>
                                            @endif
                                            @if ($mockup->is_featured)
                                                <span class="badge-success">Utama</span>
                                            @endif
                                        </div>
                                        <div class="mt-2 font-semibold">{{ $mockup->title }}</div>
                                        @if ($mockup->description)
                                            <div class="mt-1 max-w-xl text-xs text-muted">{{ Str::limit($mockup->description, 120) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $mockup->sort_order }}</td>
                                    <td>
                                        @if ($mockup->is_active)
                                            <span class="badge-success">Aktif</span>
                                        @else
                                            <span class="badge-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('superadmin.landing-settings.index', ['tab' => 'hero', 'hero_mockup_id' => $mockup->id]) }}" class="btn-secondary px-3" title="Edit">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form method="POST" action="{{ route('superadmin.landing-settings.hero-mockups.toggle', $mockup) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary px-3" title="Aktif / Nonaktif">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('superadmin.landing-settings.hero-mockups.destroy', $mockup) }}">
                                                @csrf
                                                <button type="submit" class="btn-danger px-3" data-confirm="Hapus mockup Hero ini?" data-confirm-title="Hapus Mockup Hero">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-muted">Belum ada mockup Hero. Placeholder default masih dipakai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($tab === 'content')
        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">Hero Section</h2>
                    <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Mengatur teks pembuka di bagian atas landing.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if (($sectionActives['hero'] ?? true) === true)
                        <span class="badge-success">Aktif</span>
                    @else
                        <span class="badge-danger">Nonaktif</span>
                    @endif
                    <form method="POST" action="{{ route('superadmin.landing-settings.sections.toggle', ['section' => 'hero']) }}">
                        @csrf
                        <button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan hero">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('superadmin.landing-settings.content') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kicker</label>
                    <input class="input mt-1 w-full" name="kicker" value="{{ old('kicker', $hero['kicker'] ?? '') }}" placeholder="Kalimat pendek di atas judul">
                </div>

                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
                    <input class="input mt-1 w-full" name="title" value="{{ old('title', $hero['title'] ?? '') }}" placeholder="Judul utama hero">
                </div>

                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Deskripsi</label>
                    <textarea class="input mt-1 min-h-28 w-full" name="body" placeholder="Paragraf penjelasan">{{ old('body', $hero['body'] ?? '') }}</textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Teks Tombol</label>
                        <input class="input mt-1 w-full" name="button_text" value="{{ old('button_text', $hero['button_text'] ?? '') }}" placeholder="Contoh: Coba Sebagai Guru">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">URL Tombol (opsional)</label>
                        <input class="input mt-1 w-full" name="button_url" value="{{ old('button_url', $hero['button_url'] ?? '') }}" placeholder="Kosongkan untuk default ke halaman daftar guru">
                        <div class="mt-1 text-xs text-muted">Boleh isi URL penuh (https://...) atau path (/register/...)</div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button class="btn-primary" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($tab === 'branding')
        <div class="card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">Logo Landing</h2>
                    <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Logo ini dipakai di header landing.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($branding && $branding->logo_path)
                        @if ($branding->is_active)
                            <span class="badge-success">Aktif</span>
                        @else
                            <span class="badge-danger">Nonaktif</span>
                        @endif
                        <form method="POST" action="{{ route('superadmin.landing-settings.branding.toggle') }}">
                            @csrf
                            <button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan logo custom">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </form>
                    @else
                        <span class="badge-info">Default</span>
                    @endif
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="h-16 w-16 overflow-hidden rounded-2xl border border-border bg-white dark:bg-slate-900">
                    <img src="{{ $logoUrl }}" alt="Logo" class="h-full w-full object-cover">
                </div>
                <div class="text-sm text-textSecondary dark:text-slate-300">
                    <div class="font-semibold text-slate-900 dark:text-white">Preview logo saat ini</div>
                    <div class="mt-1">Upload gambar untuk mengganti.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('superadmin.landing-settings.logo') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">File Logo</label>
                    <input type="file" class="input mt-1 w-full" name="logo" accept="image/*" required>
                    <div class="mt-1 text-xs text-muted">Disarankan PNG/SVG rasio persegi (mis. 256x256).</div>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button class="btn-primary" type="submit">
                        <i class="fa-solid fa-upload"></i>
                        Upload Logo
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($tab === 'faq')
        <div class="card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">FAQ Landing</h2>
                    <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Kelola daftar pertanyaan yang tampil di section FAQ.</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if (($sectionActives['faq'] ?? true) === true)
                        <span class="badge-success">Aktif</span>
                    @else
                        <span class="badge-danger">Nonaktif</span>
                    @endif
                    <form method="POST" action="{{ route('superadmin.landing-settings.sections.toggle', ['section' => 'faq']) }}">
                        @csrf
                        <button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan section FAQ">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                    <a href="{{ route('superadmin.landing-settings.index', ['tab' => 'faq']) }}" class="btn-secondary whitespace-nowrap">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Baru
                    </a>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-border bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $editFaq ? 'Edit FAQ' : 'Tambah FAQ' }}</div>

                    <form
                        method="POST"
                        action="{{ $editFaq ? route('superadmin.landing-settings.faq.update', $editFaq) : route('superadmin.landing-settings.faq.store') }}"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan</label>
                            <input class="input mt-1 w-full" name="question" value="{{ old('question', $editFaq?->question) }}" required>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jawaban</label>
                            <textarea class="input mt-1 min-h-24 w-full" name="answer" required>{{ old('answer', $editFaq?->answer) }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
                                <input type="number" min="0" class="input mt-1 w-full" name="sort_order" value="{{ old('sort_order', $editFaq?->sort_order ?? 0) }}">
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" class="rounded"
                                        {{ old('is_active', $editFaq?->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="text-textSecondary dark:text-slate-300">Aktif</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            @if ($editFaq)
                                <a href="{{ route('superadmin.landing-settings.index', ['tab' => 'faq']) }}" class="btn-secondary">Batal</a>
                            @endif
                            <button class="btn-primary" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl border border-border bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">Daftar FAQ</div>

                    <div class="mt-4 table-container">
                        <table class="table-ujion min-w-full">
                            <thead>
                                <tr>
                                    <th>Pertanyaan</th>
                                    <th>Urut</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($faqs as $faq)
                                    @php
                                        $isModel = $faq instanceof \App\Models\LandingFaq;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-semibold">{{ $isModel ? $faq->question : $faq['question'] }}</div>
                                            <div class="mt-1 text-xs text-muted line-clamp-2">{{ $isModel ? $faq->answer : $faq['answer'] }}</div>
                                        </td>
                                        <td class="font-semibold">{{ $isModel ? $faq->sort_order : '-' }}</td>
                                        <td>
                                            @if ($isModel)
                                                @if ($faq->is_active)
                                                    <span class="badge-success">Aktif</span>
                                                @else
                                                    <span class="badge-danger">Nonaktif</span>
                                                @endif
                                            @else
                                                <span class="badge-info">Default</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if ($isModel)
                                                <div class="flex justify-end gap-2">
                                                    <a href="{{ route('superadmin.landing-settings.index', ['tab' => 'faq', 'faq_id' => $faq->id]) }}" class="btn-secondary px-3">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <form method="POST" action="{{ route('superadmin.landing-settings.faq.toggle', $faq) }}">
                                                        @csrf
                                                        <button type="submit" class="btn-secondary px-3" title="Aktif/Nonaktif">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('superadmin.landing-settings.faq.destroy', $faq) }}">
                                                        @csrf
                                                        <button type="submit" class="btn-danger px-3" data-confirm="Hapus FAQ ini?" data-confirm-title="Hapus FAQ">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-xs text-muted">Jalankan migrate untuk edit</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-muted">Belum ada FAQ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($tab === 'pricing')
        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">Pricing / Tarif Jenjang</h2>
                    <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tab ini memakai tabel pricing_plans yang sama dengan menu Keuangan.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if (($sectionActives['pricing'] ?? true) === true)
                        <span class="badge-success">Aktif</span>
                    @else
                        <span class="badge-danger">Nonaktif</span>
                    @endif
                    <form method="POST" action="{{ route('superadmin.landing-settings.sections.toggle', ['section' => 'pricing']) }}">
                        @csrf
                        <button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan section pricing">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('superadmin.tarif-jenjang.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
                    <input class="input mt-1 w-full" name="name" value="{{ old('name') }}" placeholder="Contoh: Aktivasi Guru SD" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang</label>
                    <div class="ssd-wrap mt-1">
                        <input type="hidden" name="jenjang" value="{{ old('jenjang') }}" {{ $hasJenjangColumn ? 'required' : 'disabled' }}>
                        <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                            <span class="ssd-label">{{ old('jenjang') ?: ($hasJenjangColumn ? 'Pilih jenjang' : 'Jalankan migrate untuk aktifkan jenjang') }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                        </button>
                        <div class="ssd-panel">
                            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                            <div class="ssd-list">
                                <div class="ssd-option{{ !old('jenjang') ? ' ssd-selected' : '' }}" data-value="">Pilih jenjang</div>
                                @foreach (config('ujion.jenjangs') as $jenjang)
                                    <div class="ssd-option{{ old('jenjang') === $jenjang ? ' ssd-selected' : '' }}" data-value="{{ $jenjang }}">{{ $jenjang }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if (! $hasJenjangColumn)
                        <div class="mt-1 text-xs text-muted">Kolom `jenjang` belum ada di DB.</div>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Keterangan (opsional)</label>
                    <textarea class="input mt-1 min-h-20 w-full" name="description" placeholder="Deskripsi singkat">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nominal</label>
                    <input class="input mt-1 w-full" name="price" value="{{ old('price') }}" placeholder="99000" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subtitle (opsional)</label>
                    <input class="input mt-1 w-full" name="subtitle" value="{{ old('subtitle') }}" placeholder="Contoh: Akses akun guru / operator">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Gambar QRIS (opsional)</label>
                    <input type="file" class="input mt-1 w-full" name="image" accept="image/*" {{ $hasQrisImageColumn ? '' : 'disabled' }}>
                    @if (! $hasQrisImageColumn)
                        <div class="mt-1 text-xs text-muted">Kolom `qris_image_path` belum ada di DB. Upload gambar dinonaktifkan.</div>
                    @endif
                </div>

                <div class="md:col-span-2 flex items-center justify-end">
                    <button class="btn-primary" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan
                    </button>
                </div>
            </form>

            <div class="mt-6 table-container">
                <table class="table-ujion min-w-[980px]">
                    <thead>
                        <tr>
                            <th>Jenjang</th>
                            <th>Judul</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tarifJenjangs as $tarif)
                            <tr>
                                <td><span class="badge-info">{{ $tarif->jenjang ?: '-' }}</span></td>
                                <td>
                                    <div class="font-semibold">{{ $tarif->name }}</div>
                                    @if ($tarif->subtitle)
                                        <div class="mt-1 text-xs text-muted">{{ $tarif->subtitle }}</div>
                                    @endif
                                </td>
                                <td class="font-semibold">Rp {{ number_format((int) $tarif->price, 0, ',', '.') }}</td>
                                <td>
                                    @if ($tarif->is_active)
                                        <span class="badge-success">Aktif</span>
                                    @else
                                        <span class="badge-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('superadmin.tarif-jenjang.print', $tarif) }}" target="_blank" class="btn-secondary px-3" title="Print label">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <form method="POST" action="{{ route('superadmin.tarif-jenjang.toggle-active', $tarif) }}">
                                            @csrf
                                            <button type="submit" class="btn-secondary px-3" title="Aktif/Nonaktif">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.tarif-jenjang.destroy', $tarif) }}">
                                            @csrf
                                            <button type="submit" class="btn-danger px-3" data-confirm="Hapus tarif ini?" data-confirm-title="Hapus Tarif">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-muted">Belum ada tarif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'stats')
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="card">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted">Total Materi</div>
                <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($materialTotal) }}</div>
            </div>
            <div class="card">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted">Total Bank Soal</div>
                <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($questionTotal) }}</div>
            </div>
            <div class="card">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted">Konten Landing</div>
                <div class="mt-2 text-sm text-textSecondary dark:text-slate-300">FAQ: <span class="font-bold text-slate-900 dark:text-white">{{ number_format($faqTotal) }}</span></div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Pricing plan: <span class="font-bold text-slate-900 dark:text-white">{{ number_format($pricingTotal) }}</span></div>
            </div>
        </div>

        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">Ringkasan Materi & Soal</h2>
                    <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Ini sama dengan statistik yang tampil di landing.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if (($sectionActives['stats'] ?? true) === true)
                        <span class="badge-success">Aktif</span>
                    @else
                        <span class="badge-danger">Nonaktif</span>
                    @endif
                    <form method="POST" action="{{ route('superadmin.landing-settings.sections.toggle', ['section' => 'stats']) }}">
                        @csrf
                        <button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan section statistik">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-4 space-y-8">
                @forelse ($stats as $jenjang => $mapels)
                    <div>
                        <div class="mb-3 flex items-center gap-3">
                            <span class="badge-info">{{ $jenjang }}</span>
                            <div class="font-bold text-slate-900 dark:text-white">Materi & Soal</div>
                        </div>
                        <div class="table-container">
                            <table class="table-ujion min-w-full">
                                <thead>
                                    <tr>
                                        <th>Mapel</th>
                                        <th>Materi</th>
                                        <th>Soal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mapels as $mapel => $data)
                                        <tr>
                                            <td class="font-semibold">{{ $mapel }}</td>
                                            <td>{{ $data['materials'] ?? 0 }}</td>
                                            <td>{{ $data['questions'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-muted">Belum ada data statistik.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
