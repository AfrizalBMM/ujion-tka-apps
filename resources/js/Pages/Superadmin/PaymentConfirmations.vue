<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	transactions: {
		type: Array,
		required: true,
	},
	summary: {
		type: Object,
		required: true,
	},
	search: {
		type: String,
		default: '',
	},
	statusFilter: {
		type: String,
		default: '',
	},
});

const filters = reactive({
	q: props.search,
	status: props.statusFilter,
});

const submitFilters = () => {
	router.get(route('superadmin.payment-confirmations.index'), filters, { preserveState: true });
};

const isBlank = (value) => value === null || value === undefined || String(value).trim() === '';
</script>

<template>
	<Head title="Riwayat Transaksi" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Riwayat Transaksi</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Semua transaksi pembayaran aktivasi guru, termasuk pembayaran otomatis via Doku.</p>
				</div>
			</div>

			<div class="grid gap-4 md:grid-cols-3">
				<Link :href="route('superadmin.payment-confirmations.index', { status: 'pending' })" class="card transition hover:ring-2 hover:ring-blue-100">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Menunggu pembayaran</div>
					<div class="mt-2 text-2xl font-bold text-blue-700">{{ summary.pending }}</div>
					<div class="mt-1 text-sm text-muted">Transaksi yang belum diselesaikan guru.</div>
				</Link>
				<Link :href="route('superadmin.payment-confirmations.index', { status: 'success' })" class="card transition hover:ring-2 hover:ring-emerald-100">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Sukses</div>
					<div class="mt-2 text-2xl font-bold text-green-700">{{ summary.success }}</div>
					<div class="mt-1 text-sm text-muted">Pembayaran berhasil dan akun guru aktif.</div>
				</Link>
				<Link :href="route('superadmin.payment-confirmations.index', { status: 'failed' })" class="card transition hover:ring-2 hover:ring-rose-100">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Gagal / Kadaluarsa</div>
					<div class="mt-2 text-2xl font-bold text-rose-700">{{ summary.failed }}</div>
					<div class="mt-1 text-sm text-muted">Pembayaran tidak selesai atau dibatalkan.</div>
				</Link>
			</div>

			<div class="card">
				<form method="GET" :action="route('superadmin.payment-confirmations.index')" class="mb-6 flex flex-col gap-3 sm:flex-row" @submit.prevent="submitFilters">
					<input v-model="filters.q" type="text" name="q" class="input w-full" placeholder="Cari kode referensi, invoice Doku, nama guru, email, WA, atau paket">
					<select v-model="filters.status" name="status" class="input sm:w-48">
						<option value="">Semua status</option>
						<option value="pending">Menunggu pembayaran</option>
						<option value="success">Sukses</option>
						<option value="failed">Gagal</option>
					</select>
					<button type="submit" class="btn-primary">Cari</button>
					<Link :href="route('superadmin.payment-confirmations.index')" class="btn-secondary text-center">Reset</Link>
				</form>

				<div class="table-container">
					<table class="table-ujion min-w-[980px]">
						<thead>
							<tr>
								<th>Referensi</th>
								<th>Guru</th>
								<th>Paket</th>
								<th>Metode</th>
								<th>Status</th>
								<th>Waktu</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="transactions.length > 0">
								<tr v-for="transaction in transactions" :key="transaction.id">
									<td>
										<div class="font-bold">{{ transaction.reference_code }}</div>
										<div v-if="!isBlank(transaction.doku_invoice_number) && transaction.doku_invoice_number !== transaction.reference_code" class="mt-1 text-xs text-muted">{{ transaction.doku_invoice_number }}</div>
									</td>
									<td>
										<div class="font-semibold">{{ transaction.user?.name ?? '-' }}</div>
										<div class="mt-1 text-xs text-muted">{{ transaction.user?.email ?? '-' }}</div>
										<div class="mt-1 text-xs text-muted">{{ transaction.user?.no_wa || '-' }}</div>
									</td>
									<td>
										<div class="font-semibold text-slate-900">{{ transaction.plan_name }}</div>
										<div class="mt-1 text-sm text-muted">Rp{{ Number(transaction.amount).toLocaleString('id-ID') }}</div>
									</td>
									<td>
										<template v-if="transaction.payment_method === 'doku'">
											<span class="badge-info">Doku</span>
											<div v-if="!isBlank(transaction.doku_payment_channel)" class="mt-1 text-xs text-muted">{{ transaction.doku_payment_channel }}</div>
										</template>
										<span v-else class="badge-warning">Manual</span>
									</td>
									<td>
										<span v-if="transaction.status === 'success'" class="badge-success">Sukses</span>
										<span v-else-if="transaction.status === 'failed'" class="badge-danger">Gagal</span>
										<span v-else class="badge-warning">Menunggu</span>
									</td>
									<td>
										<div v-if="transaction.paid_at" class="text-xs font-semibold text-emerald-700">Dibayar {{ transaction.paid_at_formatted }}</div>
										<div v-else class="text-xs text-muted">Dibuat {{ transaction.created_at_formatted }}</div>
										<div v-if="!isBlank(transaction.rejection_reason)" class="mt-1 max-w-xs text-xs text-rose-600">{{ transaction.rejection_reason }}</div>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="6" class="py-12 text-center text-muted">Belum ada transaksi yang cocok.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
