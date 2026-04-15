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
                    <div class="flex gap-2">
                        <!-- Search/Filter Placeholder -->
                        <input type="text" placeholder="Cari soal..." class="input w-full py-1 text-sm sm:w-48">
                    </div>
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
                                    <button class="btn-secondary p-2" title="Edit (Coming Soon)"><i class="fa-solid fa-pen-to-square"></i></button>
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
@endsection
