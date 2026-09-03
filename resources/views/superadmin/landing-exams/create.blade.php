@extends('layouts.superadmin')

@section('title', 'Tambah Ujian Publik')

@section('content')
<div class="space-y-6">
    <div class="section-heading">
        <div>
            <h1 class="text-2xl font-bold">Tambah Ujian Publik</h1>
            <p class="text-sm text-textSecondary mt-1">Pilih ujian yang sudah ada, tentukan harga per mapel.</p>
        </div>
        <a href="{{ route('superadmin.landing-exams.index') }}" class="btn-secondary">Kembali</a>
    </div>

    @if($exams->isEmpty())
        <div class="card text-center py-12">
            <i class="fa-solid fa-circle-info text-3xl text-amber-400 mb-3 block"></i>
            <p class="text-textSecondary">Tidak ada ujian yang tersedia. Pastikan sudah membuat ujian aktif (status: terbit) di menu Manajemen Ujian.</p>
            <a href="{{ route('superadmin.exams.index') }}" class="btn-primary mt-4 inline-flex">Ke Manajemen Ujian</a>
        </div>
    @else
        <form method="POST" action="{{ route('superadmin.landing-exams.store') }}" class="card space-y-6">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div class="input-group md:col-span-2">
                    <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Pilih Ujian</label>
                    <div class="ssd-wrap mt-1">
                        <input type="hidden" name="exam_id" id="exam_id" value="{{ old('exam_id') }}" required>
                        <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                            <span class="ssd-label">Pilih ujian...</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                        </button>
                        <div class="ssd-panel">
                            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari ujian..."></div>
                            <div class="ssd-list">
                                @foreach($exams as $exam)
                                    @php
                                        $examMapels = $exam->paketSoal->mapelPakets->map(fn ($m) => ['id' => $m->id, 'label' => $m->nama_label, 'durasi' => $m->durasi_menit, 'jumlah' => $m->jumlah_soal]);
                                    @endphp
                                    <div class="ssd-option" data-value="{{ $exam->id }}" data-mapels='@json($examMapels)'>
                                        {{ $exam->judul }} ({{ $exam->paketSoal?->jenjang?->kode ?? '—' }})
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
                    <div class="ssd-wrap mt-1">
                        <input type="hidden" name="jenjang" value="{{ old('jenjang', 'SD') }}">
                        <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                            <span class="ssd-label">{{ old('jenjang', 'SD') }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                        </button>
                        <div class="ssd-panel">
                            <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
                            <div class="ssd-list">
                                @foreach(['SD', 'SMP', 'SMA'] as $j)
                                    <div class="ssd-option{{ old('jenjang') === $j ? ' ssd-selected' : '' }}" data-value="{{ $j }}">{{ $j }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Slug (opsional)</label>
                    <input type="text" name="slug" class="input" value="{{ old('slug') }}" placeholder="otomatis dari judul jika kosong">
                </div>
            </div>

            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Singkat</label>
                <input type="text" name="short_description" class="input" value="{{ old('short_description') }}" placeholder="Satu kalimat untuk card listing" maxlength="500">
            </div>

            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Lengkap</label>
                <textarea name="description" class="input min-h-32" placeholder="Deskripsi ujian yang tampil di halaman detail">{{ old('description') }}</textarea>
            </div>

            <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-textSecondary mb-4">Harga per Mapel</h3>
                <div id="mapel-pricing" class="space-y-3">
                    <p class="text-sm text-textSecondary">Pilih ujian di atas untuk menampilkan daftar mapel.</p>
                </div>
            </div>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950/70">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="h-4 w-4 text-primary">
                <span>Aktifkan (tampilkan di landing page)</span>
            </label>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="{{ route('superadmin.landing-exams.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const examInput = document.querySelector('input[name="exam_id"]');
            const pricingContainer = document.getElementById('mapel-pricing');

            const renderMapelPricing = (mapels) => {
                if (!mapels || !mapels.length) {
                    pricingContainer.innerHTML = '<p class="text-sm text-textSecondary">Ujian ini tidak memiliki mapel.</p>';
                    return;
                }

                pricingContainer.innerHTML = mapels.map((m, i) => `
                    <div class="grid gap-3 rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/70 md:grid-cols-[1fr_auto_auto]">
                        <div>
                            <div class="font-semibold text-sm">${m.label}</div>
                            <div class="text-xs text-textSecondary mt-0.5">${m.jumlah} soal — ${m.durasi} menit</div>
                            <input type="hidden" name="mapels[${i}][mapel_paket_id]" value="${m.id}">
                        </div>
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga (Rp)</label>
                            <input type="number" name="mapels[${i}][price]" class="input" min="0" value="0" required>
                        </div>
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga Coret</label>
                            <input type="number" name="mapels[${i}][original_price]" class="input" min="0" placeholder="opsional">
                        </div>
                    </div>
                `).join('');
            };

            const ssdTrigger = document.querySelector('[data-value]')?.closest('.ssd-wrap')?.querySelector('.ssd-trigger');
            const ssdOptions = document.querySelectorAll('.ssd-option[data-mapels]');

            ssdOptions.forEach(opt => {
                opt.addEventListener('click', () => {
                    const mapels = JSON.parse(opt.dataset.mapels || '[]');
                    renderMapelPricing(mapels);
                });
            });

            if (examInput && examInput.value) {
                const selectedOpt = Array.from(ssdOptions).find(o => o.dataset.value === examInput.value);
                if (selectedOpt) {
                    const mapels = JSON.parse(selectedOpt.dataset.mapels || '[]');
                    renderMapelPricing(mapels);
                }
            }
        });
        </script>
    @endif
</div>
@endsection
