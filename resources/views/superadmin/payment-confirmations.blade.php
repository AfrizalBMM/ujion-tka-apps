@extends('layouts.superadmin')

@section('title', 'Konfirmasi Pembayaran')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Konfirmasi Pembayaran</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Review transaksi QRIS yang sudah mengunggah bukti pembayaran sebelum akun guru diaktifkan.</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Menunggu review</div>
            <div class="mt-2 text-2xl font-bold text-blue-700">{{ $summary['pending'] }}</div>
            <div class="mt-1 text-sm text-muted">Transaksi dengan bukti pembayaran yang siap direview.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Disetujui</div>
            <div class="mt-2 text-2xl font-bold text-green-700">{{ $summary['success'] }}</div>
            <div class="mt-1 text-sm text-muted">Pembayaran sukses dan akun guru sudah aktif.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu perbaikan</div>
            <div class="mt-2 text-2xl font-bold text-rose-700">{{ $summary['failed'] }}</div>
            <div class="mt-1 text-sm text-muted">Bukti ditolak dan menunggu upload ulang.</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.payment-confirmations.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
            <input type="text" name="q" value="{{ $search }}" class="input w-full" placeholder="Cari kode referensi, nama guru, email, WA, atau paket">
            <button type="submit" class="btn-primary">Cari</button>
            <a href="{{ route('superadmin.payment-confirmations.index') }}" class="btn-secondary text-center">Reset</a>
        </form>

        <div class="table-container">
            <table class="table-ujion min-w-[980px]">
                <thead>
                    <tr>
                        <th>Referensi</th>
                        <th>Guru</th>
                        <th>Paket</th>
                        <th>Bukti</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="font-bold">{{ $transaction->reference_code }}</div>
                                <div class="mt-1 text-xs text-muted">{{ optional($transaction->payment_submitted_at)->format('d M Y H:i') ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold">{{ $transaction->user->name }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $transaction->user->email }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $transaction->user->no_wa ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $transaction->plan_name }}</div>
                                <div class="mt-1 text-sm text-muted">Rp{{ number_format((float) $transaction->amount, 0, ',', '.') }}</div>
                            </td>
                            <td>
                                @if ($transaction->payment_proof_path)
                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            class="btn-secondary text-xs"
                                            data-payment-proof-open
                                            data-payment-proof-src="{{ \Illuminate\Support\Facades\Storage::url($transaction->payment_proof_path) }}"
                                            data-payment-proof-name="{{ $transaction->user->name }}"
                                        >
                                            Lihat bukti
                                        </button>
                                        <div>
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($transaction->payment_proof_path) }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">Buka file asli</a>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs italic text-muted">Belum ada bukti</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('superadmin.payment-confirmations.approve', $transaction) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary">Approve</button>
                                    </form>
                                    <button
                                        type="button"
                                        class="btn-danger"
                                        data-reject-payment-open
                                        data-reject-payment-action="{{ route('superadmin.payment-confirmations.reject', $transaction) }}"
                                        data-reject-payment-name="{{ $transaction->user->name }} | {{ $transaction->reference_code }}"
                                    >
                                        Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-muted">Belum ada transaksi pending yang perlu direview.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

<div id="reject-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Tolak pembayaran</div>
                <div id="reject-payment-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Transaksi</div>
                <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">Masukkan alasan penolakan agar guru tahu apa yang perlu diperbaiki.</p>
            </div>
            <button type="button" class="btn-secondary" data-reject-payment-close>Tutup</button>
        </div>

        <form id="reject-payment-form" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="rejection_reason" class="mb-2 block text-xs font-bold uppercase tracking-wide text-muted">Alasan penolakan</label>
                <textarea
                    id="rejection_reason"
                    name="rejection_reason"
                    class="input min-h-32 w-full"
                    placeholder="Contoh: bukti transfer belum jelas, nominal tidak sesuai, atau nama pengirim belum terlihat."
                    required
                ></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn-secondary" data-reject-payment-close>Batal</button>
                <button type="submit" class="btn-danger">Kirim Penolakan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const proofModal = document.getElementById('payment-proof-modal');
    const proofImage = document.getElementById('payment-proof-image');
    const proofTitle = document.getElementById('payment-proof-title');
    const rejectPaymentModal = document.getElementById('reject-payment-modal');
    const rejectPaymentForm = document.getElementById('reject-payment-form');
    const rejectPaymentTitle = document.getElementById('reject-payment-title');
    const rejectPaymentReason = document.getElementById('rejection_reason');

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

    document.querySelectorAll('[data-reject-payment-open]').forEach((button) => {
        button.addEventListener('click', () => {
            if (rejectPaymentForm) {
                rejectPaymentForm.action = button.getAttribute('data-reject-payment-action') || '';
            }

            if (rejectPaymentTitle) {
                rejectPaymentTitle.textContent = button.getAttribute('data-reject-payment-name') || 'Transaksi';
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
