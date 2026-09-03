@extends('layouts.guest')

@section('title', 'Pending Pembayaran — Ujion')

@section('content')
@php
    $mapel = $order->landingExamMapel?->mapelPaket;
    $landingExam = $order->landingExamMapel?->landingExam;
    $exam = $landingExam?->exam;
@endphp

<div class="w-full max-w-xl space-y-5">
    <div class="rounded-3xl border border-white/80 bg-white/90 p-8 text-center shadow-card">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl shadow-inner">
            @if($order->isPaid())
                ✅
            @elseif($order->status === \App\Models\LandingExamOrder::STATUS_FAILED)
                ⚠️
            @else
                ⏳
            @endif
        </div>

        <h2 class="text-2xl font-bold mb-2 text-slate-900">
            @if($order->isPaid())
                Pembayaran Berhasil!
            @elseif($order->status === \App\Models\LandingExamOrder::STATUS_FAILED)
                Pembayaran Gagal
            @else
                Menunggu Pembayaran
            @endif
        </h2>

        <p class="text-sm text-textSecondary">
            {{ $exam?->judul ?? 'Ujian' }} — {{ $mapel?->nama_label ?? 'Mapel' }}
        </p>

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Nominal</span>
                <span class="text-lg font-bold text-slate-900">Rp{{ number_format((float) $order->amount, 0, ',', '.') }}</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-sm">
                <span class="text-slate-500">Nama</span>
                <span class="font-semibold">{{ $order->nama }}</span>
            </div>
            <div class="mt-1 flex items-center justify-between text-sm">
                <span class="text-slate-500">No WA</span>
                <span class="font-semibold">{{ $order->nomor_wa }}</span>
            </div>
        </div>
    </div>

    @if($order->status === \App\Models\LandingExamOrder::STATUS_PENDING_PAYMENT || $order->status === \App\Models\LandingExamOrder::STATUS_FAILED)
        <div id="payment-flow" class="space-y-4" data-start-url="{{ route('ujian-online.pay.start', $order->session_token) }}" data-status-url="{{ route('ujian-online.pay.status') }}" data-csrf="{{ csrf_token() }}">
            <div data-flow-panel="idle">
                <button type="button" class="btn-primary w-full" data-flow-start>
                    <i class="fa-solid fa-bolt mr-2"></i>
                    Bayar Sekarang
                </button>
                <p class="mt-2 text-xs text-slate-500">GoPay, QRIS, Virtual Account, e-wallet, dan lainnya.</p>
            </div>

            <div data-flow-panel="loading-snap" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-5">
                <i class="fa-solid fa-spinner fa-spin mb-2 block text-2xl text-slate-400"></i>
                <p class="text-sm font-semibold text-slate-900">Menyiapkan pembayaran...</p>
            </div>

            <div data-flow-panel="polling" class="hidden rounded-xl border border-blue-100 bg-blue-50 p-5">
                <i class="fa-solid fa-spinner fa-spin mb-2 block text-2xl text-blue-500"></i>
                <p class="text-sm font-semibold text-blue-900">Memproses pembayaran...</p>
                <p class="mt-1 text-xs text-blue-800">Halaman akan diperbarui otomatis setelah pembayaran dikonfirmasi.</p>
                <button type="button" class="btn-secondary mt-3 w-full" data-flow-retry>
                    <i class="fa-solid fa-rotate-left mr-2"></i>Bayar Ulang
                </button>
            </div>

            <div data-flow-panel="success" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 p-5">
                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl">✅</div>
                <p class="text-sm font-bold text-emerald-900">Pembayaran Berhasil!</p>
                <p class="mt-1 text-xs text-emerald-800">Klik tombol di bawah untuk mulai mengerjakan ujian.</p>
                <a id="start-exam-link" href="{{ route('ujian-online.start', $order->session_token) }}" class="btn-primary mt-3 inline-flex w-full">
                    <i class="fa-solid fa-pen-to-square mr-2"></i>Mulai Ujian
                </a>
            </div>

            <div data-flow-panel="failed" class="hidden rounded-xl border border-rose-100 bg-rose-50 p-5">
                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-2xl">⚠️</div>
                <p class="text-sm font-bold text-rose-900">Pembayaran Belum Selesai</p>
                <button type="button" class="btn-primary mt-3 w-full" data-flow-retry>
                    <i class="fa-solid fa-rotate-left mr-2"></i>Coba Bayar Lagi
                </button>
            </div>
        </div>
    @elseif($order->isPaid())
        <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-6 text-center">
            <p class="text-sm font-semibold text-emerald-900">Ujian siap dikerjakan!</p>
            <p class="mt-1 text-xs text-emerald-800">Klik tombol di bawah untuk mulai. Link juga dikirim via WhatsApp.</p>
            <a href="{{ route('ujian-online.start', $order->session_token) }}" class="btn-primary mt-4 inline-flex w-full">
                <i class="fa-solid fa-pen-to-square mr-2"></i>Mulai Ujian
            </a>
        </div>
    @endif

    <a href="{{ route('ujian-online.index') }}" class="btn-secondary w-full">Kembali ke Beranda</a>
