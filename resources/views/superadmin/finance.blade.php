@extends('layouts.superadmin')

@section('title', 'Keuangan & QRIS')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Keuangan & QRIS</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola nomor WhatsApp admin untuk redirect konfirmasi pembayaran dan atur tarif aktivasi per jenjang.</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted">WhatsApp Admin</div>
                    <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Redirect setelah upload bukti</div>
                </div>
                @if (! blank($adminWhatsapp))
                    <span class="badge-success">Tersimpan</span>
                @else
                    <span class="badge-warning">Belum diisi</span>
                @endif
            </div>

            <p class="mt-3 text-sm text-textSecondary dark:text-slate-300">Nomor ini dipakai untuk tombol/redirect otomatis setelah guru upload bukti pembayaran.</p>

            <form method="POST" action="{{ route('superadmin.finance.settings') }}" class="mt-4 flex flex-col gap-3">
                @csrf
                <div class="w-full">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Nomor WhatsApp Admin</label>
                    <input class="input w-full" name="admin_whatsapp" value="{{ old('admin_whatsapp', $adminWhatsapp) }}" placeholder="62812xxxxxxx / 08xxxxxxx">
                </div>
                
                <div class="w-full mt-2">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">QRIS Master Payload</label>
                    <textarea class="input w-full min-h-24 font-mono text-xs" name="master_payload" placeholder="000201010211...">{{ old('master_payload', $masterPayload) }}</textarea>
                    <p class="mt-1 text-xs text-textSecondary dark:text-slate-300">Payload QRIS default (dari aplikasi Gopay/merchant). Sistem akan menyisipkan tag 54 (nominal) secara otomatis.</p>
                </div>
                
                <div class="flex justify-end mt-2">
                    <button type="submit" class="btn-primary whitespace-nowrap">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Catatan Operasional</div>
            <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Alur QRIS</div>
            <ul class="mt-3 space-y-2 text-sm text-textSecondary dark:text-slate-300">
                <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Tarif ditentukan per jenjang (SD/SMP/SMA) sesuai pilihan saat daftar.</span></li>
                <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Nominal akan dipakai otomatis pada halaman pembayaran QRIS.</span></li>
                <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Label print bisa memakai gambar QRIS (opsional) atau fallback hasil generate.</span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Tarif Aktivasi per Jenjang</h2>
                <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tambah, edit, aktif/nonaktif, dan print label QRIS untuk tiap jenjang.</p>
            </div>
            <button type="button" class="btn-primary whitespace-nowrap" data-qris-form-open>
                <i class="fa-solid fa-plus mr-2"></i>
                Tambah Tarif
            </button>
        </div>

        <div class="mt-4 table-container">
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
                    @forelse ($tarifJenjangs as $tarifJenjang)
                        <tr>
                            <td>
                                <span class="badge-info">{{ $tarifJenjang->jenjang ?: '-' }}</span>
                            </td>
                            <td>
                                <div class="font-semibold">{{ $tarifJenjang->name }}</div>
                                @if ($tarifJenjang->subtitle)
                                    <div class="mt-1 text-xs text-muted">{{ $tarifJenjang->subtitle }}</div>
                                @endif
                            </td>
                            <td class="font-semibold">Rp {{ number_format((int) $tarifJenjang->price, 0, ',', '.') }}</td>
                            <td>
                                @if ($tarifJenjang->is_active)
                                    <span class="badge-success">Aktif</span>
                                @else
                                    <span class="badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="relative inline-block text-left" data-action-menu>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-all duration-200 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                                        data-action-menu-toggle
                                        aria-expanded="false"
                                        title="Buka aksi"
                                    >
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>

                                    <div
                                        class="invisible absolute right-0 top-full z-20 mt-2 min-w-56 translate-y-2 rounded-2xl border border-slate-200/80 bg-white p-2 opacity-0 shadow-modal transition-all duration-200 dark:border-slate-800 dark:bg-slate-950"
                                        data-action-menu-panel
                                    >
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                            data-qris-edit
                                            data-qris-id="{{ $tarifJenjang->id }}"
                                            data-qris-name="{{ $tarifJenjang->name }}"
                                            data-qris-jenjang="{{ $tarifJenjang->jenjang }}"
                                            data-qris-price="{{ $tarifJenjang->price }}"
                                            data-qris-subtitle="{{ $tarifJenjang->subtitle }}"
                                            data-qris-description="{{ $tarifJenjang->description }}"
                                            data-qris-update-action="{{ route('superadmin.tarif-jenjang.update', $tarifJenjang) }}"
                                            @if ($hasQrisImageColumn)
                                                data-qris-image-url="{{ $tarifJenjang->qris_image_path ? route('superadmin.tarif-jenjang.image', $tarifJenjang) : '' }}"
                                            @endif
                                        >
                                            <i class="fa-solid fa-pen w-4"></i>
                                            Edit
                                        </button>

                                        <a
                                            href="{{ route('superadmin.tarif-jenjang.print', $tarifJenjang) }}"
                                            target="_blank"
                                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-primary/8 hover:text-primary dark:text-slate-200 dark:hover:bg-primary/10"
                                        >
                                            <i class="fa-solid fa-print w-4"></i>
                                            Print label
                                        </a>

                                        @if ($hasQrisImageColumn && ! blank($tarifJenjang->qris_image_path))
                                            <a
                                                href="{{ route('superadmin.tarif-jenjang.image', $tarifJenjang) }}"
                                                target="_blank"
                                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                            >
                                                <i class="fa-solid fa-image w-4"></i>
                                                Buka gambar QRIS
                                            </a>
                                        @endif

                                        <form method="POST" action="{{ route('superadmin.tarif-jenjang.toggle-active', $tarifJenjang) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-amber-50 hover:text-amber-700 dark:text-slate-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300"
                                            >
                                                <i class="fa-solid fa-eye w-4"></i>
                                                Aktif / Nonaktif
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('superadmin.tarif-jenjang.destroy', $tarifJenjang) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700 dark:text-slate-200 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                                data-confirm="Hapus tarif/QRIS ini?"
                                                data-confirm-title="Hapus Tarif"
                                            >
                                                <i class="fa-solid fa-trash w-4"></i>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-muted">Belum ada tarif/QRIS yang terinput.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="qris-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div id="qris-form-title" class="text-base font-bold text-slate-900 dark:text-slate-100">Tambah QRIS</div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Isi tarif aktivasi per jenjang. Opsional: upload gambar QRIS untuk dipakai saat print label.</div>
            </div>
            <button type="button" class="btn-secondary" data-qris-form-close>Tutup</button>
        </div>

        <form id="qris-form" class="mt-5 space-y-4" method="POST" action="{{ route('superadmin.tarif-jenjang.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
                    <input id="qris-name" class="input mt-1" name="name" placeholder="Contoh: Aktivasi Guru SD" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang</label>
                    <div class="ssd-wrap mt-1">
                        <input type="hidden" name="jenjang" id="qris-jenjang" value="" {{ $hasJenjangColumn ? 'required' : 'disabled' }}>
                        <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                            <span class="ssd-label">{{ $hasJenjangColumn ? 'Pilih jenjang' : 'Jalankan migrate untuk aktifkan jenjang' }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                        </button>
                        <div class="ssd-panel">
                            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                            <div class="ssd-list">
                                <div class="ssd-option ssd-selected" data-value="">Pilih jenjang</div>
                                @foreach (config('ujion.jenjangs') as $jenjang)
                                    <div class="ssd-option" data-value="{{ $jenjang }}">{{ $jenjang }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if (! $hasJenjangColumn)
                        <p class="mt-1 text-xs text-muted">Kolom `jenjang` belum ada di DB. Jalankan `php artisan migrate` untuk mengaktifkan tarif per jenjang.</p>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Keterangan</label>
                    <textarea id="qris-description" class="input mt-1 min-h-24" name="description" placeholder="Contoh: Aktivasi akun guru/operator untuk jenjang ini."></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nominal</label>
                    <input id="qris-price" class="input mt-1" name="price" placeholder="99000" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subtitle (opsional)</label>
                    <input id="qris-subtitle" class="input mt-1" name="subtitle" placeholder="Contoh: Akses akun guru / operator">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Gambar QRIS (opsional)</label>
                    <input
                        id="qris-image"
                        type="file"
                        name="image"
                        accept="image/*"
                        class="input mt-1"
                        {{ $hasQrisImageColumn ? '' : 'disabled' }}
                    >
                    @if (! $hasQrisImageColumn)
                        <p class="mt-1 text-xs text-muted">Kolom `qris_image_path` belum ada di DB. Upload gambar dinonaktifkan.</p>
                    @endif

                    <div id="qris-image-preview-wrap" class="mt-3 hidden">
                        <img id="qris-image-preview" src="" alt="Preview QRIS" class="max-h-56 rounded-xl border border-border bg-white object-contain">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="btn-secondary" data-qris-form-reset>Reset</button>
                <button id="qris-submit" class="btn-primary" type="submit">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
