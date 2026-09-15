<script setup>
import { Head } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaketSoalForm from '@/Components/Superadmin/PaketSoalForm.vue';

defineProps({
	jenjangs: {
		type: Array,
		default: () => [],
	},
});
</script>

<template>
	<Head title="Buat Paket Soal" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">Paket Baru</span>
				<h1 class="page-title">Buat paket soal dan inisialisasi empat komponen otomatis.</h1>
				<p class="page-description">Matematika, Bahasa Indonesia, Survey Karakter, dan Survey Lingkungan Belajar akan langsung disiapkan dengan konfigurasi awal.</p>
			</section>

			<section class="card">
				<PaketSoalForm
					:jenjangs="jenjangs"
					submit-label="Simpan Paket"
					:cancel-url="route('superadmin.paket-soal.index')"
				/>
			</section>
		</div>
	</SuperadminLayout>
</template>
