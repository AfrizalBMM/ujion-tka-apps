<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	bankSoals: {
		type: Array,
		default: () => [],
	},
	filters: {
		type: Object,
		default: () => ({}),
	},
	filtersLimited: {
		type: Object,
		default: () => ({}),
	},
	jenjangs: {
		type: Array,
		default: () => [],
	},
	mapels: {
		type: Array,
		default: () => [],
	},
	curriculums: {
		type: Array,
		default: () => [],
	},
	subUnits: {
		type: Array,
		default: () => [],
	},
	soalCount: {
		type: Number,
		default: 0,
	},
	maxSoal: {
		type: Number,
		default: 0,
	},
	slotSisa: {
		type: Number,
		default: 0,
	},
});

const rootEl = ref(null);

const questionTypeLabel = (type) => {
	switch (type) {
		case 'multiple_choice':
			return 'Pilihan Ganda';
		case 'matching':
			return 'Menjodohkan';
		case 'short_answer':
			return 'Jawaban Singkat';
		default:
			return 'Semua Tipe';
	}
};

const selJenjang = () => props.jenjangs.find((j) => j.id == (props.filters.jenjang_id ?? ''));

const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));

let cleanupFns = [];

onMounted(() => {
	const root = rootEl.value;
	if (!root) return;

	const slotSisa = props.slotSisa;
	const emptySelectionMessage = 'Pilih minimal satu soal terlebih dahulu.';
	const processingImportHtml = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...';

	const form = document.getElementById('import-bank-form');
	const selectAll = document.getElementById('select-all-checkbox');
	const footer = document.getElementById('import-footer');
	const selectedCountEl = document.getElementById('selected-count');
	const footerCountEl = document.getElementById('footer-count');
	const footerSlotEl = document.getElementById('footer-slot');
	const deselectBtn = document.getElementById('deselect-all-btn');
	const previewBtn = document.getElementById('preview-btn');
	const modal = document.getElementById('preview-modal');
	const previewList = document.getElementById('preview-list');
	const previewCount = document.getElementById('preview-count');
	const quotaWarning = document.getElementById('quota-warning');
	const closePreviewBtn = document.getElementById('close-preview-btn');
	const cancelPreviewBtn = document.getElementById('cancel-preview-btn');
	const confirmImportBtn = document.getElementById('confirm-import-btn');
	const checkboxes = Array.from(root.querySelectorAll('.soal-checkbox'));

	if (
		!form ||
		!selectAll ||
		!footer ||
		!selectedCountEl ||
		!footerCountEl ||
		!footerSlotEl ||
		!deselectBtn ||
		!previewBtn ||
		!modal ||
		!previewList ||
		!previewCount ||
		!quotaWarning ||
		!closePreviewBtn ||
		!cancelPreviewBtn ||
		!confirmImportBtn ||
		checkboxes.length === 0
	) {
		return;
	}

	const getChecked = () => checkboxes.filter((checkbox) => checkbox.checked);

	const openModal = () => {
		modal.classList.remove('hidden');
		modal.classList.add('flex');
	};

	const closeModal = () => {
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	};

	const updateUI = () => {
		const checkedCount = getChecked().length;
		const full = checkedCount >= slotSisa;

		selectedCountEl.textContent = String(checkedCount);
		footerCountEl.textContent = String(checkedCount);
		footerSlotEl.textContent = String(Math.max(0, slotSisa - checkedCount));
		footer.classList.toggle('hidden', checkedCount === 0);

		checkboxes.forEach((checkbox) => {
			const card = checkbox.closest('.soal-card');
			if (card) {
				card.classList.toggle('ring-2', checkbox.checked);
				card.classList.toggle('ring-blue-500', checkbox.checked);
				card.classList.toggle('border-blue-400', checkbox.checked);
			}

			if (!checkbox.checked) {
				checkbox.disabled = full;
				if (card) {
					card.classList.toggle('opacity-40', full);
					card.style.cursor = full ? 'not-allowed' : '';
				}
				return;
			}

			checkbox.disabled = false;
			if (card) {
				card.classList.remove('opacity-40');
				card.style.cursor = '';
			}
		});

		const enabledUnchecked = checkboxes.filter((checkbox) => !checkbox.disabled && !checkbox.checked).length;
		selectAll.indeterminate = checkedCount > 0 && enabledUnchecked > 0;
		selectAll.checked = enabledUnchecked === 0 && checkedCount > 0;
	};

	const buildPreviewList = (selectedCheckboxes) => {
		previewList.innerHTML = '';

		selectedCheckboxes.forEach((checkbox, index) => {
			const card = checkbox.closest('.soal-card');
			const badge = card?.querySelector('[class*="badge-"]');
			const question = card?.querySelector('p.font-medium');
			const badgeText = badge?.textContent?.trim() || '';
			const badgeClass = badge?.className.match(/badge-\w+/)?.[0] || 'badge-info';
			const questionText = question?.textContent?.trim() || `Soal #${checkbox.value}`;
			const previewItem = document.createElement('div');

			previewItem.className = 'flex items-start gap-3 rounded-2xl border border-border bg-slate-50/70 p-3 dark:bg-slate-800/60';
			previewItem.innerHTML = `
				<span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">${index + 1}</span>
				<div class="min-w-0 flex-1">
					<span class="${badgeClass} mb-1 inline-block text-[10px] font-bold uppercase tracking-wider">${badgeText}</span>
					<p class="text-sm leading-snug text-slate-700 dark:text-slate-200">${questionText.length > 150 ? `${questionText.substring(0, 150)}…` : questionText}</p>
				</div>
			`;

			previewList.appendChild(previewItem);
		});
	};

	checkboxes.forEach((checkbox) => {
		checkbox.addEventListener('change', updateUI);
	});

	selectAll.addEventListener('change', () => {
		let remaining = slotSisa;

		checkboxes.forEach((checkbox) => {
			if (selectAll.checked && remaining > 0) {
				checkbox.checked = true;
				remaining -= 1;
				return;
			}

			checkbox.checked = false;
		});

		updateUI();
	});

	deselectBtn.addEventListener('click', () => {
		checkboxes.forEach((checkbox) => {
			checkbox.checked = false;
		});
		selectAll.checked = false;
		updateUI();
	});

	previewBtn.addEventListener('click', () => {
		const selectedCheckboxes = getChecked();
		if (selectedCheckboxes.length === 0) {
			window.alert(emptySelectionMessage);
			return;
		}

		previewCount.textContent = String(selectedCheckboxes.length);
		quotaWarning.classList.toggle('hidden', selectedCheckboxes.length <= slotSisa);
		buildPreviewList(selectedCheckboxes);
		openModal();
	});

	closePreviewBtn.addEventListener('click', closeModal);
	cancelPreviewBtn.addEventListener('click', closeModal);

	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			closeModal();
		}
	});

	const onKeydown = (event) => {
		if (event.key === 'Escape' && modal.classList.contains('flex')) {
			closeModal();
		}
	};

	document.addEventListener('keydown', onKeydown);
	cleanupFns.push(() => document.removeEventListener('keydown', onKeydown));

	confirmImportBtn.addEventListener('click', () => {
		confirmImportBtn.disabled = true;
		confirmImportBtn.innerHTML = processingImportHtml;
		form.submit();
	});

	root.querySelectorAll('.toggle-bacaan').forEach((button) => {
		button.addEventListener('click', () => {
			const target = document.getElementById(button.dataset.target || '');
			const chevron = button.querySelector('[data-chevron]');
			target?.classList.toggle('hidden');
			chevron?.classList.toggle('rotate-180');
		});
	});

	updateUI();
});

