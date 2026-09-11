<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	landingExam: {
		type: Object,
		required: true,
	},
	ordersCount: {
		type: Number,
		default: 0,
	},
	paidOrdersCount: {
		type: Number,
		default: 0,
	},
	revenue: {
		type: [Number, String],
		default: 0,
	},
	mapelItems: {
		type: Array,
		default: () => [],
	},
	tokenItems: {
		type: Array,
		default: () => [],
	},
});

const nf = (value) => Number(value).toLocaleString('id-ID');

const jenjangOptions = ['SD', 'SMP', 'SMA'];

const form = useForm({
	jenjang: props.landingExam.jenjang,
	slug: props.landingExam.slug ?? '',
	short_description: props.landingExam.short_description ?? '',
	description: props.landingExam.description ?? '',
	is_active: props.landingExam.is_active ?? false,
	mapels: props.mapelItems.map((m) => ({
		id: m.id,
		price: m.price,
		original_price: m.original_price,
		is_active: m.is_active,
	})),
});

const submit = () => {
	form.post(route('superadmin.landing-exams.update', props.landingExam.id));
};
</script>

<template>
	<Head title="Detail Ujian Publik" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="section-heading">
				<div>
					<h1 class="text-2xl font-bold">{{ landingExam.exam?.judul ?? 'Ujian Publik' }}</h1>
					<p class="text-sm text-textSecondary mt-1">/{{ landingExam.jenjang.toLowerCase() }}/{{ landingExam.slug }}</p>
				</div>
				<div class="flex gap-2">
					<Link :href="route('superadmin.landing-exams.orders', landingExam.id)" class="btn-secondary">
						<i class="fa-solid fa-receipt mr-1"></i>Pesanan
					</Link>
					<Link :href="route('superadmin.landing-exams.index')" class="btn-secondary">Kembali</Link>
				</div>
			</div>

			<div class="grid gap-4 md:grid-cols-3">
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Pendapatan</div>
					<div class="mt-2 text-xl font-black text-emerald-600">Rp{{ nf(revenue) }}</div>
				</div>
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Pesanan Dibayar</div>
					<div class="mt-2 text-xl font-black text-indigo-600">{{ paidOrdersCount }}</div>
				</div>
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pesanan</div>
					<div class="mt-2 text-xl font-black text-slate-700 dark:text-slate-200">{{ ordersCount }}</div>
				</div>
			</div>

			<form class="card space-y-6" @submit.prevent="submit">
				<div class="grid gap-4 md:grid-cols-2">
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang" :value="form.jenjang" @change="form.jenjang = $event.target.value">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ form.jenjang }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div
										v-for="j in jenjangOptions"
										:key="j"
										class="ssd-option"
										:class="form.jenjang === j ? 'ssd-selected' : ''"
										:data-value="j"
									>
										{{ j }}
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Slug</label>
						<input v-model="form.slug" type="text" name="slug" class="input">
					</div>
				</div>

				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Singkat</label>
					<input v-model="form.short_description" type="text" name="short_description" class="input" maxlength="500">
				</div>

				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Lengkap</label>
					<textarea v-model="form.description" name="description" class="input min-h-32"></textarea>
				</div>

				<div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-5 dark:border-slate-800 dark:bg-slate-900/60">
					<h3 class="text-sm font-bold uppercase tracking-[0.18em] text-textSecondary mb-4">Harga per Mapel</h3>
					<div class="space-y-3">
						<div
							v-for="(mapel, i) in mapelItems"
							:key="mapel.id"
							class="grid gap-3 rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/70 md:grid-cols-[1fr_auto_auto_auto]"
						>
							<div>
								<div class="font-semibold text-sm">{{ mapel.label }}</div>
								<div class="text-xs text-textSecondary mt-0.5">{{ mapel.jumlah_soal }} soal — {{ mapel.durasi_menit }} menit</div>
								<input type="hidden" :name="`mapels[${i}][id]`" :value="mapel.id">
							</div>
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga (Rp)</label>
								<input v-model="form.mapels[i].price" type="number" :name="`mapels[${i}][price]`" class="input" min="0" step="1000" required>
							</div>
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga Coret</label>
								<input v-model="form.mapels[i].original_price" type="number" :name="`mapels[${i}][original_price]`" class="input" min="0" step="1000">
							</div>
							<div class="flex items-center gap-2">
								<label class="flex items-center gap-2 text-xs">
									<input v-model="form.mapels[i].is_active" type="checkbox" :name="`mapels[${i}][is_active]`" value="1" class="h-4 w-4 text-primary">
									<span>Aktif</span>
								</label>
							</div>
						</div>
					</div>
				</div>

				<label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950/70">
					<input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="h-4 w-4 text-primary">
					<span>Aktifkan (tampilkan di landing page)</span>
				</label>

				<div class="flex gap-3">
					<button type="submit" class="btn-primary" :disabled="form.processing">Simpan Perubahan</button>
					<Link :href="route('superadmin.landing-exams.index')" class="btn-secondary">Kembali</Link>
				</div>
			</form>

			<div class="card">
				<h3 class="text-sm font-bold uppercase tracking-[0.18em] text-textSecondary mb-4">Token Mapel</h3>
				<div class="space-y-2">
					<div
						v-for="token in tokenItems"
						:key="token.token"
						class="flex items-center justify-between rounded-xl border border-slate-200/80 px-4 py-2 dark:border-slate-700/60"
					>
						<span class="text-sm font-medium">{{ token.label }}</span>
						<code class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-1 font-mono text-sm font-bold">{{ token.token }}</code>
					</div>
				</div>
			</div>

			<form method="POST" :action="route('superadmin.landing-exams.destroy', landingExam.id)" class="card border-red-200 dark:border-red-900/40">
				<input type="hidden" name="_token" :value="$page.props.csrf_token">
				<div class="flex items-center justify-between">
					<div>
						<h3 class="font-bold text-red-700 dark:text-red-400">Hapus Ujian Publik</h3>
						<p class="text-sm text-textSecondary mt-1">Ujian publik akan dihapus. Data pesanan tetap tersimpan.</p>
					</div>
					<button type="submit" class="btn-danger" data-confirm-title="Hapus Ujian Publik" data-confirm="Yakin ingin menghapus?">Hapus</button>
				</div>
			</form>
		</div>
	</SuperadminLayout>
</template>
