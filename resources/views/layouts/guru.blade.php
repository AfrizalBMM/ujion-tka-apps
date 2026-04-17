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

<body class="app-shell flex flex-col">
  <header class="app-topbar">
    <div class="app-topbar-panel">
      <div class="app-brand">
        <div class="app-brand-mark">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="app-brand-copy">
          <div class="app-brand-subtitle">Teaching Workspace</div>
          <div class="app-brand-title">Ujion Guru/Operator</div>
        </div>
      </div>

      <div class="app-topbar-actions">
        <div class="app-topbar-meta">
          <span class="font-semibold uppercase tracking-[0.24em] text-[11px]">Jam</span>
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
            <img
              src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Guru') }}&background=22C1C3&color=fff"
              alt="avatar" class="app-user-avatar">
            <div class="app-user-copy">
              <div class="app-user-name">{{ auth()->user()->name ?? 'Guru' }}</div>
              <div class="app-user-role">Guru / Operator</div>
            </div>
            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
          </button>
          <div class="app-dropdown">
            <a href="{{ route('guru.profile') }}" class="app-dropdown-link">
              <i class="fa-solid fa-user w-4"></i>
              Profil
            </a>
            <a href="{{ route('guru.guide') }}" class="app-dropdown-link">
              <i class="fa-solid fa-circle-info w-4"></i>
              Panduan
            </a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="app-dropdown-link w-full text-left">
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
      <a href="{{ route('guru.dashboard') }}" class="mobile-nav-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i>
        Dashboard
      </a>
      <a href="{{ route('guru.chat') }}" class="mobile-nav-link {{ request()->routeIs('guru.chat') ? 'active' : '' }}">
        <i class="fa-solid fa-comments"></i>
        Chat
      </a>
      <a href="{{ route('guru.logs') }}" class="mobile-nav-link {{ request()->routeIs('guru.logs') ? 'active' : '' }}">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Log
      </a>
      <a href="{{ route('guru.materials') }}" class="mobile-nav-link {{ request()->routeIs('guru.materials') ? 'active' : '' }}">
        <i class="fa-solid fa-book"></i>
        Materi
      </a>
      <a href="{{ route('guru.personal-questions') }}" class="mobile-nav-link {{ request()->routeIs('guru.personal-questions*') ? 'active' : '' }}">
        <i class="fa-solid fa-database"></i>
        Bank Soal
      </a>
      <a href="{{ route('guru.paket-soal.index') }}" class="mobile-nav-link {{ request()->routeIs('guru.paket-soal.*') || request()->routeIs('guru.soal.*') ? 'active' : '' }}">
        <i class="fa-solid fa-database"></i>
        Paket Soal
      </a>
      <a href="{{ route('guru.exams') }}" class="mobile-nav-link {{ request()->routeIs('guru.exams*') ? 'active' : '' }}">
        <i class="fa-solid fa-file-lines"></i>
        Ujian
      </a>
      <a href="{{ route('guru.profile') }}" class="mobile-nav-link {{ request()->routeIs('guru.profile*') ? 'active' : '' }}">
        <i class="fa-solid fa-user"></i>
        Profil
      </a>
    </div>
  </nav>

  <div class="app-body">
    <aside class="sidebar-container">

      <nav class="sidebar-nav">
        <div class="sidebar-section-title">Utama</div>
        <a href="{{ route('guru.dashboard') }}"
          class="sidebar-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
          <i class="fa-solid fa-gauge-high w-5"></i> Dashboard
        </a>
        <a href="{{ route('guru.chat') }}" class="sidebar-link {{ request()->routeIs('guru.chat') ? 'active' : '' }}">
          <i class="fa-solid fa-comments w-5"></i> Live Chat
        </a>
        <a href="{{ route('guru.logs') }}" class="sidebar-link {{ request()->routeIs('guru.logs') ? 'active' : '' }}">
          <i class="fa-solid fa-clock-rotate-left w-5"></i> Log Aktivitas
        </a>

        <div class="sidebar-section-title">Konten</div>
        <a href="{{ route('guru.materials') }}"
          class="sidebar-link {{ request()->routeIs('guru.materials') ? 'active' : '' }}">
          <i class="fa-solid fa-book w-5"></i> Materi
        </a>
        <a href="{{ route('guru.personal-questions') }}"
          class="sidebar-link {{ request()->routeIs('guru.personal-questions*') ? 'active' : '' }}">
          <i class="fa-solid fa-database w-5"></i> Bank Soal Pribadi
        </a>
        <a href="{{ route('guru.paket-soal.index') }}"
          class="sidebar-link {{ request()->routeIs('guru.paket-soal.*') || request()->routeIs('guru.soal.*') ? 'active' : '' }}">
          <i class="fa-solid fa-database w-5"></i> Paket Soal TKA
        </a>
        <a href="{{ route('guru.exams') }}"
          class="sidebar-link {{ request()->routeIs('guru.exams*') ? 'active' : '' }}">
          <i class="fa-solid fa-file-lines w-5"></i> Ujian
        </a>

        <div class="sidebar-section-title">Akun</div>
        <a href="{{ route('guru.guide') }}" class="sidebar-link {{ request()->routeIs('guru.guide') ? 'active' : '' }}">
          <i class="fa-solid fa-circle-info w-5"></i> Cara Menggunakan
        </a>
        <a href="{{ route('guru.profile') }}"
          class="sidebar-link {{ request()->routeIs('guru.profile*') ? 'active' : '' }}">
          <i class="fa-solid fa-user w-5"></i> Profil
        </a>
      </nav>

    </aside>

    <main class="page-shell">
      <div class="page-stack">
        <div class="page-content">
          <div class="page-content-inner">
            @include('components.ui.flash')
            @yield('content')
          </div>
        </div>
        <footer class="page-footer">
          &copy; {{ date('Y') }} Ujion. All rights reserved.
        </footer>
      </div>
    </main>
  </div>
</body>

</html>
