<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') - Ujion</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light dark;
            --bg-0: #f8fafc;
            --bg-1: #eef2ff;
            --card: rgba(255, 255, 255, 0.86);
            --border: rgba(148, 163, 184, 0.35);
            --text: #0f172a;
            --muted: rgba(15, 23, 42, 0.65);
            --primary: #4f6ef7;
            --primary-2: #22c1c3;
            --shadow: 0 22px 60px rgba(15, 23, 42, 0.16);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-0: #0b1220;
                --bg-1: #081426;
                --card: rgba(2, 6, 23, 0.68);
                --border: rgba(148, 163, 184, 0.24);
                --text: #e2e8f0;
                --muted: rgba(226, 232, 240, 0.68);
                --shadow: 0 22px 60px rgba(0, 0, 0, 0.42);
            }
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", sans-serif;
            color: var(--text);
            background:
                radial-gradient(900px circle at 12% 8%, rgba(79, 110, 247, 0.16), transparent 55%),
                radial-gradient(700px circle at 90% 20%, rgba(34, 193, 195, 0.18), transparent 50%),
                linear-gradient(180deg, var(--bg-0), var(--bg-1));
        }

        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
        }

        .card {
            width: min(760px, 100%);
            border-radius: 28px;
            border: 1px solid var(--border);
            background: var(--card);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
            overflow: hidden;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .mark {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(79, 110, 247, 0.95), rgba(34, 193, 195, 0.85));
            color: white;
            font-weight: 800;
            letter-spacing: 0.02em;
            flex: 0 0 auto;
        }

        .brand-title {
            font-weight: 800;
            font-size: 14px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .brand-sub {
            font-size: 11px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pill {
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-size: 12px;
            color: var(--muted);
            background: rgba(255,255,255,0.35);
        }

        @media (prefers-color-scheme: dark) {
            .pill { background: rgba(2, 6, 23, 0.45); }
        }

        .body {
            padding: 22px 20px 24px;
        }

        .code {
            font-size: clamp(44px, 7vw, 64px);
            font-weight: 900;
            letter-spacing: -0.04em;
            margin: 0;
        }

        .title {
            margin: 10px 0 0;
            font-size: clamp(18px, 3.2vw, 24px);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .message {
            margin: 10px 0 0;
            font-size: 14px;
            line-height: 1.65;
            color: var(--muted);
        }

        .actions {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            appearance: none;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.55);
            color: var(--text);
            padding: 10px 14px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            transition: transform 120ms ease, background 120ms ease, border-color 120ms ease;
        }

        @media (prefers-color-scheme: dark) {
            .btn { background: rgba(2, 6, 23, 0.55); }
        }

        .btn:hover { transform: translateY(-1px); border-color: rgba(79, 110, 247, 0.35); }
        .btn-primary {
            border-color: rgba(79, 110, 247, 0.45);
            background: linear-gradient(135deg, rgba(79, 110, 247, 0.95), rgba(34, 193, 195, 0.85));
            color: white;
        }

        .btn-primary:hover { border-color: rgba(34, 193, 195, 0.55); }

        .hint {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--muted);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    @php
        $statusCode = trim($__env->yieldContent('code')) ?: '500';
        $statusTitle = trim($__env->yieldContent('title')) ?: 'Terjadi Kendala';
        $statusMessage = trim($__env->yieldContent('message')) ?: 'Sistem tidak dapat memproses permintaan Anda untuk saat ini.';
        $showReload = in_array($statusCode, ['500', '503'], true);
    @endphp

    <div class="wrap">
        <main class="card" role="main" aria-label="Error page">
            <header class="header">
                <div class="brand">
                    <div class="mark">U</div>
                    <div class="min">
                        <div class="brand-title">Ujion</div>
                        <div class="brand-sub">Pusat Kendali</div>
                    </div>
                </div>
                <div class="pill">Status: {{ $statusCode }}</div>
            </header>

            <div class="body">
                <h1 class="code">{{ $statusCode }}</h1>
                <div class="title">{{ $statusTitle }}</div>
                <p class="message">{{ $statusMessage }}</p>

                <div class="actions">
                    <a class="btn btn-primary" href="{{ route('landing') }}">Ke Beranda</a>
                    <button class="btn" type="button" onclick="history.back()">Kembali</button>
                    @if($showReload)
                        <button class="btn" type="button" onclick="location.reload()">Muat Ulang</button>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('login'))
                        <a class="btn" href="{{ route('login') }}">Login Guru</a>
                    @endif
                </div>

                <div class="hint">
                    <span>Jika ini berulang, coba refresh atau cek koneksi.</span>
                    <span>{{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

