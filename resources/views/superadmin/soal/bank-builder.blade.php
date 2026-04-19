@extends('layouts.superadmin')

@section('title', 'Bank Builder — ' . $mapel->nama_label)

@section('content')
@php
    $optionLabels = range('A', 'Z');
@endphp

<div class="space-y-6">

    {{-- Hero --}}
    <section class="page-hero">
        <div class="flex flex-wrap items-center gap-2 text-sm text-white/70">
            <a href="{{ route('superadmin.paket-soal.index') }}" class="hover:text-white">Paket Soal</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <a href="{{ route('superadmin.paket-soal.show', $paket) }}" class="hover:text-white">{{ $paket->nama }}</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <span class="text-white">{{ $mapel->nama_label }}</span>
        </div>
        <span class="page-kicker mt-2">Import dari Bank Soal</span>
        <h1 class="page-title">Pilih soal untuk <span class="text-white/80">{{ $mapel->nama_label }}</span></h1>
        <p class="page-description">
            Paket: <strong class="text-white">{{ $paket->nama }}</strong> &middot;
            {{ $mapel->soals()->count() }}/{{ $mapel->jumlah_soal }} soal &middot;
            Jenjang {{ $paket->jenjang?->kode }}
        </p>
    </section>

    {{-- Filter bar --}}
    <section class="card">
        <form method="GET" action="{{ route('superadmin.soal.bank-builder', [$paket, $mapel]) }}"
              class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

            <div class="sm:col-span-2 lg:col-span-3">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Cari Soal / Bacaan</label>
                <input class="input mt-1" type="text" name="search"
                       value="{{ $filters['search'] }}"
                       placeholder="Ketik kata kunci pertanyaan, teks bacaan, atau materi...">
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tipe Soal</label>
                <select class="input mt-1" name="question_type">
                    <option value="">Semua Tipe</option>
                    <option value="multiple_choice" @selected($filters['question_type'] === 'multiple_choice')>Pilihan Ganda</option>
                    <option value="matching"        @selected($filters['question_type'] === 'matching')>Menjodohkan</option>
                    <option value="short_answer"    @selected($filters['question_type'] === 'short_answer')>Jawaban Singkat</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kurikulum</label>
                <select class="input mt-1" name="material_curriculum">
                    <option value="">Semua Kurikulum</option>
                    @foreach($curriculums as $c)
                        <option value="{{ $c }}" @selected($filters['material_curriculum'] === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Sub Unit Materi</label>
                <select class="input mt-1" name="material_sub_unit">
                    <option value="">Semua Sub Unit</option>
                    @foreach($subUnits as $su)
                        <option value="{{ $su }}" @selected($filters['material_sub_unit'] === $su)>{{ $su }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-3 sm:col-span-2 lg:col-span-3">
                <button class="btn-primary" type="submit">
                    <i class="fa-solid fa-filter mr-2"></i> Terapkan Filter
                </button>
                <a class="btn-secondary" href="{{ route('superadmin.soal.bank-builder', [$paket, $mapel]) }}">
                    Reset
                </a>
                <span class="ml-auto text-sm text-textSecondary">
                    <span id="selected-count" class="font-bold text-blue-600">0</span> soal dipilih dari {{ count($bankSoals) }} hasil
                </span>
            </div>
        </form>
    </section>

    {{-- Form import --}}
    <form id="import-bank-form"
          method="POST"
          action="{{ route('superadmin.soal.import-from-bank', [$paket, $mapel]) }}">
        @csrf

        {{-- Daftar soal --}}
        <section class="card min-h-[400px]">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-bold">Bank Soal Global</h2>
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium">
                    <input type="checkbox" id="select-all-checkbox" class="h-4 w-4 rounded">
                    <span>Pilih Semua</span>
                </label>
            </div>

            <div class="space-y-4">
                @forelse($bankSoals as $gq)
                    @php
                        $isPG       = $gq->question_type === 'multiple_choice';
                        $isMatch    = $gq->question_type === 'matching';
                        $isSingkat  = $gq->question_type === 'short_answer';
                        $typeBadge  = match($gq->question_type) {
                            'multiple_choice' => ['label' => 'Pilihan Ganda', 'class' => 'badge-info'],
                            'matching'        => ['label' => 'Menjodohkan',   'class' => 'badge-warning'],
                            default           => ['label' => 'Jawaban Singkat','class' => 'badge-success'],
                        };
                        $cardId = 'gq-' . $gq->id;
                    @endphp

                    <div class="soal-card group rounded-[20px] border border-border bg-white transition-all
                                hover:border-blue-300 hover:shadow-md
                                dark:border-slate-800 dark:bg-slate-900"
                         data-card-id="{{ $gq->id }}">

                        {{-- Header card --}}
                        <label class="flex cursor-pointer items-start gap-4 p-4 pb-3" for="{{ $cardId }}">
                            <input type="checkbox"
                                   id="{{ $cardId }}"
                                   name="global_question_ids[]"
                                   value="{{ $gq->id }}"
                                   class="soal-checkbox mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                            <div class="flex-1 min-w-0">
                                {{-- Badges --}}
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="{{ $typeBadge['class'] }} text-[10px] font-bold uppercase tracking-wider">
                                        {{ $typeBadge['label'] }}
                                    </span>
                                    @if($gq->material_curriculum)
                                        <span class="text-[10px] text-textSecondary">
                                            <i class="fa-solid fa-graduation-cap text-[8px]"></i>
                                            {{ $gq->material_curriculum }}
                                        </span>
                                    @endif
                                    @if($gq->material_sub_unit)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $gq->material_sub_unit }}
                                        </span>
                                    @endif
                                    @if($gq->answer_key && $isPG)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            Kunci: {{ \Illuminate\Support\Str::limit($gq->answer_key, 30) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Teks soal --}}
                                <p class="font-medium leading-relaxed text-slate-800 dark:text-slate-200">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($gq->question_text), 180) }}
                                </p>
                            </div>
                        </label>

                        {{-- Teks Bacaan (accordion) --}}
                        @if($gq->reading_passage)
                            <div class="border-t border-border px-4 pb-1 dark:border-slate-800">
                                <button type="button"
                                        class="toggle-bacaan flex w-full items-center justify-between py-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
                                        data-target="bacaan-{{ $gq->id }}">
                                    <span><i class="fa-solid fa-book-open mr-2"></i>Teks Bacaan</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-chevron></i>
                                </button>
                                <div id="bacaan-{{ $gq->id }}" class="hidden pb-3">
                                    <div class="rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $gq->reading_passage }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Pilihan Ganda --}}
                        @if($isPG && !empty($gq->options))
                            <div class="border-t border-border px-4 py-3 dark:border-slate-800">
                                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                    @foreach($gq->options as $idx => $opt)
                                        @php
                                            $label   = $optionLabels[$idx] ?? 'O' . ($idx + 1);
                                            $isBenar = $gq->answer_key == $opt;
                                        @endphp
                                        <div class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs
                                            {{ $isBenar
                                                ? 'bg-emerald-50 font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20'
                                                : 'text-slate-600 dark:text-slate-400' }}">
                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                                                {{ $isBenar ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}
                                                text-[10px] font-bold">{{ $label }}</span>
                                            {{ $opt }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Menjodohkan --}}
                        @if($isMatch && !empty($gq->options))
                            <div class="border-t border-border px-4 py-3 dark:border-slate-800">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-textSecondary">
                                            <th class="pb-1 pr-4 font-semibold">Item Kiri</th>
                                            <th class="pb-1 font-semibold">Item Kanan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border dark:divide-slate-800">
                                        @foreach($gq->options as $pair)
                                            @if(is_array($pair) && isset($pair['left'], $pair['right']))
                                                <tr>
                                                    <td class="py-1 pr-4 text-slate-700 dark:text-slate-300">{{ $pair['left'] }}</td>
                                                    <td class="py-1 font-medium text-blue-700 dark:text-blue-300">{{ $pair['right'] }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Info materi lengkap --}}
                        @if($gq->material_subelement || $gq->material_unit)
                            <div class="border-t border-border px-4 py-2 dark:border-slate-800">
                                <p class="text-[11px] text-textSecondary">
                                    @if($gq->material_subelement)
                                        <span class="font-semibold">Sub-elemen:</span> {{ $gq->material_subelement }}
                                    @endif
                                    @if($gq->material_unit)
                                        &nbsp;&middot;&nbsp;
                                        <span class="font-semibold">Unit:</span> {{ $gq->material_unit }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center py-24 text-center">
                        <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800">
                            <i class="fa-solid fa-database text-3xl text-slate-200"></i>
                        </div>
                        <p class="italic text-muted">Tidak ada soal aktif di bank soal global yang cocok dengan filter.</p>
                        <a href="{{ route('superadmin.global-questions.index') }}" class="mt-4 text-sm font-medium text-blue-600 hover:underline">
                            Tambah soal ke bank soal →
                        </a>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Sticky Footer submit --}}
        <div id="import-footer"
             class="sticky bottom-0 z-40 mt-4 hidden rounded-[20px] border border-blue-200 bg-blue-600 px-6 py-4 shadow-2xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="text-white">
                    <span class="text-lg font-bold" id="footer-count">0</span>
                    <span class="ml-1 text-blue-100">soal dipilih</span>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="deselect-all-btn"
                            class="rounded-xl border border-white/30 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                        Batalkan Pilihan
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-blue-700 shadow-md transition hover:bg-blue-50">
                        <i class="fa-solid fa-file-import mr-2"></i>
                        Masukkan ke Paket
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes   = document.querySelectorAll('.soal-checkbox');
    const selectAll    = document.getElementById('select-all-checkbox');
    const footer       = document.getElementById('import-footer');
    const selectedCountEl  = document.getElementById('selected-count');
    const footerCountEl    = document.getElementById('footer-count');
    const deselectBtn  = document.getElementById('deselect-all-btn');

    const updateUI = () => {
        const checked = document.querySelectorAll('.soal-checkbox:checked').length;
        selectedCountEl.textContent = checked;
        footerCountEl.textContent  = checked;

        footer.classList.toggle('hidden', checked === 0);

        // Highlight card yang dipilih
        checkboxes.forEach((cb) => {
            const card = cb.closest('.soal-card');
            if (card) {
                card.classList.toggle('ring-2',         cb.checked);
                card.classList.toggle('ring-blue-500',  cb.checked);
                card.classList.toggle('border-blue-400', cb.checked);
            }
        });

        // Sesuaikan state select-all
        const total = checkboxes.length;
        selectAll.indeterminate = checked > 0 && checked < total;
        selectAll.checked       = checked === total && total > 0;
    };

    checkboxes.forEach((cb) => cb.addEventListener('change', updateUI));

    selectAll.addEventListener('change', () => {
        checkboxes.forEach((cb) => { cb.checked = selectAll.checked; });
        updateUI();
    });

    deselectBtn.addEventListener('click', () => {
        checkboxes.forEach((cb) => { cb.checked = false; });
        selectAll.checked = false;
        updateUI();
    });

    // Accordion teks bacaan
    document.querySelectorAll('.toggle-bacaan').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const content  = document.getElementById(targetId);
            const chevron  = btn.querySelector('[data-chevron]');
            if (content) {
                content.classList.toggle('hidden');
                chevron?.classList.toggle('rotate-180');
            }
        });
    });

    // Konfirmasi sebelum submit
    document.getElementById('import-bank-form').addEventListener('submit', (e) => {
        const checked = document.querySelectorAll('.soal-checkbox:checked').length;
        if (checked === 0) {
            e.preventDefault();
            alert('Pilih minimal satu soal terlebih dahulu.');
        }
    });
});
</script>
@endsection
