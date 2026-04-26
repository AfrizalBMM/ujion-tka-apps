@extends('layouts.guest')

@php
    $fullscreenGuest = true;
@endphp

@section('content')
    <div class="mx-auto flex min-h-full w-full max-w-xl flex-col justify-center text-center">
        <div class="mb-7">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
                <i class="fa-solid fa-receipt text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white">Lanjutkan Aktivasi</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Masukkan nama lengkap dan nomor WhatsApp yang dipakai saat pendaftaran.
            </p>
        </div>

        @include('components.ui.flash')

        <div
            class="card animate-fade-in-up border-white/20 bg-white/80 p-6 text-left shadow-2xl backdrop-blur md:p-7 dark:bg-slate-950/60">
            <form action="{{ route('register.guru.pending.resume') }}" method="POST">
                @csrf
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Nama Lengkap</label>
                        <input type="text" name="name"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('name') }}" placeholder="Contoh: Siti Aisyah, S.Pd." required>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Boleh tulis tanpa gelar atau tanda baca,
                            misalnya Siti Aisyah.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">No WhatsApp</label>
                        <input type="text" name="no_wa"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('no_wa') }}" placeholder="08xxxxxxxxxx" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-5 w-full">Konfirmasi</button>
            </form>

            @if (!blank($adminWhatsappUrl ?? null))
                <a href="{{ $adminWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn-success mt-3 w-full">
                    <i class="fa-brands fa-whatsapp"></i>
                    WhatsApp Admin Ujion
                </a>
            @endif

            <div class="mt-5 rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">
                Pastikan data yang di masukkan valid.
            </div>
        </div>

        <div class="mt-7">
            <a href="{{ route('register.guru.form') }}" class="text-sm font-bold text-primary hover:underline">Kembali ke
                form pendaftaran</a>
        </div>
    </div>
@endsection