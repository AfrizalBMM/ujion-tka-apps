<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';

const props = defineProps({
	landingExam: {
		type: Object,
		required: true,
	},
	orders: {
		type: Object,
		required: true,
	},
	revenue: {
		type: [Number, String],
		default: 0,
	},
});

const orderItems = computed(() => props.orders.data || []);

const nf = (value) => Number(value).toLocaleString('id-ID');

const statusBadge = (status) => {
	switch (status) {
		case 'pending_payment':
			return { class: 'badge-warning', label: 'Pending' };
		case 'paid':
			return { class: 'badge-success', label: 'Dibayar' };
		case 'exam_started':
			return { class: 'badge-success', label: 'Ujian Dimulai' };
		case 'exam_completed':
			return { class: 'badge-success', label: 'Selesai' };
		case 'failed':
			return { class: 'badge-danger', label: 'Gagal' };
		default:
			return { class: '', label: status };
	}
};
</script>

<template>
	<Head :title="`Pesanan — ${landingExam.exam?.judul ?? 'Ujian Publik'}`" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="section-heading">
				<div>
					<h1 class="text-2xl font-bold">Pesanan: {{ landingExam.exam?.judul ?? '—' }}</h1>
					<p class="text-sm text-textSecondary mt-1">Riwayat pendaftaran &amp; pembayaran siswa untuk ujian publik ini.</p>
				</div>
				<Link :href="route('superadmin.landing-exams.show', landingExam.id)" class="btn-secondary">Kembali</Link>
			</div>

			<div class="grid gap-4 md:grid-cols-2">
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Pendapatan</div>
					<div class="mt-2 text-xl font-black text-emerald-600">Rp{{ nf(revenue) }}</div>
				</div>
				<div class="metric-card">
					<div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pesanan</div>
					<div class="mt-2 text-xl font-black text-indigo-600">{{ orders.total }}</div>
				</div>
			</div>

			<div class="card">
				<div v-if="orderItems.length === 0" class="empty-state text-center py-12">
					<i class="fa-solid fa-receipt text-4xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
					<p class="text-textSecondary">Belum ada pesanan untuk ujian ini.</p>
				</div>
				<template v-else>
					<div class="table-ujion overflow-x-auto">
						<table class="w-full">
							<thead>
								<tr class="border-b border-slate-200/80 dark:border-slate-700/60">
									<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Nama</th>
									<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">No WA</th>
									<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Mapel</th>
									<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Nominal</th>
									<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Status</th>
									<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Tanggal</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-slate-200/60 dark:divide-slate-700/40">
								<tr v-for="order in orderItems" :key="order.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
									<td class="px-4 py-3 text-sm font-medium">{{ order.nama }}</td>
									<td class="px-4 py-3 text-sm">{{ order.nomor_wa }}</td>
									<td class="px-4 py-3 text-sm">{{ order.mapel_label }}</td>
									<td class="px-4 py-3 text-sm font-semibold">Rp{{ nf(order.amount) }}</td>
									<td class="px-4 py-3">
										<span :class="statusBadge(order.status).class">{{ statusBadge(order.status).label }}</span>
									</td>
									<td class="px-4 py-3 text-xs text-textSecondary">{{ order.created_at_formatted }}</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="mt-4">
						<PaginationLinks :paginator="orders" />
					</div>
				</template>
			</div>
		</div>
	</SuperadminLayout>
</template>
