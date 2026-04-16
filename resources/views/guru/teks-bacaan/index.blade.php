@extends('layouts.guru')

@section('title', 'Teks Bacaan')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->nama }}</span>
        <h1 class="page-title">Teks Bacaan &middot; {{ $mapel->nama_label }}</h1>
        <p class="page-description">Teks bacaan dapat dipakai oleh beberapa soal sekaligus.</p>
        <div class="page-actions">
            <a href="{{ route('guru.soal.index', [$paket, $mapel]) }}" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Kembali ke Soal</a>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <div class="card lg:sticky lg:top-6">
                <div class="font-bold text-lg mb-4">Tambah Teks Bacaan</div>
                <form method="POST" action="{{ route('guru.teks-bacaan.store', [$paket, $mapel]) }}" class="space-y-4">
                    @csrf
                    <div class="input-group">
                        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Judul (opsional)</label>
                        <input name="judul" class="input" value="{{ old('judul') }}">
                    </div>
                    <div class="input-group">
                        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Konten</label>
                        <textarea name="konten" class="input min-h-48" required>{{ old('konten') }}</textarea>
                    </div>
                    <button class="btn-primary w-full" type="submit">Simpan</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-8 space-y-4">
            <div class="card">
                <div class="font-bold text-lg">Daftar Teks Bacaan ({{ $teksBacaans->count() }})</div>
                <div class="mt-5 space-y-4">
                    @forelse($teksBacaans as $bacaan)
                        <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $bacaan->judul ?: 'Teks Bacaan #' . $bacaan->id }}</div>
                                    <div class="mt-2 whitespace-pre-line text-sm text-textSecondary">{{ \Illuminate\Support\Str::limit($bacaan->konten, 420) }}</div>
                                </div>
                                <div class="flex gap-2 md:flex-col">
                                    <button class="btn-secondary px-3 py-2 text-xs" type="button" data-edit-bacaan='@json(["id"=>$bacaan->id,"judul"=>$bacaan->judul,"konten"=>$bacaan->konten])'>Edit</button>
                                    <form method="POST" action="{{ route('guru.teks-bacaan.destroy', [$paket, $mapel, $bacaan]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada teks bacaan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>

<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-2xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
                <div class="mt-2 text-xl font-bold">Teks Bacaan</div>
            </div>
            <button type="button" class="icon-button" data-close-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="edit-form" method="POST" class="mt-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Judul</label>
                <input name="judul" class="input" id="edit-judul">
            </div>
            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Konten</label>
                <textarea name="konten" class="input min-h-56" id="edit-konten" required></textarea>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Simpan Perubahan</button>
                <button class="btn-secondary" type="button" data-close-modal>Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('edit-modal');
    const form = document.getElementById('edit-form');
    const judul = document.getElementById('edit-judul');
    const konten = document.getElementById('edit-konten');
    const closeButtons = document.querySelectorAll('[data-close-modal]');

    const close = () => modal.classList.add('hidden');
    closeButtons.forEach((btn) => btn.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

    document.querySelectorAll('[data-edit-bacaan]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editBacaan);
            form.action = "{{ route('guru.teks-bacaan.update', [$paket, $mapel, 'bacaan' => '__ID__']) }}".replace('__ID__', data.id);
            judul.value = data.judul ?? '';
            konten.value = data.konten ?? '';
            modal.classList.remove('hidden');
            judul.focus();
        });
    });
});
</script>
@endsection
