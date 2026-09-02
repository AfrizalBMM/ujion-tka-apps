@extends('layouts.guru')

@section('title', 'Profil Guru')

@section('content')
@php
    $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : ($user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'Guru') . '&background=0f766e&color=ffffff&size=256');
    $initials = collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
    $joinedAt = $user->created_at?->translatedFormat('d F Y');
    $hasErrors = $errors->any();

    $copyBtnClass = 'inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-teal-200/70 bg-white/80 px-2.5 py-1 text-xs font-semibold text-teal-700 transition hover:border-teal-400 hover:bg-teal-50 dark:border-teal-500/30 dark:bg-slate-950/60 dark:text-teal-300 dark:hover:bg-teal-500/10';
@endphp

<div class="w-full space-y-6">
    <section class="relative overflow-hidden rounded-[32px] border border-slate-200/70 bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.22),_transparent_34%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_50%,_#ecfeff_100%)] p-6 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.18),_transparent_30%),linear-gradient(135deg,_rgba(15,23,42,0.98)_0%,_rgba(15,118,110,0.25)_100%)] sm:p-8">
        <div class="absolute -right-16 top-0 h-40 w-40 rounded-full bg-teal-400/20 blur-3xl dark:bg-teal-300/10"></div>
        <div class="absolute bottom-0 left-1/3 h-28 w-28 rounded-full bg-cyan-300/30 blur-3xl dark:bg-cyan-400/10"></div>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4 sm:gap-5">
                <div class="relative shrink-0">
                    <img src="{{ $avatarUrl }}" alt="Avatar {{ $user->name }}" class="h-20 w-20 rounded-3xl border border-white/70 object-cover shadow-lg shadow-teal-900/10 sm:h-24 sm:w-24 dark:border-slate-700/70">
                    <div class="absolute -bottom-2 -right-2 flex h-9 w-9 items-center justify-center rounded-2xl border border-white/80 bg-white text-xs font-black tracking-[0.18em] text-teal-700 shadow-md dark:border-slate-700 dark:bg-slate-900 dark:text-teal-300">
                        {{ $initials ?: 'GU' }}
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-teal-200/80 bg-white/75 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-700 backdrop-blur dark:border-teal-400/20 dark:bg-slate-900/60 dark:text-teal-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Profil Guru
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $user->name }}</h1>
                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ $user->satuan_pendidikan ?: '-' }} &middot; Jenjang {{ $user->jenjang ?: '-' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('guru.profile.edit') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                            Edit Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Email</div>
                    <button type="button" class="{{ $copyBtnClass }}" data-copy-token data-copy-text="{{ $user->email }}">
                        <i class="fa-regular fa-copy text-xs"></i>
                        <span>Salin</span>
                    </button>
                </div>
                <div class="mt-2 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->email }}</div>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">No. WhatsApp</div>
                    @if($user->no_wa)
                    <button type="button" class="{{ $copyBtnClass }}" data-copy-token data-copy-text="{{ $user->no_wa }}">
                        <i class="fa-regular fa-copy text-xs"></i>
                        <span>Salin</span>
                    </button>
                    @endif
                </div>
                <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->no_wa ?: '-' }}</div>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Sekolah / Instansi</div>
                <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->satuan_pendidikan ?: '-' }}</div>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Jenjang</div>
                <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->jenjang ?: '-' }}</div>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Bergabung</div>
                <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $joinedAt ?: '-' }}</div>
            </div>
            <div class="rounded-2xl border border-teal-200/80 bg-teal-50/60 p-4 dark:border-teal-500/20 dark:bg-teal-500/5">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-700 dark:text-teal-300">Token Akses</div>
                    <button type="button" class="{{ $copyBtnClass }}" data-copy-token data-copy-text="{{ $user->access_token }}">
                        <i class="fa-regular fa-copy text-xs"></i>
                        <span>Salin</span>
                    </button>
                </div>
                <div class="mt-2 truncate rounded-xl border border-teal-100 bg-white px-3 py-1.5 font-mono text-sm tracking-widest text-slate-800 dark:border-teal-500/20 dark:bg-slate-950 dark:text-slate-100">
                    {{ $user->access_token }}
                </div>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Akun Google</div>
                @if ($user->google_id)
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-100">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Terhubung
                        </div>
                        <form method="POST" action="{{ route('guru.profile.google.disconnect') }}"
                            onsubmit="return confirm('Putuskan koneksi akun Google? Anda tetap bisa masuk dengan WhatsApp + token.')">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-rose-500 transition hover:text-rose-600 hover:underline dark:text-rose-300">
                                Putuskan
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">Belum terhubung.</div>
                    <a href="{{ route('guru.profile.google.connect') }}"
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Hubungkan Google
                    </a>
                @endif
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="overflow-hidden rounded-[30px] border border-slate-200/70 bg-white shadow-[0_24px_70px_-42px_rgba(15,23,42,0.45)] dark:border-slate-800 dark:bg-slate-950/95">
            <div class="border-b border-slate-200/80 px-6 py-5 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Password</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Login utama Anda tetap menggunakan nomor WhatsApp + token akses. Password bersifat opsional dan hanya relevan bila login email+password diaktifkan.
                </p>
            </div>
            <form method="POST" action="{{ route('guru.profile.password') }}" class="space-y-4 px-6 py-6">
                @csrf
                <div class="space-y-2">
                    <label for="password" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Password Baru</label>
                    <input id="password" type="password" name="password" required minlength="8"
                        class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100 {{ $errors->has('password') ? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80' }}"
                        placeholder="Minimal 8 karakter">
                    @error('password')
                        <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-2">
                    <label for="password_confirmation" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        placeholder="Ulangi password baru">
                </div>
                <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400">
                    Simpan Password
                </button>
            </form>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-slate-200/70 bg-[linear-gradient(160deg,_#0f172a_0%,_#134e4a_100%)] text-white shadow-[0_24px_70px_-42px_rgba(15,23,42,0.75)] dark:border-slate-700/70">
            <div class="space-y-4 px-6 py-6">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg">
                    <i class="fa-solid fa-shield-heart"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold">Profil yang rapi membangun kepercayaan</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-200/90">
                        Gunakan nama yang konsisten, email aktif, dan foto profil yang jelas agar identitas akun terlihat profesional.
                    </p>
                </div>
                <div class="space-y-3 text-sm text-slate-100/90">
                    <div class="flex items-start gap-3 rounded-2xl bg-white/5 px-4 py-3">
                        <i class="fa-solid fa-circle-check mt-0.5 text-teal-300"></i>
                        <span>Pastikan WhatsApp dapat menerima pesan verifikasi atau informasi penting.</span>
                    </div>
                    <div class="flex items-start gap-3 rounded-2xl bg-white/5 px-4 py-3">
                        <i class="fa-solid fa-circle-check mt-0.5 text-teal-300"></i>
                        <span>Gunakan satuan pendidikan resmi agar data akun lebih mudah dicocokkan.</span>
                    </div>
                    <div class="flex items-start gap-3 rounded-2xl bg-white/5 px-4 py-3">
                        <i class="fa-solid fa-circle-check mt-0.5 text-teal-300"></i>
                        <span>Foto profil sebaiknya close-up, terang, dan tidak blur.</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