onBeforeUnmount(() => {
	cleanupFns.forEach((fn) => fn());
	cleanupFns = [];
});
</script>

<template>
	<Head :title="`Bank Builder — ${mapel.nama_label}`" />

	<SuperadminLayout>
		<div ref="rootEl" class="space-y-6">
			<section class="page-hero">
				<div class="flex flex-wrap items-center gap-2 text-sm text-white/70">
					<a :href="route('superadmin.paket-soal.index')" class="hover:text-white">Paket Soal</a>
					<i class="fa-solid fa-chevron-right text-[10px]"></i>
					<a :href="route('superadmin.paket-soal.show', paket.id)" class="hover:text-white">{{ paket.nama }}</a>
					<i class="fa-solid fa-chevron-right text-[10px]"></i>
					<span class="text-white">{{ mapel.nama_label }}</span>
				</div>
				<span class="page-kicker mt-2">Import dari Bank Soal</span>
				<h1 class="page-title">Pilih soal untuk <span class="text-white/80">{{ mapel.nama_label }}</span></h1>
				<p class="page-description">
					Paket: <strong class="text-white">{{ paket.nama }}</strong> &middot;
					{{ soalCount }}/{{ maxSoal }} soal &middot;
					Jenjang {{ paket.jenjang_kode }}
				</p>
			</section>

			<section class="card">
				<form method="GET" :action="route('superadmin.soal.bank-builder', [paket.id, mapel.id])"
					  class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">

					<div class="sm:col-span-2 lg:col-span-5">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Cari Soal / Bacaan</label>
						<input class="input mt-1" type="text" name="search"
							   :value="filters.search"
							   placeholder="Ketik kata kunci pertanyaan, teks bacaan, atau materi...">
					</div>

					<div>
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tipe Soal</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="question_type" :value="filters.question_type">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ questionTypeLabel(filters.question_type) }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="filters.question_type === '' ? 'ssd-selected' : ''" data-value="">Semua Tipe</div>
									<div class="ssd-option" :class="filters.question_type === 'multiple_choice' ? 'ssd-selected' : ''" data-value="multiple_choice">Pilihan Ganda</div>
									<div class="ssd-option" :class="filters.question_type === 'matching' ? 'ssd-selected' : ''" data-value="matching">Menjodohkan</div>
									<div class="ssd-option" :class="filters.question_type === 'short_answer' ? 'ssd-selected' : ''" data-value="short_answer">Jawaban Singkat</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kurikulum</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="material_curriculum" :value="filters.material_curriculum">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ filters.material_curriculum || 'Semua Kurikulum' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="filters.material_curriculum === '' ? 'ssd-selected' : ''" data-value="">Semua Kurikulum</div>
									<div v-for="c in curriculums" :key="c" class="ssd-option" :class="filters.material_curriculum === c ? 'ssd-selected' : ''" :data-value="c">{{ c }}</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Sub Unit Materi</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="material_sub_unit" :value="filters.material_sub_unit">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ filtersLimited.material_sub_unit || 'Semua Sub Unit' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari sub unit..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="filters.material_sub_unit === '' ? 'ssd-selected' : ''" data-value="">Semua Sub Unit</div>
									<div v-for="su in subUnits" :key="su" class="ssd-option" :class="filters.material_sub_unit === su ? 'ssd-selected' : ''" :data-value="su">{{ su }}</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Mata Pelajaran</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="material_mapel" :value="filters.material_mapel">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ filters.material_mapel || 'Semua Mapel' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="filters.material_mapel === '' ? 'ssd-selected' : ''" data-value="">Semua Mapel</div>
									<div v-for="m in mapels" :key="m" class="ssd-option" :class="filters.material_mapel === m ? 'ssd-selected' : ''" :data-value="m">{{ m }}</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang_id" :value="filters.jenjang_id">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ selJenjang() ? selJenjang().nama : 'Semua Jenjang' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="filters.jenjang_id === '' ? 'ssd-selected' : ''" data-value="">Semua Jenjang</div>
									<div v-for="j in jenjangs" :key="j.id" class="ssd-option" :class="filters.jenjang_id == j.id ? 'ssd-selected' : ''" :data-value="j.id">{{ j.nama }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-wrap items-end gap-3 sm:col-span-2 lg:col-span-5">
						<button class="btn-primary" type="submit">
							<i class="fa-solid fa-filter mr-2"></i> Terapkan Filter
						</button>
						<a class="btn-secondary" :href="route('superadmin.soal.bank-builder', [paket.id, mapel.id])">
							Reset
						</a>
						<span class="ml-auto text-sm text-textSecondary">
							<span id="selected-count" class="font-bold text-blue-600">0</span> soal dipilih dari {{ bankSoals.length }} hasil
						</span>
					</div>
				</form>
			</section>

			<form id="import-bank-form"
				  method="POST"
				  :action="route('superadmin.soal.import-from-bank', [paket.id, mapel.id])">
				<input type="hidden" name="_token" :value="$page.props.csrf_token" />

				<section class="card min-h-[400px]">
					<div class="mb-4 flex items-center justify-between gap-4">
						<h2 class="text-lg font-bold">Bank Soal Global</h2>
						<label class="flex cursor-pointer items-center gap-2 text-sm font-medium">
							<input type="checkbox" id="select-all-checkbox" class="h-4 w-4 rounded">
							<span>Pilih Semua</span>
						</label>
					</div>

					<div class="space-y-4">
						<template v-for="gq in bankSoals" :key="gq.id">
							<div class="soal-card group rounded-[20px] border border-border bg-white transition-all
										hover:border-blue-300 hover:shadow-md
										dark:border-slate-800 dark:bg-slate-900"
								 :data-card-id="gq.id">

								<label class="flex cursor-pointer items-start gap-4 p-4 pb-3" :for="`gq-${gq.id}`">
									<input type="checkbox"
										   :id="`gq-${gq.id}`"
										   name="global_question_ids[]"
										   :value="gq.id"
										   class="soal-checkbox mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

									<div class="flex-1 min-w-0">
										<div class="flex flex-wrap items-center gap-2 mb-2">
											<span :class="gq.type_badge_class" class="text-[10px] font-bold uppercase tracking-wider">
												{{ gq.type_label }}
											</span>
											<span v-if="gq.material_curriculum" class="text-[10px] text-textSecondary">
												<i class="fa-solid fa-graduation-cap text-[8px]"></i>
												{{ gq.material_curriculum }}
											</span>
											<span v-if="gq.material_sub_unit" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
												{{ gq.material_sub_unit }}
											</span>
											<span v-if="gq.answer_key_limited && gq.question_type === 'multiple_choice'" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
												Kunci: {{ gq.answer_key_limited }}
											</span>
										</div>

										<p class="font-medium leading-relaxed text-slate-800 dark:text-slate-200">
											{{ gq.question_text_limited }}
										</p>
									</div>
								</label>

								<div v-if="gq.reading_passage" class="border-t border-border px-4 pb-1 dark:border-slate-800">
									<button type="button"
											class="toggle-bacaan flex w-full items-center justify-between py-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
											:data-target="`bacaan-${gq.id}`">
										<span><i class="fa-solid fa-book-open mr-2"></i>Teks Bacaan</span>
										<i class="fa-solid fa-chevron-down text-[10px] transition-transform" data-chevron></i>
									</button>
									<div :id="`bacaan-${gq.id}`" class="hidden pb-3">
										<div class="rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300">
											{{ gq.reading_passage }}
										</div>
									</div>
								</div>

								<div v-if="gq.question_type === 'multiple_choice' && gq.options && gq.options.length" class="border-t border-border px-4 py-3 dark:border-slate-800">
									<div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
										<div v-for="(opt, idx) in gq.options" :key="idx" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs"
											:class="gq.answer_key == opt
												? 'bg-emerald-50 font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20'
												: 'text-slate-600 dark:text-slate-400'">
											<span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
												:class="gq.answer_key == opt
													? 'bg-emerald-500 text-white'
													: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ optionLabels[idx] ?? `O${idx + 1}` }}</span>
											{{ opt }}
										</div>
									</div>
								</div>

								<div v-if="gq.question_type === 'matching' && gq.options && gq.options.length" class="border-t border-border px-4 py-3 dark:border-slate-800">
									<table class="w-full text-xs">
										<thead>
											<tr class="text-left text-textSecondary">
												<th class="pb-1 pr-4 font-semibold">Item Kiri</th>
												<th class="pb-1 font-semibold">Item Kanan</th>
											</tr>
										</thead>
										<tbody class="divide-y divide-border dark:divide-slate-800">
											<tr v-for="(pair, pIdx) in gq.options" :key="pIdx">
												<td class="py-1 pr-4 text-slate-700 dark:text-slate-300">{{ pair.left }}</td>
												<td class="py-1 font-medium text-blue-700 dark:text-blue-300">{{ pair.right }}</td>
											</tr>
										</tbody>
									</table>
								</div>

								<div v-if="gq.material_subelement || gq.material_unit" class="border-t border-border px-4 py-2 dark:border-slate-800">
									<p class="text-[11px] text-textSecondary">
										<span v-if="gq.material_subelement"><span class="font-semibold">Sub-elemen:</span> {{ gq.material_subelement }}</span>
										<span v-if="gq.material_unit">&nbsp;&middot;&nbsp;<span class="font-semibold">Unit:</span> {{ gq.material_unit }}</span>
									</p>
								</div>
							</div>
						</template>
						<div v-if="bankSoals.length === 0" class="flex flex-col items-center py-24 text-center">
							<div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800">
								<i class="fa-solid fa-database text-3xl text-slate-200"></i>
							</div>
							<p class="italic text-muted">Tidak ada soal aktif di bank soal global yang cocok dengan filter.</p>
							<a :href="route('superadmin.global-questions.index')" class="mt-4 text-sm font-medium text-blue-600 hover:underline">
								Tambah soal ke bank soal &rarr;
							</a>
						</div>
					</div>
				</section>

				<div id="import-footer"
					 class="sticky bottom-0 z-40 mt-4 hidden rounded-[20px] border border-blue-200 bg-blue-600 px-6 py-4 shadow-2xl">
					<div class="flex flex-wrap items-center justify-between gap-4">
						<div class="text-white">
							<span class="text-lg font-bold" id="footer-count">0</span>
							<span class="ml-1 text-blue-100">soal dipilih</span>
							<span class="ml-3 text-xs text-blue-200">(sisa slot: <span id="footer-slot">{{ slotSisa }}</span>)</span>
						</div>
						<div class="flex gap-3">
							<button type="button" id="deselect-all-btn"
									class="rounded-xl border border-white/30 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
								Batalkan Pilihan
							</button>
							<button type="button" id="preview-btn"
									class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-blue-700 shadow-md transition hover:bg-blue-50">
								<i class="fa-solid fa-eye mr-2"></i>
								Preview & Masukkan
							</button>
						</div>
					</div>
				</div>
			</form>

			<div id="preview-modal"
				 class="fixed inset-0 z-[500] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
				<div class="w-full max-w-2xl rounded-[24px] bg-white dark:bg-slate-900 shadow-2xl flex flex-col max-h-[90vh]">

					<div class="flex items-center justify-between px-6 py-4 border-b border-border">
						<div>
							<h3 class="text-lg font-bold">Konfirmasi Import Soal</h3>
							<p class="text-xs text-textSecondary mt-0.5">
								Mapel: <strong>{{ mapel.nama_label }}</strong> &middot;
								Paket: <strong>{{ paket.nama }}</strong>
							</p>
						</div>
						<button type="button" id="close-preview-btn"
								class="text-muted hover:text-slate-800 dark:hover:text-white transition">
							<i class="fa-solid fa-xmark text-xl"></i>
						</button>
					</div>

					<div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-border flex items-center justify-between gap-4 text-sm">
						<div class="flex items-center gap-4">
							<span class="font-semibold text-blue-700 dark:text-blue-300">
								<i class="fa-solid fa-list-check mr-1.5"></i>
								<span id="preview-count">0</span> soal akan ditambahkan
							</span>
							<span class="text-textSecondary">
								Slot tersisa sebelum import: <strong>{{ slotSisa }}</strong>
							</span>
						</div>
						<span id="quota-warning"
							  class="hidden text-xs font-semibold text-amber-600 dark:text-amber-400">
							<i class="fa-solid fa-triangle-exclamation mr-1"></i>
							Melebihi kuota! Sebagian soal akan dilewati.
						</span>
					</div>

					<div id="preview-list" class="overflow-y-auto flex-1 px-6 py-4 space-y-3">
					</div>

					<div class="px-6 py-4 border-t border-border flex items-center justify-end gap-3">
						<button type="button" id="cancel-preview-btn"
								class="btn-secondary px-5 py-2.5 text-sm">
							Kembali Pilih
						</button>
						<button type="button" id="confirm-import-btn"
								class="btn-primary px-5 py-2.5 text-sm">
							<i class="fa-solid fa-file-import mr-2"></i>
							Konfirmasi & Import
						</button>
					</div>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
