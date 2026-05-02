@extends('layouts.guest')

@section('title', 'Latihan Materi — Ujion')

@section('content')
@php
    $material = $token->material;
    $optionLabels = range('A', 'Z');
@endphp

<div class="w-full max-w-5xl space-y-6">
    <div class="rounded-3xl border border-border bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Latihan Materi</div>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $material?->sub_unit ?? '-' }}</h1>
                <p class="mt-1 text-sm text-textSecondary">
                    {{ $material?->subelement ?? '-' }} &middot; {{ $material?->unit ?? '-' }}
                </p>
                <p class="mt-2 text-sm text-textSecondary">Peserta: <span class="font-semibold">{{ $session->nama }}</span></p>
            </div>
            <div class="text-right">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Token</div>
                <div class="mt-1">
                    <code class="rounded bg-indigo-50 px-3 py-2 text-lg font-black text-indigo-700">{{ $token->token }}</code>
                </div>
                <div class="mt-2 text-xs text-textSecondary">Status: <span class="font-semibold">{{ $session->status }}</span></div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-border bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Telaah Soal</h2>
                    <p class="mt-1 text-sm text-textSecondary">Jawaban langsung dinilai + tampil pembahasan. Boleh ganti jawaban (retry).</p>
                </div>
                <span class="badge-info">{{ $telaahQuestions->count() }}/2</span>
            </div>

            <div class="mt-4 space-y-4">
                @forelse($telaahQuestions as $row)
                    @php
                        $q = $row->globalQuestion;
                        $answer = $telaahAnswersByQuestionId[$q->id] ?? null;
                        $isCorrect = $answer?->is_correct;
                    @endphp

                    <div class="rounded-2xl border border-border p-4 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Telaah {{ $row->urutan }}</div>
                            @if($answer)
                                <span class="{{ $isCorrect ? 'badge-success' : 'badge-warning' }}">
                                    {{ $isCorrect ? 'Benar' : 'Belum tepat' }}
                                </span>
                            @else
                                <span class="badge-secondary">Belum dijawab</span>
                            @endif
                        </div>

                        @if($q?->reading_passage)
                            <div class="mt-3 rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300 whitespace-pre-line">
                                {{ $q->reading_passage }}
                            </div>
                        @endif

                        <div class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-200">
                            {{ $q?->question_text }}
                        </div>

                        @if(is_array($q?->options) && count($q->options))
                            <form method="POST" action="{{ route('siswa.practice.telaah.submit', $q) }}" class="mt-3 space-y-3">
                                @csrf
                                <div class="space-y-2">
                                    @foreach($q->options as $idx => $opt)
                                        @php($label = $optionLabels[$idx] ?? 'O' . ($idx + 1))
                                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                                            <input type="radio" name="jawaban" value="{{ $opt }}" class="mt-1" {{ ($answer?->jawaban ?? '') === $opt ? 'checked' : '' }} required>
                                            <div>
                                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $label }}</div>
                                                <div class="text-slate-700 dark:text-slate-300">{{ $opt }}</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn-primary">Cek Jawaban</button>
                            </form>
                        @else
                            <div class="mt-3 text-sm text-textSecondary">Pilihan jawaban belum tersedia.</div>
                        @endif

                        @if($answer && $q?->explanation)
                            <div class="mt-3 rounded-xl bg-emerald-50/70 p-3 text-sm text-slate-700 dark:bg-emerald-900/10 dark:text-slate-300 whitespace-pre-line">
                                <div class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">Pembahasan</div>
                                {{ $q->explanation }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-textSecondary">Telaah belum diset oleh admin untuk materi ini.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl border border-border bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Paket Latihan</h2>
                    <p class="mt-1 text-sm text-textSecondary">Tiap paket hanya bisa dikumpulkan sekali.</p>
                </div>
                <span class="badge-info">{{ $packages->count() }}/3</span>
            </div>

            <div class="mt-4 space-y-3">
                @foreach($packages as $pkg)
                    @php
                        $attempt = $attemptsByPackageId[$pkg->id] ?? null;
                        $done = $attempt && $attempt->status === 'selesai';
                    @endphp
                    <div class="flex flex-col gap-3 rounded-2xl border border-border p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-slate-100">Paket {{ $pkg->paket_no }}</div>
                            @if($done)
                                <div class="mt-1 text-sm text-textSecondary">Selesai &middot; Skor: <span class="font-semibold">{{ $attempt->skor }}</span></div>
                            @elseif($attempt)
                                <div class="mt-1 text-sm text-textSecondary">Sedang dikerjakan</div>
                            @else
                                <div class="mt-1 text-sm text-textSecondary">Belum dimulai</div>
                            @endif
                        </div>
                        <div>
                            @if($done)
                                <button type="button" class="btn-secondary" disabled>Sudah Selesai</button>
                            @else
                                <a class="btn-primary" href="{{ route('siswa.practice.paket.show', ['paketNo' => $pkg->paket_no]) }}">Kerjakan</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="text-center text-xs text-textSecondary">
        Jika Anda salah token, kembali ke <a class="font-semibold text-indigo-700 hover:underline" href="{{ route('siswa.practice.login') }}">halaman login latihan</a>.
    </div>
</div>
@endsection
