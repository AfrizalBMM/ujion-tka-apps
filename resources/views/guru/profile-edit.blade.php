@extends('layouts.guru')

@section('title', 'Edit Profil Guru')

@section('content')
@php
    $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : ($user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'Guru') . '&background=0f766e&color=ffffff&size=256');
    $hasErrors = $errors->any();
@endphp

<div class="w-full space-y-6">
    <section class="page-hero">
        <div class="flex items-center justify-between gap-4">
            <div>
                <span class="page-kicker">Akun Guru</span>
                <h1 class="page-title">Edit &amp; Lengkapi Profil</h1>
            </div>
            <a href="{{ route('guru.profile') }}" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>
        </div>
    </section>

    <form method="POST" action="{{ route('guru.profile.update') }}" enctype="multipart/form-data" class="overflow-hidden rounded-[30px] border border-slate-200/70 bg-white shadow-[0_24px_70px_-42px_rgba(15,23,42,0.45)] dark:border-slate-800 dark:bg-slate-950/95">
        @csrf

        <div class="border-b border-slate-200/80 px-6 py-5 dark:border-slate-800 sm:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Informasi Profil</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Perbarui data utama akun Anda agar tetap akurat dan mudah dikenali.</p>
                </div>
                @if($hasErrors)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                        Ada beberapa data yang perlu diperiksa kembali.
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-8 px-6 py-6 sm:px-8 sm:py-8">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Lengkap</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100 {{ $errors->has('name') ? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80' }}"
                        placeholder="Masukkan nama lengkap">
                    @error('name')
                        <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="email" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100 {{ $errors->has('email') ? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80' }}"
                        placeholder="nama@email.com">
                    @error('email')
                        <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Jenjang</label>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-3 text-sm font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                        {{ $user->jenjang ?: '-' }}
                    </div>
                    <p class="text-xs leading-5 text-slate-500 dark:text-slate-400">Jenjang mengikuti data aktivasi akun dan tidak diubah dari halaman profil.</p>
                </div>

                <div class="space-y-2">
                    <label for="satuan_pendidikan" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Satuan Pendidikan</label>
                    <input id="satuan_pendidikan" name="satuan_pendidikan" value="{{ old('satuan_pendidikan', $user->satuan_pendidikan) }}" required
                        class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100 {{ $errors->has('satuan_pendidikan') ? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80' }}"
                        placeholder="Contoh: SMP Negeri 1">
                    @error('satuan_pendidikan')
                        <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label for="no_wa" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nomor WhatsApp</label>
                    <input id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required
                        class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100 {{ $errors->has('no_wa') ? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80' }}"
                        placeholder="08xxxxxxxxxx">
                    @error('no_wa')
                        <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="rounded-[28px] border border-slate-200/80 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <img id="avatar-preview" src="{{ $avatarUrl }}" alt="Preview avatar {{ $user->name }}" class="h-20 w-20 rounded-3xl border border-white object-cover shadow-md dark:border-slate-700">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Foto Profil</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Unggah foto yang jelas agar akun Anda lebih mudah dikenali oleh siswa dan admin.</p>
                            @if($user->avatar)
                                <button type="button"
                                    class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-rose-500/30 dark:hover:text-rose-400"
                                    data-confirm
                                    data-confirm-form="delete-avatar-form"
                                    data-confirm-title="Hapus Foto Profil"
                                    data-confirm="Apakah Anda yakin ingin menghapus foto profil ini?">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                    Hapus Foto
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="w-full lg:max-w-sm">
                        <label for="avatar" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Unggah Avatar Baru</label>
                        <input id="avatar" type="file" name="avatar"
                            class="block w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-teal-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:file:bg-teal-500 dark:hover:file:bg-teal-400">
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format gambar: JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.</p>
                        @error('avatar')
                            <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200/80 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-950/80 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pastikan email dan nomor WhatsApp aktif agar komunikasi dengan admin tetap lancar.</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('guru.profile') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400">
                    <i class="fa-solid fa-check text-xs"></i>
                    Simpan Profil
                </button>
            </div>
        </div>
    </form>

    <form id="delete-avatar-form" method="POST" action="{{ route('guru.profile.avatar.delete') }}" class="hidden">
        @csrf
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('avatar');
    const preview = document.getElementById('avatar-preview');
    if (!input || !preview) return;

    let objectUrl = null;

    input.addEventListener('change', () => {
        const file = input.files?.[0] || null;
        if (!file) return;

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }

        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
    });
});
</script>
@endpush
@endsection
