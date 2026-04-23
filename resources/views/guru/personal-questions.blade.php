@extends('layouts.guru')
@section('title', 'Bank Soal Pribadi')
@section('content')
@php
$optionLabels = range('A', 'Z');
@endphp
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Bank Soal</span>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="page-title">Bank Soal Pribadi</h1>
                <p class="page-description">Kelola soal-soal pribadi Anda untuk digunakan dalam ujian atau latihan. Tambahkan,
                    filter, dan edit soal dengan mudah.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="hero-chip">
                    <i class="fa-solid fa-database"></i>
                    Soal Pribadi
                </div>
                <div class="hero-chip">
                    <i class="fa-solid fa-layer-group"></i>
                    Kategori & Tipe Soal
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('guru.materials') }}"
                class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                <i class="fa-solid fa-book"></i>
                Materi
            </a>
        </div>
    </section>
    <form method="GET" class="card p-4 space-y-4 sm:space-y-0 sm:flex sm:items-end sm:gap-4 mb-4">
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Pertanyaan</label>
            <input type="text" name="q" value="{{ request('q') }}" class="input mt-1 w-full" placeholder="Cari pertanyaan...">
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kategori</label>
            <select name="kategori" class="input mt-1 w-full">
                <option value="">Semua Kategori</option>
                @foreach($questions->pluck('kategori')->unique()->filter()->values() as $kategori)
                <option value="{{ $kategori }}" @selected(request('kategori')==$kategori)> {{ $kategori }} </option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Tipe Soal</label>
            <select name="tipe" class="input mt-1 w-full">
                <option value="">Semua Tipe</option>
                <option value="PG" @selected(request('tipe')=='PG' )>Pilihan Ganda</option>
                <option value="Checklist" @selected(request('tipe')=='Checklist' )>Checklist</option>
                <option value="Singkat" @selected(request('tipe')=='Singkat' )>Jawaban Singkat</option>
            </select>
        </div>
        <button class="btn-secondary h-[42px] flex items-center justify-center" type="submit">Filter</button>
        <a href="{{ route('guru.personal-questions') }}"
            class="btn-secondary h-[42px] flex items-center justify-center">Reset</a>
        <a href="{{ route('guru.personal-questions.builder') }}"
            class="btn-primary h-[42px] flex items-center justify-center">Builder Soal Fullscreen</a>
        <button class="btn-primary h-[42px] flex items-center justify-center" type="button"
            onclick="document.getElementById('modal-tambah-soal').classList.remove('hidden')">
            <i class="fa-solid fa-plus mr-2"></i>Tambah Soal
        </button>
    </form>

    <!-- Modal Tambah Soal -->
    <div id="modal-tambah-soal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="font-bold text-lg">Tambah Soal Pribadi</div>
                    <button class="text-gray-500 hover:text-gray-700"
                        onclick="document.getElementById('modal-tambah-soal').classList.add('hidden')">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('guru.personal-questions.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label class="text-xs font-bold">Jenjang</label>
                            <input class="input w-full bg-slate-100" value="{{ $user->jenjang }}" readonly>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Kategori</label>
                            <input name="kategori" class="input w-full" required>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Tipe Soal</label>
                            <select name="tipe" class="input w-full" id="guru-personal-question-type" required>
                                <option value="PG">Pilihan Ganda</option>
                                <option value="Checklist">Checklist</option>
                                <option value="Singkat">Jawaban Singkat</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Pertanyaan</label>
                            <textarea name="pertanyaan" class="input w-full" required></textarea>
                        </div>
                        <div class="md:col-span-2" data-objective-options>
                            <div class="flex items-center justify-between gap-3">
                                <label class="text-xs font-bold">Opsi Jawaban</label>
                                <button type="button" class="btn-secondary px-3 py-2 text-xs" data-option-add="guru-personal">
                                    <i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
                                </button>
                            </div>
                            <div class="mt-2 grid grid-cols-1 gap-2" data-option-list="guru-personal">
                                @foreach (range(0, 3) as $index)
                                <div
                                    class="flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ $optionLabels[$index] }}</span>
                                    <input name="options[]" class="input border-0 bg-transparent px-0"
                                        placeholder="Tulis jawaban {{ $optionLabels[$index] }}">
                                </div>
                                @endforeach
                            </div>
                            <p class="mt-1 text-[10px] text-muted italic">Gunakan untuk tipe `PG` atau `Checklist`. Kunci jawaban bisa
                                diisi huruf seperti `A` atau isi jawabannya langsung.</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Jawaban Benar</label>
                            <input name="jawaban_benar" class="input w-full" id="guru-personal-answer-key"
                                placeholder="A atau isi jawaban benar">
                        </div>
                        <div>
                            <label class="text-xs font-bold">Pembahasan</label>
                            <textarea name="pembahasan" class="input w-full"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Gambar (opsional)</label>
                            <input type="file" name="image" accept="image/*" class="input w-full">
                        </div>
                        <div>
                            <label class="text-xs font-bold">Status</label>
                            <select name="status" class="input w-full" required>
                                <option value="draft">Draft</option>
                                <option value="terbit">Terbit</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Tambah Soal</button>
                </form>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <a href="{{ route('guru.personal-questions.builder') }}" class="btn-primary mb-4 w-full sm:w-auto">Builder Soal
            Fullscreen</a>
        <div class="table-container">
            <table class="table-ujion w-full min-w-[620px]">
                <thead>
                    <tr>
                        <th>Jenjang</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($questions as $question)
                    <tr>
                        <td>{{ $question->jenjang }}</td>
                        <td>{{ $question->kategori }}</td>
                        <td>{{ $question->tipe }}</td>
                        <td>{{ $question->status }}</td>
                        <td>
                            <form method="POST" action="{{ route('guru.personal-questions.destroy', $question) }}">@csrf<button
                                    class="btn-danger">Hapus</button></form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const optionLabels = @json($optionLabels);
        const optionList = document.querySelector('[data-option-list="guru-personal"]');
        const addButton = document.querySelector('[data-option-add="guru-personal"]');
        const questionType = document.getElementById('guru-personal-question-type');
        const objectiveOptions = document.querySelector('[data-objective-options]');
        const answerKey = document.getElementById('guru-personal-answer-key');

        if (!optionList || !addButton || !questionType || !objectiveOptions || !answerKey) {
            return;
        }

        const renderOptionFields = (values) => {
            optionList.innerHTML = '';

            values.forEach((value, index) => {
                const label = optionLabels[index] ?? `O${index + 1}`;
                const row = document.createElement('div');
                row.className =
                    'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
                row.innerHTML = `
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
                <input name="options[]" class="input border-0 bg-transparent px-0" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
            `;
                optionList.appendChild(row);
            });
        };

        addButton.addEventListener('click', () => {
            const values = Array.from(optionList.querySelectorAll('input[name="options[]"]')).map((input) => input
                .value);
            values.push('');
            renderOptionFields(values);
        });

        const syncQuestionTypeState = () => {
            const isObjective = ['PG', 'Checklist'].includes(questionType.value);

            objectiveOptions.classList.toggle('hidden', !isObjective);
            addButton.disabled = !isObjective;

            optionList.querySelectorAll('input[name="options[]"]').forEach((input) => {
                input.disabled = !isObjective;
            });

            answerKey.placeholder = isObjective ?
                'A atau isi jawaban benar' :
                'Tulis jawaban singkat yang benar';
        };

        questionType.addEventListener('change', syncQuestionTypeState);
        syncQuestionTypeState();
    });
</script>
@endsection