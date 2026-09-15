<script setup>
import { Head } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

defineProps({
	exam: {
		type: Object,
		required: true,
	},
	result: {
		type: Object,
		required: true,
	},
	pembahasan: {
		type: Array,
		required: true,
	},
});
</script>

<template>
	<Head title="Hasil Simulasi" />

	<GuruLayout>
		<div class="max-w-3xl space-y-6">
			<h1 class="text-2xl font-bold mb-4">Hasil Simulasi: {{ exam.judul }}</h1>
			<div class="card p-4 mb-4">
				<div class="font-bold">Skor simulasi Anda:</div>
				<div class="text-3xl text-blue-700 font-bold">{{ result ? result.skor : '-' }}</div>
				<div v-if="result.waktu_selesai ?? null" class="mt-2 text-sm text-gray-500">Selesai pada {{ result.waktu_selesai }}</div>
			</div>
			<div class="card p-4">
				<h2 class="font-semibold mb-2">Pembahasan untuk Evaluasi Guru</h2>
				<ul class="space-y-2">
					<template v-if="pembahasan.length > 0">
						<li v-for="(p, index) in pembahasan" :key="index" class="border-b pb-2">
							<div class="text-xs font-bold uppercase text-blue-600">{{ p.mapel }}</div>
							<div class="font-bold">{{ p.pertanyaan }}</div>
							<div class="text-slate-700">Jawaban Anda: {{ p.jawaban_user }}</div>
							<div class="text-green-700">Jawaban Benar: {{ p.jawaban_benar }}</div>
							<div class="text-gray-700">Pembahasan: {{ p.pembahasan }}</div>
						</li>
					</template>
					<li v-else class="text-gray-400">Belum ada pembahasan.</li>
				</ul>
			</div>
		</div>
	</GuruLayout>
</template>
