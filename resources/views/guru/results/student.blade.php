@extends('layouts.guru')

@section('title', 'Detail Jawaban: ' . $session->nama)

@section('content')
@php($isSurvey = $session->mapelPaket?->isSurvey())
<div class="mb-8">
    <a href="{{ route('guru.results.mapel', [$session->exam_id, $session->mapel_paket_id]) }}" class="mb-4 inline-flex items-center text-sm font-semibold text-textSecondary hover:text-primary">
        <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Dashboard Bagian
    </a>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ $session->exam?->assessment_label }}</div>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Detail Respons: {{ $session->nama }}</h1>
            <p class="mt-1 text-sm text-textSecondary">Review detail pengerjaan peserta pada bagian {{ $session->mapelPaket->nama_label }}.</p>
        </div>
        <div class="rounded-2xl bg-white px-6 py-3 shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="text-right">
                <div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">{{ $isSurvey ? 'Kelengkapan' : 'Skor Total' }}</div>
                <div class="text-2xl font-black text-indigo-600">{{ number_format((float)$session->skor, 1) }}</div>
            </div>
            <div class="h-8 w-px bg-slate-200"></div>
            <div>
                 <div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Status</div>
                 <span class="inline-flex rounded-lg bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-emerald-700">Selesai</span>
            </div>
        </div>
    </div>
</div>

<div class="mb-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
    <h3 class="mb-4 text-lg font-bold text-slate-900">Ringkasan Jawaban</h3>
    <div class="flex flex-wrap gap-3">
        @foreach($session->mapelPaket->soals as $s)
        @php
            $ans = $answers->get($s->id);
            $isAnswered = false;
            $isCorrect = false;

            if ($s->tipe_soal === 'pilihan_ganda' && $ans) {
                $isAnswered = filled($ans->jawaban_pg);
                $isCorrect = $ans->jawaban_pg === $s->pilihanJawabans->firstWhere('is_benar', true)?->kode;
            }

            if ($s->tipe_soal === 'menjodohkan' && $ans) {
                $pairs = collect($ans->jawaban_menjodohkan ?? []);
                $isAnswered = $pairs->isNotEmpty();
                $isCorrect = $s->pasanganMenjodohkans->every(function ($pair) use ($pairs) {
                    $match = $pairs->firstWhere('pair_id', $pair->id);
                    return (int) ($match['match_id'] ?? 0) === (int) $pair->id;
                });
            }
        @endphp
        <div class="flex h-10 w-10 items-center justify-center rounded-xl font-bold text-white shadow-sm {{ !$ans || !$isAnswered ? 'bg-slate-300' : ($isSurvey ? 'bg-indigo-500' : ($isCorrect ? 'bg-emerald-500' : 'bg-rose-500')) }}" title="Nomor {{ $s->nomor_soal }}">
            {{ $s->nomor_soal }}
        </div>
        @endforeach
    </div>
    <div class="mt-6 flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest text-textSecondary">
        @if($isSurvey)
            <div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-indigo-500"></div> Terjawab</div>
        @else
            <div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-emerald-500"></div> Benar</div>
            <div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-rose-500"></div> Salah</div>
        @endif
        <div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-slate-300"></div> Kosong</div>
    </div>
</div>

