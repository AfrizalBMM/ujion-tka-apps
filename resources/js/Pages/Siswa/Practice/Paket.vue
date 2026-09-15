<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import UjianLayout from '@/Layouts/UjianLayout.vue';

const props = defineProps({
	session: {
		type: Object,
		required: true,
	},
	token: {
		type: Object,
		required: true,
	},
	package: {
		type: Object,
		required: true,
	},
	attempt: {
		type: Object,
		default: null,
	},
	answersByQuestionId: {
		type: Object,
		required: true,
	},
});

const pkg = computed(() => props.package);
const questions = props.package.questions ?? [];
const csrfToken = usePage().props.csrf_token;

const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));
const optionLabel = (idx) => optionLabels[idx] ?? `O${idx + 1}`;

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

const answers = reactive({});
Object.entries(props.answersByQuestionId ?? {}).forEach(([questionId, saved]) => {
	answers[questionId] = saved.jawaban ?? '';
});

const currentIndex = ref(0);
const flagged = reactive(new Set());
const showFinishModal = ref(false);
const rootForm = ref(null);
const questionPaneEl = ref(null);

const draftKey = `ujion-practice-draft-${props.session.id}-${props.package.paket_no}`;
let draftRestored = false;

const loadDraft = () => {
	try {
		const raw = localStorage.getItem(draftKey);
		if (!raw) return;

		const draft = JSON.parse(raw);
		Object.entries(draft.answers || {}).forEach(([questionId, value]) => {
			const question = questions.find((q) => String(q.id) === String(questionId));
			if (question && Array.isArray(question.options) && question.options.includes(value)) {
				answers[questionId] = value;
				draftRestored = true;
			}
		});
		(draft.flagged || []).forEach((index) => flagged.add(Number(index)));
	} catch (e) {}
};

const saveDraft = () => {
	try {
		const draft = {};
		questions.forEach((q) => {
			const value = answers[q.id];
			if (value) draft[q.id] = value;
		});

		localStorage.setItem(draftKey, JSON.stringify({
			answers: draft,
			flagged: Array.from(flagged),
		}));
	} catch (e) {}
};

const clearDraft = () => {
	try { localStorage.removeItem(draftKey); } catch (e) {}
};

const isAnswered = (question) => !!answers[question.id];

const answeredCount = computed(() => questions.filter((q) => isAnswered(q)).length);
const unansweredCount = computed(() => questions.length - answeredCount.value);
const progressPct = computed(() => (questions.length ? Math.round((answeredCount.value / questions.length) * 100) : 0));
const raguCount = computed(() => flagged.size);

const navClass = (index) => {
	let cls = 'practice-nav-btn ';
	if (flagged.has(index)) cls += 'ragu';
	else if (isAnswered(questions[index])) cls += 'answered';
	else cls += 'unanswered';

	if (index === currentIndex.value) cls += ' current';

	return cls;
};

