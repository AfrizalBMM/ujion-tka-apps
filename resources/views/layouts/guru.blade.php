<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex,nofollow">
  <title>@yield('title', 'Guru/Operator') - Ujion</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  {{-- KaTeX Math Rendering --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

  @include('partials.ssd-style')
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
  $currentGuru = auth()->user();
  $guruAvatarUrl = $currentGuru?->avatar_url
    ?? 'https://ui-avatars.com/api/?name=Guru&background=22C1C3&color=fff';

  $waGroupLink = \App\Models\AppSetting::getValue('wa_group_link');

  $routeLabels = [
    'login' => 'Login berhasil',
    'guru.profile.update' => 'Profil diperbarui',
    'guru.profile.password' => 'Password diubah',
    'guru.profile.avatar.delete' => 'Foto profil dihapus',
    'guru.exams.join' => 'Memulai simulasi ujian',
    'guru.chat.store' => 'Pesan terkirim ke admin',
    'guru.personal-questions.store' => 'Soal pribadi ditambahkan',
    'guru.personal-questions.update' => 'Soal pribadi diperbarui',
    'guru.personal-questions.destroy' => 'Soal pribadi dihapus',
    'guru.soal.store' => 'Soal paket ditambahkan',
    'guru.soal.update' => 'Soal paket diperbarui',
    'guru.soal.destroy' => 'Soal paket dihapus',
    'guru.soal.import-ujion' => 'Soal diimpor dari Ujion',
    'guru.teks-bacaan.store' => 'Teks bacaan ditambahkan',
    'guru.teks-bacaan.destroy' => 'Teks bacaan dihapus',
    'guru.materials.bookmark' => 'Materi disimpan',
    'guru.materials.unbookmark' => 'Bookmark materi dihapus',
    'guru.soal-ujion.bookmark' => 'Soal Ujion disimpan',
    'guru.soal-ujion.unbookmark' => 'Bookmark soal dihapus',
    'guru.mapel.update' => 'Konfigurasi mapel diperbarui',
  ];

  $notifLogs = collect();
  if ($currentGuru) {
    try {
      $notifLogs = \App\Models\AuditLog::where('user_id', $currentGuru->id)
        ->where('method', '!=', 'GET')
        ->whereNotNull('route_name')
        ->latest()
        ->limit(8)
        ->get();
    } catch (\Throwable) {}
  }
@endphp

<body class="app-shell flex flex-col" data-dashboard-shell="guru">
  <header class="app-topbar">
    <div class="app-topbar-panel">
      <div class="app-brand">
        <div class="app-brand-mark">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="app-brand-copy">
          <div class="app-brand-subtitle">Semangat!!!</div>
          <div class="app-brand-title">Guru / Operator</div>
        </div>
      </div>

        <div class="app-topbar-actions">
          <div class="app-topbar-meta">
            <span class="font-semibold uppercase tracking-[0.24em] text-[11px]">Jam</span>
            <span id="live-clock" class="app-clock"></span>
          </div>
        <button class="icon-button hidden md:inline-flex" title="Perbesar Font" data-font-size="increase">
          <i class="fa-solid fa-magnifying-glass-plus"></i>
        </button>
        <button class="icon-button hidden md:inline-flex" title="Perkecil Font" data-font-size="decrease">
          <i class="fa-solid fa-magnifying-glass-minus"></i>
        </button>
        <button class="icon-button hidden md:inline-flex" title="Ganti Tema" data-theme-toggle>
          <i class="fa-solid fa-moon"></i>
        </button>
        <div class="app-user-menu">
          <button class="icon-button relative" title="Notifikasi">
            <i class="fa-solid fa-bell"></i>
            @php $unreadCount = $notifLogs->filter(fn($l) => isset($routeLabels[$l->route_name]))->count(); @endphp
            @if($unreadCount > 0)
              <span class="absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5 items-center justify-center rounded-full bg-primary ring-2 ring-white dark:ring-slate-950"></span>
            @endif
          </button>
          <div class="app-dropdown min-w-72 max-w-80">
            <div class="border-b border-slate-200/70 px-3 py-2 dark:border-slate-700/60">
              <span class="text-sm font-bold text-slate-900 dark:text-white">Notifikasi</span>
            </div>
            <div class="max-h-80 overflow-y-auto py-1">
              @foreach($notifLogs as $log)
                @php $label = $routeLabels[$log->route_name] ?? null; @endphp
                @if($label)
                  <div class="flex flex-col gap-0.5 px-3 py-2 transition hover:bg-primary/8 dark:hover:bg-slate-900/70">
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</div>
                    <div class="text-xs text-textSecondary dark:text-slate-400">{{ $log->created_at?->diffForHumans() }}</div>
                  </div>
                @endif
              @endforeach
              @if($notifLogs->filter(fn($l) => isset($routeLabels[$l->route_name]))->isEmpty())
                <div class="px-3 py-6 text-center text-sm text-textSecondary dark:text-slate-400">
                  <i class="fa-solid fa-bell-slash mb-1 block text-lg text-muted"></i>
                  Belum ada notifikasi
                </div>
              @endif
            </div>
          </div>
        </div>
        <div class="app-user-menu">
          <button class="app-user-trigger">
            <img
              src="{{ $guruAvatarUrl }}"
              alt="avatar" class="app-user-avatar">
            <div class="app-user-copy">
              <div class="app-user-name">{{ $currentGuru?->name ?? 'Guru' }}</div>
              <div class="app-user-role">Guru / Operator</div>
            </div>
            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
          </button>
          <div class="app-dropdown">
            <div class="md:hidden">
              <button type="button" class="app-dropdown-link w-full text-left" data-font-size="increase">
                <i class="fa-solid fa-magnifying-glass-plus fa-fw shrink-0"></i>
                Perbesar tampilan
              </button>
              <button type="button" class="app-dropdown-link w-full text-left" data-font-size="decrease">
                <i class="fa-solid fa-magnifying-glass-minus fa-fw shrink-0"></i>
                Perkecil tampilan
              </button>
              <button type="button" class="app-dropdown-link w-full text-left" data-theme-toggle>
                <i class="fa-solid fa-moon fa-fw shrink-0"></i>
                Dark mode
              </button>
              <div class="my-1 border-t border-slate-200/70 dark:border-slate-700/60"></div>
            </div>
            <a href="{{ route('guru.profile') }}" class="app-dropdown-link">
              <i class="fa-solid fa-user fa-fw shrink-0"></i>
              Profil
            </a>
            <a href="{{ route('guru.guide') }}" class="app-dropdown-link">
              <i class="fa-solid fa-circle-info fa-fw shrink-0"></i>
              Panduan
            </a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="app-dropdown-link w-full text-left">
                <i class="fa-solid fa-right-from-bracket fa-fw shrink-0"></i>
                Keluar
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </header>

  <nav class="bottom-nav">
    <div class="bottom-nav-track">
      <a href="{{ route('guru.dashboard') }}" class="bottom-nav-item {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-house"></i>
        Beranda
      </a>
      <a href="{{ route('guru.materials') }}" class="bottom-nav-item {{ request()->routeIs('guru.materials*') ? 'active' : '' }}">
        <i class="fa-solid fa-book"></i>
        Materi
      </a>
      <a href="{{ route('guru.paket-soal.index') }}" class="bottom-nav-item {{ request()->routeIs('guru.paket-soal.*') || request()->routeIs('guru.soal.*') ? 'active' : '' }}">
        <i class="fa-solid fa-layer-group"></i>
        Soal
      </a>
      <a href="{{ route('guru.exams') }}" class="bottom-nav-item {{ request()->routeIs('guru.exams*') ? 'active' : '' }}">
        <i class="fa-solid fa-file-pen"></i>
        Ujian
      </a>
      <a href="{{ route('guru.profile') }}" class="bottom-nav-item {{ request()->routeIs('guru.profile*') ? 'active' : '' }}">
        <i class="fa-solid fa-user"></i>
        Akun
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
            title="Ciutkan sidebar">
            <i class="fa-solid fa-angles-left" data-sidebar-toggle-icon></i>
          </button>
        </div>
        <a href="{{ route('guru.dashboard') }}"
          class="sidebar-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
          <i class="fa-solid fa-gauge-high w-5"></i>
          <span class="sidebar-link-label">Dashboard</span>
        </a>
        <a href="{{ route('guru.chat') }}" class="sidebar-link {{ request()->routeIs('guru.chat') ? 'active' : '' }}">
          <i class="fa-solid fa-comments w-5"></i>
          <span class="sidebar-link-label">Live Chat</span>
        </a>

        <div class="sidebar-section-title">Konten</div>
        <a href="{{ route('guru.materials') }}"
          class="sidebar-link {{ request()->routeIs('guru.materials') ? 'active' : '' }}">
          <i class="fa-solid fa-book w-5"></i>
          <span class="sidebar-link-label">Materi</span>
        </a>
        <a href="{{ route('guru.soal-ujion.index') }}"
          class="sidebar-link {{ request()->routeIs('guru.soal-ujion*') ? 'active' : '' }}">
          <i class="fa-solid fa-layer-group w-5"></i>
          <span class="sidebar-link-label">Soal dari Ujion</span>
        </a>
        <a href="{{ route('guru.personal-questions') }}"
          class="sidebar-link {{ request()->routeIs('guru.personal-questions*') ? 'active' : '' }}">
          <i class="fa-solid fa-database w-5"></i>
          <span class="sidebar-link-label">Soal Pribadi</span>
        </a>
        <a href="{{ route('guru.paket-soal.index') }}"
          class="sidebar-link {{ request()->routeIs('guru.paket-soal.*') || request()->routeIs('guru.soal.*') ? 'active' : '' }}">
          <i class="fa-solid fa-database w-5"></i>
          <span class="sidebar-link-label">Paket Soal TKA</span>
        </a>
        <a href="{{ route('guru.exams') }}"
          class="sidebar-link {{ request()->routeIs('guru.exams*') ? 'active' : '' }}">
          <i class="fa-solid fa-file-lines w-5"></i>
          <span class="sidebar-link-label">Simulasi Ujian</span>
        </a>
        <a href="{{ route('guru.results.index') }}"
          class="sidebar-link {{ request()->routeIs('guru.results.*') ? 'active' : '' }}">
          <i class="fa-solid fa-chart-line w-5"></i>
          <span class="sidebar-link-label">Hasil Siswa</span>
        </a>

        <div class="sidebar-section-title">Akun</div>
        <a href="{{ route('guru.guide') }}" class="sidebar-link {{ request()->routeIs('guru.guide') ? 'active' : '' }}">
          <i class="fa-solid fa-circle-info w-5"></i>
          <span class="sidebar-link-label">Cara Menggunakan</span>
        </a>
        <a href="{{ route('guru.profile') }}"
          class="sidebar-link {{ request()->routeIs('guru.profile*') ? 'active' : '' }}">
          <i class="fa-solid fa-user w-5"></i>
          <span class="sidebar-link-label">Profil</span>
        </a>
        @if($waGroupLink)
        <a href="{{ $waGroupLink }}" target="_blank" rel="noopener" class="sidebar-link">
          <i class="fa-brands fa-whatsapp w-5"></i>
          <span class="sidebar-link-label">Gabung Saluran WA</span>
        </a>
        @endif
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
          &copy; {{ date('Y') }} Ujion. All rights reserved.
        </footer>
      </div>
    </main>
  </div>
  @include('partials.ssd')
  @stack('scripts')
</body>

</html>
