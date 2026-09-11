<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
	session: {
		type: Object,
		required: true,
	},
	exam: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	totalSoal: {
		type: Number,
		required: true,
	},
	dijawab: {
		type: Number,
		required: true,
	},
	remainingSeconds: {
		type: Number,
		required: true,
	},
});

const form = useForm({});

const submit = () => {
	form.post(route('siswa.selesai.submit'));
};

const menit = Math.floor(props.remainingSeconds / 60);
const detik = props.remainingSeconds % 60;
const belumDijawab = Math.max(props.totalSoal - props.dijawab, 0);
</script>

<template>
	<Head title="Konfirmasi Selesai — Ujion" />

	<GuestLayout hide-showcase>
		<div class="w-full max-w-md space-y-5">
			<div class="rounded-3xl border border-white/80 bg-white/90 p-8 text-center shadow-card">
				<div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl shadow-inner">
					⏳
				</div>

				<h1 class="text-2xl font-bold text-slate-900">Selesaikan Ujian?</h1>
				<p class="mt-2 text-sm text-textSecondary">
					Waktu Anda masih berjalan. Setelah selesai, jawaban tidak bisa diubah lagi.
				</p>

				<div v-if="totalSoal > 0" class="mt-6 grid grid-cols-3 gap-3 text-sm">
					<div class="rounded-2xl bg-emerald-50 p-3">
						<div class="text-xl font-bold text-emerald-700">{{ dijawab }}</div>
						<div class="text-xs text-emerald-600">Dijawab</div>
					</div>
					<div class="rounded-2xl bg-rose-50 p-3">
						<div class="text-xl font-bold text-rose-700">{{ belumDijawab }}</div>
						<div class="text-xs text-rose-600">Belum Dijawab</div>
					</div>
					<div class="rounded-2xl bg-indigo-50 p-3">
						<div class="text-xl font-bold text-indigo-700">{{ String(menit).padStart(2, '0') }}:{{ String(detik).padStart(2, '0') }}</div>
						<div class="text-xs text-indigo-600">Sisa Waktu</div>
					</div>
				</div>

				<div v-if="belumDijawab > 0" class="mt-5 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-left text-xs text-amber-800">
					<p class="font-semibold">Masih ada {{ belumDijawab }} soal belum dijawab.</p>
					<p class="mt-1">Soal yang tidak dijawab akan dinilai salah. Anda bisa kembali dan melengkapi dulu.</p>
				</div>

				<form class="mt-6" @submit.prevent="submit">
					<button type="submit" class="btn-primary inline-flex w-full px-8 py-3" :disabled="form.processing">
						Ya, Selesai &amp; Lihat Hasil
					</button>
				</form>

				<div class="mt-3">
					<a :href="route('siswa.ujian')" class="text-sm font-semibold text-indigo-600 underline underline-offset-2">Kembali mengerjakan ujian</a>
				</div>
			</div>
		</div>
	</GuestLayout>
</template>
