@extends('layouts.guest')

@section('content')
<div class="card mx-auto max-w-xl text-center">
    @if (session('flash'))
        @include('components.ui.flash')
    @endif

    <h2 class="text-2xl font-bold mb-4 text-blue-700">Pendaftaran Anda Sudah Tercatat</h2>
    <p class="mb-6 text-gray-600 font-medium">
        Langkah berikutnya adalah menyelesaikan pembayaran agar tim kami bisa memproses aktivasi akun Anda.
        Setelah pembayaran diverifikasi, token akses akan dikirim ke WhatsApp yang Anda daftarkan.
    </p>

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
        <p class="font-semibold">Status saat ini: {{ $statusConfig['label'] }}</p>
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
            <li>Setelah transfer berhasil, kembali ke halaman ini untuk upload bukti pembayaran.</li>
        </ul>
    </div>
    <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl text-blue-800 text-sm text-left">
        <p class="font-semibold">Setelah pembayaran berhasil:</p>
        <p class="mt-2">
            Tim superadmin akan memverifikasi pembayaran Anda. Jika sudah sesuai, akun akan diaktifkan dan
            <strong>token akses</strong> dikirim melalui <strong>WhatsApp</strong>.
        </p>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-left">
        <h3 class="text-base font-semibold text-slate-900">Upload Bukti Pembayaran</h3>
        <p class="mt-1 text-sm text-slate-600">Unggah screenshot atau foto bukti transfer yang jelas. Setelah berhasil, sistem akan otomatis membuka WhatsApp admin dengan format pesan berisi data Anda.</p>

        <form action="{{ route('register.guru.payment-proof') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">File bukti pembayaran</label>
                <input type="file" name="payment_proof" class="input w-full" accept="image/*" required>
            </div>
            <button type="submit" class="btn-primary w-full">
                {{ $paymentStatus === \App\Models\User::PAYMENT_REJECTED ? 'Kirim Ulang Bukti Pembayaran' : 'Kirim Bukti Pembayaran' }}
            </button>
        </form>

        @if (! blank($teacher->payment_proof_path))
            <p class="mt-3 text-xs text-slate-500">Bukti terbaru sudah tersimpan dan akan dipakai admin saat verifikasi.</p>
        @endif
    </div>

    <a href="/" class="btn-secondary w-full">Kembali ke Beranda</a>
</div>

<div id="payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-3xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Pembayaran QRIS</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Scan QR untuk bayar</div>
                <div id="payment-modal-subtitle" class="mt-1 text-sm text-textSecondary dark:text-slate-300"></div>
            </div>
            <button type="button" class="btn-secondary" data-payment-modal-close>Tutup</button>
        </div>

        <div class="mt-5 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                <div class="mx-auto flex max-w-[360px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                    <div id="payment-modal-qr"></div>
                </div>
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
                        <li>Pastikan nominal muncul otomatis sesuai yang tertera.</li>
                        <li>Setelah bayar berhasil, lanjutkan upload bukti pembayaran di halaman ini.</li>
                    </ol>
                </div>

                <div class="flex flex-col gap-3">
                    <a id="payment-modal-wa" href="#" target="_blank" rel="noopener noreferrer" class="btn-secondary hidden text-center">Konfirmasi via WhatsApp</a>
                    <button type="button" class="btn-primary" data-payment-modal-close>Saya Sudah Scan, Lanjut Upload Bukti</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const paymentModal = document.getElementById('payment-modal');
    const paymentQr = document.getElementById('payment-modal-qr');
    const paymentAmount = document.getElementById('payment-modal-amount');
    const paymentRef = document.getElementById('payment-modal-ref');
    const paymentSubtitle = document.getElementById('payment-modal-subtitle');
    const paymentWa = document.getElementById('payment-modal-wa');

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

                paymentQr.innerHTML = data.qr_svg || '';
                paymentAmount.textContent = data.amount || '-';
                paymentRef.textContent = data.reference_code || '-';
                paymentSubtitle.textContent = (data.plan_name ? ('Paket: ' + data.plan_name) : '');

                if (data.wa_url) {
                    paymentWa.href = data.wa_url;
                    paymentWa.classList.remove('hidden');
                } else {
                    paymentWa.href = '#';
                    paymentWa.classList.add('hidden');
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
</script>
@endsection
