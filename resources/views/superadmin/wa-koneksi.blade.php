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
            <button id="wa-reset-button" type="button" class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                Reset koneksi
            </button>
            <button id="wa-logout-button" type="button" class="inline-flex items-center rounded-full border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200">
                Logout session
            </button>
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

        <div class="card lg:col-span-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Debug Gateway</div>
                    <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Log Event Socket.IO</div>
                </div>
                <button id="wa-clear-log" type="button" class="inline-flex items-center rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    Clear log
                </button>
            </div>
            <div class="mt-4 min-h-[180px] overflow-auto rounded-2xl border border-slate-200/80 bg-slate-50 p-4 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" id="wa-debug-log">
                <div class="text-slate-500">Log koneksi gateway akan muncul di sini.</div>
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
    const resetButton = document.getElementById('wa-reset-button');
    const logoutButton = document.getElementById('wa-logout-button');
    const debugLog = document.getElementById('wa-debug-log');
    const clearLogButton = document.getElementById('wa-clear-log');
    let qrTimeout = null;
    let pendingLogout = false;

    const logDebug = (message) => {
        if (!debugLog) return;
        const time = new Date().toLocaleTimeString('id-ID');
        const entry = document.createElement('div');
        entry.className = 'mb-2 break-words';
        entry.innerHTML = `<span class="font-semibold text-slate-600 dark:text-slate-300">[${time}]</span> ${message}`;
        if (debugLog.children.length === 1 && debugLog.children[0].textContent.includes('Log koneksi gateway')) {
            debugLog.innerHTML = '';
        }
        debugLog.appendChild(entry);
        debugLog.scrollTop = debugLog.scrollHeight;
    };

    if (clearLogButton) {
        clearLogButton.addEventListener('click', () => {
            if (!debugLog) return;
            debugLog.innerHTML = '<div class="text-slate-500">Log koneksi gateway akan muncul di sini.</div>';
        });
    }

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

    let socket = null;
    let reconnectTimer = null;

    const showPlaceholder = (message, iconHtml = '<i class="fa-solid fa-qrcode text-3xl"></i>') => {
        qrImage.classList.add('hidden');
        qrPlaceholder.classList.remove('hidden');
        qrPlaceholder.innerHTML = `<div class="flex flex-col items-center justify-center gap-2 py-10 text-center text-sm text-muted">${iconHtml}<div>${message}</div></div>`;
    };

    const resetQrSection = () => {
        showPlaceholder('QR code akan muncul otomatis.');
        if (qrTimeout) {
            clearTimeout(qrTimeout);
            qrTimeout = null;
        }
    };

    const showLoadingPlaceholder = (message) => {
        showPlaceholder(message, '<i class="fa-solid fa-spinner fa-spin text-3xl"></i>');
    };

    const startQrTimer = () => {
        if (qrTimeout) {
            clearTimeout(qrTimeout);
        }

        qrTimeout = setTimeout(() => {
            setBadge('badge-info', 'Menunggu QR');
            statusText.textContent = 'Menunggu QR dari gateway. Jika tidak muncul, WA Gateway mungkin kehilangan sesi atau HP belum terhubung.';
            showPlaceholder('Jika tidak muncul, pastikan sesi di HP telah logout atau restart gateway.');
        }, 5000);
    };

    const createSession = () => {
        if (! socket || ! socket.connected) {
            return;
        }
        setBadge('badge-info', 'Memuat QR');
        statusText.textContent = 'Meminta QR baru dari gateway. Tunggu beberapa detik.';
        showLoadingPlaceholder('Memuat QR...');
        logDebug(`Mengirim create-session untuk ${senderId}`);
        socket.emit('create-session', { id: senderId, webhook: webhookUrl });
        startQrTimer();
    };

    const initSocket = () => {
        if (socket) {
            socket.off();
            socket.disconnect();
            socket = null;
        }

        socket = io(gatewayUrl, {
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionDelayMax: 5000,
        });

        socket.on('connect', () => {
            setBadge('badge-info', 'Terhubung ke server');
            statusText.textContent = 'Terhubung. Menyiapkan sesi WhatsApp...';
            logDebug('Socket terhubung ke gateway.');
            resetQrSection();
            createSession();
        });

        socket.on('disconnect', (reason) => {
            setBadge('badge-warning', 'Terputus');
            statusText.textContent = `Koneksi ke server terputus: ${reason}. Mencoba menyambung ulang...`;
            logDebug(`Socket disconnect: ${reason}`);
        });

        socket.on('qr', (data) => {
            if (qrTimeout) {
                clearTimeout(qrTimeout);
                qrTimeout = null;
            }
            setBadge('badge-warning', 'Scan QR');
            statusText.textContent = 'Silakan scan QR code ini lewat HP Admin.';
            logDebug('Menerima event QR dari gateway.');
            if (data && data.src) {
                qrImage.src = data.src;
                qrImage.classList.remove('hidden');
                qrPlaceholder.classList.add('hidden');
            }
        });

        socket.on('ready', () => {
            if (qrTimeout) {
                clearTimeout(qrTimeout);
                qrTimeout = null;
            }
            setBadge('badge-success', 'Terhubung');
            statusText.textContent = 'WhatsApp berhasil terhubung dan aktif.';
            logDebug('Gateway mengirim event ready. Koneksi WhatsApp siap.');
            qrImage.classList.add('hidden');
            qrPlaceholder.classList.remove('hidden');
            qrPlaceholder.innerHTML = '<div class="flex flex-col items-center justify-center gap-2 py-10 text-center text-sm text-muted"><i class="fa-solid fa-circle-check text-3xl"></i><div>Koneksi aktif. Anda bisa menutup halaman ini.</div></div>';
        });

        socket.on('connect_error', (err) => {
            if (qrTimeout) {
                clearTimeout(qrTimeout);
                qrTimeout = null;
            }
            setBadge('badge-danger', 'Gagal konek');
            statusText.textContent = `Tidak bisa terhubung ke WA Gateway: ${err.message || err}. Pastikan server Node.js berjalan dan URL benar.`;
            logDebug(`connect_error: ${err.message || err}`);
        });

        socket.on('close', (data) => {
            logDebug(`close event: ${data?.text || 'close'} (${data?.statusCode || 'no-code'})`);
            if (data?.text) {
                statusText.textContent = data.text;
            }
            if (pendingLogout && data?.statusCode === 401) {
                logDebug('Logout session sukses. Meminta QR ulang.');
                pendingLogout = false;
                setTimeout(() => createSession(), 500);
            }
        });

        socket.on('message', (data) => {
            logDebug(`message event: ${data?.text || JSON.stringify(data)}`);
        });

        socket.on('authenticated', (data) => {
            logDebug(`authenticated event: ${JSON.stringify(data)}`);
        });

        socket.on('connect_timeout', () => {
            setBadge('badge-danger', 'Timeout');
            statusText.textContent = 'Koneksi ke WA Gateway timeout. Cek jaringan dan pastikan gateway aktif.';
            logDebug('connect_timeout: koneksi gateway timeout.');
        });

        socket.on('reconnect_attempt', (attempt) => {
            setBadge('badge-info', 'Mencoba reconnect');
            statusText.textContent = `Mencoba reconnect ke WA Gateway (percobaan ${attempt})...`;
            logDebug(`reconnect_attempt: percobaan ${attempt}.`);
        });

        socket.on('reconnect_failed', () => {
            setBadge('badge-danger', 'Reconnect gagal');
            statusText.textContent = 'Reconnect gagal. Pastikan WA Gateway masih berjalan dan refresh halaman.';
            logDebug('reconnect_failed: gateway gagal reconnect.');
        });

        socket.on('error', (err) => {
            setBadge('badge-danger', 'Error');
            statusText.textContent = `Gateway error: ${err?.message || err}.`;
            logDebug(`socket error: ${err?.message || err}`);
        });
    };

    initSocket();

    logoutButton.addEventListener('click', () => {
        if (! socket || ! socket.connected) {
            setBadge('badge-danger', 'Tidak terhubung');
            statusText.textContent = 'Tidak bisa logout: koneksi ke gateway belum terjalin.';
            logDebug('Logout dibatalkan karena socket belum terhubung.');
            return;
        }

        pendingLogout = true;
        setBadge('badge-info', 'Logout session...');
        statusText.textContent = 'Mengirim permintaan logout ke gateway...';
        showLoadingPlaceholder('Logout dan regenerasi QR...');
        logDebug('Tombol logout session ditekan. Mengirim logout ke gateway.');

        socket.emit('logout', { id: senderId });
    });

    resetButton.addEventListener('click', () => {
        if (! socket || ! socket.connected) {
            setBadge('badge-danger', 'Tidak terhubung');
            statusText.textContent = 'Tidak bisa reset: koneksi ke gateway belum terjalin.';
            logDebug('Reset dibatalkan karena socket belum terhubung.');
            return;
        }

        pendingLogout = false;
        setBadge('badge-info', 'Reset koneksi...');
        statusText.textContent = 'Memulai ulang koneksi ke gateway...';
        showLoadingPlaceholder('Regenerasi QR...');
        logDebug('Tombol reset koneksi ditekan. Memutus dan reconnect socket.');

        socket.disconnect();

        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
        }

        reconnectTimer = setTimeout(() => {
            initSocket();
            setBadge('badge-info', 'Scan ulang');
            statusText.textContent = 'Mencoba scan ulang. Jika QR tidak muncul, pastikan sesi di HP telah logout atau restart WA Gateway.';
        }, 800);
    });
})();
</script>
@endpush
@endsection
