<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

defineProps({
	available: {
		type: Array,
		required: true,
	},
	joined: {
		type: Array,
		default: () => [],
	},
	history: {
		type: Array,
		required: true,
	},
});

const joinForm = useForm({
	token: '',
});

const joinSubmit = () => {
	joinForm.post(route('guru.exams.join'));
};

const copiedToken = ref(null);

const copyToken = async (token) => {
	if (!token) return;

	try {
		await copyTextToClipboard(token);
		copiedToken.value = token;

		window.setTimeout(() => {
			copiedToken.value = null;
		}, 2000);
	} catch (error) {
		console.error('Failed to copy token.', error);
	}
};
</script>

<template>
	<Head title="Simulasi Ujian" />

	<GuruLayout>
		<div class="space-y-6">
			<div>
				<h1 class="text-2xl font-bold">Simulasi Ujian</h1>
				<p class="mt-2 text-textSecondary dark:text-slate-300">
					Gunakan halaman ini untuk mencoba alur ujian dari sisi siswa, mengecek kesiapan paket soal, dan melihat pembahasan
					sebagai bahan evaluasi sebelum dipakai untuk pembinaan siswa.
				</p>
			</div>
			<form class="card mb-4 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="joinSubmit">
				<div class="flex-1">
					<label class="text-xs font-bold uppercase tracking-wide text-muted">Token simulasi</label>
					<input name="token" class="input w-full" placeholder="Masukkan token ujian untuk mencoba alur siswa" required v-model="joinForm.token">
				</div>
				<button class="btn-primary w-full sm:w-auto" type="submit" :disabled="joinForm.processing">
					<i class="fa-solid fa-play"></i>
					Mulai Simulasi
				</button>
			</form>
			<div class="card p-4 mb-4">
				<div class="mb-2">
					<h2 class="font-semibold">Ujian yang Bisa Dicoba</h2>
					<p class="mt-1 text-sm text-textSecondary dark:text-slate-400">Daftar ujian aktif yang bisa guru buka untuk melihat pengalaman siswa secara langsung.</p>
				</div>
				<div class="table-container">
					<table class="table-ujion w-full min-w-[640px]">
						<thead>
							<tr>
								<th>Judul</th>
								<th>Paket</th>
								<th>Tanggal</th>
								<th>Status</th>
								<th class="text-center">Token Akses</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="available.length > 0">
								<tr v-for="exam in available" :key="exam.id">
									<td>{{ exam.judul }}</td>
									<td>{{ exam.paket_nama }}</td>
									<td>{{ exam.tanggal_terbit }}</td>
									<td>{{ exam.status }}</td>
									<td>
										<span v-if="exam.tokens.length === 0" class="badge-warning text-xs">Belum ada token</span>
										<div v-else class="space-y-1.5">
											<div v-for="mt in exam.tokens" :key="mt.id" class="flex items-center gap-2">
												<span class="text-[10px] font-bold text-textSecondary w-20 truncate">
													{{ mt.mapel_label }}
												</span>
												<span :id="`token-guru-${mt.id}`"
													class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-sm font-bold tracking-widest text-primary">
													{{ mt.token }}
												</span>
												<button
													type="button"
													:id="`copy-guru-${mt.id}`"
													title="Salin token"
													class="btn-secondary px-2 py-1 text-[11px] transition-all"
													:class="copiedToken === mt.token ? 'text-emerald-600' : ''"
													@click="copyToken(mt.token)"
												>
													<i :class="copiedToken === mt.token ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
												</button>
											</div>
										</div>
									</td>
								</tr>
							</template>
							<tr v-else><td colspan="5" class="text-center text-textSecondary">Belum ada ujian aktif yang sesuai jenjang Anda.</td></tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card p-4 mb-4">
				<div class="mb-2">
					<h2 class="font-semibold">Riwayat Simulasi</h2>
					<p class="mt-1 text-sm text-textSecondary dark:text-slate-400">Simulasi yang sudah pernah Anda kerjakan beserta hasil dan pembahasannya.</p>
				</div>
				<div class="table-container">
					<table class="table-ujion w-full min-w-[620px]">
						<thead>
							<tr>
								<th>Judul Simulasi</th>
								<th>Skor</th>
								<th>Status</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="history.length > 0">
								<tr v-for="h in history" :key="`${h.exam_id}-${h.judul}`">
									<td>{{ h.judul }}</td>
									<td>{{ h.skor }}</td>
									<td>{{ h.status }}</td>
									<td><Link :href="route('guru.exams.result', h.exam_id)" class="btn-secondary">Lihat Hasil</Link></td>
								</tr>
							</template>
							<tr v-else><td colspan="4" class="text-gray-400">Belum ada riwayat simulasi.</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
