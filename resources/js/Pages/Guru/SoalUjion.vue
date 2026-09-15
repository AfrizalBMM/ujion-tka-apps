<script setup>
import { inject, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';

const props = defineProps({
	questions: {
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
	filters: {
		type: Object,
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
	bookmarks: {
		type: Array,
		required: true,
	},
});

const route = inject('route');

const filterForm = ref(null);
const search = ref(props.filters.search || '');
const mapel = ref(props.filters.mapel || '');
const curriculum = ref(props.filters.curriculum || '');

let timer = null;
let isComposing = false;

const applyFilters = () => {
	if (isComposing) return;

	const params = new URLSearchParams(new FormData(filterForm.value));
	const query = params.toString();

	router.get(`${route('guru.soal-ujion.index')}${query ? `?${query}` : ''}`, {}, {
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

const onMapelChange = (event) => {
	mapel.value = event.target.value;
	window.clearTimeout(timer);
	timer = window.setTimeout(applyFilters, 0);
};

const onCurriculumChange = (event) => {
	curriculum.value = event.target.value;
	window.clearTimeout(timer);
	timer = window.setTimeout(applyFilters, 0);
};

const toggleBookmark = (question) => {
	router.post(
		route(question.is_bookmarked ? 'guru.soal-ujion.unbookmark' : 'guru.soal-ujion.bookmark', question.id),
		{},
		{ preserveScroll: true }
	);
};

onBeforeUnmount(() => {
	window.clearTimeout(timer);
});
</script>

<template>
	<Head title="Soal dari Ujion" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
					<div>
						<span class="page-kicker">Soal dari Ujion</span>
						<h1 class="page-title">Bank Soal Global Ujion</h1>
						<p class="page-description">Soal-soal ini disusun oleh tim Ujion dan hanya dapat dilihat. Pilih soal dari sini saat membuat paket soal.</p>
					</div>
					<div class="flex flex-wrap items-center gap-2">
						<Link :href="bookmarkUrl" class="btn-secondary inline-flex items-center gap-2 bg-white/95">
							<i :class="bookmarked ? 'fa-solid' : 'fa-regular'" class="fa-bookmark"></i>
							{{ bookmarked ? 'Bookmark Saya' : 'Tampilkan Bookmark' }}
							<span class="badge-info">{{ Array.isArray(bookmarks) ? bookmarks.length : 0 }}</span>
						</Link>
					</div>
				</div>
			</section>
			<div class="card p-4">
				<form ref="filterForm" method="GET" :action="route('guru.soal-ujion.index')" class="flex flex-wrap gap-3 items-end mb-4" data-soal-ujion-filter-form @submit.prevent="applyFilters" @compositionstart="onCompositionStart" @compositionend="onCompositionEnd">
					<input v-if="bookmarked" type="hidden" name="bookmarked" value="1">
					<div class="flex-1 min-w-[150px]">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
						<input type="text" name="search" :value="search" class="input mt-1 w-full" placeholder="Kata kunci pertanyaan..." data-live-search @input="onSearchInput">
					</div>
					<div class="flex-1 min-w-[150px]">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="mapel" :value="mapel" @change="onMapelChange">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ mapel || 'Semua Mapel' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="!mapel ? ' ssd-selected' : ''" data-value="">Semua Mapel</div>
									<div v-for="m in mapels" :key="m" class="ssd-option" :class="mapel == m ? ' ssd-selected' : ''" :data-value="m">{{ m }}</div>
								</div>
							</div>
						</div>
					</div>
					<div class="flex-1 min-w-[150px]">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="curriculum" :value="curriculum" @change="onCurriculumChange">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ curriculum || 'Semua Kurikulum' }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="!curriculum ? ' ssd-selected' : ''" data-value="">Semua Kurikulum</div>
									<div v-for="c in curriculums" :key="c" class="ssd-option" :class="curriculum == c ? ' ssd-selected' : ''" :data-value="c">{{ c }}</div>
								</div>
							</div>
						</div>
					</div>
					<div class="w-full text-[11px] text-textSecondary dark:text-slate-400">Filter otomatis: ketik untuk mencari, atau pilih dropdown untuk menyaring.</div>
				</form>
				<div class="space-y-4">
					<template v-if="questions.data.length > 0">
						<div v-for="question in questions.data" :key="question.id" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-border bg-white p-5 shadow-sm hover:shadow-md transition-all dark:border-slate-800 dark:bg-slate-900">
							<div class="flex-1 min-w-0">
								<div class="flex flex-wrap items-center gap-2 mb-1">
									<span class="badge-info text-[11px]">{{ question.material_curriculum }}</span>
									<span v-if="question.material_mapel" class="badge-primary bg-blue-100 text-blue-700 text-[11px]">{{ question.material_mapel }}</span>
									<span v-if="question.jenjang_nama" class="badge-warning text-[11px]">{{ question.jenjang_nama }}</span>
									<span class="badge-info text-[11px]">Soal Ujion</span>
									<span class="text-xs text-muted">ID: #{{ question.id }}</span>
								</div>
								<div class="font-bold text-lg text-slate-800 dark:text-slate-200 mb-1">{{ question.material_subelement }}</div>
								<div class="flex flex-wrap items-center gap-2 text-sm text-textSecondary dark:text-slate-400">
									<span><i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> <span v-html="question.question_excerpt"></span></span>
								</div>
							</div>
							<div class="flex flex-row gap-2 shrink-0 items-center justify-end">
								<Link :href="route('guru.soal-ujion.show', question.id)" class="btn-secondary p-2" title="Lihat Detail">
									<i class="fa-solid fa-eye"></i>
								</Link>
								<form v-if="question.is_bookmarked" method="POST" :action="route('guru.soal-ujion.unbookmark', question.id)" @submit.prevent="toggleBookmark(question)"><button class="btn-danger p-2" title="Hapus Bookmark"><i class="fa-solid fa-bookmark"></i></button></form>
								<form v-else method="POST" :action="route('guru.soal-ujion.bookmark', question.id)" @submit.prevent="toggleBookmark(question)"><button class="btn-secondary p-2" title="Bookmark"><i class="fa-regular fa-bookmark"></i></button></form>
							</div>
						</div>
					</template>
					<div v-else class="text-center text-textSecondary py-8">Tidak ada soal ditemukan.</div>
					<div class="mt-4">
						<PaginationLinks :paginator="questions" />
					</div>
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
