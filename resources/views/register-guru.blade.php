
@extends('layouts.guest')

@section('content')
<div class="max-w-xl mx-auto mt-10 p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Registrasi Guru / Operator</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('flash'))
        <div id="success-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">
            <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm text-center">
                <div class="text-green-600 text-3xl mb-2">✔️</div>
                <div class="font-bold text-lg mb-2">{{ session('flash.message') }}</div>
                <button onclick="document.getElementById('success-modal').style.display='none'" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded">Tutup</button>
            </div>
        </div>
        <script>
            setTimeout(() => {
                document.getElementById('success-modal').style.display = 'none';
            }, 3500);
        </script>
    @endif

    <form action="{{ route('register.guru') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Nama + Gelar</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2" value="{{ old('name') }}" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Jenjang</label>
            <select name="jenjang" class="w-full border rounded px-3 py-2" required>
                <option value="" disabled selected>Pilih Jenjang</option>
                <option value="SD" @if(old('jenjang')=='SD') selected @endif>SD</option>
                <option value="SMP" @if(old('jenjang')=='SMP') selected @endif>SMP</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Tingkat</label>
            <select name="tingkat" class="w-full border rounded px-3 py-2" required>
                <option value="" disabled selected>Pilih Tingkat</option>
                @foreach([4,5,6,7,8,9] as $tingkat)
                    <option value="{{ $tingkat }}" @if(old('tingkat')==$tingkat) selected @endif>{{ $tingkat }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Satuan Pendidikan</label>
            <input type="text" name="satuan_pendidikan" class="w-full border rounded px-3 py-2" value="{{ old('satuan_pendidikan') }}" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">No WhatsApp</label>
            <input type="text" name="no_wa" class="w-full border rounded px-3 py-2" value="{{ old('no_wa') }}" required>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Daftar</button>
    </form>
</div>
@endsection
