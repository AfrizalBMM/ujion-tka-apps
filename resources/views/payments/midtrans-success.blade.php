@extends('layouts.guest')

@section('title', 'Status Pembayaran')

@section('content')
<div class="mx-auto max-w-xl">
    @if (session('flash'))
        <div class="mb-4">
            @include('components.ui.flash')
        </div>
    @endif

    @if ($transaction->status === \App\Models\Transaction::STATUS_SUCCESS)
        <div class="card text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl">✅</div>
            <h1 class="text-2xl font-bold text-emerald-900">Pembayaran Berhasil!</h1>
            <p class="mt-2 text-sm text-slate-600">Pembayaran Anda sudah kami terima dan akun guru Anda sudah aktif.</p>

            <div class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Paket</span>
                    <span class="font-semibold text-slate-900">{{ $transaction->plan_name }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Nominal</span>
                    <span class="font-semibold text-slate-900">Rp{{ number_format((float) $transaction->amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Kode Referensi</span>
                    <span class="font-semibold text-slate-900">{{ $transaction->reference_code }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Metode</span>
                    <span class="font-semibold text-slate-900">Midtrans (Otomatis)</span>
                </div>
            </div>

            @if ($token)
                <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-left">
                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Token Akses Anda</div>
                    <div class="mt-2 flex items-center gap-3">
                        <code id="midtrans-access-token" class="flex-1 break-all rounded-lg bg-white px-3 py-2 font-mono text-sm font-bold text-slate-900 ring-1 ring-blue-100">{{ $token }}</code>
                        <button type="button" id="midtrans-copy-token" class="btn-secondary shrink-0" data-token="{{ $token }}">
                            <i class="fa-solid fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-blue-800">Token ini juga dikirim ke WhatsApp Anda. Gunakan nomor WhatsApp dan token di atas untuk login.</p>
                </div>
            @endif

            <a href="{{ route('login') }}" class="btn-primary mt-6 w-full text-center">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>
                Masuk Sekarang
            </a>
        </div>
    @elseif ($transaction->status === \App\Models\Transaction::STATUS_FAILED)
        <div class="card text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-3xl">⚠️</div>
            <h1 class="text-2xl font-bold text-rose-900">Pembayaran Belum Selesai</h1>
            <p class="mt-2 text-sm text-rose-800">
                {{ $transaction->rejection_reason ?: 'Pembayaran tidak berhasil diselesaikan.' }}
            </p>
            <a href="{{ route('register.guru.pending') }}" class="btn-primary mt-6 w-full text-center">
                Coba Bayar Lagi
            </a>
        </div>
    @else
        <div class="card text-center" id="midtrans-waiting">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-spinner fa-spin text-2xl text-blue-600"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Memproses Pembayaran...</h1>
            <p class="mt-2 text-sm text-slate-600">Kami sedang mengonfirmasi pembayaran Anda. Halaman ini akan diperbarui otomatis, mohon jangan ditutup.</p>
            <p id="midtrans-waiting-error" class="mt-3 hidden text-xs text-rose-600">Gagal memeriksa status. <button type="button" id="midtrans-retry" class="font-semibold underline">Coba lagi</button></p>
        </div>
    @endif
</div>

@if ($transaction->status === \App\Models\Transaction::STATUS_PENDING)
    <script>
        (function () {
            const orderId = "{{ addslashes((string) request('order_id', $transaction->midtrans_order_id ?? $transaction->reference_code)) }}";
            const retryButton = document.getElementById('midtrans-retry');
            let attempts = 0;
            let timer = null;

            const poll = async () => {
                attempts += 1;

                try {
                    const res = await fetch("{{ route('payments.midtrans.status') }}?order_id=" + encodeURIComponent(orderId), {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }

                    const data = await res.json();

                    if (data.ok && data.status === 'success') {
                        window.location.reload();
                        return;
                    }

                    if (data.ok && data.status === 'failed') {
                        window.location.reload();
                        return;
                    }

                    document.getElementById('midtrans-waiting-error')?.classList.add('hidden');
                } catch (e) {
                    document.getElementById('midtrans-waiting-error')?.classList.remove('hidden');
                }

                if (attempts >= 60) {
                    window.clearInterval(timer);
                    return;
                }
            };

            timer = window.setInterval(poll, 3000);
            poll();

            retryButton?.addEventListener('click', () => {
                attempts = 0;
                window.clearInterval(timer);
                timer = window.setInterval(poll, 3000);
                poll();
            });
        })();
    </script>
@elseif ($transaction->status === \App\Models\Transaction::STATUS_SUCCESS)
    <script>
        document.getElementById('midtrans-copy-token')?.addEventListener('click', async (event) => {
            const token = event.currentTarget.dataset.token || '';
            try {
                await navigator.clipboard.writeText(token);
                event.currentTarget.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Tersalin';
            } catch (e) {
                const range = document.createRange();
                range.selectNodeContents(document.getElementById('midtrans-access-token'));
                const selection = window.getSelection();
                selection?.removeAllRanges();
                selection?.addRange(range);
            }
        });
    </script>
@endif
@endsection
