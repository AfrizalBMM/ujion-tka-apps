<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	globalQuestions: {
		type: Object,
		required: true,
	},
	materials: {
		type: Array,
		default: () => [],
	},
	jenjangs: {
		type: Array,
		default: () => [],
	},
	filters: {
		type: Object,
		default: () => ({}),
	},
});

const page = usePage();
const rootEl = ref(null);

const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));

const materialOptions = computed(() =>
	props.materials.map((material) => ({
		mapel: material.mapel,
		curriculum: material.curriculum,
		subelement: material.subelement,
		unit: material.unit,
		sub_unit: material.sub_unit,
	}))
);

const curriculumFilters = computed(() => [...new Set(props.materials.map((m) => m.curriculum).filter(Boolean))]);
const mapelFilters = computed(() => [...new Set(props.materials.map((m) => m.mapel).filter(Boolean))]);

const selectedJenjang = computed(() => props.jenjangs.find((j) => j.id == (props.filters.jenjang_id ?? '')));

const questionTypeLabel = (type) => {
	switch (type) {
		case 'multiple_choice':
			return 'Pilihan Ganda';
		case 'matching':
			return 'Menjodohkan';
		case 'short_answer':
			return 'Jawaban Singkat';
		default:
			return 'Semua Jenis';
	}
};

const statusLabel = (status) => {
	switch (status) {
		case 'active':
			return 'Aktif';
		case 'draft':
			return 'Draft';
		default:
			return 'Semua Status';
	}
};

const typeBadge = (question) => (question.question_type === 'matching' ? 'Menjodohkan' : String(question.question_type).replaceAll('_', ' '));

const editPayloadFor = (q) => ({
	id: q.id,
	question_type: q.question_type,
	reading_passage: q.reading_passage,
	question_text: q.question_text,
	material_mapel: q.material_mapel ?? q.material?.mapel,
	material_curriculum: q.material_curriculum ?? q.material?.curriculum,
	material_subelement: q.material_subelement ?? q.material?.subelement,
	material_unit: q.material_unit ?? q.material?.unit,
	material_sub_unit: q.material_sub_unit ?? q.material?.sub_unit,
	options: q.options ?? [],
	answer_key: q.answer_key,
	explanation: q.explanation,
	is_active: q.is_active ? '1' : '0',
	jenjang_id: q.jenjang_id,
});

const hasPages = computed(() => (props.globalQuestions.last_page ?? 1) > 1);
const pageElements = computed(() =>
	(props.globalQuestions.links ?? []).filter((link) => /^\d+$/.test(String(link.label)) || String(link.label) === '...')
);

let cleanupFns = [];

