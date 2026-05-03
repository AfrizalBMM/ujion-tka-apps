@extends('layouts.guest')

@section('title', 'Masuk Latihan — Ujion')

@section('content')
<div class="w-full max-w-md space-y-5">
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-xl font-black text-white shadow-lg">U</div>
        <h1 class="text-2xl font-bold text-slate-900">Masuk Latihan</h1>
        <p class="mt-1 text-sm text-textSecondary">Masukkan token latihan materi yang diberikan guru</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('token') }}
        </div>
    @endif

    <form method="POST" action="{{ route('materi.token.validate') }}" class="space-y-4">
        @csrf
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary" for="token">Token Latihan</label>
            <input
                id="token"
                name="token"
                type="text"
                required
                autofocus
                autocomplete="off"
                maxlength="10"
                class="input text-center font-mono text-xl font-bold uppercase tracking-[0.4em]"
                placeholder="— — — — — —"
                oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
            >
            <p class="text-xs text-textSecondary">Token berisi 8 karakter huruf dan angka.</p>
        </div>
        <button type="submit" class="btn-primary w-full py-3 text-base font-bold">
            Lanjut &rarr;
        </button>
    </form>

    <div class="text-center text-xs text-textSecondary">
        Sudah punya token ujian? <a class="font-semibold text-indigo-700 hover:underline" href="{{ route('siswa.login') }}">Masuk ujian</a>
    </div>
</div>
@endsection
