@extends('layouts.superadmin')

@section('title', $testimonial->exists ? 'Edit Testimoni' : 'Tambah Testimoni')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $testimonial->exists ? 'Edit Testimoni' : 'Tambah Testimoni' }}</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Feedback pengguna yang akan tampil di landing page section "Mereka sudah mencoba".
            </p>
        </div>
        <a href="{{ route('superadmin.testimonials.index') }}" class="btn-secondary">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $testimonial->exists ? route('superadmin.testimonials.update', $testimonial) : route('superadmin.testimonials.store') }}" enctype="multipart/form-data" class="card space-y-5">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nama</label>
                <input class="input mt-1 w-full" name="name" value="{{ old('name', $testimonial->name) }}" placeholder="Contoh: Ibu Siti Aisyah" required maxlength="191">
            </div>
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jabatan / Jenjang (opsional)</label>
                <input class="input mt-1 w-full" name="role" value="{{ old('role', $testimonial->role) }}" placeholder="Contoh: Guru SD" maxlength="191">
            </div>
        </div>

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Rating</label>
            <select name="rating" class="input mt-1 w-full">
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ (int) old('rating', $testimonial->rating) === $i ? 'selected' : '' }}>{{ $i }} Bintang</option>
                @endfor
            </select>
        </div>

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Isi Testimoni</label>
            <textarea class="input mt-1 min-h-28 w-full" name="content" placeholder="Tulis feedback pengguna di sini..." required maxlength="5000">{{ old('content', $testimonial->content) }}</textarea>
        </div>

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Foto Guru (opsional)</label>
            @if ($testimonial->photo_path)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $testimonial->photo_url }}" alt="{{ $testimonial->name }}" class="h-16 w-16 rounded-2xl object-cover">
                    <span class="text-xs text-muted">Upload foto baru untuk mengganti foto lama.</span>
                </div>
            @endif
            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="input mt-2 w-full">
            <div class="mt-1 text-xs text-muted">Format JPG/PNG/WebP, maksimal 4MB.</div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
                <input type="number" class="input mt-1 w-full" name="sort_order" value="{{ old('sort_order', $testimonial->sort_order) }}" min="0" max="9999">
                <div class="mt-1 text-xs text-muted">Angka kecil tampil lebih dulu.</div>
            </div>
            <label class="flex items-center gap-3 pt-6">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4" {{ old('is_active', $testimonial->is_active) ? 'checked' : '' }}>
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Tampilkan di landing page</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button class="btn-primary" type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection
