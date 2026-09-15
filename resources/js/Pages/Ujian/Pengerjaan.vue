<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import UjianLayout from '@/Layouts/UjianLayout.vue';

const props = defineProps({
	exam: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	questions: {
		type: Array,
		required: true,
	},
	timer: {
		type: Object,
		required: true,
	},
});

const saveUrl = route('siswa.api.save_answer');
const finishUrl = route('siswa.selesai');
const finishSubmitUrl = route('siswa.selesai.submit');
const csrfToken = (window.Laravel ?? {}).csrfToken ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const questions = ref(props.questions.map((q) => ({ ...q })));
const currentIndex = ref(0);
const saveStatus = ref('idle');
const showSaveStatus = ref(false);
const showFinishModal = ref(false);
const finishSubmitting = ref(false);
const finishError = ref(null);
const rootEl = ref(null);
const questionPaneEl = ref(null);

const initialRemaining = Number(props.timer?.remaining_seconds ?? props.timer?.duration_seconds ?? 0);
const remainingSeconds = ref(Number.isFinite(initialRemaining) ? initialRemaining : 0);
const timerKey = `ujion-mapel-${props.mapel.id}`;
sessionStorage.setItem(timerKey, String(Math.max(remainingSeconds.value, 0)));

let warned5 = false;
let retryTimer = null;
let countdownTimer = null;
const pendingSaves = new Map();

const currentQuestion = computed(() => questions.value[currentIndex.value]);

const isAnswered = (q) =>
	q.tipe_soal === 'pilihan_ganda' ? !!q.jawaban_pg : (q.jawaban_menjodohkan || []).length > 0;

const answeredCount = computed(() => questions.value.filter(isAnswered).length);
const progressPct = computed(() =>
	questions.value.length ? Math.round((answeredCount.value / questions.value.length) * 100) : 0
);
const raguCount = computed(() => questions.value.filter((q) => q.is_ragu).length);
const unansweredCount = computed(() => questions.value.length - answeredCount.value);

const timerText = computed(() => {
	const safe = Math.max(remainingSeconds.value, 0);
	const m = Math.floor(safe / 60);
	const sec = safe % 60;
	return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
});

const gridClass = (q, index) => {
	const hasPg = q.tipe_soal === 'pilihan_ganda' && !!q.jawaban_pg;
	const hasM = q.tipe_soal !== 'pilihan_ganda' && (q.jawaban_menjodohkan || []).length > 0;

	let cls = 'soal-nav-btn ';
	if (q.is_ragu) cls += 'ragu';
	else if (hasPg || hasM) cls += 'answered';
	else cls += 'unanswered';

	if (index === currentIndex.value) cls += ' current';

	return cls;
};

const selectedOptKey = (q, pairKey) => {
	const answers = Array.isArray(q.jawaban_menjodohkan) ? q.jawaban_menjodohkan : [];
	return answers.find((a) => String(a.row_key) === String(pairKey))?.opt_key ?? '';
};

const setSaveStatus = (state) => {
	saveStatus.value = state;
	if (state !== 'idle') showSaveStatus.value = true;
};

const saveStatusDotClass = computed(() => {
	switch (saveStatus.value) {
		case 'ok':
			return 'h-2 w-2 rounded-full bg-emerald-400';
		case 'saving':
			return 'h-2 w-2 rounded-full bg-indigo-400 animate-pulse';
		default:
			return 'h-2 w-2 rounded-full bg-rose-400';
	}
});

const saveStatusText = computed(() => {
	switch (saveStatus.value) {
		case 'ok':
			return 'Tersimpan';
		case 'saving':
			return 'Menyimpan...';
		default:
			return 'Koneksi terputus — mencoba lagi...';
	}
});

