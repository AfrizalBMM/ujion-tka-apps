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
            \App\Models\User::PAYMENT_SUBMITTED => ['label' => 'Bukti pembayaran sudah dikirim', 'class' => 'border-blue-100 bg-blue-50 text-blue-900'],
            \App\Models\User::PAYMENT_APPROVED => ['label' => 'Pembayaran sudah disetujui', 'class' => 'border-green-100 bg-green-50 text-green-900'],
            \App\Models\User::PAYMENT_REJECTED => ['label' => 'Perlu kirim ulang bukti pembayaran', 'class' => 'border-rose-100 bg-rose-50 text-rose-900'],
            default => ['label' => 'Menunggu pembayaran dan bukti transfer', 'class' => 'border-amber-100 bg-amber-50 text-amber-900'],
        };
    @endphp

    <div class="mb-6 rounded-xl border p-4 text-left text-sm {{ $statusConfig['class'] }}">
        <p class="font-semibold">{{ $statusConfig['label'] }}</p>
        @if ($paymentStatus === \App\Models\User::PAYMENT_SUBMITTED)
            <p class="mt-2">Bukti pembayaran Anda sudah kami terima dan sedang menunggu verifikasi admin.</p>
        @elseif ($paymentStatus === \App\Models\User::PAYMENT_APPROVED)
            <p class="mt-2">Pembayaran sudah diverifikasi. Silakan cek WhatsApp Anda untuk token akses yang dikirim admin.</p>
        @elseif ($paymentStatus === \App\Models\User::PAYMENT_REJECTED)
            <p class="mt-2">Admin meminta Anda mengirim ulang bukti pembayaran. Periksa catatan di bawah lalu unggah bukti yang lebih jelas.</p>
            @if (! blank($teacher->payment_rejection_reason))
                <p class="mt-2 rounded-lg bg-white/70 px-3 py-2"><strong>Catatan admin:</strong> {{ $teacher->payment_rejection_reason }}</p>
            @endif
        @else
            <p class="mt-2">Selesaikan pembayaran lalu unggah bukti transfer agar proses aktivasi bisa dilanjutkan.</p>
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
                    <p id="payment-countdown-message" class="mt-1 text-sm text-blue-800">Selesaikan pembayaran lebih cepat agar verifikasi bisa segera diproses.</p>
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

        <div class="mt-4">
            <button type="button" class="btn-primary w-full" data-open-payment-modal>Bayar Sekarang</button>
            <noscript>
                <form action="{{ route('register.guru.create-payment') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn-secondary w-full">Buka Halaman Pembayaran</button>
                </form>
            </noscript>
        </div>
    </div>

    <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 p-4 text-left text-sm text-amber-900">
        <p class="font-semibold">Panduan singkat pembayaran:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Klik <strong>Bayar Sekarang</strong> untuk membuka halaman QRIS dengan nominal tetap.</li>
            <li>Scan QR menggunakan aplikasi bank atau e-wallet, lalu pastikan nominal muncul otomatis.</li>
            <li>Setelah transfer berhasil, upload bukti pembayaran, lalu konfirmasi via WhatsApp.</li>
        </ul>
    </div>

    <a href="/" class="btn-secondary w-full">Kembali ke Beranda</a>
</div>

