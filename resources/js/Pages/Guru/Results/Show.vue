<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

defineProps({
	exam: {
		type: Object,
		required: true,
	},
	tokens: {
		type: Array,
		required: true,
	},
});
</script>

<template>
	<Head :title="`${exam.judul} - Ringkasan`" />

	<GuruLayout>
		<div class="mb-8">
			<Link :href="route('guru.results.index')" class="mb-4 inline-flex items-center text-sm font-semibold text-textSecondary hover:text-primary">
				<i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Daftar
			</Link>
			<div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
				<div>
					<h1 class="text-2xl font-bold text-slate-900">{{ exam.judul }}</h1>
					<p class="mt-1 text-sm text-textSecondary">Pilih komponen ujian untuk melihat analisis detail dan daftar hasil.</p>
				</div>
			</div>
		</div>

		<div class="grid gap-6 md:grid-cols-2">
			<div v-for="t in tokens" :key="t.id" class="metric-card group flex flex-col p-6">
				<div class="mb-6 flex items-start justify-between">
					<div class="flex h-14 w-14 items-center justify-center rounded-[20px] bg-slate-900 text-white shadow-lg transition-transform duration-300 group-hover:scale-110">
						<i class="fa-solid text-xl" :class="t.is_survey ? 'fa-clipboard-list' : 'fa-book-open'"></i>
					</div>
					<div class="text-right">
						<span class="metric-label">Token Aktif</span>
						<div class="mt-1 flex items-center gap-2">
							<code class="rounded bg-indigo-50 px-2 py-1 text-sm font-bold text-indigo-600">{{ t.token }}</code>
						</div>
					</div>
				</div>

				<h3 class="text-xl font-bold text-slate-900">{{ t.nama_label }}</h3>
				<p class="mt-1 text-sm text-textSecondary">{{ t.is_survey ? 'Analisis profil dan distribusi respons siswa' : (t.nama_mapel ?? 'Mata Pelajaran') }}</p>

				<div class="mt-6 flex items-center justify-between rounded-2xl bg-slate-50/50 p-4">
					<div class="text-center">
						<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">{{ t.is_survey ? 'Respon Masuk' : 'Siswa Ikut' }}</div>
						<div class="mt-1 text-xl font-black text-slate-900">{{ t.session_count }}</div>
					</div>
					<div class="h-8 w-px bg-slate-200"></div>
					<div class="text-center">
						<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">{{ t.is_survey ? 'Indeks Respons' : 'Rata-rata' }}</div>
						<div class="mt-1 text-xl font-black text-indigo-600">{{ t.avg_score }}</div>
					</div>
				</div>

				<div class="mt-6">
					<Link :href="route('guru.results.mapel', [exam.id, t.mapel_paket_id])" class="btn-primary w-full justify-center py-3 font-bold shadow-md">
						{{ t.is_survey ? 'Buka Dashboard Survey' : 'Buka Dashboard Mapel' }}
					</Link>
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
