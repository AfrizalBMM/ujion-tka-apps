@extends('layouts.superadmin')

@section('title', 'Dashboard Analytics')

@section('content')
<div class="space-y-6">
    <!-- TOP HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Analytics Hub</h1>
            <p class="mt-1 text-textSecondary dark:text-slate-400">Selamat datang kembali, admin. Berikut adalah ringkasan performa platform hari ini.</p>
        </div>
        <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-2 rounded-xl shadow-sm border border-border">
            <span class="text-xs font-bold text-muted px-2 border-r">AUTO REFRESH</span>
            <span class="text-xs font-mono text-blue-600 px-2" id="live-timer">120s</span>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="card border-l-4 border-l-blue-500 hover:translate-y-[-4px] transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-textSecondary dark:text-slate-400 uppercase tracking-wider">Guru Aktif</div>
                    <div class="mt-2 text-3xl font-bold">{{ $activeTeachersCount }}</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-chalkboard-user text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-[10px] text-muted flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-up text-green-500"></i>
                <span>+2% dari minggu lalu</span>
            </div>
        </div>

        <div class="card border-l-4 border-l-indigo-500 hover:translate-y-[-4px] transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-textSecondary dark:text-slate-400 uppercase tracking-wider">Ujian Berlangsung</div>
                    <div class="mt-2 text-3xl font-bold">{{ $ongoingExamsCount }}</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                    <i class="fa-solid fa-play-circle text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-[10px] text-muted">Real-time active sessions</div>
        </div>

        <div class="card border-l-4 border-l-green-500 hover:translate-y-[-4px] transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-textSecondary dark:text-slate-400 uppercase tracking-wider">Total Pendapatan</div>
                    <div class="mt-2 text-2xl font-bold">Rp {{ number_format((int)$totalRevenue, 0, ',', '.') }}</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-wallet text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-[10px] text-muted">Bulan ini (Gross Revenue)</div>
        </div>

        <div class="card border-l-4 border-l-purple-500 hover:translate-y-[-4px] transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-textSecondary dark:text-slate-400 uppercase tracking-wider">Guru Terbaik</div>
                    <div class="mt-2 text-lg font-bold truncate max-w-[150px]">{{ $topTeacherName ?: '-' }}</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                    <i class="fa-solid fa-trophy text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-[10px] text-muted">Berdasarkan kontribusi soal</div>
        </div>
    </div>

    <!-- MAIN CHARTS & ACTIVITY -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- ACTIVITY CHART -->
        <div class="lg:col-span-2 card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-bold text-lg">Aktivitas Sistem</h3>
                    <p class="text-xs text-muted">Jumlah aksi yang tercatat dalam 14 hari terakhir.</p>
                </div>
                <div class="flex gap-2">
                    <button class="btn-secondary px-3 py-1 text-xs">PDF Report</button>
                    <button class="btn-secondary px-3 py-1 text-xs">CSV</button>
                </div>
            </div>
            <div class="h-[300px]">
                <canvas 
                    id="superadmin-activity-chart"
                    data-labels='@json($dailyActivity["labels"] ?? [])'
                    data-values='@json($dailyActivity["values"] ?? [])'
                ></canvas>
            </div>
        </div>

        <!-- RECENT AUDIT LOGS -->
        <div class="lg:col-span-1 card">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg">Aksi Terbaru</h3>
                <a href="{{ route('superadmin.audit-logs.index') }}" class="text-xs text-primary font-bold hover:underline">Lihat Semua</a>
            </div>
            <div class="space-y-4">
                @forelse($latestAuditLogs as $log)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-700 flex flex-shrink-0 items-center justify-center shadow-sm">
                        <i class="fa-solid fa-user-gear text-xs text-slate-400"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold truncate">{{ $log->method }} {{ $log->path }}</div>
                        <div class="text-[10px] text-muted mt-0.5">{{ $log->created_at->diffForHumans() }} · IP: {{ $log->ip_address }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-10 opacity-50">
                    <i class="fa-solid fa-ghost text-3xl mb-2 block"></i>
                    <span class="text-xs italic">Belum ada aktivitas.</span>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- QUICK LINKS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('superadmin.teachers.index') }}" class="card p-4 flex flex-col items-center text-center hover:bg-blue-50 dark:hover:bg-blue-900/20 group transition-all">
            <i class="fa-solid fa-users text-2xl mb-2 text-slate-400 group-hover:text-blue-500"></i>
            <span class="text-xs font-bold">Kelola Guru</span>
        </a>
        <a href="{{ route('superadmin.finance.index') }}" class="card p-4 flex flex-col items-center text-center hover:bg-indigo-50 dark:hover:bg-indigo-900/20 group transition-all">
            <i class="fa-solid fa-credit-card text-2xl mb-2 text-slate-400 group-hover:text-indigo-500"></i>
            <span class="text-xs font-bold">Pengaturan Harga</span>
        </a>
        <a href="{{ route('superadmin.questions.index') }}" class="card p-4 flex flex-col items-center text-center hover:bg-green-50 dark:hover:bg-green-900/20 group transition-all">
            <i class="fa-solid fa-database text-2xl mb-2 text-slate-400 group-hover:text-green-500"></i>
            <span class="text-xs font-bold">Bank Soal</span>
        </a>
        <a href="{{ route('superadmin.chat.index') }}" class="card p-4 flex flex-col items-center text-center hover:bg-purple-50 dark:hover:bg-purple-900/20 group transition-all">
            <i class="fa-solid fa-comments text-2xl mb-2 text-slate-400 group-hover:text-purple-500"></i>
            <span class="text-xs font-bold">Bantuan Chat</span>
        </a>
    </div>
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
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4f46e5'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] }, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endsection
