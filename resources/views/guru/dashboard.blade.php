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
                    <div class="metric-label">Simulasi Selesai</div>
                    <div class="metric-value">{{ $simulasiSelesai }}</div>
                </div>
                <div class="metric-icon text-blue-600">
                    <i class="fa-solid fa-file-circle-plus text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Simulasi yang sudah Anda selesaikan</span>
                <span class="badge-info">Simulasi</span>
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
                <span>Rerata skor siswa (ujian & simulasi kelas)</span>
                <span class="font-semibold text-amber-500">Skor</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="metric-label">Peserta Selesai</div>
                    <div class="metric-value">{{ $totalPeserta }}</div>
                </div>
                <div class="metric-icon text-emerald-600">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Siswa unik yang menyelesaikan ujian di jenjang Anda</span>
                <span class="font-semibold text-emerald-600">Siswa</span>
            </div>
        </div>
    </div>

    <div>
        <div class="mobile-section-label">Menu Cepat</div>
        <div class="mobile-menu-grid">
            <a href="{{ route('guru.materials') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-blue-500 to-blue-600">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div class="mobile-menu-card-label">Materi</div>
            </a>
            <a href="{{ route('guru.soal-ujion.index') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-teal-500 to-cyan-600">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="mobile-menu-card-label">Soal Ujion</div>
            </a>
            <a href="{{ route('guru.personal-questions') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-purple-500 to-violet-600">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div class="mobile-menu-card-label">Bank Soal</div>
            </a>
            <a href="{{ route('guru.paket-soal.index') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-indigo-500 to-blue-600">
                    <i class="fa-solid fa-cubes"></i>
                </div>
                <div class="mobile-menu-card-label">Paket Soal</div>
            </a>
            <a href="{{ route('guru.exams') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-amber-500 to-orange-600">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div class="mobile-menu-card-label">Simulasi</div>
            </a>
            <a href="{{ route('guru.results.index') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-emerald-500 to-green-600">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="mobile-menu-card-label">Hasil Siswa</div>
            </a>
            <a href="{{ route('guru.chat') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-rose-500 to-pink-600">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <div class="mobile-menu-card-label">Live Chat</div>
            </a>
            <a href="{{ route('guru.guide') }}" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-slate-500 to-slate-600">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="mobile-menu-card-label">Panduan</div>
            </a>
            @php $waGroupLink = \App\Models\AppSetting::getValue('wa_group_link'); @endphp
            @if($waGroupLink)
            <a href="{{ $waGroupLink }}" target="_blank" rel="noopener" class="mobile-menu-card">
                <div class="mobile-menu-card-icon bg-gradient-to-br from-green-500 to-green-600">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div class="mobile-menu-card-label">Saluran WA</div>
            </a>
            @endif
        </div>
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
@endsection
