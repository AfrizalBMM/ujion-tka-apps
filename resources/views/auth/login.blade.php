@extends('layouts.guest')

@php
    $fullscreenGuest = true;
    $hideFooterGuest = true;
@endphp

@section('title', 'Masuk Guru - Ujion TKA')

@section('content')
    <div class="mx-auto flex min-h-full w-full max-w-xl flex-col justify-center text-center">
        <div class="mb-7">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
                <i class="fa-solid fa-chalkboard-user text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white">Masuk Guru / Operator</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Masuk menggunakan nama yang terdaftar dan token akses yang dikirim saat akun Anda aktif.
            </p>
        </div>

        @if (session('flash'))
            <div class="mb-5">
                @include('components.ui.flash')
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 flex gap-3 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <div>
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card animate-fade-in-up border-white/20 bg-white/80 p-6 shadow-2xl backdrop-blur md:p-7 dark:bg-slate-950/60">
            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Nama Lengkap atau No. WhatsApp</label>
                    <div class="relative">
                        <i class="fa-solid fa-address-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="login_identifier"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            placeholder="Masukkan nama atau nomor WhatsApp" value="{{ old('login_identifier') }}" required autofocus>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Token Akses</label>
                    <div class="relative">
                        <i class="fa-solid fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="access_token"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 uppercase outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            placeholder="Contoh: AB12CD34EF" required>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label for="remember" class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="remember" id="remember"
                            class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-700">
                        Ingat saya
                    </label>
                    <a href="{{ route('guru.token-request.form') }}" class="text-sm font-bold text-primary hover:underline">
                        Lupa token?
                    </a>
                </div>

                <button type="submit" class="btn-primary w-full py-3 text-lg">
                    Mulai Mengelola
                    <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
                </button>
            </form>
        </div>

        <div class="mt-7">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Belum punya akun?
                <a href="{{ route('register.guru.form') }}" class="font-bold text-primary hover:underline">Mulai pendaftaran di sini</a>
            </p>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                &copy; {{ date('Y') }} Ujion. All rights reserved.
            </p>
        </div>
    </div>
@endsection
