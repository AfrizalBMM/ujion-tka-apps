@extends('layouts.superadmin')

@section('title', 'Global Bank Soal')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Bank Soal Global</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola kumpulan soal yang dapat diakses oleh seluruh guru di platform Ujion.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- FORM ADD -->
        <div class="lg:col-span-4">
            <div class="card lg:sticky lg:top-6">
                <div class="font-bold text-lg mb-4">Input Soal Baru</div>
                <form class="space-y-4" method="POST" action="{{ route('superadmin.global-questions.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                            <select class="input mt-1" name="question_type" required>
                                <option value="multiple_choice">Pilihan Ganda</option>
                                <option value="short_answer">Jawaban Singkat</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Ref</label>
                            <select class="input mt-1" name="material_id">
                                <option value="">Tanpa Materi</option>
                                @foreach ($materials as $m)
                                    <option value="{{ $m->id }}">{{ $m->curriculum }} | {{ \Illuminate\Support\Str::limit($m->sub_unit, 20) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
                        <textarea class="input mt-1" name="question_text" rows="4" required placeholder="Apa rukun islam yang kedua?"></textarea>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban (Opsional, 1 Per Baris)</label>
                        <textarea class="input mt-1" name="options_raw" rows="4" placeholder="Syahadat\nShalat\nPuasa\nZakat"></textarea>
                        <p class="text-[10px] text-muted italic mt-1">Hanya untuk pilihan ganda.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
                            <input class="input mt-1" name="answer_key" placeholder="Shalat">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
                            <select class="input mt-1" name="is_active">
                                <option value="1">Aktif (Publik)</option>
                                <option value="0">Draft (Sembunyi)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
                        <textarea class="input mt-1" name="explanation" rows="2" placeholder="Shalat adalah tiang agama..."></textarea>
                    </div>

                    <button class="btn-primary w-full" type="submit">
                        <i class="fa-solid fa-cloud-upload mr-2"></i> Simpan ke Bank Soal
                    </button>
                </form>
            </div>
        </div>

        <!-- LIST DATA -->
        <div class="lg:col-span-8 space-y-4">
            <div class="card bg-gradient-to-r from-blue-600 to-indigo-700 text-white border-none shadow-glow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold">Batch Operations</h3>
                        <p class="text-xs text-blue-100">Gunakan CSV untuk upload dalam jumlah besar.</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <form class="flex min-w-[200px] flex-1 flex-col gap-2 sm:flex-row sm:items-center" method="POST" action="{{ route('superadmin.global-questions.import') }}" enctype="multipart/form-data">
                        @csrf
                        <input class="input bg-white/10 border-white/20 text-white placeholder-blue-200" type="file" name="file" accept=".csv" required>
                        <button class="btn-primary bg-white text-blue-700 hover:bg-blue-50 border-none" type="submit">Import</button>
                    </form>
                    <a class="btn-secondary bg-transparent border-white/30 text-white hover:bg-white/10" href="{{ route('superadmin.global-questions.template') }}">
                        <i class="fa-solid fa-file-csv mr-2"></i> Template
                    </a>
                </div>
            </div>

            <div class="card min-h-[400px]">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="font-bold text-lg">Daftar Soal Global ({{ count($globalQuestions) }})</div>
                    <div class="text-xs text-muted">Kelola soal aktif, edit metadata, dan hapus jika sudah tidak dipakai.</div>
                </div>
                
                <div class="space-y-4">
                    @if(count($globalQuestions) > 0)
                    @foreach ($globalQuestions as $q)
                        <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800 hover:border-blue-300 transition-all">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex-1">
                                    <div class="mb-2 flex flex-wrap items-center gap-3">
                                        <span class="badge-info text-[10px] uppercase font-bold tracking-wider">{{ str_replace('_', ' ', $q->question_type) }}</span>
                                        @if($q->material)
                                            <span class="text-[10px] text-muted flex items-center gap-1">
                                                <i class="fa-solid fa-book text-[8px]"></i> {{ $q->material->sub_unit }}
                                            </span>
                                        @endif
                                        @if($q->is_active)
                                            <span class="text-green-500 text-[10px] font-bold">● ACTIVE</span>
                                        @else
                                            <span class="text-slate-400 text-[10px] font-bold">○ DRAFT</span>
                                        @endif
                                    </div>
                                    <div class="text-slate-800 dark:text-slate-200 leading-relaxed font-medium">
                                        {{ $q->question_text }}
                                    </div>
                                    @if($q->options)
                                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            @foreach($q->options as $idx => $opt)
                                                <div class="text-xs p-2 bg-slate-50 dark:bg-slate-800 rounded border border-slate-100 dark:border-slate-700 {{ $q->answer_key == $opt ? 'ring-1 ring-blue-500 font-bold' : '' }}">
                                                    {{ $opt }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($q->answer_key)
                                        <div class="mt-3 text-xs">
                                            <span class="font-bold text-blue-600">Kunci:</span> {{ $q->answer_key }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-row gap-2 lg:flex-col">
                                    <form method="POST" action="{{ route('superadmin.global-questions.destroy', $q) }}">
                                        @csrf
                                        <button class="btn-danger p-2" type="submit" data-confirm="Hapus soal ini dari bank soal?" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                    <button
                                        class="btn-secondary p-2"
                                        type="button"
                                        title="Edit"
                                        data-edit-question='@json([
                                            "id" => $q->id,
                                            "material_id" => $q->material_id,
                                            "question_type" => $q->question_type,
                                            "question_text" => $q->question_text,
                                            "options_raw" => collect($q->options ?? [])->implode("\n"),
                                            "answer_key" => $q->answer_key,
                                            "explanation" => $q->explanation,
                                            "is_active" => $q->is_active ? "1" : "0",
                                        ])'
                                    ><i class="fa-solid fa-pen-to-square"></i></button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @else
                        <div class="text-center py-20 flex flex-col items-center">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                <i class="fa-solid fa-database text-3xl text-slate-200"></i>
                            </div>
                            <span class="text-muted dark:text-slate-400 italic">Belum ada soal global yang tersedia. Mulai dengan membuat soal pertama atau import CSV.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div id="edit-question-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-2xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
                <div class="mt-2 text-xl font-bold">Soal Global</div>
            </div>
            <button type="button" class="icon-button" data-close-question-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="edit-question-form" method="POST" class="mt-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
                    <select class="input mt-1" name="question_type" id="edit-question-type" required>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="short_answer">Jawaban Singkat</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Ref</label>
                    <select class="input mt-1" name="material_id" id="edit-material-id">
                        <option value="">Tanpa Materi</option>
                        @foreach ($materials as $m)
                            <option value="{{ $m->id }}">{{ $m->curriculum }} | {{ \Illuminate\Support\Str::limit($m->sub_unit, 20) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
                <textarea class="input mt-1" name="question_text" id="edit-question-text" rows="4" required></textarea>
            </div>

            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban (1 per baris atau pisahkan koma)</label>
                <textarea class="input mt-1" name="options_raw" id="edit-options-raw" rows="4"></textarea>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
                    <input class="input mt-1" name="answer_key" id="edit-answer-key">
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
                    <select class="input mt-1" name="is_active" id="edit-is-active">
                        <option value="1">Aktif (Publik)</option>
                        <option value="0">Draft (Sembunyi)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
                <textarea class="input mt-1" name="explanation" id="edit-explanation" rows="2"></textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Simpan Perubahan</button>
                <button class="btn-secondary" type="button" data-close-question-modal>Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('edit-question-modal');
    const form = document.getElementById('edit-question-form');
    const closeButtons = document.querySelectorAll('[data-close-question-modal]');
    const fields = {
        questionType: document.getElementById('edit-question-type'),
        materialId: document.getElementById('edit-material-id'),
        questionText: document.getElementById('edit-question-text'),
        optionsRaw: document.getElementById('edit-options-raw'),
        answerKey: document.getElementById('edit-answer-key'),
        isActive: document.getElementById('edit-is-active'),
        explanation: document.getElementById('edit-explanation'),
    };

    const close = () => modal.classList.add('hidden');
    closeButtons.forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    document.querySelectorAll('[data-edit-question]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editQuestion);
            form.action = "{{ route('superadmin.global-questions.update', ['globalQuestion' => '__ID__']) }}".replace('__ID__', data.id);
            fields.questionType.value = data.question_type ?? 'multiple_choice';
            fields.materialId.value = data.material_id ?? '';
            fields.questionText.value = data.question_text ?? '';
            fields.optionsRaw.value = data.options_raw ?? '';
            fields.answerKey.value = data.answer_key ?? '';
            fields.isActive.value = data.is_active ?? '1';
            fields.explanation.value = data.explanation ?? '';
            modal.classList.remove('hidden');
            fields.questionText.focus();
        });
    });
});
</script>
@endsection
