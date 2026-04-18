<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Superadmin') - Ujion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('live-clock').textContent = now.toLocaleTimeString('id-ID');
        }
        setInterval(updateClock, 1000);
        window.onload = updateClock;
    </script>
</head>

<body class="app-shell flex flex-col" data-dashboard-shell="superadmin">
    @php
        $materialFilter = request()->query('jenjang');
    @endphp
    <header class="app-topbar">
        <div class="app-topbar-panel">
            <div class="app-brand">
                <div class="app-brand-mark">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="app-brand-copy">
                    <div class="app-brand-subtitle">Pusat Kontrol</div>
                    <div class="app-brand-title">Ujion Superadmin</div>
                </div>
            </div>

            <div class="app-topbar-actions">
                <div class="app-topbar-meta">
                    <span class="font-semibold uppercase tracking-[0.24em] text-[11px]">Realtime</span>
                    <span id="live-clock" class="app-clock"></span>
                </div>
                <button class="icon-button" title="Perbesar Font" onclick="document.body.style.fontSize='1.05em'">
                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                </button>
                <button class="icon-button" title="Perkecil Font" onclick="document.body.style.fontSize='0.97em'">
                    <i class="fa-solid fa-magnifying-glass-minus"></i>
                </button>
                <button class="icon-button" title="Ganti Tema" data-theme-toggle>
                    <i class="fa-solid fa-moon"></i>
                </button>
                <div class="app-user-menu">
                    <button class="app-user-trigger">
                        <img src="https://ui-avatars.com/api/?name=Superadmin&background=4F6EF7&color=fff" alt="avatar"
                            class="app-user-avatar">
                        <div class="app-user-copy">
                            <div class="app-user-name">Superadmin</div>
                            <div class="app-user-role">Administrator</div>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </button>
                    <div class="app-dropdown">
                        <a href="{{ route('superadmin.guide') }}" class="app-dropdown-link">
                            <i class="fa-solid fa-circle-info w-4"></i>
                            Panduan
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="app-dropdown-link w-full text-left" data-confirm data-confirm-title="Konfirmasi Logout" data-confirm="Apakah Anda yakin ingin logout?">
                                <i class="fa-solid fa-right-from-bracket w-4"></i>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="mobile-nav">
        <div class="mobile-nav-track">
            <a href="{{ route('superadmin.dashboard') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i>
                Dashboard
            </a>
            <a href="{{ route('superadmin.finance.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.finance.index') ? 'active' : '' }}">
                <i class="fa-solid fa-credit-card"></i>
                Keuangan
            </a>
            <a href="{{ route('superadmin.chat.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.chat.index') ? 'active' : '' }}">
                <i class="fa-solid fa-comments"></i>
                Chat
            </a>
            <a href="{{ route('superadmin.teachers.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.teachers.index') ? 'active' : '' }}">
                <i class="fa-solid fa-chalkboard-user"></i>
                Guru
            </a>
            <a href="{{ route('superadmin.materials.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.materials.index') ? 'active' : '' }}">
                <i class="fa-solid fa-book"></i>
                Materi
            </a>
            <a href="{{ route('superadmin.global-questions.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.global-questions.*') ? 'active' : '' }}">
                <i class="fa-solid fa-database"></i>
                Bank Soal
            </a>
            <a href="{{ route('superadmin.paket-soal.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.paket-soal.*') || request()->routeIs('superadmin.soal.*') ? 'active' : '' }}">
                <i class="fa-solid fa-database"></i>
                Paket Soal
            </a>
            <a href="{{ route('superadmin.exams.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.exams.index') ? 'active' : '' }}">
                <i class="fa-solid fa-file-lines"></i>
                Ujian
            </a>
            <a href="{{ route('superadmin.audit-logs.index') }}"
                class="mobile-nav-link {{ request()->routeIs('superadmin.audit-logs.index') ? 'active' : '' }}">
                <i class="fa-solid fa-shield-halved"></i>
                Audit
            </a>
        </div>
    </nav>

    <div class="app-body">
        <aside class="sidebar-container" data-app-sidebar>
            <nav class="sidebar-nav">
                <div class="sidebar-section-row">
                    <div class="sidebar-section-title sidebar-section-title-static">Utama</div>
                    <button
                        type="button"
                        class="sidebar-toggle"
                        data-sidebar-toggle
                        aria-label="Toggle sidebar"
                        aria-expanded="true"
                        title="Ciutkan sidebar"
                    >
                        <i class="fa-solid fa-angles-left" data-sidebar-toggle-icon></i>
                    </button>
                </div>
                <a href="{{ route('superadmin.dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high w-5"></i>
                    <span class="sidebar-link-label">Dashboard</span>
                </a>
                <a href="{{ route('superadmin.finance.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.finance.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card w-5"></i>
                    <span class="sidebar-link-label">Keuangan & QR</span>
                </a>
                <a href="{{ route('superadmin.chat.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.chat.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments w-5"></i>
                    <span class="sidebar-link-label">Live Chat</span>
                </a>

                <div class="sidebar-section-title">Akademik</div>
                <a href="{{ route('superadmin.teachers.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.teachers.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-chalkboard-user w-5"></i>
                    <span class="sidebar-link-label">Daftar Guru</span>
                </a>
                <details class="sidebar-submenu" data-sidebar-submenu>
                    <summary class="sidebar-link sidebar-submenu-trigger {{ request()->routeIs('superadmin.materials.index') ? 'active' : '' }}" data-sidebar-submenu-trigger>
                        <span class="flex items-center gap-3.5">
                            <i class="fa-solid fa-book w-5"></i>
                            <span class="sidebar-link-label">Master Materi</span>
                        </span>
                        <i class="fa-solid fa-chevron-down sidebar-submenu-caret"></i>
                    </summary>
                    <div class="sidebar-submenu-links">
                        <a href="{{ route('superadmin.materials.index') }}"
                            class="sidebar-sublink {{ empty($materialFilter) ? 'active' : '' }}">
                            <span class="sidebar-sublink-badge">ALL</span>
                            <span class="sidebar-sublink-label">Semua jenjang</span>
                        </a>
                        <a href="{{ route('superadmin.materials.index', ['jenjang' => 'SD']) }}"
                            class="sidebar-sublink {{ $materialFilter === 'SD' ? 'active' : '' }}">
                            <span class="sidebar-sublink-badge">SD</span>
                            <span class="sidebar-sublink-label">Materi SD</span>
                        </a>
                        <a href="{{ route('superadmin.materials.index', ['jenjang' => 'SMP']) }}"
                            class="sidebar-sublink {{ $materialFilter === 'SMP' ? 'active' : '' }}">
                            <span class="sidebar-sublink-badge">SMP</span>
                            <span class="sidebar-sublink-label">Materi SMP</span>
                        </a>
                    </div>
                </details>
                <a href="{{ route('superadmin.global-questions.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.global-questions.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-database w-5"></i>
                    <span class="sidebar-link-label">Bank Soal Global</span>
                </a>
                <a href="{{ route('superadmin.paket-soal.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.paket-soal.*') || request()->routeIs('superadmin.soal.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-database w-5"></i>
                    <span class="sidebar-link-label">Paket Soal TKA</span>
                </a>
                <a href="{{ route('superadmin.exams.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.exams.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines w-5"></i>
                    <span class="sidebar-link-label">Manajemen Ujian</span>
                </a>

                <div class="sidebar-section-title">Sistem</div>
                <a href="{{ route('superadmin.audit-logs.index') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.audit-logs.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-shield-halved w-5"></i>
                    <span class="sidebar-link-label">Log Aktivitas</span>
                </a>
                <a href="{{ route('superadmin.guide') }}"
                    class="sidebar-link {{ request()->routeIs('superadmin.guide') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-info w-5"></i>
                    <span class="sidebar-link-label">Panduan</span>
                </a>
            </nav>

        </aside>

        <main class="page-shell">
            <div class="page-stack">
                <div class="page-content">
                    <div class="page-content-inner">
                        @include('components.ui.flash')
                        @include('components.ui.confirm-modal')
                        @yield('content')
                    </div>
                </div>
                <footer class="page-footer">
                    2026 Ujion TKA by Reditech
                </footer>
            </div>
        </main>
    </div>
</body>

</html>