</div>

<script>
(function () {
    const flow = document.getElementById('payment-flow');
    if (!flow) return;

    const panels = () => flow.querySelectorAll('[data-flow-panel]');
    const startUrl = flow.dataset.startUrl;
    const statusUrl = flow.dataset.statusUrl;
    const csrfToken = flow.dataset.csrf;

    let currentOrderId = null;
    let pollTimer = null;
    let pollAttempts = 0;

    const showPanel = (name) => {
        panels().forEach((panel) => panel.classList.toggle('hidden', panel.dataset.flowPanel !== name));
    };

    const loadSnapScript = (clientKey, isProduction) => new Promise((resolve, reject) => {
        if (window.snap) { resolve(); return; }
        const script = document.createElement('script');
        const host = isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        script.src = host + '/snap/snap.js';
        script.setAttribute('data-client-key', clientKey);
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap.'));
        document.head.appendChild(script);
    });

    const startPolling = () => {
        if (!currentOrderId) { showPanel('idle'); return; }
        showPanel('polling');
        pollAttempts = 0;
        window.clearInterval(pollTimer);
        pollTimer = window.setInterval(pollStatus, 3000);
        pollStatus();
    };

    const pollStatus = async () => {
        pollAttempts += 1;
        try {
            const res = await fetch(statusUrl + '?order_id=' + encodeURIComponent(currentOrderId), {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (data.ok && (data.status === 'paid' || data.status === 'exam_started' || data.status === 'exam_completed')) {
                window.clearInterval(pollTimer);
                showPanel('success');
                return;
            }
            if (data.ok && data.status === 'failed') {
                window.clearInterval(pollTimer);
                showPanel('failed');
                return;
            }
        } catch (e) {}
        if (pollAttempts >= 60) { window.clearInterval(pollTimer); showPanel('failed'); }
    };

    const openSnap = async () => {
        showPanel('loading-snap');
        let data;
        try {
            const res = await fetch(startUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({}),
            });
            data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Gagal memulai pembayaran.');
        } catch (e) {
            window.alert(e.message || 'Gagal memulai pembayaran.');
            showPanel('idle');
            return;
        }
        currentOrderId = data.order_id;
        try { await loadSnapScript(data.client_key, data.is_production); }
        catch (e) { window.alert(e.message); showPanel('idle'); return; }
        showPanel('idle');
        window.snap.pay(data.snap_token, {
            onSuccess: () => startPolling(),
            onPending: () => startPolling(),
            onError: () => showPanel('failed'),
            onClose: () => startPolling(),
        });
    };

    flow.querySelectorAll('[data-flow-start], [data-flow-retry]').forEach((button) => {
        button.addEventListener('click', () => { window.clearInterval(pollTimer); openSnap(); });
    });
})();
</script>
@endsection
