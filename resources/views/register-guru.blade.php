@extends('layouts.guest')

@section('content')
<div class="card mx-auto max-w-2xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Daftar Akun Guru / Operator</h2>
        <p class="mt-2 text-sm text-slate-600">
            Isi data dengan lengkap untuk mengajukan aktivasi akun. Setelah data dikirim, Anda akan menerima instruksi pembayaran dan proses verifikasi dari tim kami.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-100 p-3 text-red-700">
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
                <button onclick="document.getElementById('success-modal').style.display='none'" class="mt-4 rounded bg-blue-600 px-4 py-2 text-white">Tutup</button>
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
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-semibold">Nama + Gelar</label>
                <input type="text" name="name" class="input w-full" value="{{ old('name') }}" placeholder="Contoh: Siti Aisyah, S.Pd." required>
            </div>
            <div>
                <label class="mb-1 block font-semibold">Email Aktif</label>
                <input type="email" name="email" class="input w-full" value="{{ old('email') }}" placeholder="nama@email.com" required>
            </div>
            <div>
                <label class="mb-1 block font-semibold">Jenjang</label>
                <select name="jenjang" class="input w-full" required>
                    <option value="" disabled selected>Pilih jenjang yang diampu</option>
                    <option value="SD" @if(old('jenjang')=='SD') selected @endif>SD</option>
                    <option value="SMP" @if(old('jenjang')=='SMP') selected @endif>SMP</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-semibold">Tingkat</label>
                <select name="tingkat" class="input w-full" required>
                    <option value="" disabled selected>Pilih kelas utama</option>
                    @foreach([4,5,6,7,8,9] as $tingkat)
                        <option value="{{ $tingkat }}" @if(old('tingkat')==$tingkat) selected @endif>{{ $tingkat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-semibold">No WhatsApp</label>
                <input type="text" name="no_wa" class="input w-full" value="{{ old('no_wa') }}" placeholder="08xxxxxxxxxx" required>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-semibold">Satuan Pendidikan</label>
                <input type="text" name="satuan_pendidikan" class="input w-full" value="{{ old('satuan_pendidikan') }}" placeholder="Contoh: SD Negeri 1 Bandung" required>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <p class="font-semibold text-slate-800">Yang terjadi setelah formulir dikirim:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Data Anda masuk ke antrean verifikasi.</li>
                <li>Sistem menampilkan instruksi pembayaran sesuai paket yang aktif.</li>
                <li>Setelah pembayaran diverifikasi, token akses akan dikirim ke WhatsApp Anda.</li>
            </ul>
        </div>

        <button type="submit" class="btn-primary mt-5 w-full sm:w-auto">Lanjutkan Pendaftaran</button>
    </form>
</div>
@endsection