onMounted(() => {
	const root = rootEl.value;
	if (!root) return;

	const materialData = materialOptions.value;
	const materialFieldOrder = ['mapel', 'curriculum', 'subelement', 'unit', 'sub_unit'];
	const updateRouteTemplate = route('superadmin.global-questions.update', { globalQuestion: '__ID__' });

	const editModal = document.getElementById('edit-question-modal');
	const form = document.getElementById('edit-question-form');
	const createOptionList = root.querySelector('[data-option-list="create"]');
	const editOptionList = root.querySelector('[data-option-list="edit"]');
	const modals = Array.from(root.querySelectorAll('[id$="-modal"]'));
	const materialPickers = new Map();

	const fields = {
		questionType: document.getElementById('edit-question-type'),
		questionText: document.getElementById('edit-question-text'),
		answerKey: document.getElementById('edit-answer-key'),
		isActive: document.getElementById('edit-is-active'),
		explanation: document.getElementById('edit-explanation'),
		readingPassage: document.getElementById('edit-reading-passage'),
	};

	const importDropdownButton = document.getElementById('import-dropdown-btn');
	const importDropdownMenu = document.getElementById('import-dropdown-menu');

	importDropdownButton?.addEventListener('click', (event) => {
		event.stopPropagation();
		importDropdownMenu?.classList.toggle('hidden');
	});

	const onDocumentClickCloseImport = (event) => {
		if (!(event.target instanceof Element) || event.target.closest('#import-dropdown-wrapper')) {
			return;
		}

		importDropdownMenu?.classList.add('hidden');
	};

	document.addEventListener('click', onDocumentClickCloseImport);
	cleanupFns.push(() => document.removeEventListener('click', onDocumentClickCloseImport));

	const openModal = (modal) => {
		if (!modal) return;
		modal.classList.remove('hidden');
		modal.classList.add('flex');
	};

	const closeModal = (modal) => {
		if (!modal) return;
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	};

	const closeAllMaterialDropdowns = () => {
		materialPickers.forEach((picker) => {
			Object.values(picker.fields).forEach((field) => field.dropdown.classList.add('hidden'));
		});
	};

	const buildMaterialPicker = (container) => {
		const fieldMap = {};

		materialFieldOrder.forEach((name) => {
			const fieldRoot = container.querySelector(`[data-material-field="${name}"]`);
			if (!fieldRoot) return;

			fieldMap[name] = {
				root: fieldRoot,
				valueInput: fieldRoot.querySelector('[data-material-value]'),
				trigger: fieldRoot.querySelector('[data-material-trigger]'),
				label: fieldRoot.querySelector('[data-material-label]'),
				dropdown: fieldRoot.querySelector('[data-material-dropdown]'),
				search: fieldRoot.querySelector('[data-material-search]'),
				options: fieldRoot.querySelector('[data-material-options]'),
			};
		});

		const picker = {
			container,
			fields: fieldMap,
			state: {
				mapel: '',
				curriculum: '',
				subelement: '',
				unit: '',
				sub_unit: '',
			},
		};

		const getFilteredDataset = (fieldName) => {
			const fieldIndex = materialFieldOrder.indexOf(fieldName);

			return materialData.filter((item) => {
				return materialFieldOrder.slice(0, fieldIndex).every((key) => !picker.state[key] || item[key] === picker.state[key]);
			});
		};

		const getOptions = (fieldName) => {
			const searchTerm = picker.fields[fieldName].search.value.trim().toLowerCase();
			const options = [...new Set(getFilteredDataset(fieldName).map((item) => item[fieldName]).filter(Boolean))];

			return options.filter((option) => option.toLowerCase().includes(searchTerm));
		};

		const updateFieldUI = (fieldName) => {
			const field = picker.fields[fieldName];
			const options = getOptions(fieldName);
			const selectedValue = picker.state[fieldName];
			const placeholderMap = {
				mapel: 'Pilih mapel',
				curriculum: 'Pilih kurikulum',
				subelement: 'Pilih subelement',
				unit: 'Pilih unit',
				sub_unit: 'Pilih sub unit',
			};

			field.valueInput.value = selectedValue || '';
			field.label.textContent = selectedValue || placeholderMap[fieldName];
			field.trigger.disabled = options.length === 0 && !selectedValue;
			field.trigger.classList.toggle('cursor-not-allowed', field.trigger.disabled);
			field.trigger.classList.toggle('opacity-60', field.trigger.disabled);
			field.options.innerHTML = '';

			if (options.length === 0) {
				const emptyState = document.createElement('div');
				emptyState.className = 'rounded-xl px-3 py-2 text-sm text-muted';
				emptyState.textContent = 'Tidak ada data yang cocok.';
				field.options.appendChild(emptyState);
				return;
			}

			options.forEach((option) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = `flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800 ${option === selectedValue ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200'}`;
				button.innerHTML = `<span>${option}</span>${option === selectedValue ? '<i class="fa-solid fa-check text-xs"></i>' : ''}`;
				button.addEventListener('click', () => {
					picker.state[fieldName] = option;

					const currentIndex = materialFieldOrder.indexOf(fieldName);
					materialFieldOrder.slice(currentIndex + 1).forEach((key) => {
						picker.state[key] = '';
						picker.fields[key].search.value = '';
					});

					refreshPicker();

					const nextFieldName = materialFieldOrder[currentIndex + 1];
					if (nextFieldName) {
						openFieldDropdown(nextFieldName);
					} else {
						closeAllMaterialDropdowns();
					}
				});
				field.options.appendChild(button);
			});
		};

		const refreshPicker = () => {
			materialFieldOrder.forEach((name) => updateFieldUI(name));
		};

		const openFieldDropdown = (fieldName) => {
			closeAllMaterialDropdowns();
			const field = picker.fields[fieldName];
			if (!field || field.trigger.disabled) return;
			field.dropdown.classList.remove('hidden');
			field.search.focus();
			field.search.select();
		};

		materialFieldOrder.forEach((fieldName) => {
			const field = picker.fields[fieldName];
			field.trigger.addEventListener('click', () => {
				if (field.dropdown.classList.contains('hidden')) {
					openFieldDropdown(fieldName);
				} else {
					field.dropdown.classList.add('hidden');
				}
			});
			field.search.addEventListener('input', () => updateFieldUI(fieldName));
		});

		container.querySelector('[data-material-reset]')?.addEventListener('click', () => {
			materialFieldOrder.forEach((name) => {
				picker.state[name] = '';
				picker.fields[name].search.value = '';
			});
			refreshPicker();
			closeAllMaterialDropdowns();
		});

		refreshPicker();

		picker.setValues = (values = {}) => {
			materialFieldOrder.forEach((name) => {
				picker.state[name] = values[name] || '';
				picker.fields[name].search.value = '';
			});
			refreshPicker();
		};

		return picker;
	};

	const renderOptionFields = (container, values = []) => {
		const entries = values.length ? values : ['', '', '', ''];
		container.innerHTML = '';

		entries.forEach((value, index) => {
			const label = optionLabels[index] ?? `O${index + 1}`;
			const row = document.createElement('div');
			row.className = 'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
			row.innerHTML = `
				<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
				<input class="input border-0 bg-transparent px-0" name="options[]" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
			`;
			container.appendChild(row);
		});
	};

	const appendOptionField = (container) => {
		const values = Array.from(container.querySelectorAll('input[name="options[]"]')).map((input) => input.value);
		values.push('');
		renderOptionFields(container, values);
	};

	if (createOptionList) {
		renderOptionFields(createOptionList);
	}

	root.querySelectorAll('[data-material-picker]').forEach((container) => {
		const picker = buildMaterialPicker(container);
		materialPickers.set(container.dataset.materialPicker, picker);
	});

	root.querySelectorAll('[data-open-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			if (button.dataset.openModal === 'create-question-modal') {
				materialPickers.get('create')?.setValues();
			}
			openModal(document.getElementById(button.dataset.openModal));
		});
	});

	root.querySelectorAll('[data-close-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			closeAllMaterialDropdowns();
			closeModal(document.getElementById(button.dataset.closeModal));
		});
	});

	modals.forEach((modal) => {
		modal.addEventListener('click', (event) => {
			if (event.target === modal) {
				closeAllMaterialDropdowns();
				closeModal(modal);
			}
		});
	});

	const onDocumentClickCloseMaterial = (event) => {
		if (!(event.target instanceof Element) || event.target.closest('[data-material-field]')) {
			return;
		}
		closeAllMaterialDropdowns();
	};

	document.addEventListener('click', onDocumentClickCloseMaterial);
	cleanupFns.push(() => document.removeEventListener('click', onDocumentClickCloseMaterial));

	const onKeydown = (event) => {
		if (event.key === 'Escape') {
			closeAllMaterialDropdowns();
			modals.forEach((modal) => {
				if (!modal.classList.contains('hidden')) {
					closeModal(modal);
				}
			});
		}
	};

	document.addEventListener('keydown', onKeydown);
	cleanupFns.push(() => document.removeEventListener('keydown', onKeydown));

	root.querySelectorAll('[data-edit-question]').forEach((button) => {
		button.addEventListener('click', () => {
			const raw = button.getAttribute('data-edit-question');
			if (!raw || !form || !updateRouteTemplate) return;

			const data = JSON.parse(raw);
			form.action = updateRouteTemplate.replace('__ID__', data.id);
			const jenjangInput = document.getElementById('edit-jenjang-id');
			if (jenjangInput) {
				jenjangInput.value = data.jenjang_id ?? '';
				jenjangInput.dispatchEvent(new Event('change'));
			}
			if (fields.questionType) {
				fields.questionType.value = data.question_type ?? 'multiple_choice';
				fields.questionType.dispatchEvent(new Event('change'));
			}
			materialPickers.get('edit')?.setValues({
				mapel: data.material_mapel ?? '',
				curriculum: data.material_curriculum ?? '',
				subelement: data.material_subelement ?? '',
				unit: data.material_unit ?? '',
				sub_unit: data.material_sub_unit ?? '',
			});
			if (fields.questionText) fields.questionText.value = data.question_text ?? '';
			if (fields.readingPassage) fields.readingPassage.value = data.reading_passage ?? '';
			if (editOptionList) renderOptionFields(editOptionList, data.options ?? []);
			if (fields.answerKey) fields.answerKey.value = data.answer_key ?? '';
			if (fields.isActive) {
				fields.isActive.value = data.is_active ?? '1';
				fields.isActive.dispatchEvent(new Event('change'));
			}
			if (fields.explanation) fields.explanation.value = data.explanation ?? '';
			openModal(editModal);
			fields.questionText?.focus();
		});
	});

	root.querySelectorAll('.toggle-reading-passage').forEach((button) => {
		button.addEventListener('click', () => {
			const target = document.getElementById(button.dataset.target);
			const chevron = button.querySelector('[data-rp-chevron]');
			target?.classList.toggle('hidden');
			chevron?.classList.toggle('rotate-180');
		});
	});

	root.querySelectorAll('.toggle-explanation').forEach((button) => {
		button.addEventListener('click', () => {
			const target = document.getElementById(button.dataset.target);
			const chevron = button.querySelector('[data-ex-chevron]');
			target?.classList.toggle('hidden');
			chevron?.classList.toggle('rotate-180');
		});
	});

	root.querySelectorAll('[data-option-add]').forEach((button) => {
		button.addEventListener('click', () => {
			const target = button.dataset.optionAdd === 'edit' ? editOptionList : createOptionList;
			if (target) appendOptionField(target);
		});
	});

	const filterForm = document.getElementById('filter-form');
	if (filterForm) {
		let debounceTimer;
		const searchInput = filterForm.querySelector('input[name="search"]');
		if (searchInput) {
			searchInput.addEventListener('input', () => {
				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(() => {
					filterForm.submit();
				}, 500);
			});
		}
	}
});

