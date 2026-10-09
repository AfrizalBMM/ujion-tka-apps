<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
	plans: {
		type: Array,
		default: () => [],
	},
});

// Fallback static plans if no data from backend
const displayPlans = computed(() => {
	if (props.plans && props.plans.length > 0) {
		return props.plans;
	}
	// Static fallback matching the spec
	return [
		{
			id: 'monthly',
			name: 'Bulanan',
			period: '1 Bulan',
			price: 50000,
			original_price: 75000,
			promo_active: true,
			description: 'Cocok untuk mencoba platform dengan akses penuh.',
			features: ['Akses semua fitur', 'Soal & paket ujian', 'Materi belajar', 'Live chat admin'],
			sort_order: 1,
		},
		{
			id: 'semester',
			name: '6 Bulan',
			period: '6 Bulan',
			price: 200000,
			original_price: 300000,
			promo_active: true,
			description: 'Hemat lebih banyak untuk satu semester penuh.',
			features: ['Semua fitur Bulanan', 'Prioritas support', 'Diskon 33%', 'Ideal untuk semester'],
			sort_order: 2,
		},
		{
			id: 'yearly',
			name: 'Tahunan',
			period: '12 Bulan',
			price: 400000,
			original_price: 600000,
			promo_active: true,
			description: 'Paket terhemat untuk satu tahun ajaran penuh.',
			features: ['Semua fitur 6 Bulan', 'Harga terbaik', 'Diskon 33%', 'Garansi satu tahun'],
			sort_order: 3,
		},
	];
});

const formatRupiah = (value) => {
	if (value === null || value === undefined) return '-';
	return 'Rp' + Number(value).toLocaleString('id-ID');
};

const calcDiscount = (original, current) => {
	if (!original || original <= 0 || !current) return 0;
	return Math.round(((original - current) / original) * 100);
};

const sortedPlans = computed(() =>
	[...displayPlans.value].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);

const featuredPlan = computed(() => {
	// Highlight the middle plan (6 bulan) as "Pilihan Terbaik"
	return sortedPlans.value[Math.min(1, sortedPlans.value.length - 1)]?.id;
});
</script>

<template>
	<Head title="Paket Langganan — Ujion" />

	<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white">
		<!-- Header -->
		<header class="border-b border-slate-200/60 bg-white/80 backdrop-blur-sm">
			<div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
				<Link href="/" class="flex items-center gap-2">
					<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-primary text-white shadow-sm">
						<i class="fa-solid fa-graduation-cap"></i>
					</div>
					<span class="text-lg font-bold text-slate-900">Ujion</span>
				</Link>
				<Link href="/register/guru" class="btn-secondary px-4 py-2 text-sm font-bold">
					Daftar Sekarang
				</Link>
			</div>
		</header>

		<!-- Pricing Section -->
		<section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
			<div class="text-center">
				<span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary">
					<i class="fa-solid fa-tag"></i> Promo Berlaku
				</span>
				<h1 class="mt-4 text-3xl font-bold text-slate-900 sm:text-4xl">Pilih Paket Langganan</h1>
				<p class="mx-auto mt-3 max-w-2xl text-sm text-textSecondary sm:text-base">
					Akses penuh platform ujian TKA. Pilih paket yang sesuai kebutuhan Anda — semua fitur terbuka di setiap paket.
				</p>
			</div>

			<!-- Pricing Cards -->
			<div class="mt-10 grid gap-6 sm:gap-5 lg:grid-cols-3">
				<div
					v-for="plan in sortedPlans"
					:key="plan.id"
					class="relative flex flex-col overflow-hidden rounded-[28px] border bg-white shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-hover"
					:class="featuredPlan === plan.id ? 'border-primary ring-2 ring-primary/20 lg:scale-105' : 'border-slate-200/70'"
				>
					<!-- Featured badge -->
					<div v-if="featuredPlan === plan.id" class="absolute right-4 top-4">
						<span class="inline-flex items-center rounded-full bg-gradient-primary px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-sm">
							<i class="fa-solid fa-star mr-1 text-[8px]"></i> Pilihan Terbaik
						</span>
					</div>

					<!-- Promo badge -->
					<div v-if="plan.promo_active && plan.original_price && plan.original_price > plan.price" class="absolute left-4 top-4">
						<span class="inline-flex items-center rounded-full bg-rose-500 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-sm">
							<i class="fa-solid fa-fire mr-1 text-[8px]"></i> Promo!
						</span>
					</div>

					<!-- Card Body -->
					<div class="px-6 py-7">
						<div class="mb-1">
							<h3 class="text-xl font-bold text-slate-900">{{ plan.name }}</h3>
							<p class="text-xs font-semibold text-textSecondary">{{ plan.period }}</p>
						</div>

						<p class="mt-2 text-sm text-textSecondary">{{ plan.description }}</p>

						<!-- Price -->
						<div class="mt-6">
							<div v-if="plan.promo_active && plan.original_price && plan.original_price > plan.price" class="mb-1">
								<span class="text-sm text-slate-400 line-through">{{ formatRupiah(plan.original_price) }}</span>
								<span class="ml-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
									Hemat {{ calcDiscount(plan.original_price, plan.price) }}%
								</span>
							</div>
							<div class="flex items-baseline gap-1">
								<span class="text-3xl font-black text-slate-900">{{ formatRupiah(plan.price) }}</span>
							</div>
							<p class="mt-1 text-xs text-textSecondary">{{ plan.period }}</p>
						</div>

						<!-- Features -->
						<ul class="mt-6 space-y-3">
							<li v-for="(feature, index) in plan.features" :key="index" class="flex items-start gap-3 text-sm text-slate-700">
								<i class="fa-solid fa-check-circle mt-0.5 text-sm text-emerald-500"></i>
								<span>{{ feature }}</span>
							</li>
						</ul>
					</div>

					<!-- CTA -->
					<div class="mt-auto px-6 pb-7">
						<Link
							:href="route('register.guru.form')"
							class="w-full justify-center py-3 text-sm font-bold"
							:class="featuredPlan === plan.id ? 'btn-primary' : 'btn-secondary'"
						>
							Langganan
							<i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
						</Link>
					</div>
				</div>
			</div>

			<!-- Info Note -->
			<div class="mt-10 text-center">
				<p class="text-xs text-textSecondary">
					<i class="fa-solid fa-shield-halved mr-1 text-primary"></i>
					Pembayaran aman via Doku. Aktifkan trial gratis setelah daftar — tanpa biaya dimuka.
				</p>
			</div>

			<!-- FAQ quick links -->
			<div class="mt-8 flex flex-wrap justify-center gap-4 text-sm">
				<Link href="/" class="font-semibold text-primary hover:text-primaryHover">Kembali ke Beranda</Link>
				<span class="text-slate-300">|</span>
				<Link href="/register/guru" class="font-semibold text-primary hover:text-primaryHover">Daftar Guru</Link>
			</div>
		</section>
	</div>
</template>
