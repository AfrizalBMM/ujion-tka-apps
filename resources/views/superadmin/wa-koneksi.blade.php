@extends('layouts.superadmin')

@section('title', 'Koneksi WhatsApp')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Koneksi WhatsApp</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Scan QR untuk menghubungkan WhatsApp Admin ke WA Gateway. Status akan berubah otomatis saat konek.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span id="wa-status-badge" class="badge-warning">Menunggu koneksi</span>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="card lg:col-span-3">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">QR Code</div>
            <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Scan dari HP Admin</div>
            <p id="wa-status-text" class="mt-2 text-sm text-textSecondary dark:text-slate-300">Menghubungkan ke server...</p>

            <div class="mt-5 flex items-center justify-center rounded-2xl border border-dashed border-slate-200/80 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-950">
                <div class="w-full max-w-[320px]">
                    <img id="wa-qr-image" src="" alt="Menunggu QR Code..." class="hidden w-full rounded-xl bg-white p-3 shadow-sm dark:bg-slate-900" />
                    <div id="wa-qr-placeholder" class="flex flex-col items-center justify-center gap-2 py-10 text-center text-sm text-muted">
                        <i class="fa-solid fa-qrcode text-3xl"></i>
                        <div>QR code akan muncul otomatis.</div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-xs text-muted">
                Jika QR tidak muncul, pastikan Node.js WA Gateway berjalan di <span class="font-mono">{{ $waGatewayUrl }}</span>.
            </div>
        </div>

        <div class="card lg:col-span-2">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Konfigurasi</div>
            <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Info sesi</div>

            <div class="mt-4 space-y-3 text-sm">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Gateway URL</div>
                    <div class="mt-1 font-mono text-xs break-all">{{ $waGatewayUrl }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Sender ID</div>
                    <div class="mt-1 font-mono text-xs">{{ $senderId }}</div>
                    <div class="mt-2 text-xs text-muted">Sender ID harus sama dengan sesi yang dibuat di Node Gateway.</div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 text-xs text-muted dark:border-slate-800 dark:bg-slate-950">
                    Tips: setelah berhasil “ready”, Anda tidak perlu scan ulang kecuali sesi dihapus / logout di HP.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ $waGatewayUrl }}/socket.io/socket.io.js"></script>

@push('scripts')
<script>
(() => {
    const badge = document.getElementById('wa-status-badge');
    const statusText = document.getElementById('wa-status-text');
    const qrImage = document.getElementById('wa-qr-image');
    const qrPlaceholder = document.getElementById('wa-qr-placeholder');

    const gatewayUrl = @json($waGatewayUrl);
    const senderId = @json($senderId);
    const webhookUrl = @json(url('/api/wa-webhook'));

    const setBadge = (type, text) => {
        badge.classList.remove('badge-warning', 'badge-success', 'badge-danger', 'badge-info');
        badge.classList.add(type);
        badge.textContent = text;
    };

    if (typeof io === 'undefined') {
        setBadge('badge-danger', 'Gateway tidak terjangkau');
        statusText.textContent = 'Gagal memuat Socket.IO dari WA Gateway. Periksa URL gateway dan pastikan server Node.js berjalan.';
        return;
    }

    setBadge('badge-info', 'Menghubungkan...');

    const socket = io(gatewayUrl, {
        transports: ['websocket', 'polling'],
        reconnection: true,
        reconnectionDelayMax: 5000,
    });

    socket.on('connect', () => {
        setBadge('badge-info', 'Terhubung ke server');
        statusText.textContent = 'Terhubung. Menyiapkan sesi WhatsApp...';
        socket.emit('create-session', { id: senderId, webhook: webhookUrl });
    });

    socket.on('disconnect', () => {
        setBadge('badge-warning', 'Terputus');
        statusText.textContent = 'Koneksi ke server terputus. Mencoba menyambung ulang...';
    });

    socket.on('qr', (data) => {
        setBadge('badge-warning', 'Scan QR');
        statusText.textContent = 'Silakan scan QR code ini lewat HP Admin.';
        if (data && data.src) {
            qrImage.src = data.src;
            qrImage.classList.remove('hidden');
            qrPlaceholder.classList.add('hidden');
        }
    });

    socket.on('ready', () => {
        setBadge('badge-success', 'Terhubung');
        statusText.textContent = 'WhatsApp berhasil terhubung dan aktif.';
        qrImage.classList.add('hidden');
        qrPlaceholder.classList.remove('hidden');
        qrPlaceholder.innerHTML = '<div class="flex flex-col items-center justify-center gap-2 py-10 text-center text-sm text-muted"><i class="fa-solid fa-circle-check text-3xl"></i><div>Koneksi aktif. Anda bisa menutup halaman ini.</div></div>';
    });

    socket.on('connect_error', () => {
        setBadge('badge-danger', 'Gagal konek');
        statusText.textContent = 'Tidak bisa terhubung ke WA Gateway. Pastikan server Node.js berjalan dan URL benar.';
    });
})();
</script>
@endpush
@endsection
