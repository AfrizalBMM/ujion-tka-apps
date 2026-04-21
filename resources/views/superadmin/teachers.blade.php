@extends('layouts.superadmin')

@section('title', 'Manajemen Guru & Operator')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Manajemen Guru & Akses</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Aktivasi akun, bagikan token akses, dan tangani akun guru yang perlu ditinjau ulang.</p>
        </div>
        <button
            type="button"
            class="icon-button shrink-0"
            data-admin-flow-open
            title="Lihat alur kerja admin"
            aria-label="Lihat alur kerja admin"
        >
            <i class="fa-solid fa-circle-info"></i>
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu review</div>
            <div class="mt-2 text-2xl font-bold text-blue-700">{{ $paymentSummary[\App\Models\User::PAYMENT_SUBMITTED] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Guru yang sudah upload bukti pembayaran.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu kirim ulang</div>
            <div class="mt-2 text-2xl font-bold text-rose-700">{{ $paymentSummary[\App\Models\User::PAYMENT_REJECTED] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Masih menunggu bukti yang diperbaiki.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Belum bayar</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">{{ $paymentSummary[\App\Models\User::PAYMENT_AWAITING] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Sudah daftar tetapi belum upload bukti.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Sudah disetujui</div>
            <div class="mt-2 text-2xl font-bold text-green-700">{{ $paymentSummary[\App\Models\User::PAYMENT_APPROVED] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Pembayaran selesai dan akun siap dipakai.</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.teachers.index') }}" class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,2fr)_1fr_1fr_auto]">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Cari guru</label>
                <input type="text" name="q" value="{{ $search }}" class="input w-full" placeholder="Nama, email, WhatsApp, atau sekolah">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status pembayaran</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="payment_status" value="{{ $paymentStatus ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ match($paymentStatus ?? '') {
                            \App\Models\User::PAYMENT_SUBMITTED => 'Menunggu review',
                            \App\Models\User::PAYMENT_REJECTED  => 'Ditolak',
                            \App\Models\User::PAYMENT_AWAITING  => 'Belum upload bukti',
                            \App\Models\User::PAYMENT_APPROVED  => 'Disetujui',
                            default => 'Semua status pembayaran'
                        } }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($paymentStatus ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua status pembayaran</div>
                            <div class="ssd-option{{ ($paymentStatus ?? '') === \App\Models\User::PAYMENT_SUBMITTED ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::PAYMENT_SUBMITTED }}">Menunggu review</div>
                            <div class="ssd-option{{ ($paymentStatus ?? '') === \App\Models\User::PAYMENT_REJECTED ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::PAYMENT_REJECTED }}">Ditolak</div>
                            <div class="ssd-option{{ ($paymentStatus ?? '') === \App\Models\User::PAYMENT_AWAITING ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::PAYMENT_AWAITING }}">Belum upload bukti</div>
                            <div class="ssd-option{{ ($paymentStatus ?? '') === \App\Models\User::PAYMENT_APPROVED ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::PAYMENT_APPROVED }}">Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status akun</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="account_status" value="{{ $accountStatus ?? '' }}">
                    <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ match($accountStatus ?? '') {
                            \App\Models\User::STATUS_PENDING  => 'Pending',
                            \App\Models\User::STATUS_ACTIVE   => 'Aktif',
                            \App\Models\User::STATUS_SUSPEND  => 'Ditangguhkan',
                            default => 'Semua status akun'
                        } }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ ($accountStatus ?? '') === '' ? ' ssd-selected' : '' }}" data-value="">Semua status akun</div>
                            <div class="ssd-option{{ ($accountStatus ?? '') === \App\Models\User::STATUS_PENDING ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::STATUS_PENDING }}">Pending</div>
                            <div class="ssd-option{{ ($accountStatus ?? '') === \App\Models\User::STATUS_ACTIVE ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::STATUS_ACTIVE }}">Aktif</div>
                            <div class="ssd-option{{ ($accountStatus ?? '') === \App\Models\User::STATUS_SUSPEND ? ' ssd-selected' : '' }}" data-value="{{ \App\Models\User::STATUS_SUSPEND }}">Ditangguhkan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary w-full lg:w-auto">Terapkan</button>
                <a href="{{ route('superadmin.teachers.index') }}" class="btn-secondary w-full text-center lg:w-auto">Reset</a>
            </div>
        </form>

        <div class="table-container">
            <table class="table-ujion min-w-[1080px]">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Status Akun</th>
                        <th>Token</th>
                        <th>Bukti Bayar</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($teachers) > 0)
                    @foreach ($teachers as $teacher)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <td>
                                <div class="mb-2">
                                    @if ($teacher->account_status === \App\Models\User::STATUS_ACTIVE)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-green-700 dark:bg-green-500/15 dark:text-green-300">
                                            <i class="fa-solid fa-circle-check text-[9px]"></i>
                                            Aktif
                                        </span>
                                    @elseif ($teacher->account_status === \App\Models\User::STATUS_PENDING)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                            <i class="fa-solid fa-clock text-[9px]"></i>
                                            Menunggu Aktivasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                            <i class="fa-solid fa-ban text-[9px]"></i>
                                            Ditangguhkan
                                        </span>
                                    @endif
                                </div>
                                <div class="font-bold">{{ $teacher->name }}</div>
                                <div class="text-xs text-muted">Terdaftar: {{ $teacher->created_at->format('d M Y') }}</div>
                                <div class="mt-2 text-xs text-muted">{{ $teacher->satuan_pendidikan ?: '-' }}</div>
                            </td>
                            <td class="text-textSecondary dark:text-slate-300">
                                <div>{{ $teacher->email }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $teacher->no_wa ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="mt-2">
                                    @if ($teacher->payment_status === \App\Models\User::PAYMENT_APPROVED)
                                        <span class="badge-success">Pembayaran Disetujui</span>
                                    @elseif ($teacher->payment_status === \App\Models\User::PAYMENT_SUBMITTED)
                                        <span class="badge-info">Menunggu Review Pembayaran</span>
                                    @elseif ($teacher->payment_status === \App\Models\User::PAYMENT_REJECTED)
                                        <span class="badge-danger">Pembayaran Ditolak</span>
                                    @else
                                        <span class="badge-warning">Belum Upload Bukti</span>
                                    @endif
                                </div>
                                @if (! blank($teacher->payment_rejection_reason))
                                    <div class="mt-2 text-xs text-rose-600">Catatan: {{ $teacher->payment_rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                @if($teacher->access_token)
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 font-mono text-sm font-semibold text-blue-700 transition-all duration-200 hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300"
                                        data-copy-token
                                        data-copy-text="{{ $teacher->access_token }}"
                                        title="Klik untuk menyalin token"
                                    >
                                        <i class="fa-regular fa-copy text-xs"></i>
                                        <span>{{ $teacher->access_token }}</span>
                                    </button>
                                    <div class="mt-2 text-xs text-muted">Klik token untuk menyalin.</div>
                                @else
                                    <span class="text-muted italic">Belum aktif</span>
                                @endif
                            </td>
                            <td>
                                @if (! blank($teacher->payment_proof_path))
                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            class="btn-secondary text-xs"
                                            data-payment-proof-open
                                            data-payment-proof-src="{{ \Illuminate\Support\Facades\Storage::url($teacher->payment_proof_path) }}"
                                            data-payment-proof-name="{{ $teacher->name }}"
                                        >
                                            Bukti
                                        </button>
                                        <div>
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($teacher->payment_proof_path) }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">Buka file asli</a>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs italic text-muted">Belum ada bukti</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="relative inline-block text-left" data-action-menu>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-all duration-200 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                                        data-action-menu-toggle
                                        aria-expanded="false"
                                        title="Buka aksi manajemen"
                                    >
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>

                                    <div
                                        class="invisible absolute right-0 top-full z-20 mt-2 min-w-56 translate-y-2 rounded-2xl border border-slate-200/80 bg-white p-2 opacity-0 shadow-modal transition-all duration-200 dark:border-slate-800 dark:bg-slate-950"
                                        data-action-menu-panel
                                    >
                                        @if($teacher->payment_status === \App\Models\User::PAYMENT_SUBMITTED)
                                        <form method="POST" action="{{ route('superadmin.teachers.approve-payment', $teacher) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 dark:text-slate-200 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300">
                                                <i class="fa-solid fa-circle-check w-4"></i>
                                                Setujui pembayaran
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700 dark:text-slate-200 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                            data-reject-payment-open
                                            data-reject-payment-action="{{ route('superadmin.teachers.reject-payment', $teacher) }}"
                                            data-reject-payment-name="{{ $teacher->name }}"
                                        >
                                            <i class="fa-solid fa-reply w-4"></i>
                                            Tolak pembayaran
                                        </button>
                                        @endif

                                        @if($teacher->account_status !== \App\Models\User::STATUS_ACTIVE && $teacher->payment_status !== \App\Models\User::PAYMENT_SUBMITTED)
                                        <form method="POST" action="{{ route('superadmin.teachers.activate', $teacher) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-primary/8 hover:text-primary dark:text-slate-200 dark:hover:bg-primary/10">
                                                <i class="fa-solid fa-user-check w-4"></i>
                                                Aktifkan akun
                                            </button>
                                        </form>
                                        @endif

                                        <form method="POST" action="{{ route('superadmin.teachers.refresh-token', $teacher) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                                <i class="fa-solid fa-rotate w-4"></i>
                                                Refresh token
                                            </button>
                                        </form>

                                        @if($teacher->account_status !== \App\Models\User::STATUS_SUSPEND)
                                        <form method="POST" action="{{ route('superadmin.teachers.suspend', $teacher) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700 dark:text-slate-200 dark:hover:bg-rose-500/10 dark:hover:text-rose-300" data-confirm="Tangguhkan akses guru ini?">
                                                <i class="fa-solid fa-user-slash w-4"></i>
                                                Tangguhkan akses
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <i class="fa-solid fa-users-slash text-4xl text-slate-200 mb-3 block"></i>
                                <span class="text-muted dark:text-slate-400 italic text-lg">Belum ada user dengan role guru untuk saat ini.</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold">Template Pesan Siap Pakai</h2>
                <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Gunakan template ini saat proses verifikasi dan aktivasi masih dilakukan manual.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($notificationTemplates as $template)
                <div class="rounded-card border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold">{{ $template['title'] }}</div>
                        <div class="flex items-center gap-2">
                            <span class="badge-info">{{ $template['audience'] }}</span>
                            <button
                                type="button"
                                class="btn-secondary text-xs"
                                data-copy-template
                                data-copy-text="{{ $template['body'] }}"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                    <pre class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $template['body'] }}</pre>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div id="payment-proof-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-3xl rounded-2xl bg-white p-4 shadow-2xl dark:bg-slate-900">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Preview bukti pembayaran</div>
                <div id="payment-proof-title" class="text-lg font-bold text-slate-900 dark:text-slate-100">Guru</div>
            </div>
            <button type="button" class="btn-secondary" data-payment-proof-close>Tutup</button>
        </div>
        <div class="overflow-hidden rounded-xl border border-border bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
            <img id="payment-proof-image" src="" alt="Bukti pembayaran" class="max-h-[70vh] w-full object-contain">
        </div>
    </div>
</div>

<div id="admin-flow-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Panduan singkat</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Alur kerja yang disarankan untuk admin</div>
            </div>
            <button type="button" class="btn-secondary" data-admin-flow-close>Tutup</button>
        </div>

        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50/80 p-4 text-sm text-blue-900 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-100">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <div>
                    <p class="font-semibold">Urutan kerja yang paling aman</p>
                    <p class="mt-1">Aktifkan akun setelah pembayaran terverifikasi, lalu kirim token akses terbaru melalui kanal yang aman seperti WhatsApp resmi admin.</p>
                </div>
            </div>
        </div>

        <div class="mt-4 space-y-3 text-sm text-textSecondary dark:text-slate-300">
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="font-semibold text-slate-900 dark:text-slate-100">1. Review pembayaran</div>
                <div class="mt-1">Pastikan bukti transfer jelas dan nominal sesuai sebelum akun diaktifkan.</div>
            </div>
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="font-semibold text-slate-900 dark:text-slate-100">2. Setujui atau tolak dengan alasan</div>
                <div class="mt-1">Jika ditolak, berikan catatan yang spesifik agar guru tahu apa yang harus diperbaiki.</div>
            </div>
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="font-semibold text-slate-900 dark:text-slate-100">3. Bagikan token lewat kanal aman</div>
                <div class="mt-1">Setelah aktif, kirim token terbaru dan minta guru memakai token yang paling baru saat login.</div>
            </div>
        </div>
    </div>
</div>

<div id="reject-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Tolak pembayaran</div>
                <div id="reject-payment-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Guru</div>
                <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">Masukkan alasan penolakan agar guru tahu apa yang perlu diperbaiki.</p>
            </div>
            <button type="button" class="btn-secondary" data-reject-payment-close>Tutup</button>
        </div>

        <form id="reject-payment-form" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="payment_rejection_reason" class="mb-2 block text-xs font-bold uppercase tracking-wide text-muted">Alasan penolakan</label>
                <textarea
                    id="payment_rejection_reason"
                    name="payment_rejection_reason"
                    class="input min-h-32 w-full"
                    placeholder="Contoh: Bukti transfer belum jelas, nominal tidak sesuai, atau data pengirim belum terlihat."
                    required
                ></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn-secondary" data-reject-payment-close>Batal</button>
                <button type="submit" class="btn-danger">
                    <i class="fa-solid fa-paper-plane"></i>
                    Kirim Penolakan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();

            const menu = button.closest('[data-action-menu]');
            const panel = menu?.querySelector('[data-action-menu-panel]');
            const isOpen = button.getAttribute('aria-expanded') === 'true';

            document.querySelectorAll('[data-action-menu-toggle]').forEach((otherButton) => {
                otherButton.setAttribute('aria-expanded', 'false');
            });

            document.querySelectorAll('[data-action-menu-panel]').forEach((otherPanel) => {
                otherPanel.classList.add('invisible', 'translate-y-2', 'opacity-0');
            });

            if (!panel || isOpen) {
                return;
            }

            button.setAttribute('aria-expanded', 'true');
            panel.classList.remove('invisible', 'translate-y-2', 'opacity-0');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });

        document.querySelectorAll('[data-action-menu-panel]').forEach((panel) => {
            panel.classList.add('invisible', 'translate-y-2', 'opacity-0');
        });
    });

    document.querySelectorAll('[data-copy-template]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy-text') || '';
            const original = button.textContent;

            try {
                await navigator.clipboard.writeText(text);
                button.textContent = 'Tersalin';
                setTimeout(() => {
                    button.textContent = original;
                }, 1200);
            } catch (error) {
                button.textContent = 'Gagal';
                setTimeout(() => {
                    button.textContent = original;
                }, 1200);
            }
        });
    });

    document.querySelectorAll('[data-copy-token]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy-text') || '';
            const icon = button.querySelector('i');
            const label = button.querySelector('span');
            const originalLabel = label?.textContent || '';

            try {
                await navigator.clipboard.writeText(text);
                if (icon) {
                    icon.className = 'fa-solid fa-check text-xs';
                }
                if (label) {
                    label.textContent = 'Token tersalin';
                }
            } catch (error) {
                if (icon) {
                    icon.className = 'fa-solid fa-xmark text-xs';
                }
                if (label) {
                    label.textContent = 'Gagal menyalin';
                }
            }

            setTimeout(() => {
                if (icon) {
                    icon.className = 'fa-regular fa-copy text-xs';
                }
                if (label) {
                    label.textContent = originalLabel;
                }
            }, 1200);
        });
    });

    const proofModal = document.getElementById('payment-proof-modal');
    const proofImage = document.getElementById('payment-proof-image');
    const proofTitle = document.getElementById('payment-proof-title');
    const adminFlowModal = document.getElementById('admin-flow-modal');
    const rejectPaymentModal = document.getElementById('reject-payment-modal');
    const rejectPaymentForm = document.getElementById('reject-payment-form');
    const rejectPaymentTitle = document.getElementById('reject-payment-title');
    const rejectPaymentReason = document.getElementById('payment_rejection_reason');

    document.querySelectorAll('[data-payment-proof-open]').forEach((button) => {
        button.addEventListener('click', () => {
            proofImage.src = button.getAttribute('data-payment-proof-src') || '';
            proofTitle.textContent = button.getAttribute('data-payment-proof-name') || 'Guru';
            proofModal.classList.remove('hidden');
            proofModal.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-payment-proof-close]').forEach((button) => {
        button.addEventListener('click', () => {
            proofModal.classList.add('hidden');
            proofModal.classList.remove('flex');
            proofImage.src = '';
        });
    });

    proofModal?.addEventListener('click', (event) => {
        if (event.target === proofModal) {
            proofModal.classList.add('hidden');
            proofModal.classList.remove('flex');
            proofImage.src = '';
        }
    });

    document.querySelectorAll('[data-admin-flow-open]').forEach((button) => {
        button.addEventListener('click', () => {
            adminFlowModal?.classList.remove('hidden');
            adminFlowModal?.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-admin-flow-close]').forEach((button) => {
        button.addEventListener('click', () => {
            adminFlowModal?.classList.add('hidden');
            adminFlowModal?.classList.remove('flex');
        });
    });

    adminFlowModal?.addEventListener('click', (event) => {
        if (event.target === adminFlowModal) {
            adminFlowModal.classList.add('hidden');
            adminFlowModal.classList.remove('flex');
        }
    });

    document.querySelectorAll('[data-reject-payment-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.getAttribute('data-reject-payment-action') || '';
            const name = button.getAttribute('data-reject-payment-name') || 'Guru';

            if (rejectPaymentForm) {
                rejectPaymentForm.setAttribute('action', action);
            }

            if (rejectPaymentTitle) {
                rejectPaymentTitle.textContent = name;
            }

            if (rejectPaymentReason) {
                rejectPaymentReason.value = '';
            }

            rejectPaymentModal?.classList.remove('hidden');
            rejectPaymentModal?.classList.add('flex');
            rejectPaymentReason?.focus();
        });
    });

    document.querySelectorAll('[data-reject-payment-close]').forEach((button) => {
        button.addEventListener('click', () => {
            rejectPaymentModal?.classList.add('hidden');
            rejectPaymentModal?.classList.remove('flex');
        });
    });

    rejectPaymentModal?.addEventListener('click', (event) => {
        if (event.target === rejectPaymentModal) {
            rejectPaymentModal.classList.add('hidden');
            rejectPaymentModal.classList.remove('flex');
        }
    });
</script>
@endsection
