@extends('layouts.superadmin')

@section('title', 'Detail Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->jenjang?->kode }} &middot; {{ $paket->tahun_ajaran }}</span>
        <h1 class="page-title">{{ $paket->nama }}</h1>
        <p class="page-description">Akses tiap mapel untuk menyusun soal, bacaan, dan struktur paket ujian siswa.</p>
        <div class="page-actions">
            <a href="{{ route('superadmin.paket-soal.edit', $paket) }}" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Edit Metadata</a>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        @foreach($paket->mapelPakets as $mapel)
            <article class="card">
                <div class="section-heading mb-5">
                    <div>
                        <h2 class="section-title">{{ $mapel->nama_label }}</h2>
                        <p class="section-description">{{ $mapel->soals->count() }}/{{ $mapel->jumlah_soal }} soal &middot; {{ $mapel->durasi_menit }} menit</p>
                    </div>
                    <a href="{{ route('superadmin.soal.index', [$paket, $mapel]) }}" class="btn-primary px-4 py-2 text-xs">Kelola Soal</a>
                </div>
                <form class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-3 dark:border-slate-800 dark:bg-slate-900/60" method="POST" action="{{ route('superadmin.mapel.update', [$paket, $mapel]) }}">
                    @csrf
                    @method('PUT')
                    <div class="input-group">
                        <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Jumlah Soal</label>
                        <input type="number" name="jumlah_soal" class="input" value="{{ old('jumlah_soal', $mapel->jumlah_soal) }}" min="1" max="200" required>
                    </div>
                    <div class="input-group">
                        <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Durasi (menit)</label>
                        <input type="number" name="durasi_menit" class="input" value="{{ old('durasi_menit', $mapel->durasi_menit) }}" min="1" max="600" required>
                    </div>
                    <div class="input-group">
                        <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Urutan</label>
                        <input type="number" name="urutan" class="input" value="{{ old('urutan', $mapel->urutan) }}" min="1" max="10" required>
                    </div>
                    <div class="md:col-span-3">
                        <button class="btn-secondary px-4 py-2 text-xs" type="submit">Simpan Konfigurasi</button>
                    </div>
                </form>
                <div class="space-y-3">
                    @forelse($mapel->soals->take(5) as $soal)
                        <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold">Soal {{ $soal->nomor_soal }}</div>
                                <span class="badge-info">{{ str($soal->tipe_soal)->replace('_', ' ')->headline() }}</span>
                            </div>
                            <p class="mt-2 text-sm text-textSecondary">{{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 120) }}</p>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada soal pada mapel ini.</div>
                    @endforelse
                </div>
            </article>
        @endforeach
    </section>
</div>
@endsection
