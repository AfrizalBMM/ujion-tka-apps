@extends('layouts.guest')

@php
    $fullscreenGuest = true;
    $hideFooterGuest = true;
@endphp

@section('title', 'Lupa Token Guru - Ujion TKA')

@section('content')
    <div class="mx-auto flex min-h-full w-full max-w-xl flex-col justify-center text-center">
        <div class="mb-7">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
                <i class="fa-solid fa-key text-2xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white">Lupa Token Akses</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Kirim data verifikasi agar admin dapat membantu mengecek akun guru Anda.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 p-4 text-left text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300">
                <p class="mb-2 font-semibold">Masih ada data yang perlu diperiksa:</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card animate-fade-in-up border-white/20 bg-white/80 p-6 text-left shadow-2xl backdrop-blur md:p-7 dark:bg-slate-950/60">
            <form action="{{ route('guru.token-request.send') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Nama Lengkap</label>
                    <div class="relative">
                        <i class="fa-solid fa-address-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="text"
                            name="name"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('name') }}"
                            placeholder="Masukkan nama lengkap"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Email / No. WhatsApp Aktif</label>
                    <div class="relative">
                        <i class="fa-brands fa-whatsapp absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="text"
                            name="contact"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('contact') }}"
                            placeholder="nama@email.com atau 08xxxxxxxxxx"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Jenjang</label>
                    <div class="relative">
                        <i class="fa-solid fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <div class="ssd-wrap w-full">
                            <input type="hidden" name="jenjang" value="{{ old('jenjang') }}" required>
                            <button type="button" class="ssd-trigger w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-left outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white">
                                <span class="ssd-label">{{ old('jenjang') ?: 'Pilih jenjang' }}</span>
                                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-muted ssd-icon"></i>
                            </button>
                            <div class="ssd-panel">
                                <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                                <div class="ssd-list">
                                    <div class="ssd-option{{ !old('jenjang') ? ' ssd-selected' : '' }}" data-value="">Pilih jenjang</div>
                                    @foreach (config('ujion.jenjangs') as $jenjang)
                                        <div class="ssd-option{{ old('jenjang') === $jenjang ? ' ssd-selected' : '' }}" data-value="{{ $jenjang }}">{{ $jenjang }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-3 text-lg">
                    Kirim ke Admin
                    <i class="fa-brands fa-whatsapp ml-2 text-sm"></i>
                </button>
            </form>
        </div>

        <div class="mt-7">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Sudah ingat token?
                <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Masuk di sini</a>
            </p>
        </div>
    </div>
@endsection
