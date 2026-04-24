@extends('layouts.guest')

@php
    $fullscreenGuest = true;
@endphp

@section('content')
    <div class="mx-auto flex min-h-full w-full max-w-xl flex-col justify-center text-center">
        <div class="mb-7">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-primary shadow-glow">
                <i class="fa-solid fa-user-plus text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white">Daftar Akun Guru / Operator</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Isi data dengan lengkap untuk mengajukan aktivasi akun.
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

        @if (session('flash'))
            <div id="success-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-lg">
                    <div class="mb-2 text-3xl font-bold text-green-600">OK</div>
                    <div class="mb-2 text-lg font-bold">{{ session('flash.message') }}</div>
                    <button onclick="document.getElementById('success-modal').style.display='none'"
                        class="mt-4 rounded bg-blue-600 px-4 py-2 text-white">Tutup</button>
                </div>
            </div>
            <script>
                setTimeout(() => {
                    document.getElementById('success-modal').style.display = 'none';
                }, 3500);
            </script>
        @endif

        <div class="card animate-fade-in-up border-white/20 bg-white/80 p-6 text-left shadow-2xl backdrop-blur md:p-7 dark:bg-slate-950/60">
            <form action="{{ route('register.guru') }}" method="POST">
                @csrf
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Nama + Gelar</label>
                        <input type="text" name="name"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('name') }}" placeholder="Contoh: Siti Aisyah, S.Pd." required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Email Aktif</label>
                        <input type="email" name="email"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('email') }}" placeholder="nama@email.com" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Jenjang</label>
                        <select id="jenjang-select" name="jenjang"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white" required>
                            <option value="" disabled selected>Pilih jenjang yang diampu</option>
                            @foreach (config('ujion.jenjangs') as $jnj)
                                <option value="{{ $jnj }}" @if (old('jenjang') == $jnj) selected @endif>
                                    {{ $jnj }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">No WhatsApp</label>
                        <input type="text" name="no_wa"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('no_wa') }}" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Satuan Pendidikan</label>
                        <input type="text" name="satuan_pendidikan"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('satuan_pendidikan') }}" placeholder="Contoh: SD Negeri 1 Bandung" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-5 w-full">Lanjutkan Pendaftaran</button>
            </form>
        </div>

        <div class="mt-7">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Masuk di sini</a>
            </p>
        </div>
    </div>

@endsection
