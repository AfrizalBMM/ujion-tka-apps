@extends('layouts.guru')
@section('title', 'Bank Soal Pribadi')
@section('content')
@php
    $optionLabels = range('A', 'Z');
@endphp
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Bank Soal Pribadi</h1>
    <div class="card p-4 mb-4">
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
                            <div class="flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ $optionLabels[$index] }}</span>
                                <input name="options[]" class="input border-0 bg-transparent px-0" placeholder="Tulis jawaban {{ $optionLabels[$index] }}">
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-1 text-[10px] text-muted italic">Gunakan untuk tipe `PG` atau `Checklist`. Kunci jawaban bisa diisi huruf seperti `A` atau isi jawabannya langsung.</p>
                </div>
                <div>
                    <label class="text-xs font-bold">Jawaban Benar</label>
                    <input name="jawaban_benar" class="input w-full" id="guru-personal-answer-key" placeholder="A atau isi jawaban benar">
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
    <div class="card p-4">
        <a href="{{ route('guru.personal-questions.builder') }}" class="btn-primary mb-4 w-full sm:w-auto">Builder Soal Fullscreen</a>
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
                        <form method="POST" action="{{ route('guru.personal-questions.destroy', $question) }}">@csrf<button class="btn-danger">Hapus</button></form>
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
            row.className = 'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
            row.innerHTML = `
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
                <input name="options[]" class="input border-0 bg-transparent px-0" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
            `;
            optionList.appendChild(row);
        });
    };

    addButton.addEventListener('click', () => {
        const values = Array.from(optionList.querySelectorAll('input[name="options[]"]')).map((input) => input.value);
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

        answerKey.placeholder = isObjective
            ? 'A atau isi jawaban benar'
            : 'Tulis jawaban singkat yang benar';
    };

    questionType.addEventListener('change', syncQuestionTypeState);
    syncQuestionTypeState();
});
</script>
@endsection
