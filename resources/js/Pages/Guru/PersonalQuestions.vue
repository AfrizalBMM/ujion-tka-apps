<script setup>
import { inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';
import PersonalQuestionFormFields from '@/Components/Guru/PersonalQuestionFormFields.vue';
import { initSSD, syncSSD } from '@/core/ssd';

const props = defineProps({
	questions: {
		type: Object,
		required: true,
	},
	categories: {
		type: Array,
		required: true,
	},
	userJenjang: {
		type: String,
		default: null,
	},
	filters: {
		type: Object,
		required: true,
	},
});

const route = inject('route');
const page = usePage();

const optionLabels = Array.from({ length: 26 }, (_, index) => String.fromCharCode('A'.charCodeAt(0) + index));

const rootEl = ref(null);
const filterForm = ref(null);
const q = ref(props.filters.q || '');
const kategori = ref(props.filters.kategori || '');
const tipe = ref(props.filters.tipe || '');
const isFiltering = ref(false);
const openModal = ref('');

const tipeLabel = () => ({
	PG: 'Pilihan Ganda',
	Checklist: 'Checklist',
	Singkat: 'Jawaban Singkat',
}[tipe.value] ?? 'Semua Tipe');

let timer = null;
let isComposing = false;

const applyFilters = () => {
	if (isFiltering.value || isComposing) return;

	const params = new URLSearchParams(new FormData(filterForm.value));
	const query = params.toString();

	isFiltering.value = true;

	router.get(`${route('guru.personal-questions')}${query ? `?${query}` : ''}`, {}, {
		preserveState: true,
		replace: true,
		onFinish: () => {
			isFiltering.value = false;
		},
	});
};

const scheduleApply = (delay = 350) => {
	if (isComposing) return;

	window.clearTimeout(timer);
	timer = window.setTimeout(applyFilters, delay);
};

const onQInput = (event) => {
	q.value = event.target.value;
	scheduleApply();
};

const onCompositionStart = () => {
	isComposing = true;
};

const onCompositionEnd = () => {
	isComposing = false;
	scheduleApply(150);
};

const onHiddenChange = () => {
	window.clearTimeout(timer);
	timer = window.setTimeout(applyFilters, 0);
};

const destroyQuestion = (question) => {
	router.post(route('guru.personal-questions.destroy', question.id), {}, { preserveScroll: true });
};

const buildOptionRow = (label, value = '') => {
	const row = document.createElement('div');
	row.className = 'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
	row.innerHTML = `
		<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
		<input name="options[]" class="input border-0 bg-transparent px-0" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
	`;

	return row;
};

const initQuestionForm = ({
	optionList,
	addButton,
	questionType,
	objectiveOptions,
	answerSelect,
	answerText,
	answerHelp,
}) => {
	if (!optionList || !addButton || !questionType || !objectiveOptions || !answerSelect || !answerText || !answerHelp) {
		return;
	}

	const getValues = () => Array.from(optionList.querySelectorAll('input[name="options[]"]')).map((input) => input.value);

	const syncAnswerOptions = () => {
		const values = getValues();
		const previous = answerSelect.getAttribute('data-current-answer') || answerSelect.value;
		const currentValue = previous || answerSelect.value;

		const ssdWrap = answerSelect.closest('.ssd-wrap');
		if (ssdWrap) {
			const ssdList = ssdWrap.querySelector('.ssd-list');
			if (ssdList) {
				let html = `<div class="ssd-option${!currentValue ? ' ssd-selected' : ''}" data-value="">Pilih jawaban benar</div>`;
				Array.from({ length: Math.min(values.length, 5) }, (_, index) => optionLabels[index]).forEach((label, index) => {
					const isSelected = currentValue === label;
					const text = values[index] ? `${label} - ${values[index]}` : label;
					html += `<div class="ssd-option${isSelected ? ' ssd-selected' : ''}" data-value="${label}">${text}</div>`;
				});
				ssdList.innerHTML = html;

				delete ssdWrap.dataset.ssdInit;
				initSSD(ssdWrap);
				syncSSD(answerSelect);
			}
		}
	};

	const syncTypeState = () => {
		const isObjective = ['PG', 'Checklist'].includes(questionType.value);
		objectiveOptions.classList.toggle('hidden', !isObjective);
		addButton.disabled = !isObjective;

		const ssdWrap = answerSelect.closest('.ssd-wrap');
		if (ssdWrap) {
			ssdWrap.classList.toggle('hidden', !isObjective);
		} else {
			answerSelect.classList.toggle('hidden', !isObjective);
		}

		answerText.classList.toggle('hidden', isObjective);
		answerSelect.disabled = !isObjective;
		answerText.disabled = isObjective;

		optionList.querySelectorAll('input[name="options[]"]').forEach((input) => {
			input.disabled = !isObjective;
		});

		answerHelp.textContent = isObjective
			? 'Pilih huruf jawaban yang benar sesuai opsi aktif.'
			: 'Isi jawaban teks singkat yang dianggap benar.';
	};

	addButton.addEventListener('click', () => {
		const values = getValues();
		if (values.length >= 5) return;
		optionList.appendChild(buildOptionRow(optionLabels[values.length] ?? `O${values.length + 1}`));
		syncAnswerOptions();
	});

	optionList.addEventListener('input', syncAnswerOptions);
	questionType.addEventListener('change', syncTypeState);
	questionType.addEventListener('input', syncTypeState);
	syncAnswerOptions();
	syncTypeState();
};

const initImagePreview = (input) => {
	const key = input.getAttribute('data-image-input');
	if (!key) return;

	const wrap = rootEl.value.querySelector(`[data-image-preview-wrap="${key}"]`);
	const image = rootEl.value.querySelector(`[data-image-preview="${key}"]`);
	if (!wrap || !image) return;

	input.addEventListener('change', () => {
		const file = input.files && input.files[0] ? input.files[0] : null;
		if (!file) return;
		if (file.size > 2 * 1024 * 1024) {
			alert('Ukuran gambar maksimal 2 MB.');
			input.value = '';
			return;
		}
		image.src = URL.createObjectURL(file);
		wrap.classList.remove('hidden');
	});
};

onMounted(() => {
	initQuestionForm({
		optionList: rootEl.value.querySelector('[data-option-list="guru-personal"]'),
		addButton: rootEl.value.querySelector('[data-option-add="guru-personal"]'),
		questionType: rootEl.value.querySelector('#guru-personal-question-type'),
		objectiveOptions: rootEl.value.querySelector('[data-objective-options]'),
		answerSelect: rootEl.value.querySelector('#guru-personal-answer-key-select'),
		answerText: rootEl.value.querySelector('#guru-personal-answer-key-text'),
		answerHelp: rootEl.value.querySelector('#guru-personal-answer-key-help'),
	});

	rootEl.value.querySelectorAll('[data-edit-personal-form]').forEach((form) => {
		initQuestionForm({
			optionList: form.querySelector('[data-edit-option-list]'),
			addButton: form.querySelector('[data-edit-option-add]'),
			questionType: form.querySelector('[data-edit-question-type]'),
			objectiveOptions: form.querySelector('[data-edit-objective-options]'),
			answerSelect: form.querySelector('[data-edit-answer-select]'),
			answerText: form.querySelector('[data-edit-answer-text]'),
			answerHelp: form.querySelector('[data-edit-answer-help]'),
		});
	});

	rootEl.value.querySelectorAll('[data-image-input]').forEach(initImagePreview);
});

onBeforeUnmount(() => {
	window.clearTimeout(timer);
});
</script>

<template>
	<Head title="Bank Soal Pribadi" />

	<GuruLayout>
		<div ref="rootEl">
			<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">Bank Soal</span>
				<div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
					<div>
						<h1 class="page-title">Bank Soal Pribadi</h1>
						<p class="page-description">Kelola soal-soal pribadi Anda untuk digunakan dalam ujian atau latihan. Tambahkan,
							filter, dan edit soal dengan mudah.</p>
					</div>
					<div class="grid gap-3 sm:grid-cols-2">
						<div class="hero-chip">
							<i class="fa-solid fa-database"></i>
							Soal Pribadi
						</div>
						<div class="hero-chip">
							<i class="fa-solid fa-layer-group"></i>
							Kategori & Tipe Soal
						</div>
					</div>
				</div>
				<div class="page-actions">
					<Link :href="route('guru.materials')"
						class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
						<i class="fa-solid fa-book"></i>
						Materi
					</Link>
				</div>
			</section>
			<form ref="filterForm" method="GET" :action="route('guru.personal-questions')" class="card p-4 space-y-4 sm:space-y-0 sm:flex sm:items-end sm:gap-4 mb-4" data-personal-questions-filter-form @submit.prevent="applyFilters" @compositionstart="onCompositionStart" @compositionend="onCompositionEnd">
				<div class="flex-1 min-w-[150px]">
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Pertanyaan</label>
					<input type="text" name="q" :value="q" class="input mt-1 w-full" placeholder="Cari pertanyaan..." data-live-search @input="onQInput">
				</div>
				<div class="flex-1 min-w-[150px]">
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kategori</label>
					<div class="ssd-wrap mt-1">
						<input type="hidden" name="kategori" :value="kategori" @change="onHiddenChange">
						<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
							<span class="ssd-label">{{ kategori || 'Semua Kategori' }}</span>
							<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
						</button>
						<div class="ssd-panel">
							<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kategori..."></div>
							<div class="ssd-list">
								<div class="ssd-option" :class="!kategori ? ' ssd-selected' : ''" data-value="">Semua Kategori</div>
								<div v-for="kategoriItem in categories" :key="kategoriItem" class="ssd-option" :class="kategori == kategoriItem ? ' ssd-selected' : ''" :data-value="kategoriItem">{{ kategoriItem }}</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex-1 min-w-[150px]">
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Tipe Soal</label>
					<div class="ssd-wrap mt-1">
						<input type="hidden" name="tipe" :value="tipe" @change="onHiddenChange">
						<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
							<span class="ssd-label">{{ tipeLabel() }}</span>
							<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
						</button>
						<div class="ssd-panel">
							<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari tipe..."></div>
							<div class="ssd-list">
								<div class="ssd-option" :class="!tipe ? ' ssd-selected' : ''" data-value="">Semua Tipe</div>
								<div class="ssd-option" :class="tipe === 'PG' ? ' ssd-selected' : ''" data-value="PG">Pilihan Ganda</div>
								<div class="ssd-option" :class="tipe === 'Checklist' ? ' ssd-selected' : ''" data-value="Checklist">Checklist</div>
								<div class="ssd-option" :class="tipe === 'Singkat' ? ' ssd-selected' : ''" data-value="Singkat">Jawaban Singkat</div>
							</div>
						</div>
					</div>
				</div>
				<Link :href="route('guru.personal-questions')"
					class="btn-secondary h-[42px] flex items-center justify-center">Reset</Link>
				<Link :href="route('guru.personal-questions.builder')"
					class="btn-primary h-[42px] flex items-center justify-center">Builder Soal Fullscreen</Link>
				<button class="btn-primary h-[42px] flex items-center justify-center" type="button"
					data-modal-open="modal-tambah-soal" @click="openModal = 'modal-tambah-soal'">
					<i class="fa-solid fa-plus mr-2"></i>Tambah Soal
				</button>
			</form>

			<!-- Modal Tambah Soal -->
			<div id="modal-tambah-soal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" :class="openModal === 'modal-tambah-soal' ? '' : 'hidden'" @click.self="openModal = ''">
				<div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto">
					<div class="bg-white rounded-lg shadow-lg p-6">
						<div class="flex items-center justify-between mb-4">
							<div class="font-bold text-lg">Tambah Soal Pribadi</div>
							<button class="text-gray-500 hover:text-gray-700" type="button" data-modal-close="modal-tambah-soal" @click="openModal = ''">
								<i class="fa-solid fa-times"></i>
							</button>
						</div>
					<form method="POST" :action="route('guru.personal-questions.store')" enctype="multipart/form-data">
						<input type="hidden" name="_token" :value="page.props.csrf_token">
							<PersonalQuestionFormFields
								:user-jenjang="userJenjang"
								:option-labels="optionLabels"
								mode-key="create"
								:is-edit-mode="false"
								:question-data="{
									tipe: 'PG',
									kategori: '',
									pertanyaan: '',
									opsi: ['', '', '', ''],
									jawaban_benar: '',
									pembahasan: '',
									status: 'draft',
									image_path: null,
								}"
								:image-preview-url="null"
							/>
							<button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Tambah Soal</button>
						</form>
					</div>
				</div>
			</div>
			<div class="card p-4">
				<Link :href="route('guru.personal-questions.builder')" class="btn-primary mb-4 w-full sm:w-auto">Builder Soal
					Fullscreen</Link>
				<div id="personal-questions-table-wrap" class="table-container" :class="isFiltering ? 'opacity-60 pointer-events-none' : ''">
					<table class="table-ujion w-full min-w-[620px]">
						<thead>
							<tr>
								<th>Soal</th>
								<th>Kategori</th>
								<th>Tipe</th>
								<th>Status</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="question in questions.data" :key="question.id">
								<td class="min-w-[260px]">
									<div class="space-y-2">
										<div class="flex flex-wrap items-center gap-2">
											<span class="badge-info">Soal Pribadi</span>
											<span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ question.jenjang }}</span>
										</div>
										<div class="text-sm font-semibold leading-6 text-slate-900 dark:text-slate-100">
											{{ question.pertanyaan_excerpt }}
										</div>
										<div v-if="question.image_path" class="text-[11px] text-slate-500">Memiliki lampiran gambar</div>
									</div>
								</td>
								<td>
									<span class="badge">{{ question.kategori }}</span>
								</td>
								<td>
									<span class="badge-warning">{{ question.tipe }}</span>
								</td>
								<td>
									<span :class="question.status === 'terbit' ? 'badge-success' : 'badge-warning'">{{ question.status }}</span>
								</td>
								<td class="flex flex-wrap gap-2">
									<button type="button" class="btn-secondary" :data-modal-open="`modal-edit-soal-${question.id}`" @click="openModal = `modal-edit-soal-${question.id}`">Edit</button>
									<form method="POST" :action="route('guru.personal-questions.destroy', question.id)" @submit.prevent="destroyQuestion(question)"><button
											class="btn-danger">Hapus</button></form>
								</td>
							</tr>
						</tbody>
					</table>

					<div v-if="questions.last_page > 1" class="mt-4">
						<PaginationLinks :paginator="questions" />
					</div>
				</div>
			</div>
		</div>

		<div
			v-for="question in questions.data"
			:id="`modal-edit-soal-${question.id}`"
			:key="`modal-edit-${question.id}`"
			class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
			:class="openModal === `modal-edit-soal-${question.id}` ? '' : 'hidden'"
			@click.self="openModal = ''"
		>
			<div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto">
				<div class="bg-white rounded-lg shadow-lg p-6">
					<div class="flex items-center justify-between mb-4">
						<div class="font-bold text-lg">Edit Soal Pribadi</div>
						<button class="text-gray-500 hover:text-gray-700" type="button" :data-modal-close="`modal-edit-soal-${question.id}`" @click="openModal = ''">
							<i class="fa-solid fa-times"></i>
						</button>
					</div>
					<form method="POST" :action="route('guru.personal-questions.update', question.id)" enctype="multipart/form-data" data-edit-personal-form>
						<input type="hidden" name="_token" :value="page.props.csrf_token">
						<PersonalQuestionFormFields
							:user-jenjang="userJenjang"
							:option-labels="optionLabels"
							:mode-key="`edit-${question.id}`"
							:is-edit-mode="true"
							:question-data="{
								tipe: question.tipe,
								kategori: question.kategori,
								pertanyaan: question.pertanyaan,
								opsi: question.opsi,
								jawaban_benar: question.jawaban_label,
								pembahasan: question.pembahasan,
								status: question.status,
								image_path: question.image_path,
							}"
							:image-preview-url="question.image_url"
						/>
						<button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Simpan Perubahan</button>
					</form>
				</div>
			</div>
			</div>
		</div>
	</GuruLayout>
</template>
