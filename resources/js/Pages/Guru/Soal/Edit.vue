<script setup>
import { Head } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import SoalForm from '@/Components/Guru/SoalForm.vue';

defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	soal: {
		type: Object,
		required: true,
	},
	teksBacaans: {
		type: Array,
		default: () => [],
	},
});
</script>

<template>
	<Head :title="`Edit soal ${soal.nomor_soal}`" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }} &middot; {{ mapel.nama_label }}</span>
				<h1 class="page-title">Edit soal {{ soal.nomor_soal }}</h1>
				<p class="page-description">Perbarui detail soal, pasangan, pilihan, dan media pendukungnya.</p>
			</section>

			<section class="card">
				<SoalForm
					:paket="paket"
					:mapel="mapel"
					:teks-bacaans="teksBacaans"
					:soal="soal"
					submit-label="Perbarui Soal"
				/>
			</section>
		</div>
	</GuruLayout>
</template>
