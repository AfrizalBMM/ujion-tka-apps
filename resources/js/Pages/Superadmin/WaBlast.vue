<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	jenjangOptions: {
		type: Array,
		default: () => [],
	},
	schoolOptions: {
		type: Array,
		default: () => [],
	},
	paketSoalOptions: {
		type: Array,
		default: () => [],
	},
	blastStats: {
		type: Object,
		default: () => ({}),
	},
	blastLogs: {
		type: Array,
		default: () => [],
	},
});

const form = useForm({
	target: 'guru_all_active',
	jenjang: '',
	school: '',
	paket_soal_id: '',
	scheduled_at: '',
	message: '',
});

const jenjangVisible = computed(() => form.target === 'guru_jenjang');
const schoolVisible = computed(() => form.target === 'guru_school');
const paketVisible = computed(() => form.target === 'siswa_paket');

const submit = () => {
	form.post(route('superadmin.wa-blast.send'));
};

const ucfirst = (value) => (value ? value.charAt(0).toUpperCase() + value.slice(1) : value);

const statusClass = (status) => {
	if (status === 'success') return 'bg-emerald-100 text-emerald-700';
	if (status === 'failed') return 'bg-rose-100 text-rose-700';
	return 'bg-slate-100 text-slate-700';
};
</script>