const renderMath = () => {
	if (window.renderMathInElement && questionPaneEl.value) {
		window.renderMathInElement(questionPaneEl.value, {
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

const onQuestionChange = async () => {
	await nextTick();
	renderMath();
	questionPaneEl.value?.scrollTo(0, 0);
};

const goTo = (index) => {
	currentIndex.value = index;
	onQuestionChange();
};

const prev = () => {
	currentIndex.value = Math.max(0, currentIndex.value - 1);
	onQuestionChange();
};

const next = () => {
	currentIndex.value = Math.min(questions.length - 1, currentIndex.value + 1);
	onQuestionChange();
};

const toggleFlag = () => {
	if (flagged.has(currentIndex.value)) {
		flagged.delete(currentIndex.value);
	} else {
		flagged.add(currentIndex.value);
	}
	saveDraft();
};

const selectAnswer = (question, value) => {
	answers[question.id] = value;
	saveDraft();
};

const openFinishModal = () => {
	showFinishModal.value = true;
};

const closeFinishModal = () => {
	showFinishModal.value = false;
};

const confirmFinish = () => {
	clearDraft();
	rootForm.value?.submit();
};

let noticeTimer = null;
let noticeEl = null;

onMounted(() => {
	loadDraft();
	renderMath();

	if (draftRestored) {
		noticeEl = document.createElement('div');
		noticeEl.className = 'fixed bottom-20 left-1/2 z-40 -translate-x-1/2 rounded-full bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-lg md:bottom-6';
		noticeEl.textContent = 'Pilihan jawaban terakhir Anda dipulihkan';
		document.body.appendChild(noticeEl);
		noticeTimer = setTimeout(() => {
			noticeEl?.remove();
			noticeEl = null;
		}, 5000);
	}
});

onBeforeUnmount(() => {
	if (noticeTimer) clearTimeout(noticeTimer);
	noticeEl?.remove();
	noticeEl = null;
});
</script>

<template>
	<Head title="Paket Latihan — Ujion" />

	<UjianLayout>
		<form ref="rootForm" method="POST" :action="route('materi.paket.submit', { paketNo: pkg.paket_no })" id="practice-package-form" class="practice-shell" @submit="clearDraft">
			<input type="hidden" name="_token" :value="csrfToken">

			<header class="flex items-center justify-between gap-2 bg-slate-900 px-4 py-2 text-white shadow-lg md:px-6 md:py-3" style="flex-shrink:0">
				<div class="flex min-w-0 items-center gap-2 md:gap-4">
					<Link :href="route('materi.dashboard')" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white hover:bg-white/15" title="Kembali">
						<i class="fa-solid fa-arrow-left"></i>
					</Link>
					<div class="min-w-0">
						<h1 class="truncate text-sm font-bold md:text-base">Paket {{ pkg.paket_no }}</h1>
						<p class="truncate text-[10px] text-slate-400 md:text-xs">
							{{ token.material?.sub_unit ?? 'Materi' }} &middot; {{ questions.length }} Soal
						</p>
					</div>
				</div>

				<div class="flex items-center gap-3">
					<div class="hidden text-right sm:block">
						<div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Peserta</div>
						<div class="max-w-[180px] truncate text-xs font-bold text-indigo-300 md:text-sm">{{ session.nama }}</div>
					</div>
					<button type="button" data-open-finish-modal class="rounded-xl bg-red-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-red-700 md:px-4 md:text-xs" @click="openFinishModal">
						Kumpulkan
					</button>
				</div>
			</header>

			<div class="practice-body">
				<main ref="questionPaneEl" class="practice-question-pane pb-32 px-4 py-6 md:px-12">
					<section
						v-for="(q, index) in questions"
						:key="q.id"
						class="practice-question-card"
						:class="{ active: index === currentIndex }"
						data-question-card
						:data-question-index="index"
					>
						<div v-if="q.reading_passage" class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
							<div class="mb-3 flex items-center gap-2">
								<div class="h-6 w-1 rounded-full bg-indigo-500"></div>
								<span class="text-xs font-bold uppercase tracking-widest text-slate-500">Teks Bacaan</span>
							</div>
							<div class="whitespace-pre-line text-sm leading-relaxed text-slate-700 md:text-base md:leading-8" v-html="sanitizeRichText(q.reading_passage)"></div>
						</div>

						<div class="mb-6 flex items-center justify-between gap-3">
							<div class="flex items-center gap-2">
								<span class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-bold text-white">Soal</span>
								<span data-current-number class="text-lg font-black text-slate-900 md:text-xl">{{ index + 1 }}</span>
								<span class="text-slate-400">/</span>
								<span class="text-sm font-bold text-slate-500">{{ questions.length }}</span>
							</div>
							<span class="max-w-[140px] truncate rounded-full bg-slate-100 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 md:max-w-none md:px-3 md:text-[10px]">
								#{{ q.id }}
							</span>
						</div>

						<div class="mb-5 text-base font-semibold leading-8 text-slate-900 md:text-lg" v-html="sanitizeRichText(q.question_text)"></div>

						<div v-if="Array.isArray(q.options) && q.options.length" class="grid grid-cols-1 gap-3 md:grid-cols-2 md:grid-flow-col md:grid-rows-2">
							<label v-for="(opt, idx) in q.options" :key="idx" class="practice-option">
								<input
									v-model="answers[q.id]"
									type="radio"
									:name="'answers[' + q.id + ']'"
									:value="opt"
									class="sr-only"
									data-practice-answer
									:data-question-index="index"
									required
									@change="selectAnswer(q, opt)"
								>
								<span class="practice-option-code">{{ optionLabel(idx) }}</span>
								<span class="text-sm leading-6 text-slate-700 md:text-[15px]" v-html="sanitizeRichText(opt)"></span>
							</label>
						</div>
						<div v-else class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">
							Pilihan jawaban belum tersedia.
						</div>
					</section>

					<div class="mt-10 flex items-center justify-between gap-2">
						<button
							type="button"
							id="practice-prev-btn"
							class="flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white py-3 text-[11px] font-bold text-slate-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm"
							:class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed' : ''"
							:disabled="currentIndex === 0"
							@click="prev"
						>
							<i class="fa-solid fa-arrow-left mr-1.5 md:mr-2"></i>Sebelumnya
						</button>
						<button
							type="button"
							id="practice-flag-btn"
							class="flex flex-1 items-center justify-center rounded-2xl border border-amber-200 bg-white py-3 text-[11px] font-bold text-amber-700 shadow-sm transition active:scale-95 md:flex-none md:px-8 md:text-sm"
							:class="{ 'bg-amber-200': flagged.has(currentIndex), 'ring-2': flagged.has(currentIndex), 'ring-amber-400': flagged.has(currentIndex) }"
							@click="toggleFlag"
						>
							<i class="fa-solid fa-flag mr-1.5 md:mr-2"></i>Ragu
						</button>
						<button
							type="button"
							id="practice-next-btn"
							class="flex flex-[1.2] items-center justify-center rounded-2xl bg-indigo-600 py-3 text-[11px] font-bold text-white shadow-lg transition active:scale-95 md:flex-none md:px-12 md:text-sm"
							:class="currentIndex === questions.length - 1 ? 'opacity-30 cursor-not-allowed' : ''"
							:disabled="currentIndex === questions.length - 1"
							@click="next"
						>
							Selanjutnya<i class="fa-solid fa-arrow-right ml-1.5 md:ml-2"></i>
						</button>
					</div>
				</main>

				<aside class="practice-sidebar-pane">
					<div class="mb-4">
						<div class="mb-1 flex items-center justify-between text-xs text-slate-500">
							<span>Dijawab</span>
							<span><span data-answered-count>{{ answeredCount }}</span>/<span>{{ questions.length }}</span></span>
						</div>
						<div class="h-2 rounded-full bg-slate-200">
							<div data-progress-bar class="h-2 rounded-full bg-indigo-500 transition-all" :style="{ width: progressPct + '%' }"></div>
						</div>
					</div>

					<div data-question-grid class="mb-5 grid grid-cols-5 gap-2">
						<button
							v-for="(q, index) in questions"
							:key="q.id"
							type="button"
							:class="navClass(index)"
							:title="`Soal ${index + 1}`"
							@click="goTo(index)"
						>
							{{ index + 1 }}
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

					<button type="button" data-open-finish-modal class="w-full rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700" @click="openFinishModal">
						Kumpulkan Paket
					</button>
				</aside>
			</div>

			<div class="practice-mobile-nav fixed bottom-0 left-0 right-0 z-10 border-t border-slate-200 bg-white px-4 py-2 shadow-xl">
				<div data-mobile-grid class="flex gap-1 overflow-x-auto pb-1">
					<button
						v-for="(q, index) in questions"
						:key="`m-${q.id}`"
						type="button"
						:class="navClass(index)"
						:title="`Soal ${index + 1}`"
						@click="goTo(index)"
					>
						{{ index + 1 }}
					</button>
				</div>
			</div>
		</form>

		<div id="practice-finish-modal" class="fixed inset-0 z-50 items-center justify-center bg-black/60 backdrop-blur-sm" :class="showFinishModal ? 'flex' : 'hidden'">
			<div class="mx-4 w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
				<h3 class="text-lg font-bold text-slate-900">Kumpulkan Paket?</h3>
				<div id="practice-finish-summary" class="mt-3 space-y-2 text-sm text-slate-600">
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
				<div class="mt-6 flex gap-3">
					<button type="button" id="practice-modal-cancel" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closeFinishModal">
						Kembali
					</button>
					<button type="button" id="practice-modal-confirm" class="flex-1 rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white hover:bg-red-700" @click="confirmFinish">
						Ya, Kumpulkan
					</button>
				</div>
			</div>
		</div>
	</UjianLayout>
</template>

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
