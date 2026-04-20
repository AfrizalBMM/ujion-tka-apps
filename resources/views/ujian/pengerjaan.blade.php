@extends('layouts.ujian')

@section('title', ($mapel->nama_label ?? 'Ujian') . ' — ' . ($exam->judul ?? 'Ujion'))

@push('styles')
<style>
  html, body { height: 100%; overflow: hidden; }
  .exam-shell  { display: flex; flex-direction: column; height: 100dvh; }
  .exam-body   { display: grid; grid-template-columns: 1fr 280px; flex: 1; overflow: hidden; }
  .question-pane { overflow-y: auto; padding: 1.5rem; }
  .sidebar-pane  { overflow-y: auto; border-left: 1px solid #e2e8f0; background: #f8fafc; padding: 1.25rem; }
  @media (max-width:768px) {
    .exam-body { grid-template-columns: 1fr; }
    .sidebar-pane { display: none; }
    .mobile-nav-bar { display: flex !important; z-index: 40; }
    .question-pane { padding: 1rem 1rem 8rem 1rem !important; }
  }
  .mobile-nav-bar { display: none; }

  /* Option button */
  .opt-btn {
    display:flex; align-items:flex-start; gap:12px;
    width:100%; text-align:left;
    padding:14px 18px;
    border-radius:16px; border:2px solid #e2e8f0;
    background:#fff; cursor:pointer; transition: all .15s;
    font-size:0.9rem; line-height:1.5;
  }
  .opt-btn:hover  { border-color:#6366f1; background:#eef2ff; }
  .opt-btn.active { border-color:#6366f1; background:#eef2ff; font-weight:600; }
  .opt-kode {
    flex-shrink:0; width:32px; height:32px;
    display:flex; align-items:center; justify-content:center;
    border-radius:50%; border:2px solid #c7d2fe;
    background:#eef2ff; font-weight:700; font-size:.8rem; color:#4f46e5;
  }
  .opt-btn.active .opt-kode { background:#6366f1; color:#fff; border-color:#6366f1; }

  /* Soal grid button */
  .soal-nav-btn {
    aspect-ratio:1; border-radius:10px; font-size:.75rem; font-weight:700;
    border:none; cursor:pointer; transition:all .12s;
  }
  .soal-nav-btn.answered { background:#dcfce7; color:#166534; }
  .soal-nav-btn.ragu     { background:#fef3c7; color:#92400e; }
  .soal-nav-btn.unanswered { background:#f1f5f9; color:#475569; }
  .soal-nav-btn.current  { outline:3px solid #6366f1; outline-offset:2px; }

  /* Timer */
  #timer-display { font-variant-numeric: tabular-nums; }
  .timer-danger  { color: #ef4444 !important; animation: pulse-red 1s infinite; }
  /* Hide scrollbar */
  .scrollbar-hide::-webkit-scrollbar { display: none; }
  .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

@section('content')
{{-- ─── Data utama ─────────────────────────────────────────────────────────── --}}
@php
$payload = [
    'saveUrl'        => route('siswa.api.save_answer'),
    'finishUrl'      => route('siswa.selesai'),
    'currentMapelId' => $mapel->id,
    'timer'          => $timer,
    'questions'      => $questions,
];
@endphp

<div class="exam-shell" id="ujian-app">
    <script id="exam-data" type="application/json">@json($payload)</script>

    {{-- ─── Header Sticky ──────────────────────────────────────────────────── --}}
    <header class="flex items-center justify-between gap-2 bg-slate-900 px-4 py-2 text-white shadow-lg md:px-6 md:py-3" style="flex-shrink:0">
        <div class="flex items-center gap-2 md:gap-4 truncate">
            <div class="hidden h-10 w-10 items-center justify-center rounded-xl bg-white/10 md:flex">
                <i class="fa-solid fa-graduation-cap text-lg text-indigo-400"></i>
            </div>
            <div class="truncate">
                <h1 class="truncate text-sm font-bold md:text-base">{{ $exam->judul }}</h1>
                <p class="truncate text-[10px] text-slate-400 md:text-xs">
                    {{ $mapel->nama_label }} &middot; {{ $questions->count() }} Soal
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 md:gap-6">
            <div class="text-right">
                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Sisa Waktu</div>
                <div id="timer-display" class="font-mono text-lg font-bold text-indigo-400 md:text-2xl">00:00</div>
            </div>
            <button id="header-finish-btn" class="rounded-xl bg-red-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-red-700 md:px-4 md:text-xs">
                Selesai
            </button>
        </div>
    </header>

    {{-- ─── Body ──────────────────────────────────────────────────────────── --}}
    <div class="exam-body">

        {{-- ── Kiri: Area Soal ──────────────────────────────────────────── --}}
        <div class="question-pane pb-32 px-4 md:px-12 py-6">

            {{-- Teks Bacaan --}}
            <div id="reading-panel" class="mb-8 hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="h-6 w-1 bg-indigo-500 rounded-full"></div>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Teks Bacaan</span>
                </div>
                <div id="reading-body">
                    <h3 id="reading-title" class="mb-3 text-lg font-bold text-slate-900 md:text-xl"></h3>
                    <div id="reading-content" class="text-sm leading-relaxed text-slate-700 md:text-base md:leading-8"></div>
                </div>
            </div>

            {{-- Header soal --}}
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-bold text-white">Soal</span>
                    <span id="soal-index" class="text-lg font-black text-slate-900 md:text-xl">1</span>
                    <span class="text-slate-400">/</span>
                    <span id="soal-total" class="text-sm font-bold text-slate-500">{{ $questions->count() }}</span>
                </div>
                <span id="indikator-badge" class="max-w-[120px] truncate rounded-full bg-slate-100 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 md:max-w-none md:px-3 md:text-[10px]"></span>
            </div>

            {{-- Pertanyaan --}}
            <div id="question-text" class="mb-4 text-base font-semibold leading-8 text-slate-900 md:text-lg"></div>

            {{-- Gambar --}}
            <img id="question-image" src="" alt="Gambar soal"
                class="mb-5 hidden max-h-72 w-auto rounded-2xl border border-slate-200 shadow-sm">

            {{-- Jawaban --}}
            <div id="question-body" class="space-y-3"></div>

            {{-- Navigasi bawah --}}
            <div class="mt-10 flex items-center justify-between gap-2">
                <button id="prev-btn" class="flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white py-3 text-[11px] font-bold text-slate-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm">
                    <i class="fa-solid fa-arrow-left mr-1.5 md:mr-2"></i>Sebelumnya
                </button>
                <button id="flag-btn" class="flex flex-1 items-center justify-center rounded-2xl border border-amber-200 bg-white py-3 text-[11px] font-bold text-amber-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm">
                    <i class="fa-solid fa-flag mr-1.5 md:mr-2"></i>Ragu
                </button>
                <button id="next-btn" class="flex flex-[1.2] items-center justify-center rounded-2xl bg-indigo-600 py-3 text-[11px] font-bold text-white shadow-lg transition active:scale-95 md:flex-none md:px-12 md:text-sm">
                    Selanjutnya<i class="fa-solid fa-arrow-right ml-1.5 md:ml-2"></i>
                </button>
            </div>
        </div>

        {{-- ── Kanan: Sidebar Navigator ──────────────────────────────────── --}}
        <aside class="sidebar-pane">
            {{-- Progres --}}
            <div class="mb-4">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>Dijawab</span>
                    <span id="answered-count">0</span>/<span id="total-count">{{ $questions->count() }}</span>
                </div>
                <div class="h-2 rounded-full bg-slate-200">
                    <div id="progress-bar" class="h-2 rounded-full bg-indigo-500 transition-all" style="width:0%"></div>
                </div>
            </div>

            {{-- Grid nomor soal --}}
            <div id="question-grid" class="grid grid-cols-5 gap-2 mb-5"></div>

            {{-- Legenda --}}
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

            <button id="sidebar-finish-btn" type="button"
                class="w-full rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700">
                Selesaikan Ujian
            </button>
        </aside>
    </div>

    {{-- ─── Mobile Bottom Bar ──────────────────────────────────────────────── --}}
    <div class="mobile-nav-bar fixed bottom-0 left-0 right-0 z-10 border-t border-slate-200 bg-white px-4 py-2 shadow-xl">
        <div id="mobile-grid" class="flex gap-1 overflow-x-auto pb-1"></div>
    </div>
</div>

{{-- ─── Modal Konfirmasi Selesai ──────────────────────────────────────────── --}}
<div id="finish-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-slate-900">Selesaikan Ujian?</h3>
        <div id="finish-summary" class="mt-3 space-y-2 text-sm text-slate-600"></div>
        <div class="mt-6 flex gap-3">
            <button id="modal-cancel" type="button"
                class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Kembali
            </button>
            <button id="modal-confirm" type="button"
                class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white hover:bg-red-700">
                Ya, Selesai
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dataEl    = document.getElementById('exam-data');
    if (!dataEl) return;
    const payload   = JSON.parse(dataEl.textContent);
    const questions = payload.questions;

    // DOM refs
    const timerEl       = document.getElementById('timer-display');
    const soalIndex     = document.getElementById('soal-index');
    const soalTotal     = document.getElementById('soal-total');
    const indikatorBadge= document.getElementById('indikator-badge');
    const questionText  = document.getElementById('question-text');
    const questionImage = document.getElementById('question-image');
    const questionBody  = document.getElementById('question-body');
    const readingPanel  = document.getElementById('reading-panel');
    const readingBody   = document.getElementById('reading-body');
    const readingToggle = document.getElementById('reading-toggle');
    const readingTitle  = document.getElementById('reading-title');
    const readingContent= document.getElementById('reading-content');
    const grid          = document.getElementById('question-grid');
    const mobileGrid    = document.getElementById('mobile-grid');
    const progressBar   = document.getElementById('progress-bar');
    const answeredCount = document.getElementById('answered-count');
    const totalCount    = document.getElementById('total-count');
    const finishModal   = document.getElementById('finish-modal');
    const finishSummary = document.getElementById('finish-summary');

    const timerKey = `ujion-mapel-${payload.currentMapelId}`;
    let currentIndex      = 0;
    let warned5           = false;
    let readingOpen       = false;

    // Timer
    const initialRemaining = Number(sessionStorage.getItem(timerKey) ??
        payload.timer?.remaining_seconds ?? payload.timer?.duration_seconds ?? 0);
    let remainingSeconds = Number.isFinite(initialRemaining) ? initialRemaining : 0;

    // ─── Format waktu ──────────────────────────────────────────────────────────
    const fmt = (s) => {
        const safe = Math.max(s, 0);
        const m    = Math.floor(safe / 60);
        const sec  = safe % 60;
        return `${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
    };

    // ─── API save ──────────────────────────────────────────────────────────────
    const postAnswer = (q) => fetch(payload.saveUrl, {
        method : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN' : '{{ csrf_token() }}',
            'Accept'       : 'application/json',
        },
        body: JSON.stringify({
            question_id      : q.id,
            mapel_paket_id   : payload.currentMapelId,
            tipe_soal        : q.tipe_soal,
            jawaban_pg       : q.jawaban_pg ?? null,
            jawaban_menjodohkan: q.jawaban_menjodohkan ?? null,
            is_ragu          : q.is_ragu ?? false,
            remaining_seconds: remainingSeconds,
        }),
    }).catch(() => {});

    // ─── Render Grid ──────────────────────────────────────────────────────────
    const renderGrid = () => {
        const answered = questions.filter(q =>
            q.tipe_soal === 'pilihan_ganda' ? !!q.jawaban_pg : (q.jawaban_menjodohkan||[]).length > 0
        ).length;

        answeredCount.textContent = answered;
        const mobileAnswersCount = document.getElementById('answered-count-mobile');
        if (mobileAnswersCount) mobileAnswersCount.textContent = answered;
        
        const pct = questions.length ? Math.round(answered / questions.length * 100) : 0;
        progressBar.style.width   = pct + '%';

        [grid, mobileGrid].forEach(container => {
            container.innerHTML = '';
            questions.forEach((q, i) => {
                const btn = document.createElement('button');
                btn.type        = 'button';
                btn.textContent = q.nomor_soal;
                btn.title       = `Soal ${q.nomor_soal}`;
                btn.className   = 'soal-nav-btn ';

                const hasPg  = q.tipe_soal === 'pilihan_ganda' && !!q.jawaban_pg;
                const hasM   = q.tipe_soal !== 'pilihan_ganda' && (q.jawaban_menjodohkan||[]).length > 0;

                if (q.is_ragu)            btn.className += 'ragu';
                else if (hasPg || hasM)   btn.className += 'answered';
                else                      btn.className += 'unanswered';

                if (i === currentIndex)   btn.className += ' current';

                btn.addEventListener('click', () => { currentIndex = i; renderQuestion(); });
                container.appendChild(btn);
            });
        });
    };

    // ─── Render PG ────────────────────────────────────────────────────────────
    const renderPG = (q) => {
        const wrap = document.createElement('div');
        // md:grid-flow-col + md:grid-rows-2 akan membuat item mengisi kolom dulu:
        // Col 1: Item 1 (A), Item 2 (B)
        // Col 2: Item 3 (C), Item 4 (D)
        wrap.className = 'grid grid-cols-1 md:grid-cols-2 md:grid-flow-col md:grid-rows-2 gap-3';
        q.pilihan.forEach(opt => {
            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'opt-btn' + (q.jawaban_pg === opt.kode ? ' active' : '');
            btn.innerHTML = `<span class="opt-kode">${opt.kode}</span><span>${opt.teks}</span>`;
            btn.addEventListener('click', () => {
                q.jawaban_pg = opt.kode;
                q.is_ragu    = false;
                renderQuestion();
                renderGrid();
                postAnswer(q);
            });
            wrap.appendChild(btn);
        });
        return wrap;
    };

    // ─── Render Menjodohkan ───────────────────────────────────────────────────
    const renderMatching = (q) => {
        const wrap    = document.createElement('div');
        wrap.className = 'space-y-3';
        const answers  = Array.isArray(q.jawaban_menjodohkan) ? q.jawaban_menjodohkan : [];
        q.pasangan.forEach(pair => {
            const row      = document.createElement('div');
            row.className  = 'grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_220px]';
            const selected = answers.find(a => Number(a.pair_id) === Number(pair.id))?.match_id ?? '';
            row.innerHTML  = `<div class="text-sm font-medium text-slate-800 flex items-center">${pair.teks_kiri}</div>`;
            const sel      = document.createElement('select');
            sel.className  = 'input';
            sel.innerHTML  = `<option value="">Pilih jawaban...</option>` +
                q.matching_options.map(opt =>
                    `<option value="${opt.id}" ${Number(selected) === Number(opt.id) ? 'selected' : ''}>${opt.label}</option>`
                ).join('');
            sel.addEventListener('change', () => {
                const next = answers.filter(a => Number(a.pair_id) !== Number(pair.id));
                if (sel.value) next.push({ pair_id: pair.id, match_id: Number(sel.value) });
                q.jawaban_menjodohkan = next;
                q.is_ragu = false;
                renderGrid();
                postAnswer(q);
            });
            row.appendChild(sel);
            wrap.appendChild(row);
        });
        return wrap;
    };

    // ─── Render Soal ──────────────────────────────────────────────────────────
    const renderQuestion = () => {
        const q = questions[currentIndex];
        soalIndex.textContent      = q.nomor_soal;
        indikatorBadge.textContent = q.indikator || '';
        questionText.textContent   = q.pertanyaan;

        if (q.gambar_url) {
            questionImage.src = q.gambar_url;
            questionImage.classList.remove('hidden');
        } else {
            questionImage.classList.add('hidden');
        }

        if (q.teks_bacaan) {
            readingPanel.classList.remove('hidden');
            readingTitle.textContent   = q.teks_bacaan.judul || 'Teks Bacaan';
            readingContent.textContent = q.teks_bacaan.konten;
        } else {
            readingPanel.classList.add('hidden');
        }

        questionBody.innerHTML = '';
        questionBody.appendChild(q.tipe_soal === 'pilihan_ganda' ? renderPG(q) : renderMatching(q));
        renderGrid();

        // Update flag button style
        // Update nav buttons
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === questions.length - 1;
        
        prevBtn.classList.toggle('opacity-30', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-30', nextBtn.disabled);
        prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);
        nextBtn.classList.toggle('cursor-not-allowed', nextBtn.disabled);

        // Render math
        if (window.renderMathInElement) {
            renderMathInElement(document.body, {
                delimiters: [{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false},{left:'\\(',right:'\\)',display:false},{left:'\\[',right:'\\]',display:true}],
                throwOnError: false,
            });
        }

        // Scroll to top of question pane
        document.querySelector('.question-pane')?.scrollTo(0, 0);
    };

    // ─── Event Listeners ──────────────────────────────────────────────────────
    const prevBtn   = document.getElementById('prev-btn');
    const nextBtn   = document.getElementById('next-btn');
    const flagBtn   = document.getElementById('flag-btn');

    prevBtn.addEventListener('click', () => {
        currentIndex = Math.max(0, currentIndex - 1);
        renderQuestion();
    });

    nextBtn.addEventListener('click', () => {
        currentIndex = Math.min(questions.length - 1, currentIndex + 1);
        renderQuestion();
    });

    flagBtn.addEventListener('click', () => {
        const q = questions[currentIndex];
        q.is_ragu = !q.is_ragu;
        renderGrid();
        postAnswer(q);
        flagBtn.classList.toggle('bg-amber-200', q.is_ragu);
        flagBtn.classList.toggle('ring-2', q.is_ragu);
        flagBtn.classList.toggle('ring-amber-400', q.is_ragu);
    });

    const showFinishModal = () => {
        const answered  = questions.filter(q =>
            q.tipe_soal === 'pilihan_ganda' ? !!q.jawaban_pg : (q.jawaban_menjodohkan||[]).length > 0
        ).length;
        const ragu      = questions.filter(q => q.is_ragu).length;
        const unanswered= questions.length - answered;

        finishSummary.innerHTML = `
            <div class="flex justify-between rounded-xl bg-emerald-50 px-4 py-2">
                <span>Dijawab</span><strong class="text-emerald-700">${answered}</strong>
            </div>
            <div class="flex justify-between rounded-xl bg-amber-50 px-4 py-2">
                <span>Ragu-ragu</span><strong class="text-amber-700">${ragu}</strong>
            </div>
            <div class="flex justify-between rounded-xl bg-rose-50 px-4 py-2">
                <span>Belum dijawab</span><strong class="text-rose-700">${unanswered}</strong>
            </div>`;

        finishModal.classList.remove('hidden');
        finishModal.classList.add('flex');
    };

    const headerFinish  = document.getElementById('header-finish-btn');
    const sidebarFinish = document.getElementById('sidebar-finish-btn');
    [headerFinish, sidebarFinish].filter(b => b).forEach(b => b.addEventListener('click', showFinishModal));

    document.getElementById('modal-cancel').addEventListener('click', () => {
        finishModal.classList.add('hidden');
        finishModal.classList.remove('flex');
    });

    document.getElementById('modal-confirm').addEventListener('click', () => {
        window.location.href = payload.finishUrl;
    });

    // ─── Auto-save tiap 30 detik ──────────────────────────────────────────────
    setInterval(() => {
        const current = questions[currentIndex];
        if (current) postAnswer(current);
    }, 30000);

    // ─── Countdown ────────────────────────────────────────────────────────────
    timerEl.textContent = fmt(remainingSeconds);

    const tick = setInterval(() => {
        remainingSeconds -= 1;
        sessionStorage.setItem(timerKey, String(Math.max(remainingSeconds, 0)));
        timerEl.textContent = fmt(remainingSeconds);

        if (remainingSeconds <= 300 && !warned5) {
            warned5 = true;
            timerEl.classList.add('timer-danger');
            if (window.confirm('⚠️ Sisa waktu 5 menit lagi! Periksa kembali jawaban Anda.')) { /* dismiss */ }
        }

        if (remainingSeconds <= 0) {
            clearInterval(tick);
            sessionStorage.removeItem(timerKey);
            window.location.href = payload.finishUrl;
        }
    }, 1000);

    // ─── Init ─────────────────────────────────────────────────────────────────
    renderQuestion();
});
</script>
@endpush
