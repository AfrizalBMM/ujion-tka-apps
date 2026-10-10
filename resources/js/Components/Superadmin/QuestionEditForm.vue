<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue';
import { initSSD } from '@/core/ssd';

const props = defineProps({
	show: { type: Boolean, default: false },
	question: { type: Object, default: null },
	jenjangs: { type: Array, default: () => [] },
	materials: { type: Array, default: () => [] },
	csrfToken: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));

const materialFieldOrder = ['mapel', 'curriculum', 'subelement', 'unit', 'sub_unit'];
const materialPlaceholders = {
	mapel: 'Pilih mapel',
	curriculum: 'Pilih kurikulum',
	subelement: 'Pilih subelement',
	unit: 'Pilih unit',
	sub_unit: 'Pilih sub unit',
};

// Edit form state
const editId = ref(null);
const editJenjangId = ref('');
const editQuestionType = ref('multiple_choice');
const editReadingPassage = ref('');
const editQuestionText = ref('');
const editAnswerKey = ref('');
const editIsActive = ref('1');
const editExplanation = ref('');
const editOptions = ref(['', '', '', '']);

// Material picker state
const materialState = reactive({ mapel: '', curriculum: '', subelement: '', unit: '', sub_unit: '' });
const materialSearch = reactive({ mapel: '', curriculum: '', subelement: '', unit: '', sub_unit: '' });
const materialOpen = reactive({ mapel: false, curriculum: false, subelement: false, unit: false, sub_unit: false });

const materialData = computed(() =>
	props.materials.map((m) => ({
		mapel: m.mapel,
		curriculum: m.curriculum,
		subelement: m.subelement,
		unit: m.unit,
		sub_unit: m.sub_unit,
	}))
);

const getFilteredDataset = (fieldName) => {
	const idx = materialFieldOrder.indexOf(fieldName);
	return materialData.value.filter((item) =>
		materialFieldOrder.slice(0, idx).every((key) => !materialState[key] || item[key] === materialState[key])
	);
};

const getOptionsFor = (fieldName) => {
	const term = (materialSearch[fieldName] || '').trim().toLowerCase();
	const opts = [...new Set(getFilteredDataset(fieldName).map((item) => item[fieldName]).filter(Boolean))];
	return opts.filter((o) => o.toLowerCase().includes(term));
};

const materialFieldOptions = computed(() => {
	const result = {};
	materialFieldOrder.forEach((name) => {
		result[name] = getOptionsFor(name);
	});
	return result;
});

const closeAllMaterialDropdowns = () => {
	materialFieldOrder.forEach((name) => {
		materialOpen[name] = false;
	});
};

const selectMaterial = (fieldName, value) => {
	materialState[fieldName] = value;
	const idx = materialFieldOrder.indexOf(fieldName);
	materialFieldOrder.slice(idx + 1).forEach((key) => {
		materialState[key] = '';
		materialSearch[key] = '';
	});
	closeAllMaterialDropdowns();
	const nextField = materialFieldOrder[idx + 1];
	if (nextField && getOptionsFor(nextField).length > 0) {
		materialOpen[nextField] = true;
	}
};

const toggleMaterialDropdown = (fieldName) => {
	if (materialOpen[fieldName]) {
		materialOpen[fieldName] = false;
	} else {
		closeAllMaterialDropdowns();
		materialOpen[fieldName] = true;
	}
};

const resetMaterial = () => {
	materialFieldOrder.forEach((name) => {
		materialState[name] = '';
		materialSearch[name] = '';
	});
	closeAllMaterialDropdowns();
};

const addOption = () => {
	editOptions.value.push('');
};

const editAction = computed(() => {
	if (!editId.value) return '';
	return route('superadmin.global-questions.update', { globalQuestion: editId.value });
});

// Watch for question changes to populate form
watch(
	() => props.question,
	(q) => {
		if (!q) return;
		editId.value = q.id;
		editJenjangId.value = q.jenjang_id ?? '';
		editQuestionType.value = q.question_type ?? 'multiple_choice';
		editReadingPassage.value = q.reading_passage ?? '';
		editQuestionText.value = q.question_text ?? '';
		editAnswerKey.value = q.answer_key ?? '';
		editIsActive.value = q.is_active ? '1' : '0';
		editExplanation.value = q.explanation ?? '';
		editOptions.value = (q.options ?? []).length ? [...q.options] : ['', '', '', ''];

		// Material state
		materialState.mapel = q.material_mapel ?? q.material?.mapel ?? '';
		materialState.curriculum = q.material_curriculum ?? q.material?.curriculum ?? '';
		materialState.subelement = q.material_subelement ?? q.material?.subelement ?? '';
		materialState.unit = q.material_unit ?? q.material?.unit ?? '';
		materialState.sub_unit = q.material_sub_unit ?? q.material?.sub_unit ?? '';
		materialFieldOrder.forEach((name) => {
			materialSearch[name] = '';
		});
	},
	{ immediate: true }
);

