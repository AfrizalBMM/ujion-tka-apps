@extends('layouts.superadmin')

@section('title', $post->exists ? 'Edit Artikel' : 'Tambah Artikel')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $post->exists ? 'Edit Artikel' : 'Tambah Artikel' }}</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Konten mendukung format Markdown (judul, daftar, tautan, dst.). Slug kosong akan dibuat otomatis dari judul.
            </p>
        </div>
        <a href="{{ route('superadmin.blog.index') }}" class="btn-secondary">
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

    <form method="POST" action="{{ $post->exists ? route('superadmin.blog.update', $post) : route('superadmin.blog.store') }}" class="card space-y-5">
        @csrf

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
            <input class="input mt-1 w-full" name="title" value="{{ old('title', $post->title) }}" placeholder="Contoh: 7 Tips Menyiapkan Siswa Menghadapi TKA" required maxlength="220">
        </div>

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Slug (opsional)</label>
            <input class="input mt-1 w-full font-mono text-sm" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="otomatis-dari-judul" maxlength="220">
            <div class="mt-1 text-xs text-muted">Huruf kecil, angka, dan tanda strip. URL publik: /artikel/<span class="font-mono">slug</span></div>
        </div>

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Ringkasan / Excerpt (opsional)</label>
            <textarea class="input mt-1 min-h-20 w-full" name="excerpt" placeholder="Ringkasan singkat yang tampil di daftar artikel" maxlength="300">{{ old('excerpt', $post->excerpt) }}</textarea>
        </div>

        <div>
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Konten (Markdown)</label>
            <textarea class="input mt-1 min-h-80 w-full font-mono text-sm" name="content" placeholder="Tulis isi artikel di sini..." required>{{ old('content', $post->content) }}</textarea>
        </div>

        <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-700/60">
            <div class="text-xs font-bold uppercase tracking-wide text-textSecondary dark:text-slate-300">SEO &amp; Meta (opsional)</div>
            <div class="mt-3 space-y-4">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Meta Title</label>
                    <input class="input mt-1 w-full" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="120" placeholder="Ideal 50-60 karakter. Kosongkan untuk memakai judul artikel.">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Meta Description</label>
                    <textarea class="input mt-1 min-h-20 w-full" name="meta_description" maxlength="300" placeholder="Ideal 150-160 karakter. Kosongkan untuk memakai excerpt/isi artikel.">{{ old('meta_description', $post->meta_description) }}</textarea>
                </div>
            </div>
        </div>

        <label class="flex items-center gap-3">
            <input type="checkbox" name="is_published" value="1" class="h-4 w-4" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Terbitkan artikel</span>
        </label>

        <div class="flex items-center justify-end gap-3">
            <button class="btn-primary" type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection
