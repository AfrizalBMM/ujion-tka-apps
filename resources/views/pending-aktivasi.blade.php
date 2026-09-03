@extends('layouts.guest')

@section('content')
<div class="card mx-auto max-w-xl text-center">
    @if (session('flash'))
        @include('components.ui.flash')
    @endif

    <h2 class="text-2xl font-bold mb-4 text-blue-700">Pendaftaran Anda Berhasil</h2>

    @php
        $paymentStatus = $teacher->payment_status ?? \App\Models\User::PAYMENT_AWAITING;
        $statusConfig = match ($paymentStatus) {
            \App\Models\User::PAYMENT_APPROVED => ['label' => 'Pembayaran sudah disetujui', 'class' => 'border-green-100 bg-green-50 text-green-900'],
            \App\Models\User::PAYMENT_REJECTED => ['label' => 'Pembayaran perlu diulang', 'class' => 'border-rose-100 bg-rose-50 text-rose-900'],
            default => ['label' => 'Menunggu pembayaran', 'class' => 'border-amber-100 bg-amber-50 text-amber-900'],
        };
    @endphp

    <div class="mb-6 rounded-xl border p-4 text-left text-sm {{ $statusConfig['class'] }}">
        <p class="font-semibold">{{ $statusConfig['label'] }}</p>
        @if ($paymentStatus === \App\Models\User::PAYMENT_APPROVED)
            <p class="mt-2">Pembayaran sudah diverifikasi. Silakan cek WhatsApp Anda untuk token akses yang dikirim admin.</p>
        @elseif ($paymentStatus === \App\Models\User::PAYMENT_REJECTED)
            <p class="mt-2">Pembayaran Anda belum bisa diverifikasi. Periksa catatan di bawah, lalu coba bayar kembali.</p>
            @if (! blank($teacher->payment_rejection_reason))
                <p class="mt-2 rounded-lg bg-white/70 px-3 py-2"><strong>Catatan admin:</strong> {{ $teacher->payment_rejection_reason }}</p>
            @endif
        @else
            <p class="mt-2">Selesaikan pembayaran agar akun Anda bisa aktif dan digunakan.</p>
        @endif
    </div>

    @if (in_array($paymentStatus, [\App\Models\User::PAYMENT_AWAITING, \App\Models\User::PAYMENT_REJECTED], true))
        <div
            id="payment-countdown"
            class="mb-4 rounded-xl border border-blue-100 bg-blue-50 p-4 text-left text-blue-900"
            data-countdown-key="ujion-payment-deadline-v2-{{ $teacher->id }}"
            data-countdown-minutes="60"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p id="payment-countdown-title" class="text-sm font-semibold">Percepat aktivasi akun Anda</p>
                    <p id="payment-countdown-message" class="mt-1 text-sm text-blue-800">Selesaikan pembayaran lebih cepat agar akun bisa langsung aktif.</p>
                </div>
                <div class="shrink-0 rounded-lg bg-white px-4 py-2 text-center shadow-sm ring-1 ring-blue-100">
                    <div class="text-xs font-semibold uppercase tracking-wide text-black">Sisa Waktu</div>
                    <div id="payment-countdown-time" class="mt-1 font-mono text-2xl font-bold text-black">01:00:00</div>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left">
        <div class="flex items-start justify-between gap-4">
            <div>
                @if (! empty($selectedTarifJenjang?->name))
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tarif Jenjang</p>
                    <div class="mt-1 text-lg font-bold text-slate-900">{{ $selectedTarifJenjang->name }}</div>
                    @if ($selectedTarifJenjang->description)
                        <div class="mt-1 max-w-lg text-sm text-slate-600">{{ $selectedTarifJenjang->description }}</div>
                    @elseif ($selectedTarifJenjang->subtitle)
                        <div class="mt-1 max-w-lg text-sm text-slate-600">{{ $selectedTarifJenjang->subtitle }}</div>
                    @endif
                @endif
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nominal Aktivasi</p>
                <div class="mt-2 text-2xl font-bold text-slate-900">Rp{{ number_format($harga ?? 0, 0, ',', '.') }}</div>
            </div>
            @if ($latestTransaction)
                <div class="text-right">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Referensi Terakhir</p>
                    <div class="mt-2 text-sm font-bold text-slate-900">{{ $latestTransaction->reference_code }}</div>
                </div>
            @endif
        </div>
    </div>

    @if (in_array($paymentStatus, [\App\Models\User::PAYMENT_AWAITING, \App\Models\User::PAYMENT_REJECTED], true))
        <div id="payment-flow" class="mb-6 space-y-4" data-start-url="{{ route('payments.midtrans.start') }}" data-status-url="{{ route('payments.midtrans.status') }}" data-csrf="{{ csrf_token() }}">
            <div data-flow-panel="idle">
                @if ($midtransEnabled)
                    <button type="button" class="btn-primary w-full" data-flow-start>
                        <i class="fa-solid fa-bolt mr-2"></i>
                        Bayar Sekarang
                    </button>
                    <p class="mt-2 text-xs text-slate-500">GoPay, QRIS, Virtual Account, e-wallet, dan lainnya — akun aktif otomatis setelah bayar.</p>
                @else
                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold">Pembayaran online belum tersedia</p>
                        <p class="mt-1">Metode pembayaran otomatis sedang tidak diaktifkan admin. Silakan hubungi admin untuk menyelesaikan pembayaran.</p>
                        @if ($adminWhatsappUrl)
                            <a href="{{ $adminWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn-primary mt-3 inline-flex w-full">
                                <i class="fa-brands fa-whatsapp mr-2"></i>
                                Hubungi Admin via WhatsApp
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div data-flow-panel="loading-snap" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-5">
                <i class="fa-solid fa-spinner fa-spin mb-2 block text-2xl text-slate-400"></i>
                <p class="text-sm font-semibold text-slate-900">Menyiapkan pembayaran...</p>
                <p class="mt-1 text-xs text-slate-500">Mohon tunggu sebentar, jangan tutup halaman ini.</p>
            </div>

            <div data-flow-panel="polling" class="hidden rounded-xl border border-blue-100 bg-blue-50 p-5">
                <i class="fa-solid fa-spinner fa-spin mb-2 block text-2xl text-blue-500"></i>
                <p class="text-sm font-semibold text-blue-900">Memproses pembayaran...</p>
                <p class="mt-1 text-xs text-blue-800">Kami sedang mengonfirmasi pembayaran Anda. Halaman ini akan diperbarui otomatis.</p>
                <button type="button" class="btn-secondary mt-3 w-full" data-flow-retry>
                    <i class="fa-solid fa-rotate-left mr-2"></i>
                    Bayar Ulang / Coba Lagi
                </button>
            </div>

            <div data-flow-panel="success" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 p-5">
                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl">✅</div>
                <p class="text-sm font-bold text-emerald-900">Pembayaran Berhasil!</p>
                <p class="mt-1 text-xs text-emerald-800">Akun Anda sudah aktif. Simpan token akses berikut untuk login.</p>
                <div class="mt-3 rounded-xl border border-emerald-200 bg-white p-3 text-left">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Token Akses</div>
                    <div class="mt-2 flex items-center gap-2">
                        <code data-flow-token class="flex-1 break-all rounded-lg bg-emerald-50 px-3 py-2 font-mono text-sm font-bold text-slate-900">-</code>
                        <button type="button" class="btn-secondary shrink-0" data-flow-copy>
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Token ini juga dikirim ke WhatsApp Anda.</p>
                </div>
                <a href="{{ route('login') }}" class="btn-primary mt-3 inline-flex w-full">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i>
                    Masuk Sekarang
                </a>
            </div>

            <div data-flow-panel="failed" class="hidden rounded-xl border border-rose-100 bg-rose-50 p-5">
                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-2xl">⚠️</div>
                <p class="text-sm font-bold text-rose-900">Pembayaran Belum Selesai</p>
                <p data-flow-failed-reason class="mt-1 text-xs text-rose-800">Pembayaran tidak berhasil diselesaikan. Silakan coba lagi.</p>
                <button type="button" class="btn-primary mt-3 w-full" data-flow-retry>
                    <i class="fa-solid fa-rotate-left mr-2"></i>
                    Coba Bayar Lagi
                </button>
            </div>
        </div>
    @endif

    <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 p-4 text-left text-sm text-amber-900">
        <p class="font-semibold">Panduan singkat pembayaran:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Klik <strong>Bayar Sekarang</strong> untuk membuka popup pembayaran Midtrans.</li>
            <li>Pilih metode pembayaran favorit Anda (GoPay, QRIS, Virtual Account, dll).</li>
            <li>Setelah bayar berhasil, akun Anda aktif otomatis dan token akses tampil di halaman ini.</li>
        </ul>
    </div>

    <a href="/" class="btn-secondary w-full">Kembali ke Beranda</a>
