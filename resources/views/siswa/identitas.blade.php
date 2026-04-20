@extends('layouts.guest')

@section('title', 'Identitas Peserta — Ujion')

@section('content')
<div class="w-full max-w-md space-y-5">
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-xl font-black text-white shadow-lg">U</div>
        <h1 class="text-2xl font-bold text-slate-900">Lengkapi Identitas</h1>
        <p class="mt-1 text-sm text-textSecondary">Data ini digunakan untuk mengidentifikasi riwayat jawaban Anda</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('siswa.mulai') }}" class="space-y-4">
        @csrf
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary" for="nama">Nama Lengkap <span class="text-red-500">*</span></label>
            <input
                id="nama"
                name="nama"
                type="text"
                required
                autofocus
                autocomplete="name"
                class="input"
                placeholder="Nama lengkap sesuai kartu identitas"
                value="{{ old('nama') }}"
            >
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary" for="wa">No. WhatsApp <span class="text-textSecondary font-normal">(opsional)</span></label>
            <input
                id="wa"
                name="wa"
                type="tel"
                autocomplete="tel"
                class="input"
                placeholder="08xxxxxxxxxx"
                value="{{ old('wa') }}"
            >
            <p class="text-xs text-textSecondary">Dipakai untuk mencocokkan sesi jika Anda mengerjakan ulang.</p>
        </div>
        <button type="submit" class="btn-primary w-full py-3 text-base font-bold">
            Lanjut ke Petunjuk &rarr;
        </button>
    </form>
</div>
@endsection