const postAnswer = (q) =>
	fetch(saveUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-CSRF-TOKEN': csrfToken,
			Accept: 'application/json',
		},
		body: JSON.stringify({
			question_id: q.id,
			mapel_paket_id: props.mapel.id,
			tipe_soal: q.tipe_soal,
			jawaban_pg: q.jawaban_pg ?? null,
			jawaban_menjodohkan: q.jawaban_menjodohkan ?? null,
			is_ragu: q.is_ragu ?? false,
		}),
	})
		.then(async (response) => {
			let result = null;

			try {
				result = await response.json();
			} catch (error) {
				result = null;
			}

			if (typeof result?.remaining_seconds === 'number' && Number.isFinite(result.remaining_seconds)) {
				remainingSeconds.value = Math.max(0, result.remaining_seconds);
				sessionStorage.setItem(timerKey, String(remainingSeconds.value));
			}

			if ((response.status === 409 || result?.time_expired) && result?.redirect_url) {
				sessionStorage.removeItem(timerKey);
				window.location.href = result.redirect_url;
				return;
			}

			if (response.ok) {
				pendingSaves.delete(q.id);
				if (pendingSaves.size === 0 && saveStatus.value !== 'idle') {
					setSaveStatus('ok');
				}
			} else if (response.status !== 401) {
				pendingSaves.set(q.id, q);
				setSaveStatus('error');
				flushPendingSaves();
			}
		})
		.catch(() => {
			pendingSaves.set(q.id, q);
			setSaveStatus('error');
			flushPendingSaves();
		});

const flushPendingSaves = () => {
	if (pendingSaves.size === 0 || retryTimer !== null) return;

	const backoff = Math.min(3000 * Math.pow(2, Math.min(pendingSaves.size, 4)), 30000);
	retryTimer = setTimeout(async () => {
		retryTimer = null;
		const items = Array.from(pendingSaves.values());
		for (const q of items) {
			await postAnswer(q);
		}
	}, backoff);
};

const hasUnsavedAnswers = () => pendingSaves.size > 0;

const flushNowAndWait = async () => {
	if (pendingSaves.size === 0) return;

	setSaveStatus('saving');
	const items = Array.from(pendingSaves.values());
	await Promise.all(items.map((q) => postAnswer(q)));
};

const selectPg = (q, kode) => {
	q.jawaban_pg = kode;
	q.is_ragu = false;
	setSaveStatus('saving');
	postAnswer(q);
};

const setMatching = (q, pair, optKey) => {
	const answers = Array.isArray(q.jawaban_menjodohkan) ? q.jawaban_menjodohkan : [];
	const next = answers.filter((a) => String(a.row_key) !== String(pair.key));
	if (optKey) next.push({ row_key: pair.key, opt_key: optKey });
	q.jawaban_menjodohkan = next;
	q.is_ragu = false;
	setSaveStatus('saving');
	postAnswer(q);
};

const toggleRagu = () => {
	const q = currentQuestion.value;
	q.is_ragu = !q.is_ragu;
	postAnswer(q);
};

const goTo = (index) => {
	currentIndex.value = index;
};

const prev = () => {
	currentIndex.value = Math.max(0, currentIndex.value - 1);
};

const next = () => {
	currentIndex.value = Math.min(questions.value.length - 1, currentIndex.value + 1);
};

const openFinishModal = () => {
	showFinishModal.value = true;
};

const closeFinishModal = () => {
	showFinishModal.value = false;
	finishError.value = null;
};

const confirmFinish = async () => {
	if (finishSubmitting.value) return;
	finishSubmitting.value = true;

	if (hasUnsavedAnswers()) {
		await flushNowAndWait();

		if (hasUnsavedAnswers()) {
			finishSubmitting.value = false;
			finishError.value = 'Masih ada jawaban yang gagal tersimpan ke server. Periksa koneksi Anda lalu coba lagi.';
			return;
		}
	}

	sessionStorage.removeItem(timerKey);
	router.post(finishSubmitUrl);
};

const sanitizeRichText = (value) => {
	const template = document.createElement('template');
	template.innerHTML = String(value ?? '');
	const allowedTags = new Set(['B', 'STRONG', 'I', 'EM', 'U', 'SUP', 'SUB', 'BR', 'P', 'UL', 'OL', 'LI']);

	template.content.querySelectorAll('*').forEach((node) => {
		if (!allowedTags.has(node.tagName)) {
			node.replaceWith(document.createTextNode(node.textContent || ''));
			return;
		}

		Array.from(node.attributes).forEach((attribute) => node.removeAttribute(attribute.name));
	});

	return template.innerHTML;
};

