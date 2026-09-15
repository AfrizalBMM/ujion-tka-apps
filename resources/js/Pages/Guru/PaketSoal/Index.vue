<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

defineProps({
	jenjang: {
		type: String,
		default: null,
	},
	paketSoals: {
		type: Array,
		required: true,
	},
});
</script>

<template>
	<Head title="Paket Soal" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">Paket Relevan</span>
				<h1 class="page-title">Paket soal untuk jenjang {{ jenjang ?? '-' }}</h1>
				<p class="page-description">Guru hanya melihat dan mengelola paket yang sesuai dengan jenjang yang diajar.</p>
			</section>

			<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
				<template v-if="paketSoals.length > 0">
					<article v-for="paket in paketSoals" :key="paket.id" class="card">
						<div class="flex items-start justify-between gap-3">
							<div>
								<h2 class="section-title">{{ paket.nama }}</h2>
								<p class="section-description">{{ paket.tahun_ajaran }} &middot; {{ paket.jenjang_kode }}</p>
							</div>
							<span v-if="paket.is_active" class="badge-success">Aktif</span>
						</div>
						<div class="mt-4 flex flex-wrap gap-2">
							<span v-for="label in paket.mapel_labels" :key="label" class="badge-info">{{ label }}</span>
						</div>
						<div class="mt-5">
							<Link :href="route('guru.paket-soal.show', paket.id)" class="btn-primary px-4 py-2 text-xs">Lihat Detail</Link>
						</div>
					</article>
				</template>
				<div v-else class="empty-state md:col-span-2 xl:col-span-3">Belum ada paket yang sesuai dengan jenjang Anda.</div>
			</section>
		</div>
	</GuruLayout>
</template>
