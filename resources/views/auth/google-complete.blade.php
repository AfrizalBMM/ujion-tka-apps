@extends('layouts.guest')

@php
    $fullscreenGuest = true;
    $hideFooterGuest = true;
@endphp

@section('title', 'Lengkapi Data - Ujion TKA')

@section('content')
    <div class="mx-auto flex min-h-full w-full max-w-xl flex-col justify-center text-center">
        <div class="mb-7">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
                <i class="fa-solid fa-user-plus text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white">Lengkapi Data Guru</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Akun Google Anda berhasil terhubung. Lengkapi data di bawah untuk mengajukan aktivasi akun.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 p-4 text-left text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card animate-fade-in-up border-white/20 bg-white/80 p-6 text-left shadow-2xl backdrop-blur md:p-7 dark:bg-slate-950/60">
            <div class="mb-5 flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                @if (!empty($google['avatar']))
                    <img src="{{ $google['avatar'] }}" alt="Avatar" class="h-12 w-12 rounded-full object-cover">
                @else
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-primary text-white">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
                <div class="min-w-0 text-left">
                    <div class="truncate font-bold text-slate-900 dark:text-white">{{ $google['name'] ?: $google['email'] }}</div>
                    <div class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $google['email'] }}</div>
                </div>
            </div>

            <form action="{{ route('auth.google.complete.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Jenjang <span class="text-red-500">*</span></label>
                    <div class="ssd-wrap">
                        <input type="hidden" name="jenjang" value="{{ old('jenjang') }}" required>
                        <button type="button" class="ssd-trigger flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white py-3 pl-4 pr-4 text-left outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white">
                            <span class="ssd-label">{{ old('jenjang') ?: 'Pilih jenjang' }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                        </button>
                        <div class="ssd-panel">
                            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                            <div class="ssd-list">
                                <div class="ssd-option{{ !old('jenjang') ? ' ssd-selected' : '' }}" data-value="">Pilih jenjang</div>
                                @foreach (config('ujion.jenjangs') as $jnj)
                                    <div class="ssd-option{{ old('jenjang') == $jnj ? ' ssd-selected' : '' }}" data-value="{{ $jnj }}">{{ $jnj }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">Satuan Pendidikan <span class="text-red-500">*</span></label>
                    <input type="text" name="satuan_pendidikan"
                        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-4 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                        placeholder="Contoh: SDN 01 Jakarta" value="{{ old('satuan_pendidikan') }}" required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">No. WhatsApp <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="fa-solid fa-mobile-screen-button absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="no_wa"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            placeholder="Contoh: 08123456789" value="{{ old('no_wa') }}" required>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Token akses dan notifikasi aktivasi dikirim ke nomor ini.</p>
                </div>

                <button type="submit" class="btn-primary w-full py-3 text-lg">
                    Satu tahap lagi
                    <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
                </button>
            </form>
        </div>

        <div class="mt-7">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                &copy; {{ date('Y') }} Ujion. All rights reserved.
            </p>
        </div>
    </div>
@endsection
