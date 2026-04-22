<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Ujian {{ $exam->judul }}</title>
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
    <h1>Analisis Ujian</h1>
    <p>{{ $exam->judul }} - dibuat {{ now()->format('d M Y H:i') }}</p>

    <div class="grid">
        <div class="card"><strong>Peserta Selesai</strong><div>{{ $participantsCount }}</div></div>
        <div class="card"><strong>{{ $scoreMetricLabel }}</strong><div>{{ number_format($averageScore, 2) }}</div></div>
    </div>

    <h2>Ranking Peserta</h2>
    <table>
        <thead>
            <tr>
                <th>Peringkat</th>
                <th>Nama</th>
                <th>Bagian</th>
                <th>Nilai / Kelengkapan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ranking as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['mapel'] }}</td>
                    <td>{{ $row['score'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada peserta selesai.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ $distributionTitle }}</h2>
    <table>
        <thead>
            <tr>
                <th>Rentang</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribution as $range => $count)
                <tr>
                    <td>{{ $range }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
