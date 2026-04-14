@extends('layouts.guest')

@section('title', 'Masuk Guru - Ujion TKA')

@section('content')
<div class="card bg-white/80 backdrop-blur border border-white/20 p-8 shadow-2xl animate-fade-in-up">
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-primary shadow-glow mb-4">
            <i class="fa-solid fa-chalkboard-user text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Masuk Guru / Operator</h1>
        <p class="text-slate-500 mt-1">Gunakan Token Akses yang telah diberikan</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex gap-3 text-red-600 text-sm">
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

    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap</label>
            <div class="relative">
                <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="name" 
                    class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" 
                    placeholder="Masukkan nama sesuai registrasi"
                    value="{{ old('name') }}" required autofocus>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Token Akses</label>
            <div class="relative">
                <i class="fa-solid fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="access_token" 
                    class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all uppercase" 
                    placeholder="Contoh: AB12CD34EF"
                    required>
            </div>
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
            <label for="remember" class="ml-2 text-sm text-slate-600 cursor-pointer">Ingat saya</label>
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-lg">
            Masuk Sekarang
            <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
        <p class="text-slate-500 text-sm">
            Belum punya akun atau token? 
            <a href="{{ route('register.guru.form') }}" class="text-primary font-bold hover:underline">Daftar Sekarang</a>
        </p>
    </div>
</div>
@endsection
