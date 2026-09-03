@extends('layouts.superadmin')

@section('title', 'Detail Ujian Publik')

@section('content')
<div class="space-y-6">
    <div class="section-heading">
        <div>
            <h1 class="text-2xl font-bold">{{ $landingExam->exam?->judul ?? 'Ujian Publik' }}</h1>
            <p class="text-sm text-textSecondary mt-1">/{{ strtolower($landingExam->jenjang) }}/{{ $landingExam->slug }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.landing-exams.orders', $landingExam) }}" class="btn-secondary">
                <i class="fa-solid fa-receipt mr-1"></i>Pesanan
            </a>
            <a href="{{ route('superadmin.landing-exams.index') }}" class="btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Pendapatan</div>
            <div class="mt-2 text-xl font-black text-emerald-600">Rp{{ number_format((float) $revenue, 0, ',', '.') }}</div>
        </div>
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Pesanan Dibayar</div>
            <div class="mt-2 text-xl font-black text-indigo-600">{{ $paidOrdersCount }}</div>
        </div>
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pesanan</div>
            <div class="mt-2 text-xl font-black text-slate-700 dark:text-slate-200">{{ $ordersCount }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('superadmin.landing-exams.update', $landingExam) }}" class="card space-y-6">
        @csrf
        @method('POST')

        <div class="grid gap-4 md:grid-cols-2">
            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="jenjang" value="{{ old('jenjang', $landingExam->jenjang) }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        <span class="ssd-label">{{ old('jenjang', $landingExam->jenjang) }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                        <div class="ssd-list">
                            @foreach(['SD', 'SMP', 'SMA'] as $j)
                                <div class="ssd-option{{ $landingExam->jenjang === $j ? ' ssd-selected' : '' }}" data-value="{{ $j }}">{{ $j }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Slug</label>
                <input type="text" name="slug" class="input" value="{{ old('slug', $landingExam->slug) }}">
            </div>
        </div>

        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Singkat</label>
            <input type="text" name="short_description" class="input" value="{{ old('short_description', $landingExam->short_description) }}" maxlength="500">
        </div>

        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Lengkap</label>
            <textarea name="description" class="input min-h-32">{{ old('description', $landingExam->description) }}</textarea>
        </div>

        <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-5 dark:border-slate-800 dark:bg-slate-900/60">
            <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-textSecondary mb-4">Harga per Mapel</h3>
            <div class="space-y-3">
                @foreach($landingExam->mapels as $i => $mapel)
                    <div class="grid gap-3 rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/70 md:grid-cols-[1fr_auto_auto_auto]">
                        <div>
                            <div class="font-semibold text-sm">{{ $mapel->mapelPaket?->nama_label ?? '—' }}</div>
                            <div class="text-xs text-textSecondary mt-0.5">{{ $mapel->mapelPaket?->jumlah_soal ?? 0 }} soal — {{ $mapel->mapelPaket?->durasi_menit ?? 0 }} menit</div>
                            <input type="hidden" name="mapels[{{ $i }}][id]" value="{{ $mapel->id }}">
                        </div>
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga (Rp)</label>
                            <input type="number" name="mapels[{{ $i }}][price]" class="input" min="0" value="{{ old("mapels.{$i}.price", $mapel->price) }}" step="1000" required>
                        </div>
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga Coret</label>
                            <input type="number" name="mapels[{{ $i }}][original_price]" class="input" min="0" value="{{ old("mapels.{$i}.original_price", $mapel->original_price) }}" step="1000">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-2 text-xs">
                                <input type="checkbox" name="mapels[{{ $i }}][is_active]" value="1" @checked($mapel->is_active) class="h-4 w-4 text-primary">
                                <span>Aktif</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950/70">
            <input type="checkbox" name="is_active" value="1" @checked($landingExam->is_active) class="h-4 w-4 text-primary">
            <span>Aktifkan (tampilkan di landing page)</span>
        </label>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
            <a href="{{ route('superadmin.landing-exams.index') }}" class="btn-secondary">Kembali</a>
        </div>
    </form>

    <div class="card">
        <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-textSecondary mb-4">Token Mapel</h3>
        <div class="space-y-2">
            @foreach($landingExam->exam?->examMapelTokens ?? [] as $token)
                <div class="flex items-center justify-between rounded-xl border border-slate-200/80 px-4 py-2 dark:border-slate-700/60">
                    <span class="text-sm font-medium">{{ $token->mapelPaket?->nama_label ?? '—' }}</span>
                    <code class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-1 font-mono text-sm font-bold">{{ $token->token }}</code>
                </div>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('superadmin.landing-exams.destroy', $landingExam) }}" class="card border-red-200 dark:border-red-900/40">
        @csrf
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-bold text-red-700 dark:text-red-400">Hapus Ujian Publik</h3>
                <p class="text-sm text-textSecondary mt-1">Ujian publik akan dihapus. Data pesanan tetap tersimpan.</p>
            </div>
            <button type="submit" class="btn-danger" data-confirm data-confirm-title="Hapus Ujian Publik" data-confirm="Yakin ingin menghapus?">Hapus</button>
        </div>
    </form>
</div>
@endsection
