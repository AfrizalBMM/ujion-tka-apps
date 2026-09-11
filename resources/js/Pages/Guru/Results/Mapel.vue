<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

defineProps({
	exam: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	sessions: {
		type: Array,
		required: true,
	},
	stats: {
		type: Object,
		required: true,
	},
	questionStats: {
		type: Array,
		default: () => [],
	},
	isSurvey: {
		type: Boolean,
		required: true,
	},
	surveyOverview: {
		type: Object,
		default: null,
	},
});

const formatSkor = (value) => Number(value).toFixed(1);
</script>

<template>
	<Head :title="`${mapel.nama_label} - Analisis Hasil`" />

	<GuruLayout>
		<div class="mb-8">
			<div class="flex flex-wrap items-center justify-between gap-4">
				<div>
					<nav class="mb-4 flex items-center gap-2 text-sm font-semibold text-textSecondary">
						<Link :href="route('guru.results.index')" class="hover:text-primary">Hasil</Link>
						<i class="fa-solid fa-chevron-right text-[10px]"></i>
						<Link :href="route('guru.results.show', exam.id)" class="hover:text-primary">{{ exam.judul }}</Link>
						<i class="fa-solid fa-chevron-right text-[10px]"></i>
						<span class="text-slate-900">{{ mapel.nama_label }}</span>
					</nav>
					<h1 class="text-2xl font-bold text-slate-900">{{ mapel.nama_label }}</h1>
					<p class="mt-1 text-sm text-textSecondary">
						{{ isSurvey ? 'Analisis distribusi respons, skor per dimensi, dan kategori profil siswa.' : `Informasi analisis mendalam untuk pengerjaan ${mapel.nama_label}.` }}
					</p>
				</div>
				<div class="flex gap-3">
					<a :href="route('guru.results.export', [exam.id, mapel.id])" class="btn-secondary px-5 py-2.5 text-sm font-bold">
						<i class="fa-solid fa-file-export mr-2"></i> Export CSV
					</a>
				</div>
			</div>
		</div>

		<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<div class="stat-card">
				<div class="stat-icon bg-indigo-600">
					<i class="fa-solid fa-users"></i>
				</div>
				<div>
					<div class="metric-label">{{ isSurvey ? 'Jumlah Responden' : 'Total Peserta' }}</div>
					<div class="metric-value">{{ stats.total }}</div>
				</div>
			</div>
			<div class="stat-card">
				<div class="stat-icon bg-emerald-500">
					<i class="fa-solid fa-star text-white"></i>
				</div>
				<div>
					<div class="metric-label">{{ isSurvey ? 'Indeks Respons' : 'Rata-rata Skor' }}</div>
					<div class="metric-value">{{ stats.avg }}</div>
				</div>
			</div>
			<div class="stat-card">
				<div class="stat-icon bg-amber-500">
					<i class="fa-solid fa-trophy"></i>
				</div>
				<div>
					<div class="metric-label">{{ isSurvey ? 'Indeks Tertinggi' : 'Skor Tertinggi' }}</div>
					<div class="metric-value text-amber-600">{{ stats.max }}</div>
				</div>
			</div>
			<div class="stat-card">
				<div class="stat-icon bg-rose-500">
					<i class="fa-solid fa-circle-down"></i>
				</div>
				<div>
					<div class="metric-label">{{ isSurvey ? 'Indeks Terendah' : 'Skor Terendah' }}</div>
					<div class="metric-value text-rose-600">{{ stats.min }}</div>
				</div>
			</div>
		</div>

		<div class="grid gap-8 lg:grid-cols-3">
			<div class="lg:col-span-2">
				<div class="rounded-[32px] border border-white/80 bg-white/80 overflow-hidden shadow-card">
					<div class="border-b border-slate-100 px-6 py-5">
						<h3 class="text-lg font-bold text-slate-900">{{ isSurvey ? 'Daftar Respons Siswa' : 'Daftar Hasil Peserta' }}</h3>
					</div>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead>
								<tr class="bg-slate-50/50 text-[10px] font-bold uppercase tracking-[0.15em] text-textSecondary">
									<th class="px-6 py-4">Nama Siswa</th>
									<th class="px-6 py-4">Waktu</th>
									<th class="px-6 py-4 text-center">{{ isSurvey ? 'Indeks' : 'Skor' }}</th>
									<th class="px-6 py-4 text-right">Aksi</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-slate-100">
								<template v-if="sessions.length > 0">
									<tr v-for="s in sessions" :key="s.id" class="group hover:bg-slate-50/50 transition-colors">
										<td class="px-6 py-4">
											<div class="font-bold text-slate-900">{{ s.nama }}</div>
											<div class="text-[10px] text-textSecondary">{{ s.nomor_wa || '-' }}</div>
										</td>
										<td class="px-6 py-4">
											<div class="text-xs text-slate-700">{{ s.waktu_mulai ?? '-' }} - {{ s.waktu_selesai ?? '-' }}</div>
											<div class="text-[10px] text-textSecondary">{{ s.durasi_menit }} Menit</div>
										</td>
										<td class="px-6 py-4">
											<div class="flex justify-center">
												<span class="rounded-xl px-3 py-1 text-sm font-black" :class="s.skor >= 70 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
													{{ formatSkor(s.skor) }}
												</span>
											</div>
										</td>
										<td class="px-6 py-4 text-right">
											<Link :href="route('guru.results.student', s.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors hover:bg-indigo-600 hover:text-white" title="Lihat Detail">
												<i class="fa-solid fa-eye text-xs"></i>
											</Link>
										</td>
									</tr>
								</template>
								<tr v-else>
									<td colspan="4" class="px-6 py-12 text-center text-textSecondary">Belum ada siswa yang menyelesaikan komponen ini.</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="space-y-6">
				<template v-if="isSurvey">
					<div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
						<h3 class="mb-5 text-lg font-bold text-slate-900">Distribusi Profil</h3>
						<div class="space-y-3">
							<template v-if="Object.keys(surveyOverview?.category_distribution ?? {}).length > 0">
								<div v-for="(count, label) in surveyOverview.category_distribution" :key="label" class="flex items-center justify-between rounded-2xl bg-slate-50 p-3">
									<div class="text-sm font-bold text-slate-900">{{ label }}</div>
									<div class="rounded-xl bg-indigo-100 px-3 py-1 text-sm font-black text-indigo-700">{{ count }}</div>
								</div>
							</template>
							<div v-else class="text-sm text-textSecondary">Belum ada distribusi profil.</div>
						</div>
					</div>

					<div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
						<h3 class="mb-2 text-lg font-bold text-slate-900">Skor Per Dimensi</h3>
						<div class="space-y-3">
							<template v-if="(surveyOverview?.dimension_stats ?? []).length > 0">
								<div v-for="dimension in surveyOverview.dimension_stats" :key="dimension.dimensi" class="rounded-2xl bg-slate-50 p-4">
									<div class="flex items-center justify-between gap-3">
										<div class="font-semibold text-slate-900">{{ dimension.dimensi }}</div>
										<div class="text-sm font-black text-indigo-600">{{ Number(dimension.score_percent).toFixed(1) }}</div>
									</div>
									<div class="mt-1 text-xs text-textSecondary">{{ dimension.category }}</div>
								</div>
							</template>
							<div v-else class="text-sm text-textSecondary">Belum ada data dimensi.</div>
						</div>
					</div>
				</template>
				<template v-else>
					<div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
						<h3 class="mb-5 text-lg font-bold text-slate-900">Top 5 Ranking</h3>
						<div class="space-y-4">
							<div v-for="(s, index) in sessions.slice(0, 5)" :key="s.id" class="flex items-center gap-4 rounded-2xl bg-slate-50 p-3">
								<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-black" :class="index === 0 ? 'bg-amber-100 text-amber-700' : (index === 1 ? 'bg-slate-200 text-slate-600' : (index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-white text-slate-400'))">
									{{ index + 1 }}
								</div>
								<div class="min-w-0 flex-1">
									<div class="truncate text-sm font-bold text-slate-900">{{ s.nama }}</div>
									<div class="text-[10px] text-textSecondary">{{ formatSkor(s.skor) }} Poin</div>
								</div>
							</div>
						</div>
					</div>

					<div class="rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
						<h3 class="mb-2 text-lg font-bold text-slate-900">Analisis Butir Soal</h3>
						<p class="mb-5 text-xs text-textSecondary">Tingkat akurasi jawaban siswa untuk setiap nomor.</p>
						<div class="grid grid-cols-5 gap-2">
							<div v-for="q in questionStats" :key="q.nomor" class="group relative">
								<div class="flex h-10 w-full items-center justify-center rounded-xl font-bold text-white shadow-sm transition-transform hover:scale-110" :class="q.percent >= 75 ? 'bg-emerald-500' : (q.percent >= 50 ? 'bg-amber-500' : 'bg-rose-500')" :title="`Nomor ${q.nomor}: ${q.percent}%`">
									{{ q.nomor }}
								</div>
								<div class="pointer-events-none absolute bottom-full left-1/2 mb-2 w-20 -translate-x-1/2 rounded-lg bg-slate-900 px-2 py-1 text-center text-[9px] font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
									{{ q.percent }}% Benar
								</div>
							</div>
						</div>
						<div class="mt-6 flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-textSecondary">
							<div class="flex items-center gap-1.5"><div class="h-2 w-2 rounded-full bg-emerald-500"></div> Sering Benar</div>
							<div class="flex items-center gap-1.5"><div class="h-2 w-2 rounded-full bg-rose-500"></div> Sering Salah</div>
						</div>
					</div>
				</template>
			</div>
		</div>

		<div v-if="isSurvey" class="mt-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
			<h3 class="mb-5 text-lg font-bold text-slate-900">Distribusi Jawaban Per Butir</h3>
			<div class="space-y-4">
				<template v-if="(surveyOverview?.question_breakdown ?? []).length > 0">
					<div v-for="question in surveyOverview.question_breakdown" :key="question.nomor" class="rounded-2xl bg-slate-50 p-4">
						<div class="flex flex-wrap items-center justify-between gap-3">
							<div class="font-semibold text-slate-900">Butir {{ question.nomor }} · {{ question.dimensi }}</div>
							<div v-if="question.subdimensi" class="text-xs text-textSecondary">{{ question.subdimensi }}</div>
						</div>
						<div class="mt-3 grid gap-2 md:grid-cols-2">
							<div v-for="option in question.distribution" :key="option.kode" class="rounded-xl bg-white px-3 py-2 text-sm shadow-sm">
								<div class="font-semibold text-slate-900">{{ option.kode }} · {{ option.label }}</div>
								<div class="mt-1 text-xs text-textSecondary">{{ option.count }} respons · {{ option.percent }}%</div>
							</div>
						</div>
					</div>
				</template>
				<div v-else class="text-sm text-textSecondary">Belum ada distribusi jawaban.</div>
			</div>
		</div>
	</GuruLayout>
</template>