const renderMath = () => {
	if (window.renderMathInElement && rootEl.value) {
		window.renderMathInElement(rootEl.value, {
			delimiters: [
				{ left: '$$', right: '$$', display: true },
				{ left: '$', right: '$', display: false },
				{ left: '\\(', right: '\\)', display: false },
				{ left: '\\[', right: '\\]', display: true },
			],
			throwOnError: false,
		});
	}
};

const onBeforeUnload = (event) => {
	if (hasUnsavedAnswers()) {
		event.preventDefault();
		event.returnValue = '';
	}
};

let autoSaveTimer = null;

onMounted(() => {
	renderMath();

	autoSaveTimer = setInterval(() => {
		const current = currentQuestion.value;
		if (current && (current.jawaban_pg || (current.jawaban_menjodohkan || []).length > 0)) {
			setSaveStatus('saving');
			postAnswer(current);
		}
	}, 30000);

	countdownTimer = setInterval(() => {
		remainingSeconds.value -= 1;
		sessionStorage.setItem(timerKey, String(Math.max(remainingSeconds.value, 0)));

		if (remainingSeconds.value <= 300 && !warned5) {
			warned5 = true;
			window.confirm('⚠️ Sisa waktu 5 menit lagi! Periksa kembali jawaban Anda.');
		}

		if (remainingSeconds.value <= 0) {
			clearInterval(countdownTimer);
			sessionStorage.removeItem(timerKey);
			window.location.href = finishUrl;
		}
	}, 1000);

	window.addEventListener('beforeunload', onBeforeUnload);
});

onBeforeUnmount(() => {
	clearInterval(countdownTimer);
	clearInterval(autoSaveTimer);
	if (retryTimer !== null) clearTimeout(retryTimer);
	window.removeEventListener('beforeunload', onBeforeUnload);
});

const onQuestionChange = async () => {
	await nextTick();
	renderMath();
	questionPaneEl.value?.scrollTo(0, 0);
};
</script>

