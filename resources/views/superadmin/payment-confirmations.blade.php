@extends('layouts.superadmin')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Riwayat Transaksi</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Semua transaksi pembayaran aktivasi guru, termasuk pembayaran otomatis via Midtrans.</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('superadmin.payment-confirmations.index', ['status' => 'pending']) }}" class="card transition hover:ring-2 hover:ring-blue-100">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Menunggu pembayaran</div>
            <div class="mt-2 text-2xl font-bold text-blue-700">{{ $summary['pending'] }}</div>
            <div class="mt-1 text-sm text-muted">Transaksi yang belum diselesaikan guru.</div>
        </a>
        <a href="{{ route('superadmin.payment-confirmations.index', ['status' => 'success']) }}" class="card transition hover:ring-2 hover:ring-emerald-100">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Sukses</div>
            <div class="mt-2 text-2xl font-bold text-green-700">{{ $summary['success'] }}</div>
            <div class="mt-1 text-sm text-muted">Pembayaran berhasil dan akun guru aktif.</div>
        </a>
        <a href="{{ route('superadmin.payment-confirmations.index', ['status' => 'failed']) }}" class="card transition hover:ring-2 hover:ring-rose-100">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Gagal / Kadaluarsa</div>
            <div class="mt-2 text-2xl font-bold text-rose-700">{{ $summary['failed'] }}</div>
            <div class="mt-1 text-sm text-muted">Pembayaran tidak selesai atau dibatalkan.</div>
        </a>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.payment-confirmations.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
            <input type="text" name="q" value="{{ $search }}" class="input w-full" placeholder="Cari kode referensi, order Midtrans, nama guru, email, WA, atau paket">
            <select name="status" class="input sm:w-48">
                <option value="">Semua status</option>
                <option value="pending" @selected($statusFilter === 'pending')>Menunggu pembayaran</option>
                <option value="success" @selected($statusFilter === 'success')>Sukses</option>
                <option value="failed" @selected($statusFilter === 'failed')>Gagal</option>
            </select>
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
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="font-bold">{{ $transaction->reference_code }}</div>
                                @if (! blank($transaction->midtrans_order_id) && $transaction->midtrans_order_id !== $transaction->reference_code)
                                    <div class="mt-1 text-xs text-muted">{{ $transaction->midtrans_order_id }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="font-semibold">{{ $transaction->user?->name ?? '-' }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $transaction->user?->email ?? '-' }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $transaction->user?->no_wa ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $transaction->plan_name }}</div>
                                <div class="mt-1 text-sm text-muted">Rp{{ number_format((float) $transaction->amount, 0, ',', '.') }}</div>
                            </td>
                            <td>
                                @if ($transaction->payment_method === \App\Models\Transaction::PAYMENT_METHOD_MIDTRANS)
                                    <span class="badge-info">Midtrans</span>
                                    @if (! blank($transaction->midtrans_payment_type))
                                        <div class="mt-1 text-xs text-muted">{{ $transaction->midtrans_payment_type }}</div>
                                    @endif
                                @else
                                    <span class="badge-warning">Manual</span>
                                @endif
                            </td>
                            <td>
                                @if ($transaction->status === \App\Models\Transaction::STATUS_SUCCESS)
                                    <span class="badge-success">Sukses</span>
                                @elseif ($transaction->status === \App\Models\Transaction::STATUS_FAILED)
                                    <span class="badge-danger">Gagal</span>
                                @else
                                    <span class="badge-warning">Menunggu</span>
                                @endif
                            </td>
                            <td>
                                @if ($transaction->paid_at)
                                    <div class="text-xs font-semibold text-emerald-700">Dibayar {{ $transaction->paid_at->format('d M Y H:i') }}</div>
                                @else
                                    <div class="text-xs text-muted">Dibuat {{ $transaction->created_at->format('d M Y H:i') }}</div>
                                @endif
                                @if (! blank($transaction->rejection_reason))
                                    <div class="mt-1 max-w-xs text-xs text-rose-600">{{ $transaction->rejection_reason }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted">Belum ada transaksi yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
