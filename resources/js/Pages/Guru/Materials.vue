<script setup>
import { inject, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';

const props = defineProps({
	materials: {
		type: Object,
		required: true,
	},
	bookmarks: {
		type: Array,
		required: true,
	},
	jenjangUser: {
		type: String,
		default: null,
	},
	filters: {
		type: Object,
		required: true,
	},
	mapels: {
		type: Array,
		required: true,
	},
	curriculums: {
		type: Array,
		required: true,
	},
	bookmarked: {
		type: Boolean,
		default: false,
	},
	bookmarkUrl: {
		type: String,
		required: true,
	},
});

const route = inject('route');

const filterForm = ref(null);
const search = ref(props.filters.search || '');

let timer = null;
let isComposing = false;

const applyFilters = () => {
	if (isComposing) return;

	const params = new URLSearchParams(new FormData(filterForm.value));
	const query = params.toString();

	router.get(`${route('guru.materials')}${query ? `?${query}` : ''}`, {}, {
		preserveState: true,
		replace: true,
	});
};

const scheduleApply = (delay = 450) => {
	if (isComposing) return;

	window.clearTimeout(timer);
	timer = window.setTimeout(applyFilters, delay);
};

const onSearchInput = (event) => {
	search.value = event.target.value;
	scheduleApply();
};

const onCompositionStart = () => {
	isComposing = true;
};

const onCompositionEnd = () => {
	isComposing = false;
	scheduleApply(250);
};

const toggleBookmark = (material) => {
	router.post(
		route(material.is_bookmarked ? 'guru.materials.unbookmark' : 'guru.materials.bookmark', material.id),
		{},
		{ preserveScroll: true }
	);
};

onBeforeUnmount(() => {
	window.clearTimeout(timer);
});
</script>

<template>
	<Head title="Materi" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">Materi</span>
				<div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
					<div>
						<h1 class="page-title">Materi Pembelajaran Interaktif</h1>
						<p class="page-description">Akses materi sesuai jenjang Anda, Anda melihat materi jenjang
							<strong>{{ jenjangUser || '-' }}</strong>, termasuk materi global yang ditetapkan
							untuk jenjang yang sama.
						</p>
					</div>
					<div class="grid gap-3 sm:grid-cols-2">
						<div class="hero-chip">
							<i class="fa-solid fa-book-open-reader"></i>
							Materi terstruktur
						</div>
						<div class="hero-chip">
							<i class="fa-solid fa-star"></i>
							Bookmark materi favorit
						</div>
					</div>
				</div>
				<div class="page-actions">
					<div class="flex flex-wrap items-center gap-2">
						<Link :href="route('guru.personal-questions')"
							class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
							<i class="fa-solid fa-database"></i>
							Bank Soal Pribadi
						</Link>
						<Link :href="bookmarkUrl"
							class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
							<i :class="bookmarked ? 'fa-solid' : 'fa-regular'" class="fa-bookmark"></i>
							{{ bookmarked ? 'Bookmark Saya' : 'Tampilkan Bookmark' }}
							<span class="badge-info">{{ Array.isArray(bookmarks) ? bookmarks.length : 0 }}</span>
						</Link>
					</div>
				</div>
			</section>
			<form ref="filterForm" method="GET" :action="route('guru.materials')"
				class="card p-4 space-y-4 sm:space-y-0 sm:flex sm:items-end sm:gap-4" data-ssd-autosubmit
				data-materials-filter-form @submit.prevent="applyFilters" @compositionstart="onCompositionStart" @compositionend="onCompositionEnd">
				<input v-if="bookmarked" type="hidden" name="bookmarked" value="1">
				<div class="flex-1 min-w-[150px]">
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
					<div class="ssd-wrap mt-1">
						<input type="hidden" name="mapel" :value="filters.mapel || ''">
						<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
							<span class="ssd-label">{{ filters.mapel || 'Semua Mapel' }}</span>
							<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
						</button>
						<div class="ssd-panel">
							<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search"
								placeholder="Cari mapel..."></div>
							<div class="ssd-list">
								<div class="ssd-option" data-value="">Semua Mapel</div>
								<div v-for="m in mapels" :key="m" class="ssd-option" :class="filters.mapel === m ? ' ssd-selected' : ''" :data-value="m">
									{{ m }}
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex-1 min-w-[150px]">
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
					<div class="ssd-wrap mt-1">
						<input type="hidden" name="curriculum" :value="filters.curriculum || ''">
						<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
							<span class="ssd-label">{{ filters.curriculum || 'Semua Kurikulum' }}</span>
							<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
						</button>
						<div class="ssd-panel">
							<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search"
								placeholder="Cari kurikulum..."></div>
							<div class="ssd-list">
								<div class="ssd-option" data-value="">Semua Kurikulum</div>
								<div v-for="c in curriculums" :key="c" class="ssd-option" :class="filters.curriculum === c ? ' ssd-selected' : ''"
									:data-value="c">{{ c }}</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex-1 min-w-[200px]">
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Materi</label>
					<input type="search" name="search" :value="search" class="input mt-1 w-full"
						placeholder="Cari materi..." data-live-search @input="onSearchInput">
				</div>
				<Link :href="route('guru.materials')" class="btn-secondary h-[42px] flex items-center justify-center">Reset</Link>
			</form>

			<div class="space-y-4">
				<div v-for="m in materials.data" :key="m.id" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-border bg-white p-5 shadow-sm hover:shadow-md transition-all dark:border-slate-800 dark:bg-slate-900">
					<div class="flex-1 min-w-0">
						<div class="flex flex-wrap items-center gap-2 mb-1">
							<span class="badge-info text-[11px]">{{ m.curriculum }}</span>
							<span v-if="m.mapel" class="badge-primary bg-blue-100 text-blue-700 text-[11px]">{{ m.mapel }}</span>
							<span v-if="m.jenjang" class="badge-warning text-[11px]">{{ m.jenjang }}</span>
							<span v-if="m.bank_question_count > 0" class="badge-success text-[11px]">Sudah ada {{ m.bank_question_count }} soal</span>
							<span v-else class="badge-warning text-[11px]">Belum ada soal</span>
							<span class="text-xs text-muted">ID: #{{ m.id }}</span>
						</div>
						<div class="font-bold text-lg text-slate-800 dark:text-slate-200 mb-1">{{ m.subelement }}</div>
						<div class="flex flex-wrap items-center gap-2 text-sm text-textSecondary dark:text-slate-400">
							<span><i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ m.unit }}</span>
							<span><i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ m.sub_unit }}</span>
						</div>
					</div>
					<div class="flex flex-row gap-2 shrink-0 items-center justify-end">
						<Link :href="route('guru.materials.show', m.id)" class="btn-secondary p-2" title="Detail Materi">
							<i class="fa-solid fa-key"></i>
						</Link>
						<a v-if="m.link" :href="m.link" class="btn-secondary p-2" target="_blank" rel="noopener" title="Buka Link">
							<i class="fa-solid fa-link"></i>
						</a>
						<form v-if="m.is_bookmarked" method="POST" :action="route('guru.materials.unbookmark', m.id)" @submit.prevent="toggleBookmark(m)"><button class="btn-danger p-2" title="Hapus Bookmark"><i class="fa-solid fa-trash"></i></button></form>
						<form v-else method="POST" :action="route('guru.materials.bookmark', m.id)" @submit.prevent="toggleBookmark(m)"><button class="btn-secondary p-2" title="Bookmark"><i class="fa-regular fa-bookmark"></i></button></form>
					</div>
				</div>
				<div v-if="materials.last_page > 1" class="mt-4">
					<PaginationLinks :paginator="materials" />
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
