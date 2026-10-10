<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, nextTick } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import QuestionFilterPanel from '@/Components/Superadmin/QuestionFilterPanel.vue';
import QuestionRow from '@/Components/Superadmin/QuestionRow.vue';
import QuestionEditForm from '@/Components/Superadmin/QuestionEditForm.vue';

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

const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));

// Edit modal state
const showEditModal = ref(false);
const editingQuestion = ref(null);

// Import dropdown state
const showImportDropdown = ref(false);

// Modal state
const openModalId = ref(null);

const toggleImportDropdown = () => {
	showImportDropdown.value = !showImportDropdown.value;
};

const closeImportDropdown = () => {
	showImportDropdown.value = false;
};

const onDocumentClickImport = (e) => {
	if (!(e.target instanceof Element) || e.target.closest('#import-dropdown-wrapper')) return;
	closeImportDropdown();
};

document.addEventListener('click', onDocumentClickImport);

import { onBeforeUnmount } from 'vue';
onBeforeUnmount(() => {
	document.removeEventListener('click', onDocumentClickImport);
});

const openModal = (id) => {
	openModalId.value = id;
};

const closeModal = () => {
	openModalId.value = null;
};

const onEditQuestion = (q) => {
	editingQuestion.value = {
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
	};
	showEditModal.value = true;
};

const closeEditModal = () => {
	showEditModal.value = false;
	editingQuestion.value = null;
};

const typeBadge = (question) => (question.question_type === 'matching' ? 'Menjodohkan' : String(question.question_type).replaceAll('_', ' '));

const hasPages = computed(() => (props.globalQuestions.last_page ?? 1) > 1);
const pageElements = computed(() =>
	(props.globalQuestions.links ?? []).filter((link) => /^\d+$/.test(String(link.label)) || String(link.label) === '...')
);
</script>

<template>
	<Head title="Global Bank Soal" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
				<div>
					<h1 class="text-2xl font-bold">Bank Soal Global</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Kelola kumpulan soal yang dapat diakses oleh seluruh guru di platform Ujion.</p>
				</div>
				<div class="flex flex-wrap gap-3">
					<div v-if="!filters.jenjang_id" class="relative" id="import-dropdown-wrapper">
						<button class="btn-secondary" type="button" @click="toggleImportDropdown">
							<i class="fa-solid fa-file-import mr-2"></i> Import Soal
							<i class="fa-solid fa-chevron-down ml-2 text-xs"></i>
						</button>
						<div v-if="showImportDropdown"
							class="absolute right-0 top-full z-30 mt-2 w-52 rounded-2xl border border-border bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
							<button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
								@click="closeImportDropdown(); openModal('import-pg-modal')">
								<i class="fa-solid fa-list-check w-4 text-blue-500"></i> Pilihan Ganda
							</button>
							<button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
								@click="closeImportDropdown(); openModal('import-menjodohkan-modal')">
								<i class="fa-solid fa-shuffle w-4 text-amber-500"></i> Menjodohkan
							</button>
						</div>
					</div>
					<button class="btn-primary" type="button" @click="openModal('create-question-modal')">
						<i class="fa-solid fa-plus mr-2"></i> Input Soal Baru
					</button>
				</div>
			</div>

			<QuestionFilterPanel :filters="filters" :jenjangs="jenjangs" :materials="materials" />

			<div class="card min-h-[400px]">
				<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
					<div class="font-bold text-lg">Daftar Soal Global ({{ globalQuestions.total }})</div>
					<div class="flex flex-col gap-2 sm:flex-row sm:items-center">
						<form v-if="globalQuestions.data.length > 0" method="POST" :action="route('superadmin.global-questions.destroyAll')" id="delete-all-global-questions-form">
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
					<QuestionRow
						v-for="q in globalQuestions.data"
						:key="q.id"
						:question="q"
						@edit="onEditQuestion"
					/>
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
												<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
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

		<!-- Create Question Modal -->
		<div v-if="openModalId === 'create-question-modal'" id="create-question-modal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4" @click.self="closeModal">
			<div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Baru</div>
						<div class="mt-2 text-xl font-bold">Input Soal Global</div>
					</div>
					<button type="button" class="icon-button" aria-label="Tutup" @click="closeModal"><i class="fa-solid fa-xmark"></i></button>
				</div>

				<form class="mt-5 flex-1 space-y-4 overflow-y-auto pr-2" method="POST" :action="route('superadmin.global-questions.store')">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang *</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="jenjang_id" value="" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Pilih Jenjang</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..." aria-label="Cari jenjang"></div>
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
								<input type="hidden" name="question_type" value="multiple_choice" required>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Pilihan Ganda</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="multiple_choice">Pilihan Ganda</div>
										<div class="ssd-option" data-value="short_answer">Jawaban Singkat</div>
										<div class="ssd-option" data-value="matching">Menjodohkan</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">
							Teks Bacaan <span class="font-normal italic text-muted">(opsional, khusus Pilihan Ganda)</span>
						</label>
						<textarea class="input mt-1" name="reading_passage" rows="3"
							placeholder="Isi teks/wacana bacaan yang menjadi konteks soal ini. Kosongkan jika tidak ada."></textarea>
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan (Teks)</label>
						<textarea class="input mt-1" name="question_text" rows="4" required placeholder="Apa rukun islam yang kedua?"></textarea>
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Opsi Jawaban (satu per baris)</label>
						<textarea class="input mt-1" name="options_raw" rows="4" placeholder="A. Opsi pertama&#10;B. Opsi kedua"></textarea>
						<p class="mt-1 text-[10px] italic text-muted">Ketik opsi jawaban satu per baris. Format: A. Opsi pertama</p>
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
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
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
						<button class="btn-secondary" type="button" @click="closeModal">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Import PG Modal -->
		<div v-if="openModalId === 'import-pg-modal'" id="import-pg-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeModal">
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
					<button type="button" class="icon-button" aria-label="Tutup" @click="closeModal"><i class="fa-solid fa-xmark"></i></button>
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
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..." aria-label="Cari jenjang"></div>
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
						<button class="btn-secondary" type="button" @click="closeModal">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Import Menjodohkan Modal -->
		<div v-if="openModalId === 'import-menjodohkan-modal'" id="import-menjodohkan-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeModal">
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
					<button type="button" class="icon-button" aria-label="Tutup" @click="closeModal"><i class="fa-solid fa-xmark"></i></button>
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
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..." aria-label="Cari jenjang"></div>
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
						<button class="btn-secondary" type="button" @click="closeModal">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Edit Question Modal -->
		<QuestionEditForm
			:show="showEditModal"
			:question="editingQuestion"
			:jenjangs="jenjangs"
			:materials="materials"
			:csrf-token="page.props.csrf_token"
			@close="closeEditModal"
		/>
	</SuperadminLayout>
</template>