// Re-init SSD when modal opens (SSD dropdowns need DOM to be present)
watch(
	() => props.show,
	async (show) => {
		if (show) {
			await nextTick();
			initSSD(document.getElementById('edit-question-modal'));
			await nextTick();
			const qText = document.getElementById('edit-question-text');
			if (qText) qText.focus();
		} else {
			closeAllMaterialDropdowns();
		}
	}
);

// Close on Escape
const onKeydown = (e) => {
	if (e.key === 'Escape' && props.show) {
		closeAllMaterialDropdowns();
		emit('close');
	}
};

document.addEventListener('keydown', onKeydown);

import { onBeforeUnmount } from 'vue';
onBeforeUnmount(() => {
	document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
	<div v-if="show" id="edit-question-modal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4" @click.self="emit('close')">
		<div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
			<div class="flex items-start justify-between gap-4">
				<div>
					<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
					<div class="mt-2 text-xl font-bold">Soal Global</div>
				</div>
				<button type="button" class="icon-button" aria-label="Tutup" @click="emit('close')"><i class="fa-solid fa-xmark"></i></button>
			</div>

			<form id="edit-question-form" method="POST" :action="editAction" class="mt-5 flex-1 space-y-4 overflow-y-auto pr-2">
				<input type="hidden" name="_token" :value="csrfToken" />
				<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang_id" :value="editJenjangId" required>
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ editJenjangId ? (jenjangs.find((j) => j.id == editJenjangId)?.nama || 'Pilih Jenjang') : 'Pilih Jenjang' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..." aria-label="Cari jenjang"></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="editJenjangId === '' ? 'ssd-selected' : ''" data-value="">Pilih Jenjang</div>
									<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :class="editJenjangId == jenjang.id ? 'ssd-selected' : ''" :data-value="jenjang.id">{{ jenjang.nama }}</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="question_type" :value="editQuestionType" required>
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ editQuestionType === 'multiple_choice' ? 'Pilihan Ganda' : editQuestionType === 'short_answer' ? 'Jawaban Singkat' : editQuestionType === 'matching' ? 'Menjodohkan' : 'Pilihan Ganda' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="editQuestionType === 'multiple_choice' ? 'ssd-selected' : ''" data-value="multiple_choice">Pilihan Ganda</div>
									<div class="ssd-option" :class="editQuestionType === 'short_answer' ? 'ssd-selected' : ''" data-value="short_answer">Jawaban Singkat</div>
									<div class="ssd-option" :class="editQuestionType === 'matching' ? 'ssd-selected' : ''" data-value="matching">Menjodohkan</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div>
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">
						Teks Bacaan <span class="font-normal italic text-muted">(opsional, khusus Pilihan Ganda)</span>
					</label>
					<textarea class="input mt-1" name="reading_passage" v-model="editReadingPassage" rows="3" placeholder="Teks bacaan konteks soal..."></textarea>
				</div>

				<!-- Material Picker -->
				<div class="space-y-3">
					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div class="relative" v-for="fieldName in ['mapel', 'curriculum']" :key="fieldName">
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">{{ fieldName === 'mapel' ? 'Mapel' : 'Materi Curriculum' }}</label>
							<input type="hidden" :name="`material_${fieldName}`" :value="materialState[fieldName]">
							<button type="button" class="input mt-1 flex w-full items-center justify-between text-left"
								@click="toggleMaterialDropdown(fieldName)">
								<span>{{ materialState[fieldName] || materialPlaceholders[fieldName] }}</span>
								<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
							</button>
							<div v-if="materialOpen[fieldName]" class="absolute left-0 right-0 top-full z-20 mt-2 rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
								<input type="text" class="input mb-2" :placeholder="`Cari ${materialPlaceholders[fieldName].replace('Pilih ', '')}...`" v-model="materialSearch[fieldName]">
								<div class="max-h-56 space-y-1 overflow-y-auto">
									<div v-if="materialFieldOptions[fieldName].length === 0" class="rounded-xl px-3 py-2 text-sm text-muted">Tidak ada data yang cocok.</div>
									<button v-for="option in materialFieldOptions[fieldName]" :key="option" type="button"
										class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800"
										:class="option === materialState[fieldName] ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200'"
										@click="selectMaterial(fieldName, option)">
										<span>{{ option }}</span>
										<i v-if="option === materialState[fieldName]" class="fa-solid fa-check text-xs"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div class="relative" v-for="fieldName in ['subelement', 'unit']" :key="fieldName">
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">{{ fieldName === 'subelement' ? 'Materi Subelement' : 'Materi Unit' }}</label>
							<input type="hidden" :name="`material_${fieldName}`" :value="materialState[fieldName]">
							<button type="button" class="input mt-1 flex w-full items-center justify-between text-left"
								@click="toggleMaterialDropdown(fieldName)">
								<span>{{ materialState[fieldName] || materialPlaceholders[fieldName] }}</span>
								<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
							</button>
							<div v-if="materialOpen[fieldName]" class="absolute left-0 right-0 top-full z-20 mt-2 rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
								<input type="text" class="input mb-2" :placeholder="`Cari ${materialPlaceholders[fieldName].replace('Pilih ', '')}...`" v-model="materialSearch[fieldName]">
								<div class="max-h-56 space-y-1 overflow-y-auto">
									<div v-if="materialFieldOptions[fieldName].length === 0" class="rounded-xl px-3 py-2 text-sm text-muted">Tidak ada data yang cocok.</div>
									<button v-for="option in materialFieldOptions[fieldName]" :key="option" type="button"
										class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800"
										:class="option === materialState[fieldName] ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200'"
										@click="selectMaterial(fieldName, option)">
										<span>{{ option }}</span>
										<i v-if="option === materialState[fieldName]" class="fa-solid fa-check text-xs"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div class="relative">
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Sub Unit</label>
							<input type="hidden" name="material_sub_unit" :value="materialState.sub_unit">
							<button type="button" class="input mt-1 flex w-full items-center justify-between text-left"
								@click="toggleMaterialDropdown('sub_unit')">
								<span>{{ materialState.sub_unit || materialPlaceholders.sub_unit }}</span>
								<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
							</button>
							<div v-if="materialOpen.sub_unit" class="absolute left-0 right-0 top-full z-20 mt-2 rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
								<input type="text" class="input mb-2" placeholder="Cari sub unit..." v-model="materialSearch.sub_unit">
								<div class="max-h-56 space-y-1 overflow-y-auto">
									<div v-if="materialFieldOptions.sub_unit.length === 0" class="rounded-xl px-3 py-2 text-sm text-muted">Tidak ada data yang cocok.</div>
									<button v-for="option in materialFieldOptions.sub_unit" :key="option" type="button"
										class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800"
										:class="option === materialState.sub_unit ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200'"
										@click="selectMaterial('sub_unit', option)">
										<span>{{ option }}</span>
										<i v-if="option === materialState.sub_unit" class="fa-solid fa-check text-xs"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
					<div class="flex items-center justify-between gap-3">
						<p class="text-[10px] italic text-muted">Pilih materi bertahap dari kurikulum sampai sub unit. Opsi akan otomatis mengerucut sesuai pilihan sebelumnya.</p>
						<button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" @click="resetMaterial">Kosongkan Materi</button>
					</div>
				</div>

				<div>
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
					<textarea class="input mt-1" name="question_text" v-model="editQuestionText" id="edit-question-text" rows="4" required></textarea>
				</div>

				<div>
					<div class="flex items-center justify-between gap-3">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban</label>
						<button type="button" class="btn-secondary px-3 py-2 text-xs" @click="addOption">
							<i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
						</button>
					</div>
					<div class="mt-2 space-y-2">
						<div v-for="(opt, idx) in editOptions" :key="idx" class="flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
							<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ optionLabels[idx] ?? `O${idx + 1}` }}</span>
							<input class="input border-0 bg-transparent px-0" name="options[]" :placeholder="`Tulis jawaban ${optionLabels[idx] ?? `O${idx + 1}`}`" v-model="editOptions[idx]">
						</div>
					</div>
				</div>

				<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
						<input class="input mt-1" name="answer_key" v-model="editAnswerKey" placeholder="A atau isi jawaban benar">
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="is_active" :value="editIsActive" required>
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ editIsActive === '1' ? 'Aktif (Publik)' : 'Draft (Sembunyi)' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="editIsActive === '1' ? 'ssd-selected' : ''" data-value="1">Aktif (Publik)</div>
									<div class="ssd-option" :class="editIsActive === '0' ? 'ssd-selected' : ''" data-value="0">Draft (Sembunyi)</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div>
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
					<textarea class="input mt-1" name="explanation" v-model="editExplanation" rows="2"></textarea>
				</div>

				<div class="flex flex-wrap gap-3">
					<button class="btn-primary" type="submit">Simpan Perubahan</button>
					<button class="btn-secondary" type="button" @click="emit('close')">Batal</button>
				</div>
			</form>
		</div>
	</div>
</template>
