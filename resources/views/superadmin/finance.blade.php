@extends('layouts.superadmin')

@section('title', 'Keuangan')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-2xl font-bold">
                Keuangan
                <button
                    type="button"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-xs text-slate-500 transition hover:border-primary hover:text-primary dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                    data-catatan-operasional-open
                    title="Catatan Operasional"
                    aria-label="Catatan Operasional"
                >
                    <i class="fa-solid fa-info"></i>
                </button>
            </h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola nomor WhatsApp admin, payment gateway Midtrans, dan tarif aktivasi per jenjang.</p>
        </div>
    </div>

    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Pengaturan Pembayaran</h2>
                <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Nomor WhatsApp admin untuk redirect konfirmasi dan payment gateway Midtrans untuk pembayaran otomatis (GoPay, QRIS, Virtual Account, dll).</p>
            </div>
            <div class="flex shrink-0 gap-2">
                @if (! blank($adminWhatsapp))
                    <span class="badge-success">WA Tersimpan</span>
                @else
                    <span class="badge-warning">WA Belum diisi</span>
                @endif
                @if ($midtransSettings['enabled'])
                    <span class="badge-success">Midtrans Aktif</span>
                @else
                    <span class="badge-warning">Midtrans Nonaktif</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('superadmin.finance.settings') }}" class="mt-5 space-y-5">
            @csrf

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Nomor WhatsApp Admin</label>
                <input class="input w-full sm:max-w-md" name="admin_whatsapp" value="{{ old('admin_whatsapp', $adminWhatsapp) }}" placeholder="62812xxxxxxx / 08xxxxxxx">
                <p class="mt-1 text-xs text-textSecondary dark:text-slate-300">Nomor ini dipakai untuk tombol hubungi admin di halaman pembayaran dan notifikasi WA.</p>
            </div>

            <div class="border-t border-border pt-5 dark:border-slate-800">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-950/40">
                    <input
                        type="checkbox"
                        name="midtrans_enabled"
                        value="1"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                        @checked($midtransSettings['enabled'])
                    >
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Aktifkan pembayaran Midtrans</span>
                        <span class="mt-0.5 block text-xs text-textSecondary dark:text-slate-400">Transaksi sukses tercatat otomatis dan akun guru langsung aktif. Jika nonaktif, guru hanya bisa menghubungi admin via WhatsApp untuk pembayaran.</span>
                    </span>
                </label>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Mode</label>
                        <div class="ssd-wrap mt-1">
                            <input type="hidden" name="midtrans_environment" value="{{ old('midtrans_environment', $midtransSettings['environment']) }}">
                            <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                                <span class="ssd-label">{{ old('midtrans_environment', $midtransSettings['environment']) === 'production' ? 'Production' : 'Sandbox (Uji Coba)' }}</span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                            </button>
                            <div class="ssd-panel">
                                <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mode..."></div>
                                <div class="ssd-list">
                                    <div class="ssd-option{{ old('midtrans_environment', $midtransSettings['environment']) === 'sandbox' ? ' ssd-selected' : '' }}" data-value="sandbox">Sandbox (Uji Coba)</div>
                                    <div class="ssd-option{{ old('midtrans_environment', $midtransSettings['environment']) === 'production' ? ' ssd-selected' : '' }}" data-value="production">Production</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Client Key</label>
                        <input class="input w-full font-mono text-xs" name="midtrans_client_key" value="{{ old('midtrans_client_key', $midtransSettings['client_key']) }}" placeholder="SB-Mid-client-xxxx" autocomplete="off">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Server Key</label>
                        <input class="input w-full font-mono text-xs" name="midtrans_server_key" value="{{ old('midtrans_server_key', $midtransSettings['server_key']) }}" placeholder="SB-Mid-server-xxxx" autocomplete="off">
                        <p class="mt-1 text-xs text-textSecondary dark:text-slate-300">Ambil dari Dashboard Midtrans → Settings → Access Keys. Webhook notifikasi: <code>{{ route('api.payments.midtrans.notification') }}</code></p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-border pt-4 dark:border-slate-800">
                <button type="submit" class="btn-primary whitespace-nowrap">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Tarif Aktivasi per Jenjang</h2>
                <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tambah, edit, dan aktif/nonaktifkan tarif untuk tiap jenjang.</p>
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
                                        >
                                            <i class="fa-solid fa-pen w-4"></i>
                                            Edit
                                        </button>

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
                                                data-confirm="Hapus tarif ini?"
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
                            <td colspan="5" class="py-12 text-center text-muted">Belum ada tarif yang terinput.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="catatan-operasional-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase tracking-wide text-muted">Catatan Operasional</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Keuangan</div>
            </div>
            <button type="button" class="btn-secondary" data-catatan-operasional-close>Tutup</button>
        </div>

        <div class="mt-5 space-y-5">
            <div>
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">Tarif & Pembayaran</div>
                <ul class="mt-2 space-y-2 text-sm text-textSecondary dark:text-slate-300">
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Tarif ditentukan per jenjang (SD/SMP/SMA) sesuai pilihan saat daftar.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Nominal akan dipakai otomatis pada halaman pembayaran Midtrans.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Transaksi sukses tercatat otomatis di menu Riwayat Transaksi.</span></li>
                </ul>
            </div>

            <div>
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">Alur Midtrans</div>
                <ul class="mt-2 space-y-2 text-sm text-textSecondary dark:text-slate-300">
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Aktifkan checkbox Midtrans lalu isi Server Key & Client Key dari Dashboard Midtrans.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Mode Sandbox untuk uji coba, Production setelah bisnis disetujui Midtrans.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Transaksi sukses tercatat otomatis dan akun guru langsung aktif tanpa review admin.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Jika Midtrans nonaktif, halaman pembayaran menampilkan tombol hubungi admin via WhatsApp.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Webhook notifikasi: <code class="break-all">{{ route('api.payments.midtrans.notification') }}</code></span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    const catatanModal = document.getElementById('catatan-operasional-modal');

    document.querySelectorAll('[data-catatan-operasional-open]').forEach((button) => {
        button.addEventListener('click', () => {
            catatanModal?.classList.remove('hidden');
            catatanModal?.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-catatan-operasional-close]').forEach((button) => {
        button.addEventListener('click', () => {
            catatanModal?.classList.add('hidden');
            catatanModal?.classList.remove('flex');
        });
    });

    catatanModal?.addEventListener('click', (event) => {
        if (event.target === catatanModal) {
            catatanModal.classList.add('hidden');
            catatanModal.classList.remove('flex');
        }
    });
</script>

<div id="qris-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div id="qris-form-title" class="text-base font-bold text-slate-900 dark:text-slate-100">Tambah Tarif</div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Isi tarif aktivasi per jenjang yang dipakai sebagai nominal pembayaran Midtrans.</div>
            </div>
            <button type="button" class="btn-secondary" data-qris-form-close>Tutup</button>
        </div>

        <form id="qris-form" class="mt-5 space-y-4" method="POST" action="{{ route('superadmin.tarif-jenjang.store') }}">
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