onBeforeUnmount(() => {
	cleanupFns.forEach((fn) => fn());
	cleanupFns = [];
});
</script>

<template>
	<Head title="Global Bank Soal" />

	<SuperadminLayout>
		<div ref="rootEl" class="space-y-6">
			<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
				<div>
					<h1 class="text-2xl font-bold">Bank Soal Global</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Kelola kumpulan soal yang dapat diakses oleh seluruh guru di platform Ujion.</p>
				</div>
				<div class="flex flex-wrap gap-3">
					<div v-if="!filters.jenjang_id" class="relative" id="import-dropdown-wrapper">
						<button class="btn-secondary" type="button" id="import-dropdown-btn">
							<i class="fa-solid fa-file-import mr-2"></i> Import Soal
							<i class="fa-solid fa-chevron-down ml-2 text-xs"></i>
						</button>
						<div id="import-dropdown-menu"
							 class="absolute right-0 top-full z-30 mt-2 hidden w-52 rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
							<button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
									data-open-modal="import-pg-modal">
								<i class="fa-solid fa-list-check w-4 text-blue-500"></i> Pilihan Ganda
							</button>
							<button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
									data-open-modal="import-menjodohkan-modal">
								<i class="fa-solid fa-shuffle w-4 text-amber-500"></i> Menjodohkan
							</button>
						</div>
					</div>
					<button class="btn-primary" type="button" data-open-modal="create-question-modal">
						<i class="fa-solid fa-plus mr-2"></i> Input Soal Baru
					</button>
				</div>
			</div>

			<div class="card">
				<form id="filter-form" data-ssd-autosubmit method="GET" :action="route('superadmin.global-questions.index')" class="grid grid-cols-1 gap-3 lg:grid-cols-6">
					<div class="lg:col-span-2">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
						<input class="input mt-1" type="text" name="search" :value="filters.search ?? ''" placeholder="Cari pertanyaan, kunci, pembahasan, atau materi...">
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="question_type" :value="filters.question_type ?? ''">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ questionTypeLabel(filters.question_type ?? '') }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="(filters.question_type ?? '') === '' ? 'ssd-selected' : ''" data-value="">Semua Jenis</div>
									<div class="ssd-option" :class="(filters.question_type ?? '') === 'multiple_choice' ? 'ssd-selected' : ''" data-value="multiple_choice">Pilihan Ganda</div>
									<div class="ssd-option" :class="(filters.question_type ?? '') === 'matching' ? 'ssd-selected' : ''" data-value="matching">Menjodohkan</div>
									<div class="ssd-option" :class="(filters.question_type ?? '') === 'short_answer' ? 'ssd-selected' : ''" data-value="short_answer">Jawaban Singkat</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="status" :value="filters.status ?? ''">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ statusLabel(filters.status ?? '') }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="(filters.status ?? '') === '' ? 'ssd-selected' : ''" data-value="">Semua Status</div>
									<div class="ssd-option" :class="(filters.status ?? '') === 'active' ? 'ssd-selected' : ''" data-value="active">Aktif</div>
									<div class="ssd-option" :class="(filters.status ?? '') === 'draft' ? 'ssd-selected' : ''" data-value="draft">Draft</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mapel</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="material_mapel" :value="filters.material_mapel ?? ''">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ filters.material_mapel || 'Semua Mapel' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="(filters.material_mapel ?? '') === '' ? 'ssd-selected' : ''" data-value="">Semua Mapel</div>
									<div v-for="m in mapelFilters" :key="m" class="ssd-option" :class="(filters.material_mapel ?? '') === m ? 'ssd-selected' : ''" :data-value="m">{{ m }}</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="material_curriculum" :value="filters.material_curriculum ?? ''">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ filters.material_curriculum || 'Semua Kurikulum' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="(filters.material_curriculum ?? '') === '' ? 'ssd-selected' : ''" data-value="">Semua Kurikulum</div>
									<div v-for="curriculum in curriculumFilters" :key="curriculum" class="ssd-option" :class="(filters.material_curriculum ?? '') === curriculum ? 'ssd-selected' : ''" :data-value="curriculum">{{ curriculum }}</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang_id" :value="filters.jenjang_id ?? ''">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ selectedJenjang ? selectedJenjang.nama : 'Semua Jenjang' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="(filters.jenjang_id ?? '') === '' ? 'ssd-selected' : ''" data-value="">Semua Jenjang</div>
									<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :class="(filters.jenjang_id ?? '') == jenjang.id ? 'ssd-selected' : ''" :data-value="jenjang.id">{{ jenjang.nama }}</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Tampilkan</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="per_page" :value="filters.per_page ?? '10'">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ filters.per_page ?? '10' }} Baris</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-list">
									<div class="ssd-option" :class="(filters.per_page ?? '10') == '10' ? 'ssd-selected' : ''" data-value="10">10 Baris</div>
									<div class="ssd-option" :class="(filters.per_page ?? '10') == '20' ? 'ssd-selected' : ''" data-value="20">20 Baris</div>
									<div class="ssd-option" :class="(filters.per_page ?? '10') == '30' ? 'ssd-selected' : ''" data-value="30">30 Baris</div>
									<div class="ssd-option" :class="(filters.per_page ?? '10') == '50' ? 'ssd-selected' : ''" data-value="50">50 Baris</div>
								</div>
							</div>
						</div>
					</div>
					<div class="flex flex-wrap items-end gap-3 lg:col-span-6">

						<a class="btn-secondary" :href="route('superadmin.global-questions.index')">
							Reset
						</a>
					</div>
				</form>
			</div>

			<div class="card min-h-[400px]">
				<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
					<div class="font-bold text-lg">Daftar Soal Global ({{ globalQuestions.total }})</div>
					<div class="flex flex-col gap-2 sm:flex-row sm:items-center">
						<form v-if="globalQuestions.data.length > 0" method="POST" :action="route('superadmin.global-questions.destroyAll')" onsubmit="return false;" id="delete-all-global-questions-form">
							<input type="hidden" name="_token" :value="page.props.csrf_token" />
							<button
								type="submit"
								class="btn-danger whitespace-nowrap"
								data-confirm-title="Hapus Semua Bank Soal?"
								data-confirm="Semua bank soal global akan dihapus permanen. Tindakan ini tidak bisa dibatalkan. Lanjutkan?"
								data-confirm-require-text="HAPUS SEMUA"
								data-confirm-prompt-label="Ketik 'HAPUS SEMUA' untuk konfirmasi"
								data-confirm-prompt-placeholder="HAPUS SEMUA"
								title="Hapus Semua Bank Soal"
							>
								<i class="fa-solid fa-trash"></i>
							</button>
						</form>
					</div>
				</div>

				<div class="space-y-4">
					<template v-for="q in globalQuestions.data" :key="q.id">
						<div class="rounded-card border border-border bg-white p-4 transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900">
							<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
								<div class="flex-1">
									<div class="mb-2 flex flex-wrap items-center gap-3">
										<span class="badge-info text-[10px] font-bold uppercase tracking-wider">
											{{ typeBadge(q) }}
										</span>
										<span v-if="q.jenjang" class="badge-primary bg-indigo-100 text-indigo-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full">
											{{ q.jenjang.nama }}
										</span>
										<span v-if="q.material" class="flex items-center gap-1 text-[10px] text-muted">
											<i class="fa-solid fa-book text-[8px]"></i> {{ q.material.mapel }} | {{ q.material.sub_unit }}
										</span>
										<span v-else-if="q.material_sub_unit" class="flex items-center gap-1 text-[10px] text-muted">
											<i class="fa-solid fa-book text-[8px]"></i> {{ q.material_mapel }} | {{ q.material_sub_unit }}
										</span>
										<span v-if="q.is_active" class="text-[10px] font-bold text-green-500">ACTIVE</span>
										<span v-else class="text-[10px] font-bold text-slate-400">DRAFT</span>
									</div>

									<div class="font-medium leading-relaxed text-slate-800 dark:text-slate-200">
										{{ q.question_text }}
									</div>

									<div v-if="q.reading_passage" class="mt-2">
										<button type="button"
												class="toggle-reading-passage flex items-center gap-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
												:data-target="`rp-${q.id}`">
											<i class="fa-solid fa-book-open"></i> Lihat Teks Bacaan
											<i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-rp-chevron></i>
										</button>
										<div :id="`rp-${q.id}`" class="hidden mt-2">
											<div class="rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300">
												{{ q.reading_passage }}
											</div>
										</div>
									</div>

									<div v-if="q.options && q.question_type !== 'matching'" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
										<div v-for="(opt, idx) in q.options" :key="idx" class="rounded border p-2 text-xs dark:border-slate-700" :class="q.answer_key == opt ? 'border-emerald-200 bg-emerald-100/60 font-bold text-emerald-900 ring-1 ring-emerald-300 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-slate-100 bg-slate-50 dark:bg-slate-800'">
											<span class="mr-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/80 text-[10px] font-bold text-slate-700 dark:bg-slate-900/80 dark:text-slate-200">{{ optionLabels[idx] ?? `O${idx + 1}` }}</span>
											{{ opt }}
										</div>
									</div>

									<div v-if="q.answer_key" class="mt-3 text-xs">
										<span class="font-bold text-blue-600">Kunci:</span> {{ q.answer_key }}
									</div>

									<div v-if="q.explanation" class="mt-2 text-xs">
										<button type="button"
												class="toggle-explanation flex items-center gap-2 font-semibold text-emerald-600 hover:text-emerald-700"
												:data-target="`ex-${q.id}`">
											<i class="fa-solid fa-lightbulb"></i> Lihat Pembahasan
											<i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-ex-chevron></i>
										</button>
										<div :id="`ex-${q.id}`" class="hidden mt-2">
											<div class="rounded-xl bg-emerald-50/70 p-3 italic leading-relaxed text-slate-700 dark:bg-emerald-900/10 dark:text-slate-300">
												{{ q.explanation }}
											</div>
										</div>
									</div>
								</div>

								<div class="flex flex-row gap-2 lg:flex-col">
									<form method="POST" :action="route('superadmin.global-questions.destroy', q.id)">
										<input type="hidden" name="_token" :value="page.props.csrf_token" />
										<button class="btn-danger p-2" type="submit" data-confirm="Hapus soal ini dari bank soal?" title="Hapus">
											<i class="fa-solid fa-trash-can"></i>
										</button>
									</form>

									<button
										class="btn-secondary p-2"
										type="button"
										title="Edit"
										:data-edit-question="JSON.stringify(editPayloadFor(q))"
									>
										<i class="fa-solid fa-pen-to-square"></i>
									</button>
								</div>
							</div>
						</div>
					</template>
					<div v-if="globalQuestions.data.length === 0" class="flex flex-col items-center py-20 text-center">
						<div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50">
							<i class="fa-solid fa-database text-3xl text-slate-200"></i>
						</div>
						<span class="italic text-muted dark:text-slate-400">Belum ada soal global yang tersedia. Mulai dengan membuat soal pertama atau import Excel/CSV.</span>
					</div>
				</div>

				<div v-if="hasPages" class="mt-6">
					<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
						<div class="flex justify-between flex-1 sm:hidden">
							<Link v-if="globalQuestions.prev_page_url" :href="globalQuestions.prev_page_url" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-600 transition ease-in-out duration-150">
								Sebelumnya
							</Link>
							<span v-else class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
								Sebelumnya
							</span>

							<Link v-if="globalQuestions.next_page_url" :href="globalQuestions.next_page_url" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-600 transition ease-in-out duration-150">
								Berikutnya
							</Link>
							<span v-else class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
								Berikutnya
							</span>
						</div>

						<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
							<div>
								<p class="text-sm text-gray-700 leading-5">
									Menampilkan
									<span class="font-medium">{{ globalQuestions.from }}</span>
									sampai
									<span class="font-medium">{{ globalQuestions.to }}</span>
									dari
									<span class="font-medium">{{ globalQuestions.total }}</span>
									hasil
								</p>
							</div>

							<div>
								<span class="relative z-0 inline-flex shadow-sm rounded-md">
									<span v-if="!globalQuestions.prev_page_url" aria-disabled="true" aria-label="pagination.previous">
										<span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-l-md leading-5" aria-hidden="true">
											<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
												<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
											</svg>
										</span>
									</span>
									<Link v-else :href="globalQuestions.prev_page_url" rel="prev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-500 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150" aria-label="pagination.previous">
										<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
										</svg>
									</Link>

									<template v-for="(link, i) in pageElements" :key="i">
										<span v-if="!link.url" aria-disabled="true" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5">
											{{ link.label }}
										</span>
										<span v-else-if="link.active" aria-current="page">
											<span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5">{{ link.label }}</span>
										</span>
										<Link v-else :href="link.url" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150">
											{{ link.label }}
										</Link>
									</template>

									<span v-if="!globalQuestions.next_page_url" aria-disabled="true" aria-label="pagination.next">
										<span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-r-md leading-5" aria-hidden="true">
											<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
												<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
											</svg>
										</span>
									</span>
									<Link v-else :href="globalQuestions.next_page_url" rel="next" class="relative inline-flex items-center px-2 py-2 -ml-px rounded-r-md border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-500 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150" aria-label="pagination.next">
										<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
										</svg>
									</Link>
								</span>
							</div>
						</div>
					</nav>
				</div>
			</div>
		</div>

		<div id="create-question-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4">
			<div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Baru</div>
						<div class="mt-2 text-xl font-bold">Input Soal Global</div>
					</div>
					<button type="button" class="icon-button" data-close-modal="create-question-modal"><i class="fa-solid fa-xmark"></i></button>
				</div>

				<form class="mt-5 flex-1 space-y-4 overflow-y-auto pr-2" method="POST" :action="route('superadmin.global-questions.store')">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="jenjang_id" id="create-jenjang-id" value="" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Pilih Jenjang</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="">Pilih Jenjang</div>
										<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :data-value="jenjang.id">{{ jenjang.nama }}</div>
									</div>
								</div>
							</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="question_type" id="create-question-type" value="multiple_choice" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Pilihan Ganda</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="multiple_choice">Pilihan Ganda</div>
										<div class="ssd-option" data-value="short_answer">Jawaban Singkat</div>
										<div class="ssd-option" data-value="matching">Menjodohkan</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div id="create-reading-passage-wrapper">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">
							Teks Bacaan <span class="font-normal italic text-muted">(opsional, khusus Pilihan Ganda)</span>
						</label>
						<textarea class="input mt-1" name="reading_passage" rows="3"
								  placeholder="Isi teks/wacana bacaan yang menjadi konteks soal ini. Kosongkan jika tidak ada."></textarea>
					</div>
					<div class="space-y-3" data-material-picker="create">
						<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
							<div class="relative" data-material-field="mapel">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mapel</label>
								<input type="hidden" name="material_mapel" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih mapel</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari mapel..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
							<div class="relative" data-material-field="curriculum">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Curriculum</label>
								<input type="hidden" name="material_curriculum" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih kurikulum</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari kurikulum..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
						</div>
						<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
							<div class="relative" data-material-field="subelement">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Subelement</label>
								<input type="hidden" name="material_subelement" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih subelement</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari subelement..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
							<div class="relative" data-material-field="unit">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Unit</label>
								<input type="hidden" name="material_unit" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih unit</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari unit..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
						</div>
						<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
							<div class="relative" data-material-field="sub_unit">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Sub Unit</label>
								<input type="hidden" name="material_sub_unit" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih sub unit</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari sub unit..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
						</div>
						<div class="flex items-center justify-between gap-3">
							<p class="text-[10px] italic text-muted">Pilih materi bertahap dari kurikulum sampai sub unit. Opsi akan otomatis mengerucut sesuai pilihan sebelumnya.</p>
							<button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" data-material-reset>Kosongkan Materi</button>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
						<textarea class="input mt-1" name="question_text" rows="4" required placeholder="Apa rukun islam yang kedua?"></textarea>
					</div>

					<div>
						<div class="flex items-center justify-between gap-3">
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban</label>
							<button type="button" class="btn-secondary px-3 py-2 text-xs" data-option-add="create">
								<i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
							</button>
						</div>
						<div class="mt-2 space-y-2" data-option-list="create"></div>
						<p class="mt-1 text-[10px] italic text-muted">Untuk pilihan ganda, isi opsi satu per baris input. Kunci jawaban bisa diisi huruf seperti `A` atau isi jawabannya.</p>
					</div>

					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
							<input class="input mt-1" name="answer_key" placeholder="A atau isi jawaban benar">
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="is_active" value="1" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Aktif (Publik)</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="1">Aktif (Publik)</div>
										<div class="ssd-option" data-value="0">Draft (Sembunyi)</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
						<textarea class="input mt-1" name="explanation" rows="2" placeholder="Shalat adalah tiang agama..."></textarea>
					</div>

					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit">
							<i class="fa-solid fa-cloud-upload mr-2"></i> Simpan ke Bank Soal
						</button>
						<button class="btn-secondary" type="button" data-close-modal="create-question-modal">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<div id="import-pg-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
			<div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="flex items-center gap-2">
							<i class="fa-solid fa-list-check text-blue-500"></i>
							<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Import</div>
						</div>
						<div class="mt-2 text-xl font-bold">Upload Soal Pilihan Ganda</div>
						<p class="mt-2 text-sm text-textSecondary">Gunakan Excel atau CSV. Kolom <code>reading_passage</code> untuk teks bacaan (boleh kosong).</p>
					</div>
					<button type="button" class="icon-button" data-close-modal="import-pg-modal"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<form class="mt-5 space-y-4" method="POST" :action="route('superadmin.global-questions.import-pg')" enctype="multipart/form-data">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang_id" value="" required>
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">Pilih Jenjang Tujuan</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
								<div class="ssd-list">
									<div class="ssd-option ssd-selected" data-value="">Pilih Jenjang Tujuan</div>
									<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :data-value="jenjang.id">{{ jenjang.nama }}</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary">File Import</label>
						<input class="input mt-1 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2"
							   type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
					</div>
					<div class="rounded-2xl border border-blue-200/70 bg-blue-50/70 p-3 text-sm text-blue-900">
						<strong>Kolom wajib:</strong> question_text. Kolom opsional: material_id, reading_passage, option_a–e, answer_key, material_*, explanation, is_active.
					</div>
					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit">
							<i class="fa-solid fa-file-import mr-2"></i> Import Sekarang
						</button>
						<a class="btn-secondary" :href="route('superadmin.global-questions.template-pg')">
							<i class="fa-solid fa-file-excel mr-2"></i> Template PG
						</a>
						<button class="btn-secondary" type="button" data-close-modal="import-pg-modal">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<div id="import-menjodohkan-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
			<div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="flex items-center gap-2">
							<i class="fa-solid fa-shuffle text-amber-500"></i>
							<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Import</div>
						</div>
						<div class="mt-2 text-xl font-bold">Upload Soal Menjodohkan</div>
						<p class="mt-2 text-sm text-textSecondary">Format: kolom <code>pair_1_left</code>, <code>pair_1_right</code>, dst. hingga pair_8.</p>
					</div>
					<button type="button" class="icon-button" data-close-modal="import-menjodohkan-modal"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<form class="mt-5 space-y-4" method="POST" :action="route('superadmin.global-questions.import-menjodohkan')" enctype="multipart/form-data">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang_id" value="" required>
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">Pilih Jenjang Tujuan</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
								<div class="ssd-list">
									<div class="ssd-option ssd-selected" data-value="">Pilih Jenjang Tujuan</div>
									<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :data-value="jenjang.id">{{ jenjang.nama }}</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary">File Import</label>
						<input class="input mt-1 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2"
							   type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
					</div>
					<div class="rounded-2xl border border-amber-200/70 bg-amber-50/70 p-3 text-sm text-amber-900">
						<strong>Kolom wajib:</strong> question_text + minimal pair_1_left & pair_1_right. Kolom opsional: material_id, material_*. Maksimal 8 pasangan (pair_1 s/d pair_8).
					</div>
					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit">
							<i class="fa-solid fa-file-import mr-2"></i> Import Sekarang
						</button>
						<a class="btn-secondary" :href="route('superadmin.global-questions.template-menjodohkan')">
							<i class="fa-solid fa-file-excel mr-2"></i> Template Menjodohkan
						</a>
						<button class="btn-secondary" type="button" data-close-modal="import-menjodohkan-modal">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<div id="edit-question-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4">
			<div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
						<div class="mt-2 text-xl font-bold">Soal Global</div>
					</div>
					<button type="button" class="icon-button" data-close-modal="edit-question-modal"><i class="fa-solid fa-xmark"></i></button>
				</div>

				<form id="edit-question-form" method="POST" class="mt-5 flex-1 space-y-4 overflow-y-auto pr-2">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="jenjang_id" id="edit-jenjang-id" value="" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Pilih Jenjang</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="">Pilih Jenjang</div>
										<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :data-value="jenjang.id">{{ jenjang.nama }}</div>
									</div>
								</div>
							</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenis Soal</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="question_type" id="edit-question-type" value="multiple_choice" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Pilihan Ganda</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="multiple_choice">Pilihan Ganda</div>
										<div class="ssd-option" data-value="short_answer">Jawaban Singkat</div>
										<div class="ssd-option" data-value="matching">Menjodohkan</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div id="edit-reading-passage-wrapper">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">
							Teks Bacaan <span class="font-normal italic text-muted">(opsional, khusus Pilihan Ganda)</span>
						</label>
						<textarea class="input mt-1" name="reading_passage" id="edit-reading-passage" rows="3"
								  placeholder="Teks bacaan konteks soal..."></textarea>
					</div>
					<div class="space-y-3" data-material-picker="edit">
						<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
							<div class="relative" data-material-field="mapel">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mapel</label>
								<input type="hidden" name="material_mapel" id="edit-material-mapel" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih mapel</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari mapel..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
							<div class="relative" data-material-field="curriculum">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Curriculum</label>
								<input type="hidden" name="material_curriculum" id="edit-material-curriculum" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih kurikulum</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari kurikulum..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
						</div>
						<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
							<div class="relative" data-material-field="subelement">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Subelement</label>
								<input type="hidden" name="material_subelement" id="edit-material-subelement" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih subelement</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari subelement..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
							<div class="relative" data-material-field="unit">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Unit</label>
								<input type="hidden" name="material_unit" id="edit-material-unit" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih unit</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari unit..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
						</div>
						<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
							<div class="relative" data-material-field="sub_unit">
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Materi Sub Unit</label>
								<input type="hidden" name="material_sub_unit" id="edit-material-sub-unit" data-material-value>
								<button type="button" class="input mt-1 flex w-full items-center justify-between text-left" data-material-trigger>
									<span data-material-label>Pilih sub unit</span>
									<i class="fa-solid fa-chevron-down text-xs text-muted"></i>
								</button>
								<div class="absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900" data-material-dropdown>
									<input type="text" class="input mb-2" placeholder="Cari sub unit..." data-material-search>
									<div class="max-h-56 space-y-1 overflow-y-auto" data-material-options></div>
								</div>
							</div>
						</div>
						<div class="flex items-center justify-between gap-3">
							<p class="text-[10px] italic text-muted">Pilih materi bertahap dari kurikulum sampai sub unit. Opsi akan otomatis mengerucut sesuai pilihan sebelumnya.</p>
							<button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" data-material-reset>Kosongkan Materi</button>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
						<textarea class="input mt-1" name="question_text" id="edit-question-text" rows="4" required></textarea>
					</div>

					<div>
						<div class="flex items-center justify-between gap-3">
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban</label>
							<button type="button" class="btn-secondary px-3 py-2 text-xs" data-option-add="edit">
								<i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
							</button>
						</div>
						<div class="mt-2 space-y-2" data-option-list="edit"></div>
					</div>

					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kunci Jawaban</label>
							<input class="input mt-1" name="answer_key" id="edit-answer-key" placeholder="A atau isi jawaban benar">
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Status</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="is_active" id="edit-is-active" value="1" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Aktif (Publik)</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="1">Aktif (Publik)</div>
										<div class="ssd-option" data-value="0">Draft (Sembunyi)</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pembahasan / Penjelasan</label>
						<textarea class="input mt-1" name="explanation" id="edit-explanation" rows="2"></textarea>
					</div>

					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit">Simpan Perubahan</button>
						<button class="btn-secondary" type="button" data-close-modal="edit-question-modal">Batal</button>
					</div>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
