<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Menutup jendela pembayaran — Ujion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
    <div class="card max-w-sm text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-2xl">
            <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
        </div>
        <p class="text-sm font-semibold text-slate-900">Menutup jendela pembayaran...</p>
        <p class="mt-1 text-xs text-slate-500">Anda akan kembali otomatis ke halaman sebelumnya.</p>
        <a href="{{ $fallbackUrl }}" class="btn-secondary mt-4 inline-flex">Kembali sekarang</a>
    </div>

    <script>
        (function () {
            var message = @json($message);
            var fallbackUrl = @json($fallbackUrl);

            if (window.opener) {
                try {
                    window.opener.postMessage(message, window.location.origin);
                } catch (e) {}
                window.setTimeout(function () { window.close(); }, 300);
                window.setTimeout(function () { window.location.href = fallbackUrl; }, 1500);

                return;
            }

            window.location.href = fallbackUrl;
        })();
    </script>
</body>
</html>
