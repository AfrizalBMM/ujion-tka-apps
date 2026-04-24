@extends('layouts.superadmin')

@section('title', 'Manajemen Keuangan')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Manajemen QR & Harga</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Atur satu QRIS master dan nominal aktivasi per jenjang agar flow pendaftaran guru tetap sederhana.</p>
    </div>

    <div class="card border border-amber-100 bg-amber-50/80">
        <div class="flex items-start gap-3 text-sm text-amber-900">
            <i class="fa-solid fa-receipt mt-0.5"></i>
            <div>
                <p class="font-semibold">Catatan operasional</p>
                <p class="mt-1">Sistem sekarang memakai satu QRIS master dan tarif per jenjang. Guru memilih jenjang saat daftar, lalu nominal diambil otomatis dari pengaturan di halaman ini.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="mb-6 rounded-2xl border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="font-bold text-lg">WhatsApp Admin</div>
                        @if (! blank($adminWhatsapp))
                            <span class="badge-success"><i class="fa-solid fa-circle-check mr-1"></i> Tersimpan</span>
                        @else
                            <span class="badge-warning"><i class="fa-solid fa-circle-exclamation mr-1"></i> Belum diisi</span>
                        @endif
                    </div>
                    <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Nomor ini dipakai untuk redirect otomatis setelah upload bukti pembayaran.</div>
                </div>
                <form method="POST" action="{{ route('superadmin.finance.admin-whatsapp') }}" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-end">
                    @csrf
                    <div class="w-full sm:w-80">
                        <input class="input mt-1 w-full" name="admin_whatsapp" value="{{ old('admin_whatsapp', $adminWhatsapp) }}" placeholder="62812xxxxxxx / 08xxxxxxx">
                    </div>
                    <button type="submit" class="btn-primary w-full sm:w-auto">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan
                    </button>
                </form>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="font-bold text-lg">QRIS per Jenjang</div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tambah QRIS (gambar) sekaligus judul, jenjang, dan nominal. Tabel di bawah menampilkan semua QRIS yang sudah terinput.</div>
            </div>
            <button type="button" class="btn-primary w-full sm:w-auto" data-qris-form-open>
                <i class="fa-solid fa-plus mr-2"></i> Add QRIS
            </button>
        </div>

        <div class="mt-8 table-container">
            <table class="table-ujion min-w-[980px]">
                <thead>
                    <tr>
                        <th>Jenjang</th>
                        <th>Judul</th>
                        <th>Nominal</th>
                        <th>Gambar</th>
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
                                @if ($hasQrisImageColumn && ! blank($tarifJenjang->qris_image_path))
                                    <img src="{{ route('superadmin.tarif-jenjang.image', $tarifJenjang) }}" alt="QRIS" class="h-12 w-12 rounded-lg object-cover border border-border dark:border-slate-800">
                                @else
                                    <span class="text-xs italic text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($tarifJenjang->is_active)
                                    <span class="badge-success">Aktif</span>
                                @else
                                    <span class="badge-danger">Off</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="btn-secondary p-2"
                                        title="Edit"
                                        data-qris-edit
                                        data-qris-id="{{ $tarifJenjang->id }}"
                                        data-qris-name="{{ $tarifJenjang->name }}"
                                        data-qris-jenjang="{{ $tarifJenjang->jenjang }}"
                                        data-qris-price="{{ $tarifJenjang->price }}"
                                        data-qris-subtitle="{{ $tarifJenjang->subtitle }}"
                                        data-qris-description="{{ $tarifJenjang->description }}"
                                        data-qris-image-url="{{ $hasQrisImageColumn && ! blank($tarifJenjang->qris_image_path) ? route('superadmin.tarif-jenjang.image', $tarifJenjang) : '' }}"
                                        data-qris-update-action="{{ route('superadmin.tarif-jenjang.update', $tarifJenjang) }}"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="{{ route('superadmin.tarif-jenjang.print', $tarifJenjang) }}" target="_blank" rel="noopener noreferrer" class="btn-secondary p-2" title="Print Label QRIS">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <form method="POST" action="{{ route('superadmin.tarif-jenjang.toggle-active', $tarifJenjang) }}">
                                        @csrf
                                        <button class="btn-secondary p-2" type="submit" title="Toggle aktif"><i class="fa-solid fa-eye"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('superadmin.tarif-jenjang.destroy', $tarifJenjang) }}">
                                        @csrf
                                        <button class="btn-danger p-2" type="submit" data-confirm="Hapus tarif/QRIS ini?" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted">Belum ada QRIS yang terinput.</td>
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
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Isi tarif aktivasi per jenjang. Gambar QRIS dipakai untuk tampilan dan print label (fallback ke generate bila kosong).</div>
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
                    <select id="qris-jenjang" class="input mt-1" name="jenjang" {{ $hasJenjangColumn ? 'required' : 'disabled' }}>
                        <option value="" disabled selected>{{ $hasJenjangColumn ? 'Pilih jenjang' : 'Jalankan migrate untuk aktifkan jenjang' }}</option>
                        @foreach (config('ujion.jenjangs') as $jenjang)
                            <option value="{{ $jenjang }}">{{ $jenjang }}</option>
                        @endforeach
                    </select>
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
                    <input id="qris-image" class="input mt-1" type="file" name="image" accept="image/*" {{ $hasQrisImageColumn ? '' : 'disabled' }}>
                    @if (! $hasQrisImageColumn)
                        <p class="mt-1 text-xs text-muted">Kolom `qris_image_path` belum ada di DB. Jalankan `php artisan migrate` agar upload gambar QRIS aktif.</p>
                    @endif
                    <div id="qris-image-preview-wrap" class="mt-3 hidden flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                        <img id="qris-image-preview" src="" alt="Preview QRIS" class="h-20 w-20 rounded-lg object-cover border border-slate-200 dark:border-slate-800">
                        <div class="text-xs text-muted">Gambar saat ini. Upload file baru untuk mengganti.</div>
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
