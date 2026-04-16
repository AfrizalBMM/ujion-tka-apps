<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ringkasan Dashboard Superadmin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #0f172a; }
        h1, h2 { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak</button>
    <h1>Ringkasan Dashboard Superadmin</h1>
    <p>Dibuat pada {{ now()->format('d M Y H:i') }}</p>

    <div class="grid">
        <div class="card"><strong>Guru Aktif</strong><div>{{ $activeTeachersCount }}</div></div>
        <div class="card"><strong>Ujian Berlangsung</strong><div>{{ $ongoingExamsCount }}</div></div>
        <div class="card"><strong>Total Pendapatan</strong><div>Rp {{ number_format((int) $totalRevenue, 0, ',', '.') }}</div></div>
        <div class="card"><strong>Guru Terbaik</strong><div>{{ $topTeacherName ?: '-' }}</div></div>
    </div>

    <h2>Aktivitas 14 Hari Terakhir</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jumlah Aktivitas</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($dailyActivity['labels'] ?? []) as $index => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $dailyActivity['values'][$index] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Belum ada aktivitas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
