@extends('layouts.ujian')

@section('title', 'Paket Latihan — Ujion')

@push('styles')
<style>
  html, body { height: 100%; overflow: hidden; }
  .practice-shell { display: flex; flex-direction: column; height: 100dvh; }
  .practice-body { display: grid; grid-template-columns: 1fr 280px; flex: 1; overflow: hidden; }
  .practice-question-pane { overflow-y: auto; padding: 1.5rem; }
  .practice-sidebar-pane { overflow-y: auto; border-left: 1px solid #e2e8f0; background: #f8fafc; padding: 1.25rem; }
  .practice-mobile-nav { display: none; }

  @media (max-width: 768px) {
    .practice-body { grid-template-columns: 1fr; }
    .practice-sidebar-pane { display: none; }
    .practice-mobile-nav { display: flex !important; z-index: 40; }
    .practice-question-pane { padding: 1rem 1rem 8rem 1rem !important; }
  }

  .practice-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    width: 100%;
    cursor: pointer;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    padding: 14px 18px;
    text-align: left;
    transition: all .15s;
  }
  .practice-option:hover { border-color: #6366f1; background: #eef2ff; }
  .practice-option:has(input:checked) { border-color: #6366f1; background: #eef2ff; font-weight: 600; }
  .practice-option-code {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 2px solid #c7d2fe;
    border-radius: 999px;
    background: #eef2ff;
    color: #4f46e5;
    font-size: .8rem;
    font-weight: 800;
  }
  .practice-option:has(input:checked) .practice-option-code {
    border-color: #6366f1;
    background: #6366f1;
    color: #fff;
  }
  .practice-nav-btn {
    aspect-ratio: 1;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: .75rem;
    font-weight: 800;
    transition: all .12s;
  }
  .practice-nav-btn.answered { background: #dcfce7; color: #166534; }
  .practice-nav-btn.ragu { background: #fef3c7; color: #92400e; }
  .practice-nav-btn.unanswered { background: #f1f5f9; color: #475569; }
  .practice-nav-btn.current { outline: 3px solid #6366f1; outline-offset: 2px; }
  .practice-question-card { display: none; }
  .practice-question-card.active { display: block; }
</style>
@endpush

@section('content')
@php
    $material = $token->material;
    $optionLabels = range('A', 'Z');
    $questions = $package->questions->values();
    $formatRichText = function ($value) {
        $allowedTags = '<b><strong><i><em><u><sup><sub><br><p><ul><ol><li>';
        $clean = strip_tags((string) $value, $allowedTags);

        return preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean);
    };
@endphp

<form method="POST" action="{{ route('materi.paket.submit', ['paketNo' => $package->paket_no]) }}" id="practice-package-form" class="practice-shell">
    @csrf

    <header class="flex items-center justify-between gap-2 bg-slate-900 px-4 py-2 text-white shadow-lg md:px-6 md:py-3" style="flex-shrink:0">
        <div class="flex min-w-0 items-center gap-2 md:gap-4">
            <a href="{{ route('materi.dashboard') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white hover:bg-white/15" title="Kembali">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="min-w-0">
                <h1 class="truncate text-sm font-bold md:text-base">Paket {{ $package->paket_no }}</h1>
                <p class="truncate text-[10px] text-slate-400 md:text-xs">
                    {{ $material?->sub_unit ?? 'Materi' }} &middot; {{ $questions->count() }} Soal
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Peserta</div>
                <div class="max-w-[180px] truncate text-xs font-bold text-indigo-300 md:text-sm">{{ $session->nama }}</div>
            </div>
            <button type="button" data-open-finish-modal class="rounded-xl bg-red-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-red-700 md:px-4 md:text-xs">
                Kumpulkan
            </button>
        </div>
    </header>

    <div class="practice-body">
        <main class="practice-question-pane pb-32 px-4 py-6 md:px-12">
            @foreach($questions as $index => $q)
                @php
                    $saved = $answersByQuestionId[$q->id] ?? null;
                @endphp

                <section class="practice-question-card {{ $index === 0 ? 'active' : '' }}" data-question-card data-question-index="{{ $index }}">
                    @if($q->reading_passage)
                        <div class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="mb-3 flex items-center gap-2">
                                <div class="h-6 w-1 rounded-full bg-indigo-500"></div>
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Teks Bacaan</span>
                            </div>
                            <div class="whitespace-pre-line text-sm leading-relaxed text-slate-700 md:text-base md:leading-8">
                                {!! $formatRichText($q->reading_passage) !!}
                            </div>
                        </div>
                    @endif

                    <div class="mb-6 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-bold text-white">Soal</span>
                            <span data-current-number class="text-lg font-black text-slate-900 md:text-xl">{{ $index + 1 }}</span>
                            <span class="text-slate-400">/</span>
                            <span class="text-sm font-bold text-slate-500">{{ $questions->count() }}</span>
                        </div>
                        <span class="max-w-[140px] truncate rounded-full bg-slate-100 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 md:max-w-none md:px-3 md:text-[10px]">
                            #{{ $q->id }}
                        </span>
                    </div>

                    <div class="mb-5 text-base font-semibold leading-8 text-slate-900 md:text-lg">
                        {!! $formatRichText($q->question_text) !!}
                    </div>

                    @if(is_array($q->options) && count($q->options))
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:grid-flow-col md:grid-rows-2">
                            @foreach($q->options as $idx => $opt)
                                @php
                                    $label = $optionLabels[$idx] ?? 'O' . ($idx + 1);
                                @endphp
                                <label class="practice-option">
                                    <input
                                        type="radio"
                                        name="answers[{{ $q->id }}]"
                                        value="{{ $opt }}"
                                        class="sr-only"
                                        data-practice-answer
                                        data-question-index="{{ $index }}"
                                        {{ ($saved?->jawaban ?? '') === $opt ? 'checked' : '' }}
                                        required
                                    >
                                    <span class="practice-option-code">{{ $label }}</span>
                                    <span class="text-sm leading-6 text-slate-700 md:text-[15px]">{!! $formatRichText($opt) !!}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">
                            Pilihan jawaban belum tersedia.
                        </div>
                    @endif
                </section>
            @endforeach

            <div class="mt-10 flex items-center justify-between gap-2">
                <button type="button" id="practice-prev-btn" class="flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white py-3 text-[11px] font-bold text-slate-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm">
                    <i class="fa-solid fa-arrow-left mr-1.5 md:mr-2"></i>Sebelumnya
                </button>
                <button type="button" id="practice-flag-btn" class="flex flex-1 items-center justify-center rounded-2xl border border-amber-200 bg-white py-3 text-[11px] font-bold text-amber-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm">
                    <i class="fa-solid fa-flag mr-1.5 md:mr-2"></i>Ragu
                </button>
                <button type="button" id="practice-next-btn" class="flex flex-[1.2] items-center justify-center rounded-2xl bg-indigo-600 py-3 text-[11px] font-bold text-white shadow-lg transition active:scale-95 md:flex-none md:px-12 md:text-sm">
                    Selanjutnya<i class="fa-solid fa-arrow-right ml-1.5 md:ml-2"></i>
                </button>
            </div>
        </main>

        <aside class="practice-sidebar-pane">
            <div class="mb-4">
                <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
                    <span>Dijawab</span>
                    <span><span data-answered-count>0</span>/<span>{{ $questions->count() }}</span></span>
                </div>
                <div class="h-2 rounded-full bg-slate-200">
                    <div data-progress-bar class="h-2 rounded-full bg-indigo-500 transition-all" style="width:0%"></div>
                </div>
            </div>

            <div data-question-grid class="mb-5 grid grid-cols-5 gap-2"></div>

            <div class="space-y-2 text-xs">
                <div class="flex items-center gap-2">
                    <span class="h-4 w-4 rounded bg-emerald-100 ring-1 ring-green-300"></span>
                    <span class="text-slate-600">Dijawab</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-4 w-4 rounded bg-amber-100 ring-1 ring-amber-300"></span>
                    <span class="text-slate-600">Ragu-ragu</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-4 w-4 rounded bg-slate-100 ring-1 ring-slate-300"></span>
                    <span class="text-slate-600">Belum dijawab</span>
                </div>
            </div>

            <hr class="my-4 border-slate-200">

            <button type="button" data-open-finish-modal class="w-full rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700">
                Kumpulkan Paket
            </button>
        </aside>
    </div>

    <div class="practice-mobile-nav fixed bottom-0 left-0 right-0 z-10 border-t border-slate-200 bg-white px-4 py-2 shadow-xl">
        <div data-mobile-grid class="flex gap-1 overflow-x-auto pb-1"></div>
    </div>
</form>

<div id="practice-finish-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-slate-900">Kumpulkan Paket?</h3>
        <div id="practice-finish-summary" class="mt-3 space-y-2 text-sm text-slate-600"></div>
        <div class="mt-6 flex gap-3">
            <button type="button" id="practice-modal-cancel" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Kembali
            </button>
            <button type="button" id="practice-modal-confirm" class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white hover:bg-red-700">
                Ya, Kumpulkan
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('practice-package-form');
    if (!form) return;

    const cards = Array.from(document.querySelectorAll('[data-question-card]'));
    const grid = document.querySelector('[data-question-grid]');
    const mobileGrid = document.querySelector('[data-mobile-grid]');
    const progressBars = Array.from(document.querySelectorAll('[data-progress-bar]'));
    const answeredCounters = Array.from(document.querySelectorAll('[data-answered-count]'));
    const finishModal = document.getElementById('practice-finish-modal');
    const finishSummary = document.getElementById('practice-finish-summary');
    const prevBtn = document.getElementById('practice-prev-btn');
    const nextBtn = document.getElementById('practice-next-btn');
    const flagBtn = document.getElementById('practice-flag-btn');

    let currentIndex = 0;
    const flagged = new Set();

    const isAnswered = (index) => !!document.querySelector(`[data-practice-answer][data-question-index="${index}"]:checked`);

    const renderNav = () => {
        const answered = cards.filter((_, index) => isAnswered(index)).length;
        const percent = cards.length ? Math.round(answered / cards.length * 100) : 0;

        answeredCounters.forEach((counter) => { counter.textContent = answered; });
        progressBars.forEach((bar) => { bar.style.width = `${percent}%`; });

        [grid, mobileGrid].filter(Boolean).forEach((container) => {
            container.innerHTML = '';
            cards.forEach((_, index) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = index + 1;
                btn.title = `Soal ${index + 1}`;
                btn.className = 'practice-nav-btn ';

                if (flagged.has(index)) {
                    btn.className += 'ragu';
                } else if (isAnswered(index)) {
                    btn.className += 'answered';
                } else {
                    btn.className += 'unanswered';
                }

                if (index === currentIndex) {
                    btn.className += ' current';
                }

                btn.addEventListener('click', () => {
                    currentIndex = index;
                    renderQuestion();
                });
                container.appendChild(btn);
            });
        });

        flagBtn.classList.toggle('bg-amber-200', flagged.has(currentIndex));
        flagBtn.classList.toggle('ring-2', flagged.has(currentIndex));
        flagBtn.classList.toggle('ring-amber-400', flagged.has(currentIndex));
    };

    const renderQuestion = () => {
        cards.forEach((card, index) => {
            card.classList.toggle('active', index === currentIndex);
        });

        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === cards.length - 1;
        prevBtn.classList.toggle('opacity-30', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-30', nextBtn.disabled);
        prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);
        nextBtn.classList.toggle('cursor-not-allowed', nextBtn.disabled);

        renderNav();

        if (window.renderMathInElement) {
            renderMathInElement(document.body, {
                delimiters: [
                    {left:'$$', right:'$$', display:true},
                    {left:'$', right:'$', display:false},
                    {left:'\\(', right:'\\)', display:false},
                    {left:'\\[', right:'\\]', display:true},
                ],
                throwOnError: false,
            });
        }

        document.querySelector('.practice-question-pane')?.scrollTo(0, 0);
    };

    prevBtn.addEventListener('click', () => {
        currentIndex = Math.max(0, currentIndex - 1);
        renderQuestion();
    });

    nextBtn.addEventListener('click', () => {
        currentIndex = Math.min(cards.length - 1, currentIndex + 1);
        renderQuestion();
    });

    flagBtn.addEventListener('click', () => {
        if (flagged.has(currentIndex)) {
            flagged.delete(currentIndex);
        } else {
            flagged.add(currentIndex);
        }
        renderNav();
    });

    document.querySelectorAll('[data-practice-answer]').forEach((input) => {
        input.addEventListener('change', renderNav);
    });

    const showFinishModal = () => {
        const answered = cards.filter((_, index) => isAnswered(index)).length;
        const unanswered = cards.length - answered;

        finishSummary.innerHTML = `
            <div class="flex justify-between rounded-xl bg-emerald-50 px-4 py-2">
                <span>Dijawab</span><strong class="text-emerald-700">${answered}</strong>
            </div>
            <div class="flex justify-between rounded-xl bg-amber-50 px-4 py-2">
                <span>Ragu-ragu</span><strong class="text-amber-700">${flagged.size}</strong>
            </div>
            <div class="flex justify-between rounded-xl bg-rose-50 px-4 py-2">
                <span>Belum dijawab</span><strong class="text-rose-700">${unanswered}</strong>
            </div>`;

        finishModal.classList.remove('hidden');
        finishModal.classList.add('flex');
    };

    document.querySelectorAll('[data-open-finish-modal]').forEach((button) => {
        button.addEventListener('click', showFinishModal);
    });

    document.getElementById('practice-modal-cancel').addEventListener('click', () => {
        finishModal.classList.add('hidden');
        finishModal.classList.remove('flex');
    });

    document.getElementById('practice-modal-confirm').addEventListener('click', () => {
        form.submit();
    });

    renderQuestion();
});
</script>
@endpush
