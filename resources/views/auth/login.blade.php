@extends('layouts.guest')

@php
    $hideShowcase = true;
@endphp

@section('title', 'Masuk Guru - Ujion TKA')

@section('content')
    <div class="card animate-fade-in-up border-white/20 bg-white/80 p-6 shadow-2xl backdrop-blur md:p-7">
        <div class="mb-6 text-center">
            <div class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
                <i class="fa-solid fa-chalkboard-user text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Masuk Guru / Operator</h1>
            <p class="text-slate-500 mt-1">Masuk menggunakan nama yang terdaftar dan token akses yang dikirim saat akun Anda
                aktif.</p>
        </div>

        @if (session('flash'))
            @include('components.ui.flash')
        @endif

        @if ($errors->any())
            <div class="mb-5 flex gap-3 rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-600">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <div>
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap atau No. WhatsApp</label>
                <div class="relative">
                    <i class="fa-solid fa-address-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="login_identifier"
                        class="w-full rounded-xl border border-slate-200 py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="Masukkan nama atau nomor WhatsApp" value="{{ old('login_identifier') }}" required
                        autofocus>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Token Akses</label>
                <div class="relative">
                    <i class="fa-solid fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="access_token"
                        class="w-full rounded-xl border border-slate-200 py-3 pl-11 pr-4 uppercase outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="Contoh: AB12CD34EF" required>
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember"
                    class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                <label for="remember" class="ml-2 text-sm text-slate-600 cursor-pointer">Ingat saya</label>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-lg">
                Mulai Mengelola
                <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
            </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-5 text-center">
            <p class="text-slate-500 text-sm">
                Belum punya akun
                <a href="{{ route('register.guru.form') }}" class="text-primary font-bold hover:underline">Mulai pendaftaran
                    di sini</a>
            </p>
        </div>
    </div>
@endsection