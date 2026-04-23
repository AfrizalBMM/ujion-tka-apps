@php
    $question = $soal ?? null;
    $isSurvey = $mapel->isSurvey();
    $currentType = old('tipe_soal', $question->tipe_soal ?? $tipeSoal ?? 'pilihan_ganda');
    $pilihanValues = old('pilihan', $question ? $question->pilihanJawabans->map(fn ($item) => ['kode' => $item->kode, 'teks' => $item->teks])->values()->all() : [
        ['kode' => 'A', 'teks' => ''],
        ['kode' => 'B', 'teks' => ''],
        ['kode' => 'C', 'teks' => ''],
        ['kode' => 'D', 'teks' => ''],
    ]);
    $pilihanSurveyValues = old('pilihan', $question ? $question->pilihanJawabans->map(fn ($item) => [
        'kode' => $item->kode,
        'teks' => $item->teks,
        'nilai_survey' => $item->nilai_survey,
        'profil_label' => $item->profil_label,
    ])->values()->all() : [
        ['kode' => 'A', 'teks' => '', 'nilai_survey' => 4, 'profil_label' => 'Sangat Sesuai'],
        ['kode' => 'B', 'teks' => '', 'nilai_survey' => 3, 'profil_label' => 'Sesuai'],
        ['kode' => 'C', 'teks' => '', 'nilai_survey' => 2, 'profil_label' => 'Tidak Sesuai'],
        ['kode' => 'D', 'teks' => '', 'nilai_survey' => 1, 'profil_label' => 'Sangat Tidak Sesuai'],
    ]);
    $jawabanBenar = old('jawaban_benar', $question?->pilihanJawabans->firstWhere('is_benar', true)?->kode);
    $pasanganValues = old('pasangan', $question ? $question->pasanganMenjodohkans->map(fn ($item) => ['teks_kiri' => $item->teks_kiri, 'teks_kanan' => $item->teks_kanan])->values()->all() : [
        ['teks_kiri' => '', 'teks_kanan' => ''],
        ['teks_kiri' => '', 'teks_kanan' => ''],
        ['teks_kiri' => '', 'teks_kanan' => ''],
    ]);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" data-soal-form>
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Nomor Soal</label>
            <input type="number" name="nomor_soal" min="1" max="{{ $mapel->jumlah_soal }}" class="input" value="{{ old('nomor_soal', $question->nomor_soal ?? $nextNomor ?? 1) }}" required>
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tipe Soal</label>
            <select name="tipe_soal" class="input" data-soal-type required @disabled($isSurvey)>
                <option value="pilihan_ganda" @selected($currentType === 'pilihan_ganda')>Pilihan Ganda</option>
                @unless($isSurvey)
                    <option value="menjodohkan" @selected($currentType === 'menjodohkan')>Menjodohkan</option>
                @endunless
            </select>
            @if($isSurvey)
                <input type="hidden" name="tipe_soal" value="pilihan_ganda">
            @endif
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Teks Bacaan</label>
            <select name="teks_bacaan_id" class="input">
                <option value="">Tanpa teks bacaan</option>
                @foreach($teksBacaans as $bacaan)
                    <option value="{{ $bacaan->id }}" @selected(old('teks_bacaan_id', $question->teks_bacaan_id ?? null) == $bacaan->id)>{{ $bacaan->judul ?: 'Teks bacaan #' . $bacaan->id }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Bobot</label>
            <input type="number" name="bobot" min="1" class="input" value="{{ old('bobot', $question->bobot ?? 1) }}" @disabled($isSurvey)>
            @if($isSurvey)
                <input type="hidden" name="bobot" value="{{ old('bobot', $question->bobot ?? 1) }}">
            @endif
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="input-group xl:col-span-2">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">{{ $isSurvey ? 'Indikator / Tujuan Butir' : 'Indikator' }}</label>
            <textarea name="indikator" class="input min-h-28" required>{{ old('indikator', $question->indikator ?? '') }}</textarea>
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">{{ $isSurvey ? 'Dimensi Survey' : 'Dimensi' }}</label>
            <input type="text" name="dimensi" class="input" value="{{ old('dimensi', $question->dimensi ?? '') }}" {{ $isSurvey ? 'required' : '' }}>
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Subdimensi</label>
            <input type="text" name="subdimensi" class="input" value="{{ old('subdimensi', $question->subdimensi ?? '') }}">
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kategori Profil</label>
            <input type="text" name="kategori_profil" class="input" value="{{ old('kategori_profil', $question->kategori_profil ?? '') }}" placeholder="{{ $isSurvey ? 'Contoh: Disiplin diri' : 'Opsional' }}">
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Arah Skor</label>
            <select name="arah_skor" class="input">
                <option value="positif" @selected(old('arah_skor', $question->arah_skor ?? 'positif') === 'positif')>Positif</option>
                <option value="negatif" @selected(old('arah_skor', $question->arah_skor ?? 'positif') === 'negatif')>Negatif</option>
            </select>
        </div>
    </div>

    <div class="input-group">
        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">{{ $isSurvey ? 'Teks Soal / Pernyataan' : 'Pertanyaan' }}</label>
        <textarea name="pertanyaan" class="input min-h-36" required>{{ old('pertanyaan', $question->pertanyaan ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-[1fr_auto]">
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Gambar Soal</label>
            <input type="file" name="gambar" accept="image/*" class="input" data-image-input="utama">
        </div>
        <div class="rounded-[24px] border border-dashed border-slate-300/80 bg-slate-50/70 p-3 text-center dark:border-slate-700 dark:bg-slate-900/50">
            <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Preview</div>
            <img src="{{ $question?->gambar_url }}" alt="Preview gambar soal" class="mx-auto mt-3 {{ $question?->gambar_url ? '' : 'hidden' }} max-h-32 rounded-xl" data-image-preview="utama">
        </div>
    </div>

    <section class="{{ $currentType === 'pilihan_ganda' ? '' : 'hidden' }}" data-type-panel="pilihan_ganda">
        <div class="section-heading mb-4">
            <div>
                <h3 class="section-title">Pilihan Jawaban</h3>
                <p class="section-description">{{ $isSurvey ? 'Isi empat opsi respons dan atur skor tiap pilihan untuk analisis profil.' : 'Isi empat opsi dan tentukan satu jawaban benar.' }}</p>
            </div>
        </div>
        <div class="space-y-4">
            @foreach(($isSurvey ? $pilihanSurveyValues : $pilihanValues) as $index => $pilihan)
                @php $kode = $pilihan['kode'] ?? chr(65 + $index); @endphp
                <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="grid gap-4 lg:grid-cols-[auto_1fr_220px] {{ $isSurvey ? 'xl:grid-cols-[auto_1fr_180px_180px]' : '' }}">
                        <div class="input-group lg:max-w-24">
                            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kode</label>
                            <input type="text" readonly name="pilihan[{{ $index }}][kode]" class="input bg-slate-100 dark:bg-slate-800" value="{{ $kode }}">
                        </div>
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Teks Pilihan {{ $kode }}</label>
                            <textarea name="pilihan[{{ $index }}][teks]" class="input min-h-24">{{ $pilihan['teks'] ?? '' }}</textarea>
                        </div>
                        @if($isSurvey)
                            <div class="input-group">
                                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Nilai Survey</label>
                                <input type="number" name="pilihan[{{ $index }}][nilai_survey]" min="1" max="4" class="input" value="{{ $pilihan['nilai_survey'] ?? '' }}" required>
                            </div>
                            <div class="input-group">
                                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Label Profil</label>
                                <input type="text" name="pilihan[{{ $index }}][profil_label]" class="input" value="{{ $pilihan['profil_label'] ?? '' }}" placeholder="Contoh: Sangat sesuai">
                            </div>
                        @endif
                        <div class="space-y-3">
                            @unless($isSurvey)
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950/70">
                                    <input type="radio" name="jawaban_benar" value="{{ $kode }}" class="h-4 w-4 text-primary" @checked($jawabanBenar === $kode)>
                                    <span>Jawaban benar</span>
                                </label>
                            @endunless
                            <input type="file" name="pilihan_gambar[{{ $kode }}]" accept="image/*" class="input" data-image-input="pilihan-{{ $kode }}">
                            <img src="{{ optional($question?->pilihanJawabans->firstWhere('kode', $kode))->gambar_url }}" alt="Preview pilihan {{ $kode }}" class="max-h-24 rounded-xl {{ optional($question?->pilihanJawabans->firstWhere('kode', $kode))->gambar_url ? '' : 'hidden' }}" data-image-preview="pilihan-{{ $kode }}">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="{{ $currentType === 'menjodohkan' && ! $isSurvey ? '' : 'hidden' }}" data-type-panel="menjodohkan">
        <div class="section-heading mb-4">
            <div>
                <h3 class="section-title">Pasangan Menjodohkan</h3>
                <p class="section-description">Minimal tiga pasangan kiri dan kanan.</p>
            </div>
            <button type="button" class="btn-secondary" data-add-pair>
                <i class="fa-solid fa-plus"></i>
                Tambah Pasangan
            </button>
        </div>
        <div class="space-y-3" data-pair-list>
            @foreach($pasanganValues as $index => $pasangan)
                <div class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-4 md:grid-cols-2 dark:border-slate-800 dark:bg-slate-900/60" data-pair-row>
                    <div class="input-group">
                        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kolom Kiri</label>
                        <textarea name="pasangan[{{ $index }}][teks_kiri]" class="input min-h-24">{{ $pasangan['teks_kiri'] ?? '' }}</textarea>
                    </div>
                    <div class="input-group">
                        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kolom Kanan</label>
                        <textarea name="pasangan[{{ $index }}][teks_kanan]" class="input min-h-24">{{ $pasangan['teks_kanan'] ?? '' }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-pair>Hapus Baris</button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        <button class="btn-primary" type="submit">{{ $submitLabel }}</button>
        <a href="{{ $cancelUrl }}" class="btn-secondary">Kembali</a>
    </div>
</form>

<template id="pair-row-template">
    <div class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-4 md:grid-cols-2 dark:border-slate-800 dark:bg-slate-900/60" data-pair-row>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kolom Kiri</label>
            <textarea class="input min-h-24" data-name="teks_kiri"></textarea>
        </div>
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kolom Kanan</label>
            <textarea class="input min-h-24" data-name="teks_kanan"></textarea>
        </div>
        <div class="md:col-span-2">
            <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-pair>Hapus Baris</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-soal-form]');
    if (!form) return;
    const typeInput = form.querySelector('[data-soal-type]');
    const panels = form.querySelectorAll('[data-type-panel]');
    const pairList = form.querySelector('[data-pair-list]');
    const pairTemplate = document.getElementById('pair-row-template');

    const syncTypePanels = () => panels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.typePanel !== typeInput.value);
    });

    const reindexPairs = () => {
        pairList?.querySelectorAll('[data-pair-row]').forEach((row, index) => {
            row.querySelectorAll('[data-name]').forEach((input) => {
                input.name = `pasangan[${index}][${input.dataset.name}]`;
            });
            row.querySelectorAll('textarea[name*="[teks_"]').forEach((input) => {
                const field = input.name.includes('[teks_kiri]') ? 'teks_kiri' : 'teks_kanan';
                input.name = `pasangan[${index}][${field}]`;
            });
        });
    };

    form.querySelector('[data-add-pair]')?.addEventListener('click', () => {
        pairList?.appendChild(pairTemplate.content.cloneNode(true));
        reindexPairs();
    });

    pairList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-pair]');
        if (!button) return;
        const rows = pairList.querySelectorAll('[data-pair-row]');
        if (rows.length <= 3) {
            window.alert('Minimal tiga pasangan harus tersedia.');
            return;
        }
        button.closest('[data-pair-row]')?.remove();
        reindexPairs();
    });

    form.querySelectorAll('[data-image-input]').forEach((input) => {
        input.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            const preview = form.querySelector(`[data-image-preview="${input.dataset.imageInput}"]`);
            if (!file || !preview) return;
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
        });
    });

    typeInput?.addEventListener('change', syncTypePanels);
    syncTypePanels();
    reindexPairs();
});
</script>
