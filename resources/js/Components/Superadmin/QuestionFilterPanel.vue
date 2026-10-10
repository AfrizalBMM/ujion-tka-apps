<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';

const props = defineProps({
	filters: { type: Object, default: () => ({}) },
	jenjangs: { type: Array, default: () => [] },
	materials: { type: Array, default: () => [] },
});

const filterForm = ref(null);
const searchValue = ref(props.filters.search ?? '');

const questionTypeLabel = (type) => {
	switch (type) {
		case 'multiple_choice': return 'Pilihan Ganda';
		case 'matching': return 'Menjodohkan';
		case 'short_answer': return 'Jawaban Singkat';
		default: return 'Semua Jenis';
	}
};

const statusLabel = (status) => {
	switch (status) {
		case 'active': return 'Aktif';
		case 'draft': return 'Draft';
		default: return 'Semua Status';
	}
};

const mapelFilters = computed(() => [...new Set(props.materials.map((m) => m.mapel).filter(Boolean))]);
const curriculumFilters = computed(() => [...new Set(props.materials.map((m) => m.curriculum).filter(Boolean))]);
const selectedJenjang = computed(() => props.jenjangs.find((j) => j.id == (props.filters.jenjang_id ?? '')));

let debounceTimer;
const onSearchInput = () => {
	clearTimeout(debounceTimer);
	debounceTimer = setTimeout(() => {
		if (filterForm.value) filterForm.value.submit();
	}, 500);
};

onBeforeUnmount(() => {
	clearTimeout(debounceTimer);
});
</script>

<template>
	<div class="card">
		<form ref="filterForm" id="filter-form" data-ssd-autosubmit method="GET" :action="route('superadmin.global-questions.index')" class="grid grid-cols-1 gap-3 lg:grid-cols-6">
			<div class="lg:col-span-2">
				<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Soal</label>
				<input class="input mt-1" type="text" name="search" v-model="searchValue" @input="onSearchInput" placeholder="Cari pertanyaan, kunci, pembahasan, atau materi...">
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
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
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
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
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
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..." aria-label="Cari mapel"></div>
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
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..." aria-label="Cari kurikulum"></div>
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
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..." aria-label="Cari jenjang"></div>
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
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..." aria-label="Cari"></div>
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
</template>
