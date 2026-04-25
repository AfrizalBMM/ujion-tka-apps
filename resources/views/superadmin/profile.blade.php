@extends('layouts.superadmin')

@section('title', 'Profil Superadmin')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Akun Superadmin</span>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="page-title">Profil dan keamanan akun.</h1>
                <p class="page-description">Kelola identitas admin, avatar header, dan password masuk superadmin.</p>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-white">
                <img src="{{ $user->avatar_url }}" class="h-12 w-12 rounded-full border border-white/30 object-cover" alt="Avatar {{ $user->name }}">
                <div>
                    <div class="font-bold">{{ $user->name }}</div>
                    <div class="text-xs text-slate-200">{{ $user->email }}</div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
        <form method="POST" action="{{ route('superadmin.profile.update') }}" enctype="multipart/form-data" class="card p-6">
            @csrf
            <div class="section-heading mb-5">
                <div>
                    <h2 class="section-title">Data Profil</h2>
                    <p class="section-description">Nama dan email ini tampil di header serta dipakai untuk login admin.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Nama</label>
                    <input name="name" class="input w-full" value="{{ old('name', $user->name) }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Email</label>
                    <input type="email" name="email" class="input w-full" value="{{ old('email', $user->email) }}" required>
                </div>
            </div>

            <div class="mt-5">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Avatar</label>
                <div class="flex flex-col gap-4 rounded-2xl border border-border bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40 sm:flex-row sm:items-center">
                    <img src="{{ $user->avatar_url }}" class="h-20 w-20 rounded-full border border-white object-cover shadow" alt="Avatar {{ $user->name }}">
                    <div class="flex-1">
                        <input type="file" name="avatar" class="input w-full" accept="image/*">
                        <div class="mt-2 text-xs text-muted">Format gambar umum, maksimal 2 MB.</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary mt-5">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan Profil
            </button>
        </form>

        <form method="POST" action="{{ route('superadmin.profile.password') }}" class="card p-6">
            @csrf
            <div class="section-heading mb-5">
                <div>
                    <h2 class="section-title">Ganti Password</h2>
                    <p class="section-description">Gunakan password baru untuk login superadmin berikutnya.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Password Baru</label>
                    <input type="password" name="password" class="input w-full" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="input w-full" required>
                </div>
            </div>

            <button type="submit" class="btn-secondary mt-5">
                <i class="fa-solid fa-key"></i>
                Ganti Password
            </button>
        </form>
    </div>
</div>
@endsection
