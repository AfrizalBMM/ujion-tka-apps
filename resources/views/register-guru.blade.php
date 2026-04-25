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
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Nama + Gelar <span class="text-red-500">*</span></label>
                        <input type="text" name="name"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('name') }}" placeholder="Contoh: Siti Aisyah, S.Pd." required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Email Aktif <span class="text-red-500">*</span></label>
                        <input type="email" id="email_input" name="email"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('email') }}" placeholder="nama@email.com" required>
                        <p id="email_feedback" class="mt-1 text-xs hidden font-medium"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Jenjang <span class="text-red-500">*</span></label>
                        <div class="ssd-wrap mt-1">
                            <input type="hidden" name="jenjang" value="{{ old('jenjang') }}" required>
                            <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white">
                                <span class="ssd-label">{{ old('jenjang') ?: 'Pilih jenjang yang diampu' }}</span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                            </button>
                            <div class="ssd-panel">
                                <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                                <div class="ssd-list">
                                    <div class="ssd-option{{ !old('jenjang') ? ' ssd-selected' : '' }}" data-value="">Pilih jenjang yang diampu</div>
                                    @foreach (config('ujion.jenjangs') as $jnj)
                                        <div class="ssd-option{{ old('jenjang') == $jnj ? ' ssd-selected' : '' }}" data-value="{{ $jnj }}">
                                            {{ $jnj }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">No WhatsApp <span class="text-red-500">*</span></label>
                        <input type="text" id="no_wa_input" name="no_wa"
                            class="input w-full dark:border-slate-800 dark:bg-slate-950/40 dark:text-white"
                            value="{{ old('no_wa') }}" placeholder="08xxxxxxxxxx" required>
                        <p id="wa_feedback" class="mt-1 text-xs hidden font-medium"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700 dark:text-slate-200">Satuan Pendidikan <span class="text-red-500">*</span></label>
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
                Sudah daftar tapi belum dapat token?
                <a href="{{ route('register.guru.pending') }}" class="font-bold text-primary hover:underline">Lanjutkan aktivasi</a>
            </p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Masuk di sini</a>
            </p>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const waInput = document.getElementById('no_wa_input');
        const emailInput = document.getElementById('email_input');
        const waFeedback = document.getElementById('wa_feedback');
        const emailFeedback = document.getElementById('email_feedback');
        const submitBtn = document.querySelector('form button[type="submit"]');
        
        let waTimeout = null;
        let emailTimeout = null;
        let isWaInvalid = false;
        let isEmailInvalid = false;

        function updateSubmitButton() {
            if (isWaInvalid || isEmailInvalid) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        if (waInput) {
            waInput.addEventListener('input', function() {
                clearTimeout(waTimeout);
                const value = this.value.trim();
                
                if (value.length < 9) {
                    waFeedback.classList.add('hidden');
                    isWaInvalid = false;
                    updateSubmitButton();
                    return;
                }

                waTimeout = setTimeout(() => {
                    fetch(`{{ route('register.guru.check-wa') }}?no_wa=${encodeURIComponent(value)}`)
                        .then(res => res.json())
                        .then(data => {
                            waFeedback.classList.remove('hidden', 'text-green-600', 'text-red-600');
                            if (data.exists) {
                                waFeedback.textContent = data.message;
                                waFeedback.classList.add('text-red-600');
                                isWaInvalid = true;
                            } else {
                                waFeedback.textContent = data.message;
                                waFeedback.classList.add('text-green-600');
                                isWaInvalid = false;
                            }
                            updateSubmitButton();
                        })
                        .catch(err => console.error('Error checking WA:', err));
                }, 500);
            });
        }

        if (emailInput) {
            emailInput.addEventListener('input', function() {
                clearTimeout(emailTimeout);
                const value = this.value.trim();
                
                if (value.length < 5 || !value.includes('@')) {
                    emailFeedback.classList.add('hidden');
                    isEmailInvalid = false;
                    updateSubmitButton();
                    return;
                }

                emailTimeout = setTimeout(() => {
                    fetch(`{{ route('register.guru.check-email') }}?email=${encodeURIComponent(value)}`)
                        .then(res => res.json())
                        .then(data => {
                            emailFeedback.classList.remove('hidden', 'text-green-600', 'text-red-600');
                            if (data.exists) {
                                emailFeedback.textContent = data.message;
                                emailFeedback.classList.add('text-red-600');
                                isEmailInvalid = true;
                            } else {
                                emailFeedback.textContent = data.message;
                                emailFeedback.classList.add('text-green-600');
                                isEmailInvalid = false;
                            }
                            updateSubmitButton();
                        })
                        .catch(err => console.error('Error checking Email:', err));
                }, 500);
            });
        }
    });
</script>
@endpush

