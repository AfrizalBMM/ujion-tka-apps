@extends('layouts.guru')
@section('title', 'Profil Guru')
@section('content')
<div class="w-full space-y-6">
    <h1 class="text-2xl font-bold">Profil</h1>
    <form method="POST" action="{{ route('guru.profile.update') }}" enctype="multipart/form-data" class="card p-6 space-y-4">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold">Nama</label>
                <input name="name" class="input w-full" value="{{ $user->name }}" required>
            </div>
            <div>
                <label class="text-xs font-bold">Email</label>
                <input type="email" name="email" class="input w-full" value="{{ old('email', $user->email) }}" required>
            </div>
            <div>
                <label class="text-xs font-bold">Jenjang</label>
                <div class="input w-full bg-slate-100 text-slate-600 dark:bg-slate-900/60 dark:text-slate-300">
                    {{ $user->jenjang ?: '-' }}
                </div>
                <p class="mt-1 text-xs text-muted">Jenjang mengikuti data aktivasi akun dan tidak dapat diubah dari profil.</p>
            </div>
            <div>
                <label class="text-xs font-bold">Satuan</label>
                <input name="satuan_pendidikan" class="input w-full" value="{{ old('satuan_pendidikan', $user->satuan_pendidikan) }}" required>
            </div>
            <div>
                <label class="text-xs font-bold">No. Whatsapp</label>
                <input name="no_wa" class="input w-full" value="{{ old('no_wa', $user->no_wa) }}" required>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold">Avatar</label>
            <input type="file" name="avatar" class="input w-full">
            @if($user->avatar)
                <img src="{{ asset('storage/'.$user->avatar) }}" class="w-16 h-16 rounded-full mt-2">
            @endif
        </div>
        <button class="btn-primary" type="submit">Simpan Profil</button>
    </form>
</div>
@endsection
