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
	tipeSoal: {
		type: String,
		default: 'pilihan_ganda',
	},
	teksBacaans: {
		type: Array,
		default: () => [],
	},
	nextNomor: {
		type: Number,
		default: 1,
	},
});
</script>

<template>
	<Head title="Tambah Soal" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }} &middot; {{ mapel.nama_label }}</span>
				<h1 class="page-title">Tambah soal baru</h1>
				<p class="page-description">Form ini mendukung pilihan ganda dan menjodohkan dalam satu alur kerja.</p>
			</section>

			<section class="card">
				<SoalForm
					:paket="paket"
					:mapel="mapel"
					:teksBacaans="teksBacaans"
					:tipeSoal="tipeSoal"
					:nextNomor="nextNomor"
					submit-label="Simpan Soal"
					:cancel-url="route('superadmin.soal.index', [paket.id, mapel.id])"
				/>
			</section>
		</div>
	</SuperadminLayout>
</template>
