<script setup>
import { Head } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import SoalForm from '@/Components/Superadmin/SoalForm.vue';

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
	<Head title="Edit Soal" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }} &middot; {{ mapel.nama_label }}</span>
				<h1 class="page-title">Edit soal {{ soal.nomor_soal }}</h1>
				<p class="page-description">Perbarui pertanyaan, indikator, opsi, pasangan, dan media pendukung.</p>
			</section>

			<section class="card">
				<SoalForm
					:paket="paket"
					:mapel="mapel"
					:soal="soal"
					:teksBacaans="teksBacaans"
					submit-label="Perbarui Soal"
					:cancel-url="route('superadmin.soal.index', [paket.id, mapel.id])"
				/>
			</section>
		</div>
	</SuperadminLayout>
</template>
