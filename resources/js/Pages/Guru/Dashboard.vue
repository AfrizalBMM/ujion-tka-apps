<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

defineProps({
	simulasiSelesai: {
		type: Number,
		required: true,
	},
	rataRataKelas: {
		type: Number,
		required: true,
	},
	totalPeserta: {
		type: Number,
		required: true,
	},
	pengumuman: {
		type: Array,
		required: true,
	},
	paymentBanner: {
		type: Object,
		default: null,
	},
});

const page = usePage();

const waGroupLink = computed(() => page.props.guruLayout?.waGroupLink || null);

const formatAmount = (value) => Number(value).toLocaleString('id-ID');
</script>

<template>
	<Head title="Dashboard Guru" />

	<GuruLayout>
		<div class="space-y-6">
			<div v-if="paymentBanner" class="card border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10">
				<div id="doku-payment-banner" class="space-y-4">
					<div data-doku-panel="idle">
						<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
							<div>
								<div class="flex items-center gap-2">
									<i class="fa-solid fa-lock text-amber-500"></i>
									<span class="text-sm font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Aktifkan Akun Anda</span>
								</div>
								<p class="mt-1 text-sm text-amber-900 dark:text-amber-100">
									Selesaikan pembayaran aktivasi
									<strong v-if="paymentBanner.planName">{{ paymentBanner.planName }}</strong>
									untuk membuka semua fitur.
									<template v-if="paymentBanner.amount">
										Nominal: <strong>Rp{{ formatAmount(paymentBanner.amount) }}</strong>
									</template>
								</p>
								<p v-if="paymentBanner.planDescription" class="mt-1 text-xs text-amber-700/80 dark:text-amber-200/70">{{ paymentBanner.planDescription }}</p>
							</div>
							<button type="button" class="btn-primary shrink-0 whitespace-nowrap" data-doku-start>
								<i class="fa-solid fa-bolt mr-2"></i>
								Bayar Sekarang
							</button>
						</div>
					</div>

					<div data-doku-panel="loading" class="hidden">
						<div class="flex items-center gap-3 text-sm font-semibold text-amber-900 dark:text-amber-100">
							<i class="fa-solid fa-spinner fa-spin text-lg"></i>
							Menyiapkan pembayaran...
						</div>
						<p class="mt-1 text-xs text-amber-700 dark:text-amber-200/70">Jendela pembayaran akan terbuka di tab baru.</p>
					</div>

					<div data-doku-panel="polling" class="hidden">
						<div class="flex items-center gap-3 text-sm font-semibold text-amber-900 dark:text-amber-100">
							<i class="fa-solid fa-spinner fa-spin text-lg"></i>
							Menunggu pembayaran...
						</div>
						<p class="mt-1 text-xs text-amber-700 dark:text-amber-200/70">
							Selesaikan pembayaran di jendela checkout yang terbuka. Halaman ini akan diperbarui otomatis setelah pembayaran berhasil.
						</p>
						<button type="button" class="btn-secondary mt-3" data-doku-start>
							<i class="fa-solid fa-rotate-left mr-2"></i>
							Bayar Ulang / Coba Lagi
						</button>
					</div>

					<div data-doku-panel="failed" class="hidden">
						<div class="flex items-center gap-2 text-sm font-bold text-rose-700 dark:text-rose-300">
							<i class="fa-solid fa-circle-exclamation"></i>
							Pembayaran Belum Selesai
						</div>
						<p class="mt-1 text-xs text-rose-700/80 dark:text-rose-200/70">Pembayaran tidak berhasil diselesaikan atau kedaluwarsa. Silakan coba lagi.</p>
						<button type="button" class="btn-primary mt-3" data-doku-start>
							<i class="fa-solid fa-rotate-left mr-2"></i>
							Coba Bayar Lagi
						</button>
					</div>
				</div>
			</div>

			<section class="page-hero">
				<span class="page-kicker">Dashboard Guru</span>
				<div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
					<div>
						<h1 class="page-title">Ruang kerja mengajar yang lebih rapi dan nyaman dibaca.</h1>
						<p class="page-description">Pantau progres kelas, aktivitas terbaru, dan pengumuman penting dari satu dashboard yang sekarang terasa lebih hidup.</p>
					</div>
					<div class="grid gap-3 sm:grid-cols-2">
						<div class="hero-chip">
							<i class="fa-solid fa-book-open-reader"></i>
							Materi dan soal lebih terarah
						</div>
						<div class="hero-chip">
							<i class="fa-solid fa-chart-line"></i>
							Insight kelas lebih cepat dibaca
						</div>
					</div>
				</div>
				<div class="page-actions">
					<template v-if="paymentBanner">
						<button type="button" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white" data-doku-start>
							<i class="fa-solid fa-bolt"></i>
							Bayar Sekarang
						</button>
					</template>
					<template v-else>
						<Link :href="route('guru.materials')" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
							<i class="fa-solid fa-book"></i>
							Buka Materi
						</Link>
						<Link :href="route('guru.exams')" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
							<i class="fa-solid fa-file-lines"></i>
							Coba Simulasi
						</Link>
					</template>
				</div>
			</section>

			<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
				<div class="metric-card">
					<div class="flex items-start justify-between gap-4">
						<div>
							<div class="metric-label">Simulasi Selesai</div>
							<div class="metric-value">{{ simulasiSelesai }}</div>
						</div>
						<div class="metric-icon text-blue-600">
							<i class="fa-solid fa-file-circle-plus text-xl"></i>
						</div>
					</div>
					<div class="metric-meta">
						<span>Simulasi yang sudah Anda selesaikan</span>
						<span class="badge-info">Simulasi</span>
					</div>
				</div>
				<div class="metric-card">
					<div class="flex items-start justify-between gap-4">
						<div>
							<div class="metric-label">Rata-rata Skor Kelas</div>
							<div class="metric-value">{{ Number(rataRataKelas).toFixed(2) }}</div>
						</div>
						<div class="metric-icon text-amber-500">
							<i class="fa-solid fa-chart-simple text-xl"></i>
						</div>
					</div>
					<div class="metric-meta">
						<span>Rerata skor siswa (ujian & simulasi kelas)</span>
						<span class="font-semibold text-amber-500">Skor</span>
					</div>
				</div>
				<div class="metric-card">
					<div class="flex items-start justify-between gap-4">
						<div>
							<div class="metric-label">Peserta Selesai</div>
							<div class="metric-value">{{ totalPeserta }}</div>
						</div>
						<div class="metric-icon text-emerald-600">
							<i class="fa-solid fa-users text-xl"></i>
						</div>
					</div>
					<div class="metric-meta">
						<span>Siswa unik yang menyelesaikan ujian di jenjang Anda</span>
						<span class="font-semibold text-emerald-600">Siswa</span>
					</div>
				</div>
			</div>

			<div>
				<div class="mobile-section-label">Menu Cepat</div>
				<div class="mobile-menu-grid">
					<Link :href="route('guru.materials')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-blue-500 to-blue-600">
							<i class="fa-solid fa-book"></i>
						</div>
						<div class="mobile-menu-card-label">Materi</div>
					</Link>
					<Link :href="route('guru.soal-ujion.index')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-teal-500 to-cyan-600">
							<i class="fa-solid fa-layer-group"></i>
						</div>
						<div class="mobile-menu-card-label">Soal Ujion</div>
					</Link>
					<Link :href="route('guru.personal-questions')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-purple-500 to-violet-600">
							<i class="fa-solid fa-database"></i>
						</div>
						<div class="mobile-menu-card-label">Bank Soal</div>
					</Link>
					<Link :href="route('guru.paket-soal.index')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-indigo-500 to-blue-600">
							<i class="fa-solid fa-cubes"></i>
						</div>
						<div class="mobile-menu-card-label">Paket Soal</div>
					</Link>
					<Link :href="route('guru.exams')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-amber-500 to-orange-600">
							<i class="fa-solid fa-file-pen"></i>
						</div>
						<div class="mobile-menu-card-label">Simulasi</div>
					</Link>
					<Link :href="route('guru.results.index')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-emerald-500 to-green-600">
							<i class="fa-solid fa-chart-line"></i>
						</div>
						<div class="mobile-menu-card-label">Hasil Siswa</div>
					</Link>
					<Link :href="route('guru.chat')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-rose-500 to-pink-600">
							<i class="fa-solid fa-comments"></i>
						</div>
						<div class="mobile-menu-card-label">Live Chat</div>
					</Link>
					<Link :href="route('guru.guide')" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-slate-500 to-slate-600">
							<i class="fa-solid fa-circle-info"></i>
						</div>
						<div class="mobile-menu-card-label">Panduan</div>
					</Link>
					<a v-if="waGroupLink" :href="waGroupLink" target="_blank" rel="noopener" class="mobile-menu-card">
						<div class="mobile-menu-card-icon bg-gradient-to-br from-green-500 to-green-600">
							<i class="fa-brands fa-whatsapp"></i>
						</div>
						<div class="mobile-menu-card-label">Saluran WA</div>
					</a>
				</div>
			</div>

			<div class="card p-5">
				<div class="section-heading mb-4">
					<div>
						<h2 class="section-title">Pengumuman Penting</h2>
						<p class="section-description">Info yang perlu diperhatikan untuk operasional mengajar.</p>
					</div>
				</div>
				<ul class="space-y-3">
					<template v-if="pengumuman.length > 0">
						<li v-for="info in pengumuman" :key="info" class="rounded-2xl border border-blue-100 bg-blue-50/80 px-4 py-3 text-sm text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-200">{{ info }}</li>
					</template>
					<li v-else class="empty-state text-gray-400">Tidak ada pengumuman.</li>
				</ul>
			</div>
		</div>
	</GuruLayout>
</template>
