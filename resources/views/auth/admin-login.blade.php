@extends('layouts.guest')

@php
    $hideShowcase = true;
@endphp

@section('title', 'Admin Login - Ujion TKA')

@section('content')
<div class="animate-fade-in-up text-slate-900 dark:text-white">
    <div class="mb-6 text-center">
        <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
            <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold">Ngadimin Hub</h1>
        <p class="mt-1 text-slate-500 dark:text-slate-400">Superadmin Access Only</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-400">
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

    <form action="{{ route('admin.login') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-300">Email Admin</label>
            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"></i>
                <input type="email" name="email" 
                    class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
                    placeholder="admin@ujion.com"
                    value="{{ old('email') }}" required autofocus>
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-300">Password</label>
            <div class="relative">
                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"></i>
                <input type="password" name="password" 
                    class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
                    placeholder="••••••••"
                    required>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-lg">
            Authenticate
            <i class="fa-solid fa-shield-check ml-2 text-sm"></i>
        </button>
    </form>

    <div class="mt-6 border-t border-slate-200 pt-5 text-center dark:border-slate-800">
        <a href="{{ route('landing') }}" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Landing
        </a>
    </div>
</div>
@endsection
