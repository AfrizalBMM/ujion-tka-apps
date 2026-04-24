<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label QRIS - {{ $tarifJenjang->name }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .page-shell {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 32px 16px;
        }

        .print-container {
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 2px solid #111827;
            padding: 20px;
            background: #ffffff;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }

        .divider {
            border: 0;
            border-top: 1px dashed #d1d5db;
            margin: 16px 0;
        }

        .print-actions {
            margin-top: 16px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #111827;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700;
        }

        .btn-primary {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page-shell {
                min-height: auto;
                padding: 0;
            }

            .print-container {
                max-width: 100%;
                border: 2px solid #000000;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <div>
            <div class="print-container">
                <h1 style="margin: 0; font-size: 24px;">UJION TKA</h1>
                <p style="margin: 5px 0; color: #555;">Sistem Ujian Terintegrasi</p>

                <hr class="divider">

                <h2 style="margin: 15px 0; font-size: 24px;">{{ $tarifJenjang->name }}</h2>

                @if ($tarifJenjang->description)
                    <p style="margin: 0 0 12px; color: #4b5563; font-size: 13px; line-height: 1.5;">
                        {{ $tarifJenjang->description }}
                    </p>
                @elseif ($tarifJenjang->subtitle)
                    <p style="margin: 0 0 12px; color: #4b5563; font-size: 13px; line-height: 1.5;">
                        {{ $tarifJenjang->subtitle }}
                    </p>
                @endif

                <div style="margin: 20px 0;">
                    @if (! empty($qrisImageUrl))
                        <img src="{{ $qrisImageUrl }}" alt="QRIS" style="width: 250px; height: 250px; object-fit: contain;">
                    @else
                        {!! $qrCodeSvg !!}
                    @endif
                </div>

                <h1 style="margin: 0; font-size: 32px;">Rp {{ $formattedPrice }}</h1>

                <p style="font-size: 12px; margin-top: 20px; line-height: 1.6;">
                    *Pastikan nominal yang muncul di aplikasi sesuai.<br>
                    Konfirmasi bukti bayar ke WhatsApp Admin.
                </p>
            </div>

            <div class="print-actions no-print">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print Ulang</button>
                <a href="{{ route('superadmin.finance.index') }}" class="btn">Kembali ke Keuangan</a>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
