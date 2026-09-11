<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import MapelConfigForm from '@/Components/Guru/MapelConfigForm.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

defineProps({
	paket: {
		type: Object,
		required: true,
	},
	canManage: {
		type: Boolean,
		required: true,
	},
	mapelPakets: {
		type: Array,
		required: true,
	},
	exams: {
		type: Array,
		required: true,
	},
});

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
		console.error('Failed to copy paket token.', error);
	}
};
</script>

<template>
	<Head title="Detail Paket Soal" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.jenjang_kode }} &middot; {{ paket.tahun_ajaran }}</span>
				<h1 class="page-title">{{ paket.nama }}</h1>
				<p class="page-description">
					{{ canManage
						? 'Anda dapat meninjau dan mengelola komponen akademik maupun survey sesuai jenjang Anda.'
						: 'Paket ini dibuat oleh superadmin, sehingga di akun guru hanya dapat dilihat sebagai referensi.' }}
				</p>
			</section>

			<section class="grid gap-4 xl:grid-cols-2">
				<article v-for="mapel in mapelPakets" :key="mapel.id" class="card">
					<div class="section-heading mb-4">
						<div>
							<h2 class="section-title">{{ mapel.nama_label }}</h2>
							<p class="section-description">{{ mapel.soals_count }}/{{ mapel.jumlah_soal }} butir &middot;
								{{ mapel.durasi_menit }} menit &middot; {{ mapel.is_survey ? 'Survey Profiling' : 'Akademik' }}
							</p>
						</div>
						<Link :href="route('guru.soal.index', [paket.id, mapel.id])"
							class="btn-primary px-4 py-2 text-xs">{{ canManage ? 'Kelola' : 'Lihat Soal' }}</Link>
					</div>
					<MapelConfigForm v-if="canManage" :paket="paket" :mapel="mapel" />
					<div v-else class="rounded-[24px] border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">
						Konfigurasi mapel ini dikelola oleh superadmin dan tidak bisa diubah dari akun guru.
					</div>
					<div class="space-y-3">
						<template v-if="mapel.soals.length > 0">
							<div v-for="soal in mapel.soals" :key="soal.id"
								class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
								<div class="flex items-center justify-between gap-3">
									<div class="font-semibold">Soal {{ soal.nomor_soal }}</div>
									<span class="badge-info">{{ soal.tipe_label }}</span>
								</div>
								<p class="mt-2 text-sm text-textSecondary">
									{{ soal.pertanyaan_limited }}
								</p>
								<p v-if="mapel.is_survey && soal.dimensi" class="mt-2 text-xs font-medium text-slate-500">{{ soal.dimensi }}{{ soal.subdimensi ? ' · ' + soal.subdimensi : '' }}</p>
							</div>
						</template>
						<div v-else class="empty-state">Belum ada soal pada mapel ini.</div>
					</div>
				</article>
			</section>

			<section class="card">
				<div class="section-heading mb-4">
					<div>
						<h2 class="section-title flex items-center gap-2">
							<i class="fa-solid fa-key text-primary"></i>
							Token Ujian Aktif
						</h2>
						<p class="section-description">
							Ujian yang sedang aktif dari paket ini. Salin token lalu masukkan di halaman
							<Link :href="route('guru.exams')" class="font-semibold text-primary underline underline-offset-2">Simulasi
								Ujian</Link>
							untuk memulai simulasi.
						</p>
					</div>
				</div>

				<div v-if="exams.length === 0"
					class="flex items-center gap-3 rounded-2xl border border-amber-100 bg-amber-50/80 p-4 dark:border-amber-800/40 dark:bg-amber-900/20">
					<i class="fa-solid fa-circle-info text-xl text-amber-500"></i>
					<div>
						<p class="text-sm font-medium text-amber-900 dark:text-amber-300">Belum ada ujian aktif dari paket ini.</p>
						<p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
							Admin Ujion belum membuat atau mengaktifkan ujian dari paket soal ini. Hubungi admin untuk mendapatkan token.
						</p>
					</div>
				</div>
				<div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
					<div v-for="exam in exams" :key="exam.id"
						class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/60">
						<div>
							<div class="font-semibold text-sm">{{ exam.judul }}</div>
							<div class="mt-0.5 text-xs text-textSecondary">
								{{ exam.tanggal_terbit }} &middot; maks {{ exam.max_peserta }} peserta
							</div>
						</div>

						<div class="space-y-2">
							<span v-if="exam.tokens.length === 0" class="badge-warning text-xs">Belum ada token</span>
							<template v-else>
								<div v-for="mt in exam.tokens" :key="mt.id"
									class="flex items-center gap-2 rounded-xl border border-slate-200/50 bg-white/50 p-2 dark:border-slate-800/50 dark:bg-slate-950/30">
									<div class="flex-1 min-w-0">
										<div class="text-[10px] font-bold uppercase tracking-wider text-textSecondary truncate">
											{{ mt.mapel_label }}
										</div>
										<div :id="`token-guru-paket-${mt.id}`" class="font-mono text-sm font-bold tracking-widest text-primary">
											{{ mt.token }}
										</div>
									</div>
									<button type="button" :id="`copy-guru-paket-${mt.id}`"
										:title="'Salin token'"
										class="btn-secondary h-8 w-8 flex items-center justify-center rounded-lg px-0 transition-all text-xs"
										:class="copiedToken === mt.token ? 'text-emerald-600' : ''"
										@click="copyToken(mt.token)">
										<i :class="copiedToken === mt.token ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
									</button>
								</div>
							</template>
						</div>

						<Link :href="route('guru.exams')" class="btn-primary w-full py-2 text-center text-xs">
							<i class="fa-solid fa-play mr-1.5"></i>Simulasi Ujian
						</Link>
					</div>
				</div>
			</section>
		</div>
	</GuruLayout>
</template>
