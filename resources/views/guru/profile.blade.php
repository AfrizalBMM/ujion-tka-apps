@extends('layouts.guru')

@section('title', 'Profil Guru')

@section('content')
@php
    $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : ($user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'Guru') . '&background=0f766e&color=ffffff&size=256');
    $initials = collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
    $joinedAt = $user->created_at?->translatedFormat('d F Y');
    $hasErrors = $errors->any();
@endphp

<div class="w-full space-y-6">
    <section class="relative overflow-hidden rounded-[32px] border border-slate-200/70 bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.22),_transparent_34%),linear-gradient(135deg,_#ffffff_0%,_#f8fafc_50%,_#ecfeff_100%)] p-6 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.18),_transparent_30%),linear-gradient(135deg,_rgba(15,23,42,0.98)_0%,_rgba(15,118,110,0.25)_100%)] sm:p-8">
        <div class="absolute -right-16 top-0 h-40 w-40 rounded-full bg-teal-400/20 blur-3xl dark:bg-teal-300/10"></div>
        <div class="absolute bottom-0 left-1/3 h-28 w-28 rounded-full bg-cyan-300/30 blur-3xl dark:bg-cyan-400/10"></div>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4 sm:gap-5">
                <div class="relative shrink-0">
                    <img src="{{ $avatarUrl }}" alt="Avatar {{ $user->name }}" class="h-20 w-20 rounded-3xl border border-white/70 object-cover shadow-lg shadow-teal-900/10 sm:h-24 sm:w-24 dark:border-slate-700/70">
                    <div class="absolute -bottom-2 -right-2 flex h-9 w-9 items-center justify-center rounded-2xl border border-white/80 bg-white text-xs font-black tracking-[0.18em] text-teal-700 shadow-md dark:border-slate-700 dark:bg-slate-900 dark:text-teal-300">
                        {{ $initials ?: 'GU' }}
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-teal-200/80 bg-white/75 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-700 backdrop-blur dark:border-teal-400/20 dark:bg-slate-900/60 dark:text-teal-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Profil Guru
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $user->name }}</h1>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Kelola identitas akun Anda dengan tampilan yang lebih rapi, profesional, dan nyaman digunakan saat memperbarui data sekolah maupun kontak.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[420px]">
                <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Email</div>
                    <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->email }}</div>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Jenjang</div>
                    <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->jenjang ?: '-' }}</div>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/75 p-4 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/55">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Bergabung</div>
                    <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $joinedAt ?: '-' }}</div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_360px]">
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
                            <img src="{{ $avatarUrl }}" alt="Preview avatar {{ $user->name }}" class="h-20 w-20 rounded-3xl border border-white object-cover shadow-md dark:border-slate-700">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Foto Profil</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Unggah foto yang jelas agar akun Anda lebih mudah dikenali oleh siswa dan admin.</p>
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
                <button class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400" type="submit">
                    Simpan Profil
                </button>
            </div>
        </form>

        <aside class="space-y-6">
            <section class="overflow-hidden rounded-[30px] border border-slate-200/70 bg-white shadow-[0_24px_70px_-42px_rgba(15,23,42,0.45)] dark:border-slate-800 dark:bg-slate-950/95">
                <div class="border-b border-slate-200/80 px-6 py-5 dark:border-slate-800">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Ringkasan Akun</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Informasi cepat terkait identitas dan kesiapan akun Anda.</p>
                </div>
                <div class="space-y-4 px-6 py-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nama Tampilan</div>
                        <div class="mt-2 text-base font-bold text-slate-900 dark:text-white">{{ $user->name }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Sekolah / Instansi</div>
                        <div class="mt-2 text-base font-bold text-slate-900 dark:text-white">{{ $user->satuan_pendidikan ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Kontak Aktif</div>
                        <div class="mt-2 space-y-1 text-sm text-slate-700 dark:text-slate-200">
                            <p>{{ $user->email }}</p>
                            <p>{{ $user->no_wa ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[30px] border border-slate-200/70 bg-[linear-gradient(160deg,_#0f172a_0%,_#134e4a_100%)] text-white shadow-[0_24px_70px_-42px_rgba(15,23,42,0.75)] dark:border-slate-700/70">
                <div class="space-y-4 px-6 py-6">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold">Profil yang rapi membangun kepercayaan</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-200/90">
                            Gunakan nama yang konsisten, email aktif, dan foto profil yang jelas agar identitas akun terlihat profesional.
                        </p>
                    </div>
                    <div class="space-y-3 text-sm text-slate-100/90">
                        <div class="flex items-start gap-3 rounded-2xl bg-white/5 px-4 py-3">
                            <i class="fa-solid fa-circle-check mt-0.5 text-teal-300"></i>
                            <span>Pastikan WhatsApp dapat menerima pesan verifikasi atau informasi penting.</span>
                        </div>
                        <div class="flex items-start gap-3 rounded-2xl bg-white/5 px-4 py-3">
                            <i class="fa-solid fa-circle-check mt-0.5 text-teal-300"></i>
                            <span>Gunakan satuan pendidikan resmi agar data akun lebih mudah dicocokkan.</span>
                        </div>
                        <div class="flex items-start gap-3 rounded-2xl bg-white/5 px-4 py-3">
                            <i class="fa-solid fa-circle-check mt-0.5 text-teal-300"></i>
                            <span>Foto profil sebaiknya close-up, terang, dan tidak blur.</span>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