<div class="space-y-6">
    @foreach($session->mapelPaket->soals as $s)
    @php
        $ans = $answers->get($s->id);
        $correctOption = $s->pilihanJawabans->firstWhere('is_benar', true);
        $isAnswered = false;
        $isCorrect = false;

        if ($s->tipe_soal === 'pilihan_ganda' && $ans) {
            $isAnswered = filled($ans->jawaban_pg);
            $isCorrect = $ans->jawaban_pg === $correctOption?->kode;
        }

        $matchingAnswers = collect($ans?->jawaban_menjodohkan ?? []);
        if ($s->tipe_soal === 'menjodohkan' && $ans) {
            $isAnswered = $matchingAnswers->isNotEmpty();
            $isCorrect = $s->pasanganMenjodohkans->every(function ($pair) use ($matchingAnswers) {
                $match = $matchingAnswers->firstWhere('pair_id', $pair->id);
                return (int) ($match['match_id'] ?? 0) === (int) $pair->id;
            });
        }
    @endphp
    <div class="rounded-[32px] border border-white/80 bg-white/80 p-8 shadow-card transition-all duration-300 hover:shadow-hover">
        <div class="mb-6 flex items-center justify-between">
            <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-1.5 text-xs font-bold text-slate-800">
                Soal Nomor {{ $s->nomor_soal }}
            </span>
            <span class="text-xs font-bold {{ !$isAnswered ? 'text-slate-500' : ($isSurvey ? 'text-indigo-600' : ($isCorrect ? 'text-emerald-600' : 'text-rose-600')) }}">
                @if(!$isAnswered)
                    KOSONG
                @elseif($isSurvey)
                    TERJAWAB
                @elseif($isCorrect)
                    BENAR (+{{ $s->bobot }})
                @else
                    SALAH (+0)
                @endif
            </span>
        </div>

        <div class="prose prose-slate max-w-none mb-8 text-slate-900">
            {!! $s->pertanyaan !!}
        </div>

        @if($s->tipe_soal === 'pilihan_ganda')
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($s->pilihanJawabans as $opt)
            @php
                $isChosen = ($ans && $ans->jawaban_pg === $opt->kode);
                $isCorrectOpt = $opt->is_benar;
                $bgColor = 'bg-slate-50 border-slate-100';
                $icon = '';

                if ($isSurvey) {
                    if ($isChosen) {
                        $bgColor = 'bg-indigo-50 border-indigo-200 text-indigo-900';
                        $icon = '<i class="fa-solid fa-check text-indigo-500 mr-2"></i>';
                    }
                } else {
                    if ($isChosen && $isCorrectOpt) {
                        $bgColor = 'bg-emerald-50 border-emerald-200 text-emerald-900';
                        $icon = '<i class="fa-solid fa-circle-check text-emerald-500 mr-2"></i>';
                    } elseif ($isChosen && !$isCorrectOpt) {
                        $bgColor = 'bg-rose-50 border-rose-200 text-rose-900';
                        $icon = '<i class="fa-solid fa-circle-xmark text-rose-500 mr-2"></i>';
                    } elseif (!$isChosen && $isCorrectOpt) {
                        $bgColor = 'bg-emerald-50 border-emerald-200 ring-2 ring-emerald-500/20';
                        $icon = '<i class="fa-solid fa-circle-check text-emerald-500 mr-2 opacity-50"></i>';
                    }
                }
            @endphp
            <div class="relative flex items-center rounded-2xl border p-4 transition-all {{ $bgColor }}">
                <span class="mr-4 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/80 font-black shadow-sm text-sm">{{ $opt->kode }}</span>
                <div class="flex-1 text-sm">{!! $icon !!} {!! $opt->teks !!}</div>
            </div>
            @endforeach
        </div>
        @elseif($s->tipe_soal === 'menjodohkan')
        <div class="rounded-2xl bg-slate-50 p-6">
            <h4 class="mb-4 text-xs font-bold uppercase tracking-widest text-textSecondary">{{ $isSurvey ? 'Respons Menjodohkan' : 'Analisis Menjodohkan' }}</h4>
            <div class="space-y-3">
                @foreach($s->pasanganMenjodohkans as $pair)
                @php
                    $match = $matchingAnswers->firstWhere('pair_id', $pair->id);
                    $selectedPair = $s->pasanganMenjodohkans->firstWhere('id', $match['match_id'] ?? null);
                    $isPairCorrect = (int) ($match['match_id'] ?? 0) === (int) $pair->id;
                @endphp
                <div class="flex items-center justify-between rounded-xl bg-white p-3 shadow-sm border border-slate-100">
                    <div class="text-sm font-semibold text-slate-800">{{ $pair->teks_kiri }}</div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm border-b-2 {{ !$selectedPair ? 'border-slate-300' : ($isSurvey ? 'border-indigo-500' : ($isPairCorrect ? 'border-emerald-500' : 'border-rose-500')) }} px-2 font-bold">
                            {{ $selectedPair?->teks_kanan ?? 'Kosong' }}
                        </div>
                        @unless($isSurvey)
                            <div class="text-[10px] text-textSecondary">Kunci: {{ $pair->teks_kanan }}</div>
                        @endunless
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($s->indikator)
        <div class="mt-8 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
            <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-indigo-700">
                <i class="fa-solid fa-lightbulb"></i> {{ $isSurvey ? 'Catatan Butir' : 'Pembahasan / Indikator' }}
            </div>
            <div class="text-sm text-slate-700 prose prose-indigo">
                {!! $s->indikator !!}
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
