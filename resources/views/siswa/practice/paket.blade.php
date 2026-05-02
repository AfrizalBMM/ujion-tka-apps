@extends('layouts.guest')

@section('title', 'Paket Latihan — Ujion')

@section('content')
@php
    $material = $token->material;
    $optionLabels = range('A', 'Z');
@endphp

<div class="w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('siswa.practice.dashboard') }}" class="btn-secondary inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
        <div class="text-xs text-textSecondary">
            Token: <span class="font-semibold">{{ $token->token }}</span> &middot; Peserta: <span class="font-semibold">{{ $session->nama }}</span>
        </div>
    </div>

    <div class="rounded-3xl border border-border bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Paket Latihan</div>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">Paket {{ $package->paket_no }}</h1>
        <p class="mt-1 text-sm text-textSecondary">Materi: {{ $material?->sub_unit ?? '-' }}</p>
        <div class="mt-2 text-xs text-textSecondary">Total soal: <span class="font-semibold">{{ $package->questions->count() }}</span></div>
    </div>

    <form method="POST" action="{{ route('siswa.practice.paket.submit', ['paketNo' => $package->paket_no]) }}" class="space-y-4">
        @csrf

        @foreach($package->questions as $index => $q)
            @php
                $saved = $answersByQuestionId[$q->id] ?? null;
            @endphp

            <div class="rounded-3xl border border-border bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Soal {{ $index + 1 }}</div>
                    <span class="badge-info">#{{ $q->id }}</span>
                </div>

                @if($q->reading_passage)
                    <div class="mt-3 rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300 whitespace-pre-line">
                        {{ $q->reading_passage }}
                    </div>
                @endif

                <div class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-200">
                    {{ $q->question_text }}
                </div>

                @if(is_array($q->options) && count($q->options))
                    <div class="mt-4 space-y-2">
                        @foreach($q->options as $idx => $opt)
                            @php($label = $optionLabels[$idx] ?? 'O' . ($idx + 1))
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                                <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" class="mt-1" {{ ($saved?->jawaban ?? '') === $opt ? 'checked' : '' }} required>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $label }}</div>
                                    <div class="text-slate-700 dark:text-slate-300">{{ $opt }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 text-sm text-textSecondary">Pilihan jawaban belum tersedia.</div>
                @endif
            </div>
        @endforeach

        <div class="flex items-center justify-end">
            <button type="submit" class="btn-primary" data-confirm="Kumpulkan paket ini? Setelah dikumpulkan, paket tidak bisa dikerjakan ulang." data-confirm-title="Kumpulkan Paket">Kumpulkan Paket</button>
        </div>
    </form>
</div>
@endsection
