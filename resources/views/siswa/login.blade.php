@extends('layouts.guest')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded shadow">
        <h2 class="text-2xl font-bold text-center">Masuk Ujian Siswa</h2>
        <form method="POST" action="{{ route('siswa.token.validate') }}">
            @csrf
            <div class="mb-4">
                <label for="token" class="block mb-1 font-semibold">Token Ujian</label>
                <input id="token" name="token" type="text" required autofocus class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" placeholder="Masukkan token dari guru/superadmin">
            </div>
            <button type="submit" class="w-full py-2 font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">Masuk</button>
        </form>
    </div>
</div>
@endsection
