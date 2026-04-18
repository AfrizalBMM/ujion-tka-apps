@extends('layouts.guru')
@section('title', 'Dashboard Guru')
@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Dashboard Guru</span>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="page-title">Ruang kerja mengajar yang lebih rapi dan nyaman dibaca.</h1>
                <p class="page-description">Pantau progres kelas, aktivitas terbaru, dan pengumuman penting dari satu dashboard yang sekarang terasa lebih hidup.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="hero-chip">
                    <i class="fa-solid fa-book-open-reader"></i>
                    Materi dan soal lebih terarah
                </div>
                <div class="hero-chip">
                    <i class="fa-solid fa-chart-line"></i>
                    Insight kelas lebih cepat dibaca
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('guru.materials') }}" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                <i class="fa-solid fa-book"></i>
                Buka Materi
            </a>
            <a href="{{ route('guru.exams') }}" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                <i class="fa-solid fa-file-lines"></i>
                Coba Simulasi
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Ujian Dibuat</div>
                    <div class="metric-value">{{ $ujianDibuat }}</div>
                </div>
                <div class="metric-icon text-blue-600">
                    <i class="fa-solid fa-file-circle-plus text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Total sesi yang sudah disiapkan</span>
                <span class="badge-info">Aktif</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Rata-rata Skor Kelas</div>
                    <div class="metric-value">{{ number_format($rataRataKelas, 2) }}</div>
                </div>
                <div class="metric-icon text-amber-500">
                    <i class="fa-solid fa-chart-simple text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Rerata performa peserta</span>
                <span class="font-semibold text-amber-500">Skor</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Total Peserta</div>
                    <div class="metric-value">{{ $totalPeserta }}</div>
                </div>
                <div class="metric-icon text-emerald-600">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Peserta yang tercatat di sistem</span>
                <span class="font-semibold text-emerald-600">Kelas</span>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="card p-5">
            <div class="section-heading mb-4">
                <div>
                    <h2 class="section-title">Aktivitas Terbaru</h2>
                    <p class="section-description">Jejak aktivitas akun guru yang terakhir tercatat.</p>
                </div>
            </div>
            <ul class="divide-y divide-slate-200/80 dark:divide-slate-800">
            @if(count($logs) > 0)
                @foreach ($logs as $log)
                    <li class="flex items-start gap-3 py-3 text-sm text-gray-700 dark:text-slate-300">
                        <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                        </span>
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-white">{{ $log->route_name }}</div>
                            <div class="mt-1 text-xs text-textSecondary dark:text-slate-400">{{ $log->created_at }} · {{ $log->ip_address }}</div>
                        </div>
                    </li>
                @endforeach
            @else
                <li class="empty-state mt-2 text-gray-400">Belum ada aktivitas.</li>
            @endif
            </ul>
        </div>

        <div class="card p-5">
            <div class="section-heading mb-4">
                <div>
                    <h2 class="section-title">Pengumuman Penting</h2>
                    <p class="section-description">Info yang perlu diperhatikan untuk operasional mengajar.</p>
                </div>
            </div>
            <ul class="space-y-3">
            @if(count($pengumuman) > 0)
                @foreach ($pengumuman as $info)
                    <li class="rounded-2xl border border-blue-100 bg-blue-50/80 px-4 py-3 text-sm text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-200">{{ $info }}</li>
                @endforeach
            @else
                <li class="empty-state text-gray-400">Tidak ada pengumuman.</li>
            @endif
            </ul>
        </div>
    </div>
</div>
@endsection
