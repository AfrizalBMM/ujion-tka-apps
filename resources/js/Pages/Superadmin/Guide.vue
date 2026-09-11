<script setup>
import { Head } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
</script>

<template>
	<Head title="Panduan Superadmin" />

	<SuperadminLayout>
		<div class="max-w-3xl space-y-8">
			<h1 class="text-2xl font-bold mb-4">Panduan Penggunaan Superadmin</h1>
			<div class="card p-6 space-y-4">
				<h2 class="font-semibold">Menu &amp; Fitur</h2>
				<ul class="list-disc ml-6 space-y-1">
					<li><b>Dashboard:</b> Statistik, aktivitas terbaru, dan ringkasan fitur.</li>
					<li><b>QR Harga:</b> Kelola harga &amp; QR pembayaran.</li>
					<li><b>Live Chat:</b> Komunikasi dengan guru/operator secara real-time.</li>
					<li><b>Daftar Guru:</b> Manajemen data guru/operator.</li>
					<li><b>Materi:</b> Kelola materi pembelajaran.</li>
					<li><b>Bank Soal:</b> Manajemen soal, import ke ujian.</li>
					<li><b>Ujian:</b> Buat &amp; kelola ujian, builder soal fullscreen.</li>
					<li><b>Log Aktivitas:</b> Audit trail aktivitas semua user.</li>
				</ul>
			</div>
			<div class="card p-6 space-y-4">
				<h2 class="font-semibold">Tips Penggunaan</h2>
				<ul class="list-disc ml-6 space-y-1">
					<li>Gunakan <b>builder soal</b> untuk membuat ujian dengan mudah dan cepat.</li>
					<li>Manfaatkan fitur <b>export</b> untuk analisis hasil ujian.</li>
					<li>Periksa <b>log aktivitas</b> secara berkala untuk keamanan sistem.</li>
					<li>Gunakan <b>dark mode</b> dan pengaturan font untuk kenyamanan tampilan.</li>
				</ul>
			</div>
		</div>
	</SuperadminLayout>
</template>
