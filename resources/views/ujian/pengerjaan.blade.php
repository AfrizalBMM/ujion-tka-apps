@extends('layouts.guest')

@section('title', 'Pengerjaan Ujian')

@section('content')
<div
    id="ujian-app"
    class="space-y-4"
    data-exam='@json(["saveUrl" => route("siswa.api.save_answer"), "finishUrl" => route("siswa.selesai"), "currentMapelId" => $currentMapel->id, "timer" => $timer, "questions" => $questions])'
>
    <section class="rounded-[28px] border border-white/80 bg-slate-900 px-5 py-5 text-white shadow-modal">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.24em] text-white/70">{{ $exam->judul }}</div>
                <h1 class="mt-2 text-2xl font-bold">{{ $currentMapel->nama_label }}</h1>
                <p class="mt-1 text-sm text-slate-300">{{ $session->nama }} &middot; {{ $paket->nama }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-center">
                <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-white/70">Sisa Waktu</div>
                <div id="timer-display" class="mt-2 text-3xl font-bold">--:--</div>
            </div>
        </div>
        <div class="mt-5 flex flex-wrap gap-2">
            @foreach($mapels as $mapel)
                <a href="{{ route('siswa.ujian', ['mapel' => $mapel->id]) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $currentMapel->id === $mapel->id ? 'bg-white text-slate-900' : 'border border-white/20 bg-white/10 text-white' }}">
                    {{ $mapel->nama_label }}
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section class="rounded-[28px] border border-white/80 bg-white/90 p-5 shadow-card">
            <div id="reading-panel" class="mb-5 hidden rounded-[24px] border border-sky-200 bg-sky-50 p-4">
                <div class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700">Teks Bacaan</div>
                <h2 id="reading-title" class="mt-2 text-lg font-bold text-slate-900"></h2>
                <div id="reading-content" class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"></div>
            </div>

            <div class="text-sm text-textSecondary">Soal <span id="soal-index">1</span> dari <span id="soal-total">{{ $questions->count() }}</span></div>
            <div class="mt-3 text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Indikator</div>
            <div id="question-indicator" class="mt-2 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm"></div>
            <div id="question-text" class="mt-5 text-lg font-semibold leading-8 text-slate-900"></div>
            <img id="question-image" src="" alt="Gambar soal" class="mt-4 hidden max-h-72 rounded-2xl border border-slate-200">

            <div id="question-body" class="mt-6"></div>

            <div class="mt-8 flex flex-wrap gap-3">
                <button id="prev-btn" class="btn-secondary" type="button">Sebelumnya</button>
                <button id="flag-btn" class="btn-secondary" type="button">Tandai Ragu</button>
                <button id="next-btn" class="btn-primary" type="button">Selanjutnya</button>
            </div>
        </section>

        <aside class="rounded-[28px] border border-white/80 bg-white/90 p-5 shadow-card">
            <div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Status Navigasi</div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                <div class="rounded-xl bg-emerald-100 px-3 py-2 text-center font-semibold text-emerald-700">Dijawab</div>
                <div class="rounded-xl bg-amber-100 px-3 py-2 text-center font-semibold text-amber-700">Ragu</div>
                <div class="rounded-xl bg-slate-100 px-3 py-2 text-center font-semibold text-slate-600">Belum</div>
            </div>

            <div id="question-grid" class="mt-5 grid grid-cols-5 gap-2"></div>

            <button id="finish-btn" type="button" class="btn-danger mt-6 w-full">Selesaikan Ujian</button>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('ujian-app');
    if (!app) return;

    const payload = JSON.parse(app.dataset.exam);
    const questions = payload.questions;
    const timerKey = `ujion-mapel-${payload.currentMapelId}`;
    const timerDisplay = document.getElementById('timer-display');
    const body = document.getElementById('question-body');
    const image = document.getElementById('question-image');
    const readingPanel = document.getElementById('reading-panel');
    const readingTitle = document.getElementById('reading-title');
    const readingContent = document.getElementById('reading-content');
    const questionText = document.getElementById('question-text');
    const indicator = document.getElementById('question-indicator');
    const indexEl = document.getElementById('soal-index');
    const totalEl = document.getElementById('soal-total');
    const grid = document.getElementById('question-grid');
    let currentIndex = 0;
    let warned = false;

    const initialRemaining = Number(sessionStorage.getItem(timerKey) ?? payload.timer.remaining_seconds ?? payload.timer.duration_seconds);
    let remainingSeconds = Number.isFinite(initialRemaining) ? initialRemaining : 0;

    const postAnswer = (question) => fetch(payload.saveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            question_id: question.id,
            mapel_paket_id: payload.currentMapelId,
            tipe_soal: question.tipe_soal,
            jawaban_pg: question.jawaban_pg ?? null,
            jawaban_menjodohkan: question.jawaban_menjodohkan ?? null,
            is_ragu: question.is_ragu ?? false,
            remaining_seconds: remainingSeconds
        })
    });

    const formatTime = (seconds) => {
        const safe = Math.max(seconds, 0);
        const minutes = Math.floor(safe / 60);
        const secs = safe % 60;
        return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const renderGrid = () => {
        grid.innerHTML = '';
        questions.forEach((question, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = question.nomor_soal;
            let className = 'rounded-xl px-3 py-3 text-sm font-semibold ';
            if (question.is_ragu) className += 'bg-amber-100 text-amber-800 ';
            else if (question.tipe_soal === 'pilihan_ganda' ? question.jawaban_pg : (question.jawaban_menjodohkan || []).length) className += 'bg-emerald-100 text-emerald-800 ';
            else className += 'bg-slate-100 text-slate-600 ';
            if (index === currentIndex) className += 'ring-2 ring-primary ';
            button.className = className;
            button.addEventListener('click', () => {
                currentIndex = index;
                renderQuestion();
            });
            grid.appendChild(button);
        });
    };

    const renderMultipleChoice = (question) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'grid gap-3';
        question.pilihan.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `rounded-[24px] border px-4 py-4 text-left ${question.jawaban_pg === option.kode ? 'border-primary bg-primary/8' : 'border-slate-200 bg-white'}`;
            button.innerHTML = `<div class="font-semibold">${option.kode}</div><div class="mt-2 text-sm text-slate-700">${option.teks}</div>`;
            button.addEventListener('click', () => {
                question.jawaban_pg = option.kode;
                question.is_ragu = false;
                renderQuestion();
                renderGrid();
                postAnswer(question);
            });
            wrapper.appendChild(button);
        });
        return wrapper;
    };

    const renderMatching = (question) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'space-y-3';
        const answers = Array.isArray(question.jawaban_menjodohkan) ? question.jawaban_menjodohkan : [];
        question.pasangan.forEach((pair) => {
            const row = document.createElement('div');
            row.className = 'grid gap-3 rounded-[24px] border border-slate-200 bg-slate-50/80 p-4 md:grid-cols-[1fr_220px]';
            const selected = answers.find((item) => Number(item.pair_id) === Number(pair.id))?.match_id ?? '';
            row.innerHTML = `<div class="text-sm font-medium text-slate-800">${pair.teks_kiri}</div>`;
            const select = document.createElement('select');
            select.className = 'input';
            select.innerHTML = `<option value="">Pilih jawaban</option>${question.matching_options.map((opt) => `<option value="${opt.id}" ${Number(selected) === Number(opt.id) ? 'selected' : ''}>${opt.label}</option>`).join('')}`;
            select.addEventListener('change', () => {
                const nextAnswers = answers.filter((item) => Number(item.pair_id) !== Number(pair.id));
                if (select.value) {
                    nextAnswers.push({ pair_id: pair.id, match_id: Number(select.value) });
                }
                question.jawaban_menjodohkan = nextAnswers;
                question.is_ragu = false;
                renderGrid();
                postAnswer(question);
            });
            row.appendChild(select);
            wrapper.appendChild(row);
        });
        return wrapper;
    };

    const renderQuestion = () => {
        const question = questions[currentIndex];
        indexEl.textContent = question.nomor_soal;
        totalEl.textContent = questions.length;
        indicator.textContent = question.indikator;
        questionText.textContent = question.pertanyaan;

        if (question.gambar_url) {
            image.src = question.gambar_url;
            image.classList.remove('hidden');
        } else {
            image.classList.add('hidden');
        }

        if (question.teks_bacaan) {
            readingPanel.classList.remove('hidden');
            readingTitle.textContent = question.teks_bacaan.judul || 'Teks Bacaan';
            readingContent.textContent = question.teks_bacaan.konten;
        } else {
            readingPanel.classList.add('hidden');
        }

        body.innerHTML = '';
        body.appendChild(question.tipe_soal === 'pilihan_ganda' ? renderMultipleChoice(question) : renderMatching(question));
        renderGrid();
    };

    document.getElementById('prev-btn').addEventListener('click', () => {
        currentIndex = Math.max(0, currentIndex - 1);
        renderQuestion();
    });

    document.getElementById('next-btn').addEventListener('click', () => {
        currentIndex = Math.min(questions.length - 1, currentIndex + 1);
        renderQuestion();
    });

    document.getElementById('flag-btn').addEventListener('click', () => {
        const question = questions[currentIndex];
        question.is_ragu = !question.is_ragu;
        renderGrid();
        postAnswer(question);
    });

    document.getElementById('finish-btn').addEventListener('click', () => {
        if (window.confirm('Yakin ingin menyelesaikan ujian sekarang?')) {
            window.location.href = payload.finishUrl;
        }
    });

    timerDisplay.textContent = formatTime(remainingSeconds);
    renderQuestion();

    window.setInterval(() => {
        remainingSeconds -= 1;
        sessionStorage.setItem(timerKey, String(Math.max(remainingSeconds, 0)));
        timerDisplay.textContent = formatTime(remainingSeconds);

        if (remainingSeconds <= 300 && !warned) {
            warned = true;
            window.alert('Sisa waktu 5 menit lagi.');
        }

        if (remainingSeconds <= 0) {
            sessionStorage.removeItem(timerKey);
            window.location.href = payload.finishUrl;
        }
    }, 1000);

    window.setInterval(() => {
        const current = questions[currentIndex];
        if (current) postAnswer(current);
    }, 30000);
});
</script>
@endsection
