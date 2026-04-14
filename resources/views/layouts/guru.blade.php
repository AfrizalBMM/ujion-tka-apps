<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Guru/Operator') - Ujion</title>
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
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-400 shadow-glow"></div>
            <span class="font-bold text-lg">Ujion Guru/Operator</span>
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
                    <img src="https://ui-avatars.com/api/?name=Guru" alt="avatar" class="w-8 h-8 rounded-full border">
                    <span class="text-sm font-semibold">{{ auth()->user()->name ?? 'Guru' }}</span>
                    <i class="fa-solid fa-chevron-down ml-1"></i>
                </button>
                <div class="absolute right-0 mt-2 w-40 bg-white border rounded shadow-lg hidden group-hover:block z-50">
                    <a href="{{ route('guru.profile') }}" class="block px-4 py-2 hover:bg-gray-100">Profil</a>
                    <a href="#logout" class="block px-4 py-2 hover:bg-gray-100">Keluar</a>
                </div>
            </div>
        </div>
    </header>
    <div class="flex flex-1 w-full">
        <aside class="w-64 bg-white border-r p-6 hidden md:block">
            <nav class="flex flex-col gap-2">
                <a href="#dashboard" class="sidebar-link"><i class="fa-solid fa-gauge-high w-5"></i> Dashboard</a>
                <a href="#chat" class="sidebar-link"><i class="fa-solid fa-comments w-5"></i> Live Chat</a>
                <a href="#siswa" class="sidebar-link"><i class="fa-solid fa-users w-5"></i> Siswa</a>
                <a href="#materi" class="sidebar-link"><i class="fa-solid fa-book w-5"></i> Materi</a>
                <a href="#banksoal" class="sidebar-link"><i class="fa-solid fa-database w-5"></i> Bank Soal</a>
                <a href="#ujian" class="sidebar-link"><i class="fa-solid fa-file-lines w-5"></i> Ujian</a>
                <a href="#panduan" class="sidebar-link"><i class="fa-solid fa-circle-info w-5"></i> Cara Menggunakan</a>
                <a href="#profil" class="sidebar-link"><i class="fa-solid fa-user w-5"></i> Profil</a>
            </nav>
        </aside>
        <main class="flex-1 p-6">
            @include('components.ui.flash')
            @yield('content')
        </main>
    </div>
    <footer class="w-full bg-white border-t py-4 text-center text-gray-400 text-xs mt-8">
        &copy; {{ date('Y') }} Ujion. All rights reserved.
    </footer>
</body>
</html>
