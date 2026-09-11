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
	tipeSoal: {
		type: String,
		required: true,
	},
	teksBacaans: {
		type: Array,
		default: () => [],
	},
	nextNomor: {
		type: Number,
		required: true,
	},
});
</script>

<template>
	<Head title="Tambah Soal" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }} &middot; {{ mapel.nama_label }}</span>
				<h1 class="page-title">Tambah soal</h1>
				<p class="page-description">Gunakan form ini untuk menyusun butir soal sesuai revisi paket per jenjang.</p>
			</section>

			<section class="card">
				<SoalForm
					:paket="paket"
					:mapel="mapel"
					:teks-bacaans="teksBacaans"
					:tipe-soal="tipeSoal"
					:next-nomor="nextNomor"
					submit-label="Simpan Soal"
				/>
			</section>
		</div>
	</GuruLayout>
</template>
