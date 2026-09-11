<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

defineProps({
	landingExams: {
		type: Array,
		default: () => [],
	},
	totalRevenue: {
		type: [Number, String],
		default: 0,
	},
	totalOrders: {
		type: Number,
		default: 0,
	},
});

const nf = (value) => Number(value).toLocaleString('id-ID');
</script>

<template>
	<Head title="Ujian Publik Langsung" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="section-heading">
				<div>
					<h1 class="text-2xl font-bold">Ujian Publik Langsung Siswa</h1>
					<p class="text-sm text-textSecondary mt-1">Kelola ujian yang ditampilkan di landing page untuk siswa yang membeli langsung.</p>
				</div>
				<Link :href="route('superadmin.landing-exams.create')" class="btn-primary">
					<i class="fa-solid fa-plus mr-2"></i>
					Tambah Ujian Publik
				</Link>
			</div>

			<div class="grid gap-4 md:grid-cols-3">
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pendapatan</div>
					<div class="mt-2 text-2xl font-black text-emerald-600">Rp{{ nf(totalRevenue) }}</div>
				</div>
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pesanan</div>
					<div class="mt-2 text-2xl font-black text-indigo-600">{{ totalOrders }}</div>
				</div>
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Ujian Aktif</div>
					<div class="mt-2 text-2xl font-black text-slate-700 dark:text-slate-200">{{ landingExams.filter((le) => le.is_active).length }}</div>
				</div>
			</div>

			<div class="card">
				<div v-if="landingExams.length === 0" class="empty-state text-center py-12">
					<i class="fa-solid fa-store text-4xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
					<p class="text-textSecondary">Belum ada ujian publik. Klik "Tambah Ujian Publik" untuk membuat.</p>
				</div>
				<div v-else class="table-ujion overflow-x-auto">
					<table class="w-full">
						<thead>
							<tr class="border-b border-slate-200/80 dark:border-slate-700/60">
								<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Ujian</th>
								<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Jenjang</th>
								<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Mapel Aktif</th>
								<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Pesanan</th>
								<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Status</th>
								<th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-widest text-textSecondary">Aksi</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-200/60 dark:divide-slate-700/40">
							<template v-if="landingExams.length > 0">
								<tr v-for="le in landingExams" :key="le.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
									<td class="px-4 py-3">
										<div class="font-semibold text-sm">{{ le.exam?.judul ?? '—' }}</div>
										<div class="text-xs text-textSecondary mt-0.5">/{{ le.jenjang.toLowerCase() }}/{{ le.slug }}</div>
									</td>
									<td class="px-4 py-3">
										<span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ le.jenjang }}</span>
									</td>
									<td class="px-4 py-3 text-sm">{{ (le.mapels || []).filter((m) => m.is_active).length }} / {{ (le.mapels || []).length }}</td>
									<td class="px-4 py-3 text-sm font-semibold">{{ le.paid_orders_count ?? 0 }}</td>
									<td class="px-4 py-3">
										<span v-if="le.is_active" class="badge-success">Aktif</span>
										<span v-else class="badge-warning">Nonaktif</span>
									</td>
									<td class="px-4 py-3 text-right">
										<div class="flex items-center justify-end gap-2">
											<Link :href="route('superadmin.landing-exams.show', le.id)" class="btn-secondary px-3 py-1.5 text-xs">
												<i class="fa-solid fa-eye mr-1"></i>Detail
											</Link>
											<form method="POST" :action="route('superadmin.landing-exams.toggle', le.id)" class="inline">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-secondary px-3 py-1.5 text-xs">
													<template v-if="le.is_active">
														<i class="fa-solid fa-pause mr-1"></i>Nonaktifkan
													</template>
													<template v-else>
														<i class="fa-solid fa-play mr-1"></i>Aktifkan
													</template>
												</button>
											</form>
										</div>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="6" class="px-4 py-8 text-center text-textSecondary">Tidak ada data.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
