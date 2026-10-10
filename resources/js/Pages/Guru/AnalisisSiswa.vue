<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

const props = defineProps({
	exams: {
		type: Array,
		required: true,
	},
	selectedExamId: {
		type: [Number, String, null],
		default: null,
	},
	stats: {
		type: Object,
		default: () => ({
			total_siswa: 0,
			rata_rata: 0,
			tertinggi: 0,
			terendah: 0,
			lulus: 0,
		}),
	},
	siswa: {
		type: Array,
		default: () => [],
	},
	distribution: {
		type: Object,
		default: () => ({}),
	},
	passingGrade: {
		type: Number,
		default: 500,
	},
});

const formatSkor = (value) => (value === null || value === undefined ? '-' : Number(value).toFixed(1));

const formatRupiah = (value) => {
	if (value === null || value === undefined) return '-';
	return 'Rp' + Number(value).toLocaleString('id-ID');
};

const selectedExam = ref(props.selectedExamId || (props.exams.length > 0 ? props.exams[0].id : null));

const onExamChange = () => {
	if (selectedExam.value) {
		router.get(route('guru.analisis-siswa'), { exam_id: selectedExam.value }, {
			preserveScroll: true,
			preserveState: true,
			only: ['selectedExamId', 'stats', 'siswa', 'distribution'],
		});
	}
};

// Chart distribusi nilai — CSS bar chart (no external lib needed for this simple case)
const distributionBars = computed(() => {
	const dist = props.distribution || {};
	const ranges = [
		{ key: '700-800', label: '700-800', color: 'bg-emerald-500', textColor: 'text-emerald-700' },
		{ key: '600-699', label: '600-699', color: 'bg-emerald-400', textColor: 'text-emerald-600' },
		{ key: '500-599', label: '500-599', color: 'bg-amber-400', textColor: 'text-amber-600' },
		{ key: '400-499', label: '400-499', color: 'bg-orange-400', textColor: 'text-orange-600' },
		{ key: '200-399', label: '200-399', color: 'bg-rose-500', textColor: 'text-rose-700' },
	];
	const maxCount = Math.max(...ranges.map(r => dist[r.key] || 0), 1);
	return ranges.map(r => ({
		...r,
		count: dist[r.key] || 0,
		percent: Math.round(((dist[r.key] || 0) / maxCount) * 100),
		sharePercent: props.stats.total_siswa > 0
			? Math.round(((dist[r.key] || 0) / props.stats.total_siswa) * 100)
			: 0,
	}));
});

const hasData = computed(() => props.siswa.length > 0);

const searchQuery = ref('');
const filteredSiswa = computed(() => {
	if (!searchQuery.value.trim()) return props.siswa;
	const q = searchQuery.value.toLowerCase().trim();
	return props.siswa.filter(s =>
		(s.nama || '').toLowerCase().includes(q) ||
		(s.nomor_wa || '').toLowerCase().includes(q)
	);
});

const getStatusBadge = (skor) => {
	if (skor === null || skor === undefined) return { label: 'Belum Selesai', class: 'bg-slate-100 text-slate-600' };
	if (skor >= props.passingGrade) return { label: 'Lulus', class: 'bg-emerald-100 text-emerald-700' };
	return { label: 'Belum Lulus', class: 'bg-rose-100 text-rose-700' };
};
</script>

