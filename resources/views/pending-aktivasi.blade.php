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

    <div class="mb-6">
        <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 sm:p-6">
            <img src="{{ $qr_url ?? asset('img/qr-placeholder.png') }}" alt="QR Pembayaran" class="mb-4 h-40 w-40 object-contain shadow-sm sm:h-48 sm:w-48">
            <div class="text-2xl font-bold text-slate-800">Rp{{ number_format($harga ?? 0, 0, ',', '.') }}</div>
            <div class="text-sm text-slate-500 mt-1 uppercase tracking-wider">Total Pembayaran</div>
        </div>
    </div>
    <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 p-4 text-left text-sm text-amber-900">
        <p class="font-semibold">Panduan singkat pembayaran:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Scan QR di atas menggunakan aplikasi pembayaran Anda.</li>
            <li>Pastikan nominal yang dibayarkan sesuai dengan total yang tertera.</li>
            <li>Simpan bukti pembayaran sampai akun Anda aktif.</li>
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
        <p class="mt-1 text-sm text-slate-600">Unggah screenshot atau foto bukti transfer yang jelas agar admin bisa memverifikasi lebih cepat.</p>

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
@endsection
