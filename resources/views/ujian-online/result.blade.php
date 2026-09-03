@extends('layouts.guest')

@section('title', 'Hasil Ujian — Ujion')

@section('content')
<div class="w-full space-y-6">
    {{-- Score Summary --}}
    <div class="rounded-3xl border border-white/80 bg-white/90 p-8 text-center shadow-card">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-3xl shadow-inner">
            🎯
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Hasil Ujian</h1>
        <p class="mt-2 text-sm text-textSecondary">{{ $mapelPaket?->nama_label ?? 'Mapel' }}</p>

        <div class="mt-6">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Skor Akhir</div>
            <div class="mt-2 text-5xl font-black text-indigo-600">{{ number_format((float) $sesi->skor, 1) }}</div>
            <div class="text-sm text-textSecondary">/ 100</div>
        </div>

        @if($totalSoal > 0)
            <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl bg-emerald-50 p-3">
                    <div class="text-xl font-bold text-emerald-700">{{ $dijawab }}</div>
                    <div class="text-xs text-emerald-600">Soal Dijawab</div>
                </div>
                <div class="rounded-2xl bg-rose-50 p-3">
                    <div class="text-xl font-bold text-rose-700">{{ $totalSoal - $dijawab }}</div>
                    <div class="text-xs text-rose-600">Tidak Dijawab</div>
                </div>
            </div>
        @endif
    </div>

    {{-- Questions & Answers --}}
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-slate-900">Kunci Jawaban & Pembahasan</h2>

        @foreach($questions as $q)
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 dark:border-slate-700/60 dark:bg-slate-900">
                {{-- Question Header --}}
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">Soal {{ $q['nomor_soal'] }}</span>
                    <span class="text-xs font-bold uppercase tracking-widest {{ $q['is_correct_pg'] || ($q['tipe_soal'] === 'menjodohkan' && $q['pair_details']->where('is_correct', true)->count() === $q['pair_details']->count()) ? 'text-emerald-600' : 'text-rose-600' }}">
                        @if($q['tipe_soal'] === 'pilihan_ganda')
                            {{ $q['is_correct_pg'] ? 'BENAR' : 'SALAH' }}
                        @else
                            {{ $q['pair_details']->where('is_correct', true)->count() }}/{{ $q['pair_details']->count() }} Benar
                        @endif
                    </span>
                </div>

                {{-- Reading Passage --}}
                @if($q['teks_bacaan'])
                    <div class="mt-4 rounded-2xl border border-amber-100 bg-amber-50 p-4">
                        <div class="text-xs font-bold uppercase tracking-widest text-amber-700">{{ $q['teks_bacaan']['judul'] ?: 'Teks Bacaan' }}</div>
                        <div class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">{!! nl2br(e($q['teks_bacaan']['konten'])) !!}</div>
                    </div>
                @endif

                {{-- Question Text --}}
                <div class="mt-4 text-sm leading-relaxed text-slate-900 dark:text-slate-100">{!! nl2br(e($q['pertanyaan'])) !!}</div>

                @if($q['gambar_url'])
                    <img src="{{ $q['gambar_url'] }}" alt="Gambar soal {{ $q['nomor_soal'] }}" class="mt-4 max-h-64 rounded-xl">
                @endif

                {{-- Pilihan Ganda --}}
                @if($q['tipe_soal'] === 'pilihan_ganda')
                    <div class="mt-4 space-y-2">
                        @foreach($q['pilihan'] as $p)
                            @php $isCorrect = $p['is_benar']; $isStudent = $p['is_student_choice']; @endphp
                            <div class="flex items-start gap-3 rounded-xl border p-3 text-sm
                                {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/30' : ($isStudent ? 'border-rose-300 bg-rose-50 dark:border-rose-700 dark:bg-rose-950/30' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40') }}">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $isCorrect ? 'bg-emerald-600 text-white' : ($isStudent ? 'bg-rose-600 text-white' : 'bg-slate-300 text-slate-700 dark:bg-slate-600 dark:text-slate-200') }}">
                                    {{ $p['kode'] }}
                                </span>
                                <span class="flex-1 pt-0.5 text-slate-900 dark:text-slate-100">{{ $p['teks'] }}</span>
                                @if($isCorrect)
                                    <i class="fa-solid fa-check text-emerald-600 mt-0.5"></i>
                                @elseif($isStudent)
                                    <i class="fa-solid fa-xmark text-rose-600 mt-0.5"></i>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(!$q['is_correct_pg'] && $q['student_jawaban_pg'])
                        <div class="mt-3 text-xs text-rose-600">Jawaban Anda: <strong>{{ $q['student_jawaban_pg'] }}</strong> — Kunci: <strong>{{ $q['correct_kode'] }}</strong></div>
                    @elseif(!$q['student_jawaban_pg'])
                        <div class="mt-3 text-xs text-amber-600">Tidak dijawab. Kunci: <strong>{{ $q['correct_kode'] }}</strong></div>
                    @endif
                @endif

                {{-- Menjodohkan --}}
                @if($q['tipe_soal'] === 'menjodohkan')
                    <div class="mt-4 space-y-2">
                        @foreach($q['pair_details'] as $pair)
                            <div class="grid grid-cols-3 items-center gap-2 rounded-xl border p-3 text-sm
                                {{ $pair['is_correct'] ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-700 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50/50 dark:border-rose-700 dark:bg-rose-950/20' }}">
                                <div class="text-slate-700 dark:text-slate-300">{{ $pair['teks_kiri'] }}</div>
                                <div class="text-center text-xs text-textSecondary">
                                    {{ $pair['is_correct'] ? '✅' : '❌' }}
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-textSecondary">Jawaban benar:</div>
                                    <div class="font-medium text-emerald-700 dark:text-emerald-300">{{ $pair['correct_teks_kanan'] }}</div>
                                    @if(!$pair['is_correct'])
                                        <div class="mt-1 text-xs text-rose-600">Jawaban Anda: {{ $pair['student_teks_kanan'] ?? '—' }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Pembahasan --}}
                @if($q['pembahasan'])
                    <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-blue-700 dark:text-blue-300">
                            <i class="fa-solid fa-lightbulb"></i>Pembahasan
                        </div>
                        <div class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">{!! nl2br(e($q['pembahasan'])) !!}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="text-center pt-4">
        <a href="{{ route('ujian-online.index') }}" class="btn-secondary">Kembali ke Beranda</a>
    </div>
</div>
@endsection