<template>
	<Head title="Analisis Hasil Siswa" />

	<GuruLayout>
		<div class="w-full space-y-6">
			<!-- Header -->
			<div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
				<div>
					<span class="page-kicker">Dashboard Analisis</span>
					<h1 class="page-title">Analisis Hasil & Siswa</h1>
					<p class="mt-1 text-sm text-textSecondary">Pantau hasil ujian siswa secara menyeluruh — statistik, distribusi nilai, dan detail per siswa.</p>
				</div>
				<div class="flex flex-wrap gap-3">
					<Link :href="route('guru.results.index')" class="btn-secondary px-5 py-2.5 text-sm font-bold">
						<i class="fa-solid fa-list mr-2"></i> Daftar Ujian
					</Link>
				</div>
			</div>

			<!-- Filter Paket Ujian -->
			<div class="rounded-[32px] border border-white/80 bg-white/80 p-5 shadow-card">
				<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div class="flex-1">
						<label for="exam-filter" class="mb-2 block text-xs font-bold uppercase tracking-widest text-textSecondary">Filter Paket Ujian</label>
						<select
							id="exam-filter"
							v-model="selectedExam"
							@change="onExamChange"
							class="w-full rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100"
						>
							<option v-for="exam in exams" :key="exam.id" :value="exam.id">
								{{ exam.nama }} ({{ exam.total_peserta }} peserta)
							</option>
						</select>
					</div>
					<div class="sm:w-64">
						<label for="search-siswa" class="mb-2 block text-xs font-bold uppercase tracking-widest text-textSecondary">Cari Siswa</label>
						<div class="relative">
							<i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
							<input
								id="search-siswa"
								v-model="searchQuery"
								type="text"
								placeholder="Nama / No WA..."
								class="w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3 pl-11 pr-4 text-sm text-slate-800 transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100"
							>
						</div>
					</div>
				</div>
			</div>

			<!-- Stats Cards -->
			<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
				<div class="stat-card">
					<div class="stat-icon bg-indigo-600">
						<i class="fa-solid fa-users"></i>
					</div>
					<div>
						<div class="metric-label">Total Siswa</div>
						<div class="metric-value">{{ stats.total_siswa }}</div>
					</div>
				</div>
				<div class="stat-card">
					<div class="stat-icon bg-emerald-500">
						<i class="fa-solid fa-star text-white"></i>
					</div>
					<div>
						<div class="metric-label">Rata-rata Skor</div>
						<div class="metric-value">{{ formatSkor(stats.rata_rata) }}</div>
					</div>
				</div>
				<div class="stat-card">
					<div class="stat-icon bg-amber-500">
						<i class="fa-solid fa-trophy"></i>
					</div>
					<div>
						<div class="metric-label">Tertinggi</div>
						<div class="metric-value text-amber-600">{{ formatSkor(stats.tertinggi) }}</div>
					</div>
				</div>
				<div class="stat-card">
					<div class="stat-icon bg-rose-500">
						<i class="fa-solid fa-circle-down"></i>
					</div>
					<div>
						<div class="metric-label">Terendah</div>
						<div class="metric-value text-rose-600">{{ formatSkor(stats.terendah) }}</div>
					</div>
				</div>
				<div class="stat-card">
					<div class="stat-icon bg-teal-500">
						<i class="fa-solid fa-graduation-cap text-white"></i>
					</div>
					<div>
						<div class="metric-label">Lulus (≥{{ passingGrade }})</div>
						<div class="metric-value text-teal-600">{{ stats.lulus }}</div>
					</div>
				</div>
			</div>

			<!-- Main Content Grid -->
			<div class="grid gap-6 lg:grid-cols-3">
				<!-- Tabel Hasil Siswa -->
				<div class="lg:col-span-2">
					<div class="rounded-[32px] border border-white/80 bg-white/80 overflow-hidden shadow-card">
						<div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
							<h3 class="text-lg font-bold text-slate-900">Hasil per Siswa</h3>
							<span class="text-xs font-bold uppercase tracking-widest text-textSecondary">{{ filteredSiswa.length }} siswa</span>
						</div>
						<div class="overflow-x-auto">
							<table class="w-full text-left">
								<thead>
									<tr class="bg-slate-50/50 text-[10px] font-bold uppercase tracking-[0.15em] text-textSecondary">
										<th class="px-6 py-4">#</th>
										<th class="px-6 py-4">Nama Siswa</th>
										<th class="px-6 py-4 text-center">Skor</th>
										<th class="px-6 py-4 text-center">Status</th>
										<th class="px-6 py-4">Waktu Selesai</th>
										<th class="px-6 py-4 text-right">Aksi</th>
									</tr>
								</thead>
								<tbody class="divide-y divide-slate-100">
									<template v-if="filteredSiswa.length > 0">
										<tr v-for="(s, index) in filteredSiswa" :key="s.id" class="group hover:bg-slate-50/50 transition-colors">
											<td class="px-6 py-4 text-sm font-bold text-slate-400">{{ index + 1 }}</td>
											<td class="px-6 py-4">
												<div class="font-bold text-slate-900">{{ s.nama }}</div>
												<div class="text-[10px] text-textSecondary">{{ s.nomor_wa || '-' }}</div>
											</td>
											<td class="px-6 py-4 text-center">
												<span v-if="s.skor !== null && s.skor !== undefined"
													class="rounded-xl px-3 py-1 text-sm font-black"
													:class="s.skor >= passingGrade ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
													{{ formatSkor(s.skor) }}
												</span>
												<span v-else class="text-xs text-slate-400">-</span>
											</td>
											<td class="px-6 py-4 text-center">
												<span class="rounded-full px-2.5 py-1 text-[10px] font-bold"
													:class="getStatusBadge(s.skor).class">
													{{ getStatusBadge(s.skor).label }}
												</span>
											</td>
											<td class="px-6 py-4">
												<div class="text-xs text-slate-700">{{ s.waktu_selesai || '-' }}</div>
												<div class="text-[10px] text-textSecondary">{{ s.durasi_menit || 0 }} menit</div>
											</td>
											<td class="px-6 py-4 text-right">
												<Link :href="route('guru.results.student', s.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors hover:bg-indigo-600 hover:text-white" title="Lihat Detail">
													<i class="fa-solid fa-eye text-xs"></i>
												</Link>
											</td>
										</tr>
									</template>
									<tr v-else>
										<td colspan="6" class="px-6 py-12 text-center text-textSecondary">
											<i class="fa-solid fa-folder-open mb-2 block text-2xl text-slate-300"></i>
											Belum ada siswa yang menyelesaikan ujian ini.
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- Distribusi Nilai Chart (CSS bar chart) -->
				<div class="space-y-6">
					<div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
						<h3 class="mb-5 text-lg font-bold text-slate-900">Distribusi Nilai</h3>
						<div class="space-y-4">
							<div v-for="bar in distributionBars" :key="bar.key" class="space-y-1.5">
								<div class="flex items-center justify-between text-xs">
									<span class="font-bold text-slate-700">{{ bar.label }}</span>
									<span class="font-semibold text-textSecondary">{{ bar.count }} siswa ({{ bar.sharePercent }}%)</span>
								</div>
								<div class="h-7 overflow-hidden rounded-xl bg-slate-100">
									<div
										class="h-full rounded-xl transition-all duration-700 ease-out"
										:class="bar.color"
										:style="{ width: bar.percent + '%' }"
									></div>
								</div>
							</div>
						</div>
						<div class="mt-6 flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-textSecondary">
							<div class="flex items-center gap-1.5"><div class="h-2 w-2 rounded-full bg-emerald-500"></div> Tinggi</div>
							<div class="flex items-center gap-1.5"><div class="h-2 w-2 rounded-full bg-rose-500"></div> Rendah</div>
						</div>
					</div>

					<!-- Ringkasan kelulusan -->
					<div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
						<h3 class="mb-5 text-lg font-bold text-slate-900">Ringkasan Kelulusan</h3>
						<div class="space-y-4">
							<div class="flex items-center justify-between rounded-2xl bg-emerald-50 p-4">
								<div class="flex items-center gap-3">
									<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-white">
										<i class="fa-solid fa-check"></i>
									</div>
									<div>
										<div class="text-sm font-bold text-slate-900">Lulus</div>
										<div class="text-[10px] text-textSecondary">Skor ≥ {{ passingGrade }}</div>
									</div>
								</div>
								<div class="text-2xl font-black text-emerald-600">{{ stats.lulus }}</div>
							</div>
							<div class="flex items-center justify-between rounded-2xl bg-rose-50 p-4">
								<div class="flex items-center gap-3">
									<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500 text-white">
										<i class="fa-solid fa-xmark"></i>
									</div>
									<div>
										<div class="text-sm font-bold text-slate-900">Belum Lulus</div>
										<div class="text-[10px] text-textSecondary">Skor &lt; {{ passingGrade }}</div>
									</div>
								</div>
								<div class="text-2xl font-black text-rose-600">{{ Math.max(stats.total_siswa - stats.lulus, 0) }}</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
