@extends('layouts.guest')

@section('title', 'Pembayaran QRIS')

@section('head')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="card text-center">
        <div class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
            <i class="fa-solid fa-qrcode text-2xl text-white"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Pembayaran QRIS</h1>
        <p class="mt-2 text-sm text-slate-600">Scan QRIS berikut untuk menyelesaikan pembayaran aktivasi akun guru Anda.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <div class="card">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
                <div class="mx-auto flex max-w-[360px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    {!! $qrCodeSvg !!}
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nominal</div>
                    <div class="mt-2 text-xl font-bold text-slate-900">{{ $formattedAmount }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Referensi</div>
                    <div class="mt-2 break-all text-sm font-bold text-slate-900">{{ $transaction->reference_code }}</div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card">
                <h2 class="text-lg font-bold text-slate-900">Detail Pembayaran</h2>
                <dl class="mt-4 space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between gap-3">
                        <dt>Paket</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $transaction->plan_name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt>Nama Guru</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $transaction->user->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt>Status</dt>
                        <dd>
                            @if ($transaction->status === \App\Models\Transaction::STATUS_SUCCESS)
                                <span class="badge-success">Sukses</span>
                            @elseif ($transaction->status === \App\Models\Transaction::STATUS_FAILED)
                                <span class="badge-danger">Perlu Perbaikan</span>
                            @else
                                <span class="badge-warning">Menunggu Pembayaran</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="card border border-amber-100 bg-amber-50/80">
                <h2 class="text-lg font-bold text-amber-950">Instruksi</h2>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-amber-900">
                    <li>Scan QR menggunakan aplikasi bank atau e-wallet yang mendukung QRIS.</li>
                    <li>Pastikan nominal muncul otomatis sebesar <strong>{{ $formattedAmount }}</strong>.</li>
                    <li>Selesaikan pembayaran lalu kembali ke halaman aktivasi untuk upload bukti bayar.</li>
                </ol>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('register.guru.pending') }}" class="btn-primary w-full text-center">
                    Saya Sudah Bayar, Lanjut Upload Bukti
                </a>
                @if ($waUrl)
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="btn-secondary w-full text-center">
                        Konfirmasi via WhatsApp
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
