<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

defineProps({
	material: {
		type: Object,
		required: true,
	},
	session: {
		type: Object,
		required: true,
	},
	attempts: {
		type: Array,
		required: true,
	},
	telaahAnswers: {
		type: Array,
		required: true,
	},
	avgScore: {
		type: Number,
		default: null,
	},
});
</script>

<template>
	<Head :title="`Detail Latihan Materi: ${session.nama}`" />

	<GuruLayout>
		<div class="mb-8">
			<Link :href="route('guru.results.practice.show', material.id)" class="mb-4 inline-flex items-center text-sm font-semibold text-textSecondary hover:text-primary">
				<i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Analisis Materi
			</Link>
			<div class="flex flex-wrap items-center justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold text-slate-900">Analisis Latihan: {{ session.nama }}</h1>
					<p class="mt-1 text-sm text-textSecondary">{{ material.sub_unit }} &middot; {{ session.nomor_wa || '-' }}</p>
				</div>
				<div class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-white px-6 py-3 shadow-sm">
					<div class="text-right">
						<div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Rata-rata Paket</div>
						<div class="text-2xl font-black text-indigo-600">{{ avgScore !== null ? Number(avgScore).toFixed(1) : '-' }}</div>
					</div>
					<div class="h-8 w-px bg-slate-200"></div>
					<div>
						<div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Status</div>
						<span class="badge-secondary">{{ session.status }}</span>
					</div>
				</div>
			</div>
		</div>

		<div class="mb-8">
			<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
				<div>
					<h3 class="text-lg font-bold text-slate-900">Paket Latihan</h3>
					<p class="mt-1 text-sm text-textSecondary">Ringkasan pengerjaan siswa per paket dan unduhan PDF paket.</p>
				</div>
			</div>

			<div class="grid gap-5 md:grid-cols-3">
				<template v-if="attempts.length > 0">
					<div v-for="attempt in attempts" :key="attempt.id" class="flex min-h-[280px] flex-col justify-between rounded-[28px] border border-white/80 bg-white/85 p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-hover">
						<div>
							<div class="mb-4 flex items-start justify-between gap-3">
								<div>
									<div class="text-[10px] font-bold uppercase tracking-[0.18em] text-textSecondary">Paket</div>
									<div class="mt-1 text-3xl font-black text-slate-900">{{ attempt.paket_no ?? '-' }}</div>
								</div>
								<span :class="attempt.status === 'selesai' ? 'badge-success' : 'badge-warning'">{{ attempt.status }}</span>
							</div>

							<div class="rounded-2xl bg-indigo-50 p-4">
								<div class="text-[10px] font-bold uppercase tracking-widest text-indigo-700">Skor</div>
								<div class="mt-1 text-3xl font-black text-indigo-700">{{ attempt.skor !== null ? Number(attempt.skor).toFixed(1) : '-' }}</div>
							</div>

							<div class="mt-4">
								<div class="mb-1 flex items-center justify-between text-xs text-textSecondary">
									<span>Terjawab</span>
									<span class="font-bold text-slate-700">{{ attempt.answered_count }}/{{ attempt.total_soal }}</span>
								</div>
								<div class="h-2 rounded-full bg-slate-200">
									<div class="h-2 rounded-full bg-indigo-500" :style="{ width: `${attempt.progress}%` }"></div>
								</div>
							</div>

							<div class="mt-4 grid grid-cols-3 gap-2 text-center">
								<div class="rounded-2xl bg-emerald-50 p-3">
									<div class="text-lg font-black text-emerald-700">{{ attempt.correct_count }}</div>
									<div class="text-[10px] font-bold uppercase tracking-widest text-emerald-700">Benar</div>
								</div>
								<div class="rounded-2xl bg-rose-50 p-3">
									<div class="text-lg font-black text-rose-700">{{ attempt.wrong_count }}</div>
									<div class="text-[10px] font-bold uppercase tracking-widest text-rose-700">Salah</div>
								</div>
								<div class="rounded-2xl bg-slate-50 p-3">
									<div class="text-lg font-black text-slate-700">{{ attempt.empty_count }}</div>
									<div class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Kosong</div>
								</div>
							</div>

							<div class="mt-4 space-y-1 text-xs text-textSecondary">
								<div>Mulai: <span class="font-semibold text-slate-700">{{ attempt.waktu_mulai ?? '-' }}</span></div>
								<div>Selesai: <span class="font-semibold text-slate-700">{{ attempt.waktu_selesai ?? '-' }}</span></div>
							</div>
						</div>

						<div class="mt-5">
							<a v-if="attempt.has_package"
								:href="route('guru.results.practice.package-pdf', { material: material.id, session: session.id, attempt: attempt.id })"
								class="btn-secondary w-full justify-center"
								target="_blank"
								rel="noopener">
								<i class="fa-solid fa-file-pdf"></i>
								Download PDF Hasil Paket {{ attempt.paket_no }}
							</a>
							<button v-else-if="attempt.paket_no" type="button" class="btn-secondary w-full justify-center" disabled
								title="Paket sudah diregenerasi admin — PDF hanya tersedia untuk paket aktif">
								<i class="fa-solid fa-file-pdf"></i>
								PDF tidak tersedia (paket arsip)
							</button>
							<button v-else type="button" class="btn-secondary w-full justify-center" disabled>
								<i class="fa-solid fa-file-pdf"></i>
								PDF belum tersedia
							</button>
						</div>
					</div>
				</template>
				<div v-else class="col-span-full rounded-[28px] border border-white/80 bg-white/85 p-8 text-center text-sm text-textSecondary shadow-card">
					Belum ada paket yang dikerjakan siswa ini.
				</div>
			</div>
		</div>

		<div class="mb-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
			<h3 class="mb-4 text-lg font-bold text-slate-900">Telaah Soal</h3>
			<div class="grid gap-4 md:grid-cols-2">
				<template v-if="telaahAnswers.length > 0">
					<div v-for="(answer, index) in telaahAnswers" :key="index" class="rounded-2xl bg-slate-50 p-4">
						<div class="flex items-center justify-between">
							<div class="font-bold text-slate-900">Telaah {{ index + 1 }}</div>
							<span :class="answer.is_correct ? 'badge-success' : 'badge-warning'">{{ answer.is_correct ? 'Benar' : 'Belum tepat' }}</span>
						</div>
						<div class="mt-3 text-sm font-semibold text-slate-800">{{ answer.question_text }}</div>
						<div class="mt-2 text-sm text-textSecondary">Jawaban: <span class="font-semibold">{{ answer.jawaban || '-' }}</span></div>
					</div>
				</template>
				<div v-else class="text-sm text-textSecondary">Belum ada jawaban telaah.</div>
			</div>
		</div>
	</GuruLayout>
</template>
