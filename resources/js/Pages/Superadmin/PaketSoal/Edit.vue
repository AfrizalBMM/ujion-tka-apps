<script setup>
import { Head } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaketSoalForm from '@/Components/Superadmin/PaketSoalForm.vue';

defineProps({
	paket: {
		type: Object,
		required: true,
	},
	jenjangs: {
		type: Array,
		default: () => [],
	},
});
</script>

<template>
	<Head title="Edit Paket Soal" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">Edit Metadata</span>
				<h1 class="page-title">{{ paket.nama }}</h1>
				<p class="page-description">Perbarui jenjang, tahun ajaran, nama paket, dan status aktif.</p>
			</section>

			<section class="card">
				<PaketSoalForm
					:paket="paket"
					:jenjangs="jenjangs"
					submit-label="Perbarui Paket"
					:cancel-url="route('superadmin.paket-soal.show', paket.id)"
				/>
			</section>
		</div>
	</SuperadminLayout>
</template>
