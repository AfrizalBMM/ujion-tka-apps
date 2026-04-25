@extends('layouts.superadmin')

@section('title', 'Dashboard Analytics')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Analytics Hub</span>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="page-title">Pusat kendali platform Ujion dalam tampilan admin yang lebih rapi dan terstruktur.</h1>
                <p class="page-description">Ringkasan performa guru, ujian, dan transaksi disusun ulang supaya cepat dipindai tanpa membuat area kerja terasa padat atau saling bertabrakan.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="hero-chip">
                    <i class="fa-solid fa-bolt"></i>
                    Monitoring realtime lebih cepat
                </div>
                <div class="hero-chip">
                    <i class="fa-solid fa-chart-column"></i>
                    Panel analitik lebih fokus
                </div>
            </div>
        </div>
        <div class="page-actions">
            <div class="hero-chip">
                <span class="text-white/70">AUTO REFRESH</span>
                <span class="font-mono font-semibold text-white" id="live-timer">120s</span>
            </div>
            <a href="{{ route('superadmin.audit-logs.index') }}" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                <i class="fa-solid fa-shield-halved"></i>
                Audit Logs
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Guru Aktif</div>
                    <div class="metric-value">{{ $activeTeachersCount }}</div>
                </div>
                <div class="metric-icon text-blue-600">
                    <i class="fa-solid fa-chalkboard-user text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span class="inline-flex items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up text-green-500"></i>
                    +2% dari minggu lalu
                </span>
                <span class="badge-info">Stabil</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Ujian Berlangsung</div>
                    <div class="metric-value">{{ $ongoingExamsCount }}</div>
                </div>
                <div class="metric-icon text-indigo-600">
                    <i class="fa-solid fa-play-circle text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Real-time active sessions</span>
                <span class="badge-info">Live</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Total Pendapatan</div>
                    <div class="metric-value text-2xl">Rp {{ number_format((int) $totalRevenue, 0, ',', '.') }}</div>
                </div>
                <div class="metric-icon text-green-600">
                    <i class="fa-solid fa-wallet text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Bulan ini (Gross Revenue)</span>
                <span class="font-semibold text-green-600">Finance</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Guru Terbaik</div>
                    <div class="mt-3 max-w-[160px] truncate text-lg font-bold text-slate-900 dark:text-white">{{ $topTeacherName ?: '-' }}</div>
                </div>
                <div class="metric-icon text-purple-600">
                    <i class="fa-solid fa-trophy text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Berdasarkan kontribusi soal</span>
                <span class="font-semibold text-purple-600">Top Rank</span>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)]">
        <div class="card">
            <div class="section-heading mb-6">
                <div>
                    <h3 class="section-title">Aktivitas Sistem</h3>
                    <p class="section-description">Jumlah aksi yang tercatat dalam 14 hari terakhir.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('superadmin.dashboard.print') }}" target="_blank" rel="noopener" class="btn-secondary px-3 py-1 text-xs">Versi Cetak</a>
                    <a href="{{ route('superadmin.dashboard.export-csv') }}" class="btn-secondary px-3 py-1 text-xs">CSV</a>
                </div>
            </div>
            <div class="h-[320px]">
                <canvas
                    id="superadmin-activity-chart"
                    data-labels='@json($dailyActivity["labels"] ?? [])'
                    data-values='@json($dailyActivity["values"] ?? [])'
                ></canvas>
            </div>
        </div>

        <div class="card">
            <div class="section-heading mb-6">
                <div>
                    <h3 class="section-title">Aksi Terbaru</h3>
                    <p class="section-description">Log singkat untuk memantau kejadian penting.</p>
                </div>
                <a href="{{ route('superadmin.audit-logs.index') }}" class="text-xs font-bold text-primary hover:underline">Lihat Semua</a>
            </div>
            <div class="space-y-3">
                @forelse($latestAuditLogs as $log)
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-3 dark:border-slate-800 dark:bg-slate-800/45">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-slate-700">
                                <i class="fa-solid fa-user-gear text-xs text-slate-400"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-xs font-bold text-slate-800 dark:text-white">{{ $log->method }} {{ $log->path }}</div>
                                <div class="mt-1 text-[10px] text-textSecondary dark:text-slate-400">{{ $log->created_at->diffForHumans() }} � IP: {{ $log->ip_address }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state opacity-70">
                        <i class="fa-solid fa-ghost mb-2 block text-3xl"></i>
                        <span class="text-xs italic">Belum ada aktivitas.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-heading mb-6">
            <div>
                <h3 class="section-title">Landing Clicks</h3>
                <p class="section-description">Akses landing (views) dan klik CTA yang tercatat per hari (14 hari terakhir).</p>
            </div>
        </div>
        <div class="h-[320px]">
            <canvas
                id="superadmin-landing-click-chart"
                data-labels='@json($landingTraffic["labels"] ?? [])'
                data-views='@json($landingTraffic["views"] ?? [])'
                data-clicks='@json($landingTraffic["clicks"] ?? [])'
            ></canvas>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('superadmin.teachers.index') }}" class="quick-action">
            <div class="quick-action-icon"><i class="fa-solid fa-users"></i></div>
            <div class="quick-action-title">Kelola Guru</div>
            <div class="quick-action-copy">Aktivasi akun, suspend akses, dan pantau status guru dari satu panel kerja.</div>
        </a>
        <a href="{{ route('superadmin.finance.index') }}" class="quick-action">
            <div class="quick-action-icon"><i class="fa-solid fa-credit-card"></i></div>
            <div class="quick-action-title">Pengaturan Harga</div>
            <div class="quick-action-copy">Atur paket, promo, dan QR pembayaran tanpa membuat dashboard terasa berat.</div>
        </a>
        <a href="{{ route('superadmin.questions.index') }}" class="quick-action">
            <div class="quick-action-icon"><i class="fa-solid fa-database"></i></div>
            <div class="quick-action-title">Bank Soal</div>
            <div class="quick-action-copy">Kelola koleksi soal pusat yang dipakai lintas guru dan lintas ujian.</div>
        </a>
        <a href="{{ route('superadmin.chat.index') }}" class="quick-action">
            <div class="quick-action-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="quick-action-title">Bantuan Chat</div>
            <div class="quick-action-copy">Tinjau percakapan masuk dan respon cepat kebutuhan operator atau guru.</div>
        </a>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('superadmin-activity-chart');
        if (ctx) {
            const labels = JSON.parse(ctx.getAttribute('data-labels') || '[]');
            const values = JSON.parse(ctx.getAttribute('data-values') || '[]');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Aktivitas',
                        data: values,
                        borderColor: '#4F6EF7',
                        backgroundColor: 'rgba(79, 110, 247, 0.12)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#4F6EF7'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.16)', borderDash: [5, 5] },
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        const landingChart = document.getElementById('superadmin-landing-click-chart');
        if (landingChart) {
            const labels = JSON.parse(landingChart.getAttribute('data-labels') || '[]');
            const views = JSON.parse(landingChart.getAttribute('data-views') || '[]');
            const clicks = JSON.parse(landingChart.getAttribute('data-clicks') || '[]');

            new Chart(landingChart, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Views',
                            data: views,
                            borderColor: '#0EA5E9',
                            backgroundColor: 'rgba(14, 165, 233, 0.18)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                        {
                            label: 'Clicks',
                            data: clicks,
                            borderColor: '#4F6EF7',
                            backgroundColor: 'rgba(79, 110, 247, 0.18)',
                            borderWidth: 1,
                            borderRadius: 10,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.16)', borderDash: [5, 5] },
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
