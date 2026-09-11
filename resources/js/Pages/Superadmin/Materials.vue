<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	materials: {
		type: Array,
		default: () => [],
	},
	filter: {
		type: String,
		default: null,
	},
	mapel: {
		type: String,
		default: null,
	},
	curriculum: {
		type: String,
		default: null,
	},
	subelement: {
		type: String,
		default: null,
	},
	subelementLimited: {
		type: String,
		default: null,
	},
	unit: {
		type: String,
		default: null,
	},
	unitLimited: {
		type: String,
		default: null,
	},
	subUnit: {
		type: String,
		default: null,
	},
	subUnitLimited: {
		type: String,
		default: null,
	},
	search: {
		type: String,
		default: '',
	},
	mapels: {
		type: Array,
		default: () => [],
	},
	curriculums: {
		type: Array,
		default: () => [],
	},
	subelements: {
		type: Array,
		default: () => [],
	},
	units: {
		type: Array,
		default: () => [],
	},
	subUnits: {
		type: Array,
		default: () => [],
	},
});

const contextJenjang = computed(() => ['SD', 'SMP', 'SMA'].includes(props.filter) ? props.filter : null);
const contextTitle = computed(() => {
	switch (contextJenjang.value) {
		case 'SD':
			return 'Materi SD';
		case 'SMP':
			return 'Materi SMP';
		case 'SMA':
			return 'Materi SMA';
		default:
			return 'Semua Jenjang';
	}
});
const templateLabel = computed(() => (contextJenjang.value ? `Download Template ${contextJenjang.value}` : 'Download Template Excel'));
const importTitle = computed(() => (contextJenjang.value ? `Import Materi ${contextJenjang.value}` : 'Import Materi'));
const importCopy = computed(() =>
	contextJenjang.value
		? `Semua baris tanpa kolom jenjang akan otomatis dibaca sebagai materi ${contextJenjang.value}.`
		: 'Upload file Excel atau CSV untuk menambahkan banyak materi sekaligus.'
);
const activeFilterCount = computed(
	() => [props.mapel, props.curriculum, props.subelement, props.unit, props.subUnit, props.search !== '' ? props.search : null].filter(Boolean).length
);

const importOpen = ref(false);
const createOpen = ref(false);

const importForm = useForm({
	file: null,
	default_jenjang: contextJenjang.value || '',
});

const submitImport = () => {
	importForm.post(route('superadmin.materials.import'), {
		onSuccess: () => {
			importOpen.value = false;
			importForm.reset();
		},
	});
};

const createForm = useForm({
	jenjang: contextJenjang.value || '',
	curriculum: 'Merdeka',
	mapel: '',
	subelement: '',
	unit: '',
	sub_unit: '',
	link: '',
});

const submitCreate = () => {
	createForm.post(route('superadmin.materials.store'), {
		onSuccess: () => {
			createOpen.value = false;
			createForm.reset('mapel', 'subelement', 'unit', 'sub_unit', 'link');
			createForm.jenjang = contextJenjang.value || '';
			createForm.curriculum = 'Merdeka';
		},
	});
};
</script>

