@extends('layouts.guest')

@section('content')
<div class="flex flex-col items-center justify-center">
    <div class="w-full max-w-md space-y-6 rounded-2xl border border-white/70 bg-white/90 p-5 shadow-card sm:p-6">
        <h2 class="text-2xl font-bold text-center">Lengkapi Identitas</h2>
        <form method="POST" action="{{ route('siswa.mulai') }}">
            @csrf
            <div class="mb-4">
                <label for="nama" class="block mb-1 font-semibold">Nama Lengkap <span class="text-red-500">*</span></label>
                <input id="nama" name="nama" type="text" required autofocus class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" placeholder="Nama lengkap">
            </div>
            <div class="mb-4">
                <label for="wa" class="block mb-1 font-semibold">No WhatsApp (opsional)</label>
                <input id="wa" name="wa" type="text" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" placeholder="08xxxxxxxxxx">
            </div>
            <button type="submit" class="w-full py-2 font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">Lanjut</button>
        </form>
    </div>
</div>
@endsection
