<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
	session: {
		type: Object,
		default: null,
	},
	totalSoal: {
		type: Number,
		default: 0,
	},
	dijawab: {
		type: Number,
		default: 0,
	},
});

const isSurvey = computed(() => !!props.session?.mapelPaket?.is_survey);
const isPublicExam = computed(() => !!props.session?.landing_exam_order_id);
const publicOrder = computed(() => (isPublicExam.value ? props.session.landingExamOrder : null));
const belumDijawab = computed(() => Math.max(props.totalSoal - props.dijawab, 0));
</script>

<template>
	<Head title="Selesai — Ujion" />

	<GuestLayout hide-showcase>
		<div class="w-full max-w-md space-y-5">
			<div class="rounded-3xl border border-white/80 bg-white/90 p-8 text-center shadow-card">
				<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl shadow-inner">
					✅
				</div>

				<h1 class="text-2xl font-bold text-slate-900">{{ isSurvey ? 'Survey Selesai!' : 'Ujian Selesai!' }}</h1>
				<p class="mt-2 text-sm text-textSecondary">
					{{ session?.nama ? `Terima kasih, ${session.nama}.` : 'Terima kasih.' }}
					Jawaban Anda sudah berhasil disimpan.
				</p>

				<div v-if="session?.skor !== null && session?.skor !== undefined" class="mt-6">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">
						{{ isSurvey ? 'Indeks Respons' : 'Estimasi Skor Mapel' }}
					</div>
					<div class="mt-2 text-5xl font-black text-indigo-600">
						{{ Number(session.skor).toFixed(1) }}
					</div>
					<div class="text-sm text-textSecondary">{{ isSurvey ? 'Profil tersimpan untuk analisis guru.' : '/ 100' }}</div>
				</div>

				<div v-if="totalSoal > 0" class="mt-5 grid grid-cols-2 gap-3 text-sm">
					<div class="rounded-2xl bg-emerald-50 p-3">
						<div class="text-xl font-bold text-emerald-700">{{ dijawab }}</div>
						<div class="text-xs text-emerald-600">Soal Dijawab</div>
					</div>
					<div class="rounded-2xl bg-rose-50 p-3">
						<div class="text-xl font-bold text-rose-700">{{ belumDijawab }}</div>
						<div class="text-xs text-rose-600">Tidak Dijawab</div>
					</div>
				</div>

				<template v-if="isPublicExam && publicOrder">
					<div class="mt-6">
						<a :href="route('ujian-online.result', publicOrder.session_token)" class="btn-primary inline-flex px-8 py-3">
							Lihat Hasil & Pembahasan
						</a>
					</div>
				</template>
				<template v-else>
					<div class="mt-6 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-left text-xs text-amber-800">
						<p class="font-semibold">Jika ada mapel berikutnya:</p>
						<p class="mt-1">Tunggu token mapel berikutnya dari guru/pengawas, kemudian masukkan token tersebut di halaman masuk ujian.</p>
					</div>

					<div class="mt-6">
						<a :href="route('siswa.login')" class="btn-primary inline-flex px-8 py-3">
							Masuk Mapel Berikutnya
						</a>
					</div>
				</template>
				<div class="mt-3">
					<a href="/" class="text-sm text-textSecondary underline underline-offset-2">Kembali ke Beranda</a>
				</div>
			</div>
		</div>
	</GuestLayout>
</template>
