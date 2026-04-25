<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label QRIS - {{ $tarifJenjang->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-image: none !important;
            }

            .print-frame {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="min-h-screen text-textPrimary antialiased">
    <main class="mx-auto flex min-h-screen max-w-4xl flex-col items-center justify-center gap-5 px-4 py-8">
        <div class="no-print w-full max-w-md">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-muted">Preview label</div>
                    <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tekan print untuk mencetak ulang.</div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-primary" onclick="window.print()">
                        <i class="fa-solid fa-print"></i>
                        Print
                    </button>
                    <a href="{{ route('superadmin.finance.index') }}" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <section class="w-full max-w-md">
            <div class="card print-frame">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-primary">UJION TKA</div>
                        <div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Label Pembayaran QRIS</div>
                        <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Scan untuk membayar sesuai nominal.</div>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-2xl border border-border bg-white/70 px-3 py-2 text-xs font-semibold text-textSecondary shadow-sm backdrop-blur-sm dark:bg-slate-950/40 dark:text-slate-300">
                        <i class="fa-solid fa-shield-halved text-muted"></i>
                        Superadmin
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-border bg-white/70 p-4 backdrop-blur-sm dark:bg-slate-950/40">
                    <div class="flex flex-wrap items-center gap-2">
                        @if (! blank($tarifJenjang->jenjang))
                            <span class="badge-info">{{ $tarifJenjang->jenjang }}</span>
                        @endif
                        @if (($tarifJenjang->is_active ?? true))
                            <span class="badge-success">Aktif</span>
                        @else
                            <span class="badge-danger">Nonaktif</span>
                        @endif
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $tarifJenjang->name }}</h1>

                    @if ($tarifJenjang->description)
                        <p class="mt-2 text-sm leading-6 text-textSecondary dark:text-slate-300">{{ $tarifJenjang->description }}</p>
                    @elseif ($tarifJenjang->subtitle)
                        <p class="mt-2 text-sm leading-6 text-textSecondary dark:text-slate-300">{{ $tarifJenjang->subtitle }}</p>
                    @endif
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-border bg-white p-4 dark:bg-slate-950">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Nominal</div>
                            <div class="mt-1 text-3xl font-bold text-slate-900 dark:text-slate-100">Rp {{ $formattedPrice }}</div>
                        </div>
                        <div class="rounded-xl border border-border bg-slate-50 px-3 py-2 text-xs font-semibold text-textSecondary dark:bg-slate-900/60 dark:text-slate-300">
                            Pastikan nominal sesuai
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-center rounded-2xl border border-dashed border-border bg-white p-4 dark:bg-slate-950">
                        @if (! empty($qrisImageUrl))
                            <img src="{{ $qrisImageUrl }}" alt="QRIS {{ $tarifJenjang->name }}" class="h-64 w-64 object-contain">
                        @else
                            <div class="[&>svg]:h-64 [&>svg]:w-64">{!! $qrCodeSvg !!}</div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-border bg-white/70 p-4 text-sm text-textSecondary backdrop-blur-sm dark:bg-slate-950/40 dark:text-slate-300">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info mt-1 text-muted"></i>
                        <div class="leading-6">
                            <div class="font-semibold text-slate-900 dark:text-slate-100">Instruksi singkat</div>
                            <div class="mt-1">Scan QRIS menggunakan aplikasi bank/e-wallet yang mendukung.</div>
                            <div>Setelah membayar, upload bukti dan konfirmasi ke WhatsApp admin.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
