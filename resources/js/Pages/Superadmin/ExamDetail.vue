<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

defineProps({
	exam: {
		type: Object,
		required: true,
	},
});

const copySuccess = ref(false);

const copyToken = async (token) => {
	try {
		await copyTextToClipboard(token);
		copySuccess.value = true;

		window.setTimeout(() => {
			copySuccess.value = false;
		}, 1500);
	} catch (error) {
		console.error('Failed to copy exam token.', error);
	}
};
</script>

<template>
	<Head title="Detail Ujian" />

	<SuperadminLayout>
		<div class="max-w-3xl space-y-6">
			<div class="card p-6 flex flex-col items-center">
				<div class="text-lg font-bold mb-2">Token Ujian</div>
				<div class="flex flex-col items-center gap-3 sm:flex-row">
					<span id="token-text" class="break-all text-center font-mono text-2xl tracking-widest bg-gray-100 px-4 py-2 rounded sm:text-3xl">{{ exam.token }}</span>
					<button type="button" class="btn-secondary w-full sm:w-auto" @click="copyToken(exam.token)">Copy Token</button>
				</div>
				<div id="copy-success" class="text-green-600 mt-2" :class="copySuccess ? '' : 'hidden'">Token berhasil disalin!</div>
			</div>
			<div class="card p-6">
				<div class="mb-2 font-bold">Judul Ujian:</div>
				<div class="mb-4">{{ exam.judul }}</div>
				<div class="mb-2 font-bold">Tanggal Terbit:</div>
				<div class="mb-4">{{ exam.tanggal_terbit_formatted }}</div>
				<div class="mb-2 font-bold">Max Peserta:</div>
				<div class="mb-4">{{ exam.max_peserta }}</div>
				<div class="mb-2 font-bold">Status:</div>
				<div class="mb-4">{{ exam.status }}</div>
				<div class="mb-2 font-bold">Aktif:</div>
				<div class="mb-4">
					<span v-if="exam.is_active" class="badge-success">Aktif</span>
					<span v-else class="badge-danger">Nonaktif</span>
				</div>
				<a :href="route('superadmin.exams.builder', exam.id)" class="btn-primary">Builder Soal</a>
			</div>
		</div>
	</SuperadminLayout>
</template>