</div>

<script>
    (function () {
        const flow = document.getElementById('payment-flow');
        if (! flow) return;

        const panels = () => flow.querySelectorAll('[data-flow-panel]');
        const startUrl = flow.dataset.startUrl;
        const statusUrl = flow.dataset.statusUrl;
        const csrfToken = flow.dataset.csrf;

        let currentOrderId = null;
        let pollTimer = null;
        let pollAttempts = 0;

        const showPanel = (name) => {
            panels().forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.flowPanel !== name);
            });
        };

        const loadSnapScript = (clientKey, isProduction) => new Promise((resolve, reject) => {
            if (window.snap) {
                resolve();

                return;
            }

            const script = document.createElement('script');
            const host = isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
            script.src = host + '/snap/snap.js';
            script.setAttribute('data-client-key', clientKey);
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap. Periksa koneksi internet Anda.'));
            document.head.appendChild(script);
        });

        const startPolling = () => {
            if (! currentOrderId) {
                showPanel('idle');

                return;
            }

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

                if (! res.ok) {
                    throw new Error('HTTP ' + res.status);
                }

                const data = await res.json();

                if (data.ok && data.status === 'success') {
                    window.clearInterval(pollTimer);
                    renderSuccess(data.token);

                    return;
                }

                if (data.ok && data.status === 'failed') {
                    window.clearInterval(pollTimer);
                    showPanel('failed');

                    return;
                }
            } catch (e) {
                // keep polling — retry on next tick
            }

            if (pollAttempts >= 60) {
                window.clearInterval(pollTimer);
                showPanel('failed');
            }
        };

        const renderSuccess = (token) => {
            const tokenEl = flow.querySelector('[data-flow-token]');
            if (tokenEl) {
                tokenEl.textContent = token || '-';
            }
            showPanel('success');
        };

        const openSnap = async () => {
            showPanel('loading-snap');

            let data;
            try {
                const res = await fetch(startUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                data = await res.json();

                if (! res.ok || ! data.ok) {
                    throw new Error(data.message || 'Gagal memulai pembayaran.');
                }
            } catch (e) {
                window.alert(e.message || 'Gagal memulai pembayaran. Silakan coba lagi.');
                showPanel('idle');

                return;
            }

            currentOrderId = data.order_id;

            try {
                await loadSnapScript(data.client_key, data.is_production);
            } catch (e) {
                window.alert(e.message);
                showPanel('idle');

                return;
            }

            showPanel('idle');

            window.snap.pay(data.snap_token, {
                onSuccess: () => startPolling(),
                onPending: () => startPolling(),
                onError: () => showPanel('failed'),
                onClose: () => startPolling(),
            });
        };

        flow.querySelectorAll('[data-flow-start], [data-flow-retry]').forEach((button) => {
            button.addEventListener('click', () => {
                window.clearInterval(pollTimer);
                openSnap();
            });
        });

        flow.querySelector('[data-flow-copy]')?.addEventListener('click', async (event) => {
            const token = flow.querySelector('[data-flow-token]')?.textContent?.trim() || '';
            const button = event.currentTarget;

            try {
                await navigator.clipboard.writeText(token);
                button.innerHTML = '<i class="fa-solid fa-check"></i>';
            } catch (e) {
                const range = document.createRange();
                range.selectNodeContents(flow.querySelector('[data-flow-token]'));
                const selection = window.getSelection();
                selection?.removeAllRanges();
                selection?.addRange(range);
            }

            window.setTimeout(() => {
                button.innerHTML = '<i class="fa-solid fa-copy"></i>';
            }, 1500);
        });
    })();

    const paymentCountdown = document.getElementById('payment-countdown');
    const paymentCountdownTitle = document.getElementById('payment-countdown-title');
    const paymentCountdownMessage = document.getElementById('payment-countdown-message');
    const paymentCountdownTime = document.getElementById('payment-countdown-time');

    if (paymentCountdown && paymentCountdownTime) {
        const countdownKey = paymentCountdown.dataset.countdownKey;
        const countdownMinutes = Number.parseInt(paymentCountdown.dataset.countdownMinutes || '15', 10);
        const durationMs = Math.max(countdownMinutes, 1) * 60 * 1000;
        let deadline = Date.now() + durationMs;

        if (countdownKey) {
            const storedDeadline = Number.parseInt(localStorage.getItem(countdownKey) || '', 10);
            if (Number.isFinite(storedDeadline) && storedDeadline > 0) {
                deadline = storedDeadline;
            } else {
                localStorage.setItem(countdownKey, String(deadline));
            }
        }

        const renderCountdown = () => {
            const remainingMs = Math.max(deadline - Date.now(), 0);
            const totalSeconds = Math.ceil(remainingMs / 1000);
            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');

            paymentCountdownTime.textContent = `${hours}:${minutes}:${seconds}`;

            if (remainingMs <= 0) {
                paymentCountdownTitle.textContent = 'Pembayaran tetap bisa dilanjutkan';
                paymentCountdownMessage.textContent = 'Waktu pengingat selesai, tetapi Anda masih bisa menyelesaikan pembayaran.';
                window.clearInterval(countdownTimer);
            }
        };

        const countdownTimer = window.setInterval(renderCountdown, 1000);
        renderCountdown();
    }
</script>
@endsection
