@extends('layouts.superadmin')

@section('title', $mode === 'edit' ? 'Edit Pesan Otomatis' : 'Tambah Pesan Otomatis')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $mode === 'edit' ? 'Edit Pesan Otomatis' : 'Tambah Pesan Otomatis' }}</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Gunakan placeholder seperti <span class="font-mono">{name}</span> atau <span class="font-mono">{token}</span> sesuai kebutuhan.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.wa-templates.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="card max-w-3xl">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Form</div>
        <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Template</div>

        <form method="POST" action="{{ $mode === 'edit' ? route('superadmin.wa-templates.update', $template) : route('superadmin.wa-templates.store') }}" class="mt-4 space-y-4">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Key</label>
                    <input name="key" class="input w-full font-mono" value="{{ old('key', $template->key) }}" placeholder="contoh: payment_approved" {{ $mode === 'edit' ? '' : '' }}>
                    @error('key')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status</label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                        <span>Aktif</span>
                    </label>
                    @error('is_active')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Judul</label>
                <input name="title" class="input w-full" value="{{ old('title', $template->title) }}" placeholder="Contoh: Notifikasi pembayaran diterima">
                @error('title')
                    <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Deskripsi (opsional)</label>
                <textarea name="description" class="input w-full min-h-24" placeholder="Catatan internal admin">{{ old('description', $template->description) }}</textarea>
                @error('description')
                    <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Isi pesan</label>
                <textarea name="body" class="input w-full min-h-60" placeholder="Tulis isi pesan WhatsApp...">{{ old('body', $template->body) }}</textarea>
                @error('body')
                    <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
