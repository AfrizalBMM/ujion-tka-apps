@extends('layouts.guest')

@section('title', 'Admin Login - Ujion TKA')

@section('content')
<div class="card bg-slate-900 border border-slate-800 p-8 shadow-2xl animate-fade-in-up text-white">
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-primary shadow-glow mb-4">
            <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold">Ngadimin Hub</h1>
        <p class="text-slate-400 mt-1">Superadmin Access Only</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-950/50 border border-red-900/50 flex gap-3 text-red-400 text-sm">
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

    <form action="{{ route('admin.login') }}" method="POST" class="space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-bold text-slate-300 mb-2">Email Admin</label>
            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="email" name="email" 
                    class="w-full pl-11 pr-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-white" 
                    placeholder="admin@ujion.com"
                    value="{{ old('email') }}" required autofocus>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-slate-300 mb-2">Password</label>
            <div class="relative">
                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="password" name="password" 
                    class="w-full pl-11 pr-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-white" 
                    placeholder="••••••••"
                    required>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-lg">
            Authenticate
            <i class="fa-solid fa-shield-check ml-2 text-sm"></i>
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-slate-800 text-center">
        <a href="{{ route('landing') }}" class="text-slate-500 text-sm hover:text-slate-300">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Landing
        </a>
    </div>
</div>
@endsection