<template>
	<Head title="Master Data Materi" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div>
				<h1 class="text-2xl font-bold">Data Kurikulum & Materi</h1>
				<p class="mt-2 text-textSecondary dark:text-slate-300">Kelola hierarki kurikulum, subelemen, unit, dan sub unit materi global.</p>
			</div>

			<div class="card border-primary/15 bg-primary/5">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Konteks Input</div>
						<div class="mt-2 text-lg font-bold text-slate-900 dark:text-white">{{ contextTitle }}</div>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">
							<template v-if="contextJenjang">
								Form tambah data, template, dan import di halaman ini diselaraskan ke jenjang {{ contextJenjang }} agar tidak mudah tertukar.
							</template>
							<template v-else>
								Pilih submenu `SD`, `SMP`, atau `SMA` dari sidebar jika ingin fokus input materi per jenjang tertentu.
							</template>
						</p>
					</div>
					<div class="flex items-center gap-2">
						<span class="badge-info">{{ contextTitle }}</span>
						<span v-if="filter === 'GLOBAL'" class="badge-warning">Global</span>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
					<div class="flex items-center gap-3">
						<div class="font-bold text-lg">Daftar Materi</div>
						<template v-if="activeFilterCount > 0">
							<span class="badge-info text-xs">{{ activeFilterCount }} filter aktif</span>
							<a :href="route('superadmin.materials.index', filter ? { jenjang: filter } : {})"
							   class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
								<i class="fa-solid fa-xmark"></i> Reset
							</a>
						</template>
					</div>
					<div class="flex flex-col gap-2 sm:flex-row items-center">
						<form v-if="materials.length > 0" method="POST" :action="route('superadmin.materials.destroyAll')" onsubmit="return false;" id="delete-all-materials-form">
							<input type="hidden" name="_token" :value="$page.props.csrf_token" />
							<button type="submit" class="btn-danger whitespace-nowrap" data-confirm-title="Hapus Semua Materi?" data-confirm="Semua data materi akan dihapus permanen. Lanjutkan?" title="Hapus Semua Materi">
								<i class="fa-solid fa-trash"></i> Hapus Semua
							</button>
						</form>
						<button type="button" class="btn-secondary whitespace-nowrap" @click="importOpen = true">
							<i class="fa-solid fa-file-arrow-up"></i>
							Import Excel
						</button>
						<button type="button" class="btn-primary whitespace-nowrap" @click="createOpen = true">
							<i class="fa-solid fa-plus"></i>
							Tambah Materi
						</button>
					</div>
				</div>

				<form method="GET" :action="route('superadmin.materials.index')" id="material-filter-form"
					  class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end" data-ssd-autosubmit>
					<input v-if="filter" type="hidden" name="jenjang" :value="filter">

					<div class="flex flex-col gap-1 min-w-[160px]">
						<label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Mata Pelajaran</label>
						<div class="ssd-wrap">
							<input type="hidden" name="mapel" :value="mapel ?? ''">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label truncate">{{ mapel || 'Semua' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" data-value="">Semua</div>
									<div v-for="m in mapels" :key="m" class="ssd-option" :class="mapel === m ? 'ssd-selected' : ''" :data-value="m">{{ m }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-col gap-1 min-w-[140px]">
						<label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Kurikulum</label>
						<div class="ssd-wrap">
							<input type="hidden" name="curriculum" :value="curriculum ?? ''">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label truncate">{{ curriculum || 'Semua' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" data-value="">Semua</div>
									<div v-for="c in curriculums" :key="c" class="ssd-option" :class="curriculum === c ? 'ssd-selected' : ''" :data-value="c">{{ c }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-col gap-1 min-w-[180px]">
						<label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Subelemen</label>
						<div class="ssd-wrap">
							<input type="hidden" name="subelement" :value="subelement ?? ''">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label truncate">{{ subelementLimited || 'Semua' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari subelemen..."></div>
								<div class="ssd-list">
									<div class="ssd-option" data-value="">Semua</div>
									<div v-for="se in subelements" :key="se" class="ssd-option" :class="subelement === se ? 'ssd-selected' : ''" :data-value="se">{{ se }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-col gap-1 min-w-[200px]">
						<label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Unit / Bab</label>
						<div class="ssd-wrap">
							<input type="hidden" name="unit" :value="unit ?? ''">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label truncate">{{ unitLimited || 'Semua' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari unit..."></div>
								<div class="ssd-list">
									<div class="ssd-option" data-value="">Semua</div>
									<div v-for="u in units" :key="u" class="ssd-option" :class="unit === u ? 'ssd-selected' : ''" :data-value="u">{{ u }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-col gap-1 min-w-[220px]">
						<label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Sub Unit</label>
						<div class="ssd-wrap">
							<input type="hidden" name="sub_unit" :value="subUnit ?? ''">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label truncate">{{ subUnitLimited || 'Semua' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari sub unit..."></div>
								<div class="ssd-list">
									<div class="ssd-option" data-value="">Semua</div>
									<div v-for="su in subUnits" :key="su" class="ssd-option" :class="subUnit === su ? 'ssd-selected' : ''" :data-value="su">{{ su }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-col gap-1 flex-1 min-w-[200px]">
						<label class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Cari Materi</label>
						<div class="relative flex items-center">
							<i class="fa-solid fa-magnifying-glass absolute left-3 text-muted text-xs pointer-events-none"></i>
							<input type="text" name="search" :value="search"
								   placeholder="Mapel, Subelemen, unit..."
								   class="input pl-8 text-sm w-full">
							<button type="submit" class="ml-2 btn-primary px-4 py-2 text-sm whitespace-nowrap">
								Cari
							</button>
						</div>
					</div>
				</form>

				<div class="grid grid-cols-1 gap-3">
					<template v-if="materials.length > 0">
						<div v-for="m in materials" :key="m.id" class="flex flex-col gap-4 rounded-card border border-border bg-white p-4 transition-shadow hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-start sm:justify-between">
							<div>
								<div class="flex items-center gap-2 mb-1">
									<span class="badge-info text-[10px]">{{ m.curriculum }}</span>
									<span v-if="m.mapel" class="badge-primary bg-blue-100 text-blue-700 text-[10px]">{{ m.mapel }}</span>
									<span v-if="m.jenjang" class="badge-warning text-[10px]">{{ m.jenjang }}</span>
									<span v-if="m.bank_question_count > 0" class="badge-success text-[10px]">Sudah ada {{ m.bank_question_count }} soal</span>
									<span v-else class="badge-warning text-[10px]">Belum ada soal</span>
									<div class="text-xs text-muted">ID: #{{ m.id }}</div>
								</div>
								<div class="font-bold text-slate-800 dark:text-slate-200">{{ m.subelement }}</div>
								<div class="mt-1 text-sm text-textSecondary dark:text-slate-400">
									<i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ m.unit }}
									<i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ m.sub_unit }}
								</div>
							</div>
							<div class="flex items-center gap-2">
								<a :href="route('superadmin.materials.practice.show', m.id)" class="btn-secondary p-2" title="Latihan Materi">
									<i class="fa-solid fa-key"></i>
								</a>
								<div class="flex items-center gap-2">
									<a :href="route('superadmin.materials.practice.show', m.id)" class="btn-secondary p-2" title="Latihan Materi">
										<i class="fa-solid fa-key"></i>
									</a>
									<form method="POST" :action="route('superadmin.materials.destroy', m.id)">
										<input type="hidden" name="_token" :value="$page.props.csrf_token" />
										<button class="btn-danger p-2" type="submit" data-confirm="Hapus materi ini? Data soal yang terikat akan kehilangan referensi materi. Lanjutkan?" title="Hapus">
											<i class="fa-solid fa-trash-can"></i>
										</button>
									</form>
								</div>
							</div>
						</div>
					</template>
					<div v-else class="text-center py-20 border-2 border-dashed rounded-xl">
						<i class="fa-solid fa-book-open text-5xl text-slate-100 mb-4 block"></i>
						<template v-if="activeFilterCount > 0">
							<span class="text-muted italic">Tidak ada materi yang cocok dengan filter yang dipilih.</span>
							<div class="mt-3">
								<a :href="route('superadmin.materials.index', filter ? { jenjang: filter } : {})"
								   class="btn-secondary text-sm">
									<i class="fa-solid fa-rotate-left"></i> Reset Filter
								</a>
							</div>
						</template>
						<span v-else class="text-muted italic">Belum ada kurikulum/materi yang diinput.</span>
					</div>
				</div>
			</div>
		</div>

		<div id="material-import-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" :class="importOpen ? '' : 'hidden'">
			<div class="w-full max-w-xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Import</div>
						<div class="mt-2 text-xl font-bold">{{ importTitle }}</div>
						<p class="mt-2 text-sm text-textSecondary">{{ importCopy }}</p>
					</div>
					<button type="button" class="icon-button" @click="importOpen = false">
						<i class="fa-solid fa-xmark"></i>
					</button>
				</div>
				<form enctype="multipart/form-data" class="mt-5 space-y-4" @submit.prevent="submitImport">
					<input v-if="contextJenjang" type="hidden" name="default_jenjang" :value="contextJenjang">
					<div>
						<label class="text-xs font-bold text-textSecondary">File Excel / CSV</label>
						<input class="input mt-1" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required @input="importForm.file = $event.target.files[0]">
					</div>
					<div class="rounded-2xl border border-blue-200/70 bg-blue-50/70 p-4 text-sm text-blue-900">
						<div class="font-semibold">Template yang disarankan</div>
						<p class="mt-1">
							<template v-if="contextJenjang">
								Template dan import akan mengutamakan jenjang {{ contextJenjang }}.
							</template>
							<template v-else>
								Gunakan kolom `jenjang` di file Excel untuk membedakan materi SD, SMP, dan SMA.
							</template>
						</p>
						<a class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800" :href="route('superadmin.materials.template', contextJenjang ? { jenjang: contextJenjang } : {})">
							<i class="fa-solid fa-file-excel"></i> {{ templateLabel }}
						</a>
					</div>
					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit" :disabled="importForm.processing">Import File</button>
						<button class="btn-secondary" type="button" @click="importOpen = false">Batal</button>
					</div>
				</form>
			</div>
		</div>

		<div id="material-create-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" :class="createOpen ? '' : 'hidden'">
			<div class="w-full max-w-2xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Tambah Data</div>
						<div class="mt-2 text-xl font-bold">Tambah Materi Baru</div>
						<p class="mt-2 text-sm text-textSecondary">Tambahkan materi baru sesuai jenjang yang sedang aktif agar referensinya tetap rapi.</p>
					</div>
					<button type="button" class="icon-button" @click="createOpen = false">
						<i class="fa-solid fa-xmark"></i>
					</button>
				</div>
				<form class="mt-5 space-y-4" @submit.prevent="submitCreate">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang (opsional)</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang" :value="createForm.jenjang" @change="createForm.jenjang = $event.target.value">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ createForm.jenjang || 'Semua Jenjang' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="!createForm.jenjang ? 'ssd-selected' : ''" data-value="">Semua Jenjang</div>
									<div class="ssd-option" :class="createForm.jenjang === 'SD' ? 'ssd-selected' : ''" data-value="SD">SD</div>
									<div class="ssd-option" :class="createForm.jenjang === 'SMP' ? 'ssd-selected' : ''" data-value="SMP">SMP</div>
									<div class="ssd-option" :class="createForm.jenjang === 'SMA' ? 'ssd-selected' : ''" data-value="SMA">SMA</div>
								</div>
							</div>
						</div>
						<p class="mt-1 text-[10px] text-muted italic">
							<template v-if="contextJenjang">
								Default mengikuti submenu {{ contextJenjang }} yang sedang dibuka.
							</template>
							<template v-else>
								Pilih jenjang dengan jelas agar materi tidak tercampur.
							</template>
						</p>
					</div>
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
							<div class="ssd-wrap mt-1">
								<input type="hidden" name="curriculum" :value="createForm.curriculum" @change="createForm.curriculum = $event.target.value">
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">Kurikulum Merdeka</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
									<div class="ssd-list">
										<div class="ssd-option ssd-selected" data-value="Merdeka">Kurikulum Merdeka</div>
										<div class="ssd-option" data-value="K-13">K-13 (Masa Transisi)</div>
									</div>
								</div>
							</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
							<input class="input mt-1" name="mapel" v-model="createForm.mapel" required placeholder="E.g: Bahasa Indonesia / Matematika">
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subelemen</label>
							<input class="input mt-1" name="subelement" v-model="createForm.subelement" required placeholder="E.g: Literasi">
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Unit / Bab</label>
							<input class="input mt-1" name="unit" v-model="createForm.unit" required placeholder="E.g: Rukun Iman">
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Sub Unit / Sub Bab</label>
							<input class="input mt-1" name="sub_unit" v-model="createForm.sub_unit" required placeholder="E.g: Mengenal Malaikat">
						</div>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Link Materi (opsional)</label>
						<input class="input mt-1" name="link" v-model="createForm.link" placeholder="https://...">
					</div>
					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit" :disabled="createForm.processing">Tambah Materi</button>
						<button class="btn-secondary" type="button" @click="createOpen = false">Batal</button>
					</div>
					<p class="text-[10px] text-muted italic">Materi yang ditambahkan akan tersedia sebagai referensi saat pembuatan bank soal oleh semua guru.</p>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
