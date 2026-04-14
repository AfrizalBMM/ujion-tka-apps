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
<body class="min-h-screen bg-slate-50 text-gray-900 flex flex-col">
    <header class="w-full bg-white shadow py-3 px-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 to-blue-400 shadow-glow"></div>
            <span class="font-bold text-lg">Ujion Superadmin</span>
        </div>
        <div class="flex items-center gap-6">
            <span id="live-clock" class="font-mono text-blue-600"></span>
            <button class="text-gray-500 hover:text-blue-600" title="Perbesar Font" onclick="document.body.style.fontSize='1.1em'">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
            <button class="text-gray-500 hover:text-blue-600" title="Perkecil Font" onclick="document.body.style.fontSize='0.95em'">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </button>
            <button class="text-gray-500 hover:text-blue-600" title="Light/Dark Mode" onclick="document.body.classList.toggle('dark')">
                <i class="fa-solid fa-moon"></i>
            </button>
            <div class="relative group">
                <button class="flex items-center gap-2 focus:outline-none">
                    <img src="https://ui-avatars.com/api/?name=Superadmin" alt="avatar" class="w-8 h-8 rounded-full border">
                    <span class="text-sm font-semibold">Superadmin</span>
                    <span class="text-xs text-gray-400">(Admin)</span>
                    <i class="fa-solid fa-chevron-down ml-1"></i>
                </button>
                <div class="absolute right-0 mt-2 w-40 bg-white border rounded shadow-lg hidden group-hover:block z-50">
                    <a href="#pengaturan" class="block px-4 py-2 hover:bg-gray-100">Pengaturan</a>
                    <a href="#logout" class="block px-4 py-2 hover:bg-gray-100">Keluar</a>
                </div>
            </div>
        </div>
    </header>
    <div class="flex flex-1 w-full relative">
        <aside class="sidebar-container hidden md:flex">
            <nav class="flex flex-col gap-2 w-full">
                <a href="{{ route('superadmin.dashboard') }}" class="sidebar-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high w-5"></i> Dashboard
                </a>
                <a href="{{ route('superadmin.finance.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.finance.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card w-5"></i> Keuangan & QR
                </a>
                <a href="{{ route('superadmin.chat.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.chat.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments w-5"></i> Live Chat
                </a>
                <a href="{{ route('superadmin.teachers.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.teachers.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-chalkboard-user w-5"></i> Daftar Guru
                </a>
                <a href="{{ route('superadmin.materials.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.materials.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-book w-5"></i> Master Materi
                </a>
                <a href="{{ route('superadmin.global-questions.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.global-questions.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-database w-5"></i> Bank Soal Global
                </a>
                <a href="{{ route('superadmin.exams.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.exams.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines w-5"></i> Manajemen Ujian
                </a>
                <div class="my-4 border-t border-slate-100 dark:border-slate-800 mx-4"></div>
                <a href="{{ route('superadmin.audit-logs.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.audit-logs.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-shield-halved w-5"></i> Log Aktivitas
                </a>
                <a href="{{ route('superadmin.guide') }}" class="sidebar-link {{ request()->routeIs('superadmin.guide') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-info w-5"></i> Panduan
                </a>
            </nav>
        </aside>
        <main class="flex-1 p-6 md:ml-64">
            @include('components.ui.flash')
            @yield('content')
        </main>
    </div>
    <footer class="w-full bg-white border-t py-4 text-center text-gray-400 text-xs mt-8">
        2026 Ujion TKA by Reditech
    </footer>
</body>
</html>