<template>
	<Head title="Blast Pengumuman" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Blast Pengumuman</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Kirim pengumuman WhatsApp ke guru aktif. Pengiriman dilakukan lewat antrean (queue) agar tidak memberatkan request.
					</p>
				</div>
			</div>

			<div class="grid gap-4 lg:grid-cols-5">
				<div class="card lg:col-span-3">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Pesan</div>
					<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Tulis pengumuman</div>

					<form class="mt-4 space-y-4" @submit.prevent="submit">
						<div>
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Target penerima</label>
							<select id="wa-target" v-model="form.target" name="target" class="input w-full">
								<option value="guru_all_active">Semua guru aktif</option>
								<option value="guru_jenjang">Guru aktif per jenjang</option>
								<option value="guru_school">Guru aktif per sekolah</option>
								<option value="siswa_all">Semua siswa (peserta ujian)</option>
								<option value="siswa_paket">Siswa per paket soal</option>
							</select>
						</div>

						<div id="wa-jenjang-wrap" :class="jenjangVisible ? '' : 'hidden'">
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Jenjang</label>
							<select v-model="form.jenjang" name="jenjang" class="input w-full">
								<option value="">Pilih jenjang</option>
								<option v-for="jenjang in jenjangOptions" :key="jenjang" :value="jenjang">{{ jenjang }}</option>
							</select>
						</div>

						<div id="wa-school-wrap" :class="schoolVisible ? '' : 'hidden'">
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Satuan pendidikan (nama sekolah)</label>
							<input v-model="form.school" name="school" class="input w-full" list="school-options" placeholder="Contoh: SMP Negeri 1">
							<datalist id="school-options">
								<option v-for="school in schoolOptions" :key="school" :value="school"></option>
							</datalist>
							<div class="mt-2 text-xs text-textSecondary dark:text-slate-300">Pencarian memakai <span class="font-mono">LIKE</span>. Isi sebagian nama sekolah juga boleh.</div>
						</div>

						<div id="wa-paket-wrap" :class="paketVisible ? '' : 'hidden'">
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Paket soal</label>
							<select v-model="form.paket_soal_id" name="paket_soal_id" class="input w-full">
								<option value="">Pilih paket soal</option>
								<option v-for="paket in paketSoalOptions" :key="paket.id" :value="paket.id">
									{{ paket.nama }}{{ paket.jenjang ? ` (${paket.jenjang.kode})` : '' }}
								</option>
							</select>
						</div>

						<div>
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Jadwal pengiriman</label>
							<input
								v-model="form.scheduled_at"
								type="datetime-local"
								name="scheduled_at"
								class="input w-full"
								placeholder="Pilih tanggal dan waktu"
							>
							<div class="mt-2 text-xs text-textSecondary dark:text-slate-300">
								Kosongkan untuk mengirim segera. Waktu ditentukan dalam zona waktu lokal.
							</div>
							<div v-if="form.errors.scheduled_at" class="mt-2 text-sm text-rose-600">{{ form.errors.scheduled_at }}</div>
						</div>

						<div>
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Isi pesan</label>
							<textarea v-model="form.message" name="message" class="input w-full min-h-40" placeholder="Contoh: 📢 Pengumuman jadwal ujian..."></textarea>
							<div v-if="form.errors.message" class="mt-2 text-sm text-rose-600">{{ form.errors.message }}</div>
						</div>

						<div class="flex justify-end">
							<button type="submit" class="btn-primary" :disabled="form.processing">
								<i class="fa-solid fa-paper-plane mr-2"></i>
								Jadwalkan Blast
							</button>
						</div>
					</form>
				</div>

				<div class="card lg:col-span-2">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Catatan</div>
					<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Operasional</div>

					<ul class="mt-4 space-y-2 text-sm text-textSecondary dark:text-slate-300">
						<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Pastikan WA Gateway (Node.js) sedang berjalan.</span></li>
						<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Jalankan queue worker: <span class="font-mono">php artisan queue:work --queue=high,low</span></span></li>
						<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Sistem memberi delay acak per pesan untuk mengurangi risiko pembatasan WhatsApp.</span></li>
					</ul>
				</div>
			</div>

			<div class="mt-6 card">
				<div class="flex items-center justify-between gap-4">
					<div>
						<div class="text-xs font-semibold uppercase tracking-wide text-muted">Monitoring Blast</div>
						<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Aktivitas Pesan WhatsApp</div>
					</div>
					<div class="text-sm text-textSecondary dark:text-slate-300">Menampilkan 10 catatan terbaru dari log pengiriman WA.</div>
				</div>

				<div class="mt-4 grid gap-4 sm:grid-cols-3">
					<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
						<div class="text-xs uppercase tracking-wide text-muted">Terkirim</div>
						<div class="mt-2 text-3xl font-bold text-emerald-600">{{ blastStats.success ?? 0 }}</div>
					</div>
					<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
						<div class="text-xs uppercase tracking-wide text-muted">Gagal</div>
						<div class="mt-2 text-3xl font-bold text-rose-600">{{ blastStats.failed ?? 0 }}</div>
					</div>
					<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-900 dark:bg-slate-900">
						<div class="text-xs uppercase tracking-wide text-muted">Lainnya</div>
						<div class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ blastStats.unknown ?? 0 }}</div>
					</div>
				</div>

				<div class="mt-4 text-xs text-textSecondary dark:text-slate-400">
					<strong>Debug:</strong> blastStats = {{ JSON.stringify(blastStats ?? {}) }}, blastLogs count = {{ (blastLogs ?? []).length }}
				</div>

				<div class="mt-6 overflow-x-auto">
					<table class="min-w-full border-collapse text-left text-sm">
						<thead>
							<tr>
								<th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Waktu</th>
								<th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Nomor</th>
								<th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Status</th>
								<th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Pesan</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="blastLogs.length > 0">
								<tr v-for="log in blastLogs" :key="log.id" class="hover:bg-slate-100 dark:hover:bg-slate-800">
									<td class="border-b border-slate-200 px-4 py-3 text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ log.created_at_formatted }}</td>
									<td class="border-b border-slate-200 px-4 py-3 text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ log.phone }}</td>
									<td class="border-b border-slate-200 px-4 py-3">
										<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold" :class="statusClass(log.status)">
											{{ ucfirst(log.status) }}
										</span>
									</td>
									<td class="border-b border-slate-200 px-4 py-3 text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ log.message_limited }}</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="4" class="border-b border-slate-200 px-4 py-6 text-center text-sm text-textSecondary dark:border-slate-700">Belum ada log blast WhatsApp.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