<template>
	<Head :title="(mapel.nama_label ?? 'Ujian') + ' — ' + (exam.judul ?? 'Ujion')" />

	<UjianLayout>
		<div ref="rootEl" class="exam-shell">
			<header class="flex items-center justify-between gap-2 bg-slate-900 px-4 py-2 text-white shadow-lg md:px-6 md:py-3" style="flex-shrink: 0">
				<div class="flex items-center gap-2 truncate md:gap-4">
					<div class="hidden h-10 w-10 items-center justify-center rounded-xl bg-white/10 md:flex">
						<i class="fa-solid fa-graduation-cap text-lg text-indigo-400"></i>
					</div>
					<div class="truncate">
						<h1 class="truncate text-sm font-bold md:text-base">{{ exam.judul }}</h1>
						<p class="truncate text-[10px] text-slate-400 md:text-xs">
							{{ mapel.nama_label }} &middot; {{ questions.length }} Soal
						</p>
					</div>
				</div>

				<div class="flex items-center gap-3 md:gap-6">
					<div
						id="save-status"
						class="items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-semibold md:text-xs"
						:class="showSaveStatus ? 'flex' : 'hidden'"
						role="status"
						aria-live="polite"
					>
						<span id="save-status-dot" :class="saveStatusDotClass"></span>
						<span id="save-status-text">{{ saveStatusText }}</span>
					</div>
					<div class="text-right">
						<div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Sisa Waktu</div>
						<div
							id="timer-display"
							class="font-mono text-lg font-bold text-indigo-400 md:text-2xl"
							:class="{ 'timer-danger': remainingSeconds <= 300 }"
						>
							{{ timerText }}
						</div>
					</div>
					<button
						class="rounded-xl bg-red-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-red-700 md:px-4 md:text-xs"
						@click="openFinishModal"
					>
						Selesai
					</button>
				</div>
			</header>

			<div class="exam-body">
				<div ref="questionPaneEl" class="question-pane px-4 pb-32 py-6 md:px-12">
					<div v-if="currentQuestion.teks_bacaan" class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
						<div class="mb-3 flex items-center gap-2">
							<div class="h-6 w-1 rounded-full bg-indigo-500"></div>
							<span class="text-xs font-bold uppercase tracking-widest text-slate-500">Teks Bacaan</span>
						</div>
						<div>
							<h3 class="mb-3 text-lg font-bold text-slate-900 md:text-xl" v-html="sanitizeRichText(currentQuestion.teks_bacaan.judul || 'Teks Bacaan')"></h3>
							<div class="text-sm leading-relaxed text-slate-700 md:text-base md:leading-8" v-html="sanitizeRichText(currentQuestion.teks_bacaan.konten)"></div>
						</div>
					</div>

					<div class="mb-6 flex items-center justify-between">
						<div class="flex items-center gap-2">
							<span class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-bold text-white">Soal</span>
							<span class="text-lg font-black text-slate-900 md:text-xl">{{ currentQuestion.nomor_soal }}</span>
							<span class="text-slate-400">/</span>
							<span class="text-sm font-bold text-slate-500">{{ questions.length }}</span>
						</div>
						<span class="max-w-[120px] truncate rounded-full bg-slate-100 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 md:max-w-none md:px-3 md:text-[10px]">
							{{ currentQuestion.indikator || '' }}
						</span>
					</div>

					<div class="mb-4 text-base font-semibold leading-8 text-slate-900 md:text-lg" v-html="sanitizeRichText(currentQuestion.pertanyaan)"></div>

					<img
						v-if="currentQuestion.gambar_url"
						:src="currentQuestion.gambar_url"
						alt="Gambar soal"
						class="mb-5 hidden max-h-72 w-auto rounded-2xl border border-slate-200 shadow-sm"
					>

					<div v-if="currentQuestion.tipe_soal === 'pilihan_ganda'" class="grid grid-cols-1 gap-3 md:grid-flow-col md:grid-cols-2 md:grid-rows-2">
						<button
							v-for="opt in currentQuestion.pilihan"
							:key="opt.kode"
							type="button"
							class="opt-btn"
							:class="{ active: currentQuestion.jawaban_pg === opt.kode }"
							@click="selectPg(currentQuestion, opt.kode)"
						>
							<span class="opt-kode">{{ opt.kode }}</span>
							<span v-html="sanitizeRichText(opt.teks)"></span>
						</button>
					</div>

					<div v-else class="space-y-3">
						<div
							v-for="pair in currentQuestion.pasangan"
							:key="pair.key"
							class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_220px]"
						>
							<div class="flex items-center text-sm font-medium text-slate-800" v-html="sanitizeRichText(pair.teks_kiri)"></div>
							<select
								class="input"
								:value="selectedOptKey(currentQuestion, pair.key)"
								@change="setMatching(currentQuestion, pair, $event.target.value)"
							>
								<option value="">Pilih jawaban...</option>
								<option v-for="opt in currentQuestion.matching_options" :key="opt.key" :value="opt.key">
									{{ opt.label }}
								</option>
							</select>
						</div>
					</div>

					<div class="mt-10 flex items-center justify-between gap-2">
						<button
							class="flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white py-3 text-[11px] font-bold text-slate-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm"
							:class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed' : ''"
							:disabled="currentIndex === 0"
							@click="prev(); onQuestionChange()"
						>
							<i class="fa-solid fa-arrow-left mr-1.5 md:mr-2"></i>Sebelumnya
						</button>
						<button
							class="flex flex-1 items-center justify-center rounded-2xl border border-amber-200 bg-white py-3 text-[11px] font-bold text-amber-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm"
							:class="{ 'bg-amber-200': currentQuestion.is_ragu, 'ring-2': currentQuestion.is_ragu, 'ring-amber-400': currentQuestion.is_ragu }"
							@click="toggleRagu"
						>
							<i class="fa-solid fa-flag mr-1.5 md:mr-2"></i>Ragu
						</button>
						<button
							class="flex flex-[1.2] items-center justify-center rounded-2xl bg-indigo-600 py-3 text-[11px] font-bold text-white shadow-lg transition active:scale-95 md:flex-none md:px-12 md:text-sm"
							:class="currentIndex === questions.length - 1 ? 'opacity-30 cursor-not-allowed' : ''"
							:disabled="currentIndex === questions.length - 1"
							@click="next(); onQuestionChange()"
						>
							Selanjutnya<i class="fa-solid fa-arrow-right ml-1.5 md:ml-2"></i>
						</button>
					</div>
				</div>

				<aside class="sidebar-pane">
					<div class="mb-4">
						<div class="mb-1 flex items-center justify-between text-xs text-slate-500">
							<span>Dijawab</span>
							<span>{{ answeredCount }}/{{ questions.length }}</span>
						</div>
						<div class="h-2 rounded-full bg-slate-200">
							<div class="h-2 rounded-full bg-indigo-500 transition-all" :style="{ width: progressPct + '%' }"></div>
						</div>
					</div>

					<div class="mb-5 grid grid-cols-5 gap-2">
						<button
							v-for="(q, index) in questions"
							:key="q.id"
							type="button"
							:class="gridClass(q, index)"
							:title="`Soal ${q.nomor_soal}`"
							@click="goTo(index); onQuestionChange()"
						>
							{{ q.nomor_soal }}
						</button>
					</div>

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

					<button
						type="button"
						class="w-full rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700"
						@click="openFinishModal"
					>
						Selesaikan Ujian
					</button>
				</aside>
			</div>

			<div class="mobile-nav-bar fixed bottom-0 left-0 right-0 z-10 border-t border-slate-200 bg-white px-4 py-2 shadow-xl">
				<div class="flex gap-1 overflow-x-auto pb-1">
					<button
						v-for="(q, index) in questions"
						:key="`m-${q.id}`"
						type="button"
						:class="gridClass(q, index)"
						:title="`Soal ${q.nomor_soal}`"
						@click="goTo(index); onQuestionChange()"
					>
						{{ q.nomor_soal }}
					</button>
				</div>
			</div>
		</div>

		<div
			class="fixed inset-0 z-50 items-center justify-center bg-black/60 backdrop-blur-sm"
			:class="showFinishModal ? 'flex' : 'hidden'"
		>
			<div class="mx-4 w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
				<h3 class="text-lg font-bold text-slate-900">Selesaikan Ujian?</h3>
				<div class="mt-3 space-y-2 text-sm text-slate-600">
					<div class="flex justify-between rounded-xl bg-emerald-50 px-4 py-2">
						<span>Dijawab</span><strong class="text-emerald-700">{{ answeredCount }}</strong>
					</div>
					<div class="flex justify-between rounded-xl bg-amber-50 px-4 py-2">
						<span>Ragu-ragu</span><strong class="text-amber-700">{{ raguCount }}</strong>
					</div>
					<div class="flex justify-between rounded-xl bg-rose-50 px-4 py-2">
						<span>Belum dijawab</span><strong class="text-rose-700">{{ unansweredCount }}</strong>
					</div>
				</div>
				<div v-if="finishError" class="mt-3 rounded-xl bg-rose-50 px-4 py-2 text-xs text-rose-700">
					{{ finishError }}
				</div>
				<div class="mt-6 flex gap-3">
					<button
						type="button"
						class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
						@click="closeFinishModal"
					>
						Kembali
					</button>
					<button
						type="button"
						class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white hover:bg-red-700"
						:disabled="finishSubmitting"
						@click="confirmFinish"
					>
						{{ finishSubmitting ? 'Menyimpan jawaban...' : 'Ya, Selesai' }}
					</button>
				</div>
			</div>
		</div>
	</UjianLayout>
</template>

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

.soal-nav-btn {
  aspect-ratio:1; border-radius:10px; font-size:.75rem; font-weight:700;
  border:none; cursor:pointer; transition:all .12s;
}
.soal-nav-btn.answered { background:#dcfce7; color:#166534; }
.soal-nav-btn.ragu     { background:#fef3c7; color:#92400e; }
.soal-nav-btn.unanswered { background:#f1f5f9; color:#475569; }
.soal-nav-btn.current  { outline:3px solid #6366f1; outline-offset:2px; }

#timer-display { font-variant-numeric: tabular-nums; }
.timer-danger  { color: #ef4444 !important; animation: pulse-red 1s infinite; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