<div id="payment-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/70 px-3 py-4 sm:items-center sm:px-4 sm:py-6">
    <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] overflow-y-auto rounded-2xl bg-white p-4 shadow-2xl dark:bg-slate-900 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Pembayaran QRIS</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Scan QR untuk bayar</div>
                <div id="payment-modal-subtitle" class="mt-1 text-sm text-textSecondary dark:text-slate-300"></div>
            </div>
            <button type="button" class="btn-secondary w-full sm:w-auto" data-payment-modal-close>Tutup</button>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-[1.15fr_0.85fr] lg:gap-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-3 text-center shadow-sm dark:border-slate-800 dark:bg-slate-950/40 sm:p-4">
                <div class="mx-auto flex w-full max-w-[320px] items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900 sm:max-w-[360px] sm:p-4">
                    <div id="payment-modal-qr" class="w-full max-w-full"></div>
                </div>
                <button type="button" id="payment-download-qr" class="btn-secondary mt-3 w-full" disabled>Download QR</button>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nominal</div>
                    <div id="payment-modal-amount" class="mt-2 text-xl font-bold text-slate-900 dark:text-slate-100">-</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Referensi</div>
                    <div id="payment-modal-ref" class="mt-2 break-all text-sm font-bold text-slate-900 dark:text-slate-100">-</div>
                </div>

                <div class="rounded-2xl border border-amber-100 bg-amber-50/80 p-4 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                    <div class="font-semibold">Instruksi</div>
                    <ol class="mt-2 list-decimal space-y-1 pl-5">
                        <li>Scan QR menggunakan aplikasi bank atau e-wallet yang mendukung QRIS.</li>
                        <li>Pastikan Nama dan Nominal muncul otomatis sesuai yang tertera.</li>
                        <li>Setelah bayar berhasil, pilih file bukti lalu klik konfirmasi WhatsApp.</li>
                    </ol>
                </div>

                <form action="{{ route('register.guru.payment-proof') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
                    @csrf
                    <div>
                        <label for="payment_proof" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Upload bukti pembayaran</label>
                        <label for="payment_proof" class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-blue-400 dark:hover:bg-blue-950/30">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-blue-600 shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105 dark:bg-slate-900 dark:ring-slate-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 16V4m0 0 4 4m-4-4-4 4M20 16.5v1.25A2.25 2.25 0 0 1 17.75 20H6.25A2.25 2.25 0 0 1 4 17.75V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Pilih gambar bukti pembayaran</span>
                            <span id="payment-proof-file-name" class="mt-1 max-w-full truncate text-xs text-slate-500 dark:text-slate-400">Belum ada file dipilih</span>
                            <span class="mt-2 text-xs text-slate-400 dark:text-slate-500">JPG, PNG, atau WEBP</span>
                        </label>
                        <input type="file" id="payment_proof" name="payment_proof" class="sr-only" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        @error('payment_proof')
                            <div class="mt-1 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full whitespace-normal text-center leading-snug">
                        {{ $paymentStatus === \App\Models\User::PAYMENT_REJECTED ? 'Kirim Ulang Bukti & Konfirmasi WhatsApp' : 'Konfirmasi via WhatsApp' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    #payment-modal-qr svg {
        display: block;
        height: auto !important;
        max-width: 100%;
        width: 100% !important;
    }
</style>

<script>
    const paymentModal = document.getElementById('payment-modal');
    const paymentQr = document.getElementById('payment-modal-qr');
    const paymentAmount = document.getElementById('payment-modal-amount');
    const paymentRef = document.getElementById('payment-modal-ref');
    const paymentSubtitle = document.getElementById('payment-modal-subtitle');
    const paymentProofInput = document.getElementById('payment_proof');
    const paymentProofFileName = document.getElementById('payment-proof-file-name');
    const paymentDownloadQr = document.getElementById('payment-download-qr');
    const paymentCountdown = document.getElementById('payment-countdown');
    const paymentCountdownTitle = document.getElementById('payment-countdown-title');
    const paymentCountdownMessage = document.getElementById('payment-countdown-message');
    const paymentCountdownTime = document.getElementById('payment-countdown-time');

    const openPaymentModal = () => {
        paymentModal?.classList.remove('hidden');
        paymentModal?.classList.add('flex');
    };

    const closePaymentModal = () => {
        paymentModal?.classList.add('hidden');
        paymentModal?.classList.remove('flex');
    };

    document.querySelectorAll('[data-payment-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closePaymentModal());
    });

    paymentModal?.addEventListener('click', (event) => {
        if (event.target === paymentModal) {
            closePaymentModal();
        }
    });

    document.querySelectorAll('[data-open-payment-modal]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                btn.disabled = true;
                btn.classList.add('opacity-80');
                btn.textContent = 'Memuat QRIS...';

                const res = await fetch("{{ route('register.guru.payment-data') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data?.message || 'Gagal memuat QRIS.');
                }

                const qrSvg = data.qr_svg;
                if (typeof qrSvg !== 'string') {
                    throw new Error('QRIS tidak valid. Silakan refresh halaman dan coba lagi.');
                }

                paymentQr.innerHTML = qrSvg;
                paymentAmount.textContent = data.amount || '-';
                paymentRef.textContent = data.reference_code || '-';
                paymentSubtitle.textContent = (data.plan_name ? ('Paket: ' + data.plan_name) : '');
                if (paymentDownloadQr) {
                    paymentDownloadQr.disabled = false;
                }

                openPaymentModal();
            } catch (e) {
                alert(e?.message || 'Gagal memuat QRIS.');
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-80');
                btn.textContent = 'Bayar Sekarang';
            }
        });
    });

    paymentProofInput?.addEventListener('change', () => {
        if (paymentProofFileName) {
            paymentProofFileName.textContent = paymentProofInput.files?.[0]?.name || 'Belum ada file dipilih';
        }
    });

    paymentDownloadQr?.addEventListener('click', async () => {
        const svg = paymentQr?.querySelector('svg');
        if (!svg) {
            alert('QR belum tersedia untuk diunduh.');
            return;
        }

        const clonedSvg = svg.cloneNode(true);
        clonedSvg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');

        const box = svg.viewBox?.baseVal;
        const svgWidth = box?.width || Number.parseFloat(svg.getAttribute('width')) || svg.clientWidth || 320;
        const svgHeight = box?.height || Number.parseFloat(svg.getAttribute('height')) || svg.clientHeight || 320;
        const size = Math.max(svgWidth, svgHeight, 1024);
        if (!clonedSvg.getAttribute('viewBox')) {
            clonedSvg.setAttribute('viewBox', `0 0 ${svgWidth} ${svgHeight}`);
        }
        clonedSvg.setAttribute('width', size);
        clonedSvg.setAttribute('height', size);

        const svgText = new XMLSerializer().serializeToString(clonedSvg);
        const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(svgBlob);
        const image = new Image();

        image.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;

            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, size, size);
            context.drawImage(image, 0, 0, size, size);

            URL.revokeObjectURL(url);

            canvas.toBlob((blob) => {
                if (!blob) {
                    alert('Gagal membuat file QR.');
                    return;
                }

                const link = document.createElement('a');
                const ref = paymentRef?.textContent?.trim()?.replace(/[^a-z0-9-]+/gi, '-') || 'qris';
                const downloadUrl = URL.createObjectURL(blob);
                link.href = downloadUrl;
                link.download = `qris-${ref}.png`;
                link.click();
                setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
            }, 'image/png');
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            alert('Gagal mengunduh QR. Silakan coba lagi.');
        };

        image.src = url;
    });

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
                paymentCountdownMessage.textContent = 'Waktu pengingat selesai, tetapi Anda masih bisa membayar dan mengirim bukti pembayaran.';
                window.clearInterval(countdownTimer);
            }
        };

        const countdownTimer = window.setInterval(renderCountdown, 1000);
        renderCountdown();
    }
</script>
@endsection
