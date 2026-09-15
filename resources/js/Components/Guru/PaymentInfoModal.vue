<script setup>
import { computed } from 'vue';

const props = defineProps({
	open: {
		type: Boolean,
		default: false,
	},
	info: {
		type: Object,
		default: null,
	},
});

const emit = defineEmits(['close']);

const jenjangLabel = computed(() => props.info?.jenjangLabel || props.info?.jenjang || null);

const formatAmount = (value) => Number(value).toLocaleString('id-ID');

const steps = [
	{ icon: 'fa-bolt', title: 'Klik "Lanjut Bayar"', description: 'Jendela pembayaran akan terbuka di tab baru.' },
	{ icon: 'fa-credit-card', title: 'Selesaikan pembayaran', description: 'Pilih metode pembayaran yang tersedia dan ikuti instruksinya.' },
	{ icon: 'fa-circle-check', title: 'Akun langsung aktif', description: 'Halaman otomatis diperbarui dan semua fitur terbuka.' },
];
</script>

<template>
	<div v-if="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
		<div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')"></div>

		<div class="relative flex min-h-full items-center justify-center p-4">
			<div class="card w-full max-w-md">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="flex items-center gap-2">
							<i class="fa-solid fa-lock text-amber-500"></i>
							<span class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">Aktivasi Akun</span>
						</div>
						<h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Buka Semua Fitur Ujion</h2>
					</div>
					<button type="button" class="btn-secondary px-3" aria-label="Tutup" @click="emit('close')">
						<i class="fa-solid fa-xmark"></i>
					</button>
				</div>

				<div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
					<div class="flex flex-wrap items-center justify-between gap-2">
						<span class="text-sm font-bold text-amber-900 dark:text-amber-100">{{ info?.planName || 'Biaya Aktivasi' }}</span>
						<span v-if="jenjangLabel" class="badge-info">Jenjang {{ jenjangLabel }}</span>
					</div>
					<div v-if="info?.amount" class="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-100">
						Rp{{ formatAmount(info.amount) }}
					</div>
					<p v-if="info?.planDescription" class="mt-1 text-xs text-amber-700/80 dark:text-amber-200/70">{{ info.planDescription }}</p>
				</div>

				<div class="mt-4">
					<div class="text-xs font-bold uppercase tracking-wide text-textSecondary dark:text-slate-300">Alur Pembayaran Singkat</div>
					<ol class="mt-2 space-y-3">
						<li v-for="(step, index) in steps" :key="index" class="flex items-start gap-3">
							<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
								<i class="fa-solid text-xs" :class="step.icon"></i>
							</span>
							<div>
								<div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ step.title }}</div>
								<div class="text-xs text-textSecondary dark:text-slate-400">{{ step.description }}</div>
							</div>
						</li>
					</ol>
				</div>

				<div class="mt-6 flex items-center justify-end gap-2">
					<button type="button" class="btn-secondary" @click="emit('close')">Nanti Saja</button>
					<button type="button" class="btn-primary" data-doku-start-confirm>
						<i class="fa-solid fa-bolt mr-2"></i>
						Lanjut Bayar
					</button>
				</div>
			</div>
		</div>
	</div>
</template>
