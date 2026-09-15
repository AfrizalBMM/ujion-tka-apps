<script setup>
import { Head } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

defineProps({
	exam: {
		type: Object,
		required: true,
	},
	participantsCount: {
		type: Number,
		default: 0,
	},
	averageScore: {
		type: Number,
		default: 0,
	},
	averageScoreFormatted: {
		type: String,
		default: '0,00',
	},
	surveyComponents: {
		type: Array,
		default: () => [],
	},
	ranking: {
		type: Array,
		default: () => [],
	},
	distribution: {
		type: Object,
		default: () => ({}),
	},
});
</script>

<template>
	<Head title="Analisis Ujian" />

	<SuperadminLayout>
		<div class="max-w-4xl space-y-8">
			<div class="grid gap-4 sm:grid-cols-2">
				<div class="card p-6">
					<div class="text-sm font-bold text-slate-500">Peserta Akademik Selesai</div>
					<div class="mt-2 text-3xl font-bold text-slate-900">{{ participantsCount }}</div>
				</div>
				<div class="card p-6">
					<div class="text-sm font-bold text-slate-500">Rata-rata Skor Akademik</div>
					<div class="mt-2 text-3xl font-bold text-blue-700">{{ averageScoreFormatted }}</div>
				</div>
			</div>
			<div v-if="surveyComponents.length > 0" class="card p-6">
				<h2 class="font-bold text-xl mb-4">Ringkasan Survey</h2>
				<div class="grid gap-4 md:grid-cols-2">
					<div v-for="survey in surveyComponents" :key="survey.mapel_label" class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
						<div class="font-bold text-slate-900">{{ survey.mapel_label }}</div>
						<div class="mt-1 text-sm text-slate-500">{{ survey.participants }} responden &middot; indeks {{ survey.average_score_formatted }}</div>
						<div class="mt-3 space-y-2">
							<div v-for="(count, label) in survey.category_distribution" :key="label" class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm">
								<span>{{ label }}</span>
								<span class="font-bold text-indigo-600">{{ count }}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="card p-6">
				<h2 class="font-bold text-xl mb-4">Ranking Peserta</h2>
				<div class="table-container">
					<table class="table-ujion w-full min-w-[420px]">
						<thead>
							<tr>
								<th>Peringkat</th>
								<th>Nama</th>
								<th>Skor</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(p, i) in ranking" :key="i">
								<td>{{ i+1 }}</td>
								<td>{{ p.name }}</td>
								<td>{{ p.score }}</td>
							</tr>
							<tr v-if="ranking.length === 0">
								<td colspan="3" class="text-center text-gray-400">Belum ada peserta yang menyelesaikan ujian.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card p-6">
				<h2 class="font-bold text-xl mb-4">Distribusi Nilai</h2>
				<div class="table-container">
					<table class="table-ujion w-full min-w-[420px]">
						<thead>
							<tr>
								<th>Rentang Nilai</th>
								<th>Jumlah Peserta</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(count, range) in distribution" :key="range">
								<td>{{ range }}</td>
								<td>{{ count }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="flex flex-col justify-end gap-2 sm:flex-row">
				<a :href="route('superadmin.exams.analysis.export-csv', exam.id)" class="btn-primary w-full sm:w-auto">Export CSV</a>
				<a :href="route('superadmin.exams.analysis.print', exam.id)" target="_blank" rel="noopener" class="btn-secondary w-full sm:w-auto">Versi Cetak</a>
			</div>
		</div>
	</SuperadminLayout>
</template>
