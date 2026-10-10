<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';
import { closeAllActionMenus } from '@/core/action-menus';

const props = defineProps({
	coupons: {
		type: Object,
		required: true,
	},
	filters: {
		type: Object,
		default: () => ({}),
	},
});

const couponItems = computed(() => props.coupons.data || []);

const nf = (value) => Number(value || 0).toLocaleString('id-ID');

const typeBadge = (type) => {
	switch (type) {
		case 'percentage':
			return { class: 'badge-info', label: 'Persen' };
		case 'nominal':
			return { class: 'badge-success', label: 'Nominal' };
		case 'free':
			return { class: 'badge-warning', label: 'Gratis' };
		default:
			return { class: '', label: type };
	}
};

const formatValue = (coupon) => {
	if (coupon.type === 'percentage') return `${Number(coupon.value)}%`;
	if (coupon.type === 'nominal') return `Rp${nf(coupon.value)}`;
	return 'Gratis';
};

const formatDate = (dt) => {
	if (!dt) return '-';
	const d = new Date(dt);
	if (isNaN(d.getTime())) return '-';
	return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

// --- Filters ---
const filterForm = useForm({
	search: props.filters.search ?? '',
	active: props.filters.active ?? '',
});

const applyFilters = () => {
	filterForm.get(route('superadmin.coupons.index'), {
		preserveScroll: true,
		preserveState: true,
	});
};

const resetFilters = () => {
	filterForm.reset();
	filterForm.get(route('superadmin.coupons.index'), {
		preserveScroll: true,
		preserveState: true,
	});
};

// --- Modal (create/edit) ---
const modalOpen = ref(false);
const modalEl = ref(null);
const editing = ref(false);
const formAction = ref('');
const codeInput = ref(null);

watch(modalOpen, (val) => {
	if (val) nextTick(() => codeInput.value?.focus());
});

const couponForm = useForm({
	code: '',
	type: 'percentage',
	value: '',
	min_transaction: '',
	max_usage_total: '',
	max_usage_per_user: '',
	jenjang_target: '',
	starts_at: '',
	ends_at: '',
	is_active: true,
});

const resetForm = () => {
	formAction.value = route('superadmin.coupons.store');
	editing.value = false;
	couponForm.reset();
	couponForm.is_active = true;
};

const openCreate = () => {
	closeAllActionMenus();
	resetForm();
	modalOpen.value = true;
};

const openEdit = (coupon) => {
	closeAllActionMenus();
	resetForm();
	editing.value = true;
	formAction.value = route('superadmin.coupons.update', coupon.id);
	couponForm.code = coupon.code ?? '';
	couponForm.type = coupon.type ?? 'percentage';
	couponForm.value = coupon.type === 'free' ? '' : (coupon.value ?? '');
	couponForm.min_transaction = coupon.min_transaction ?? '';
	couponForm.max_usage_total = coupon.max_usage_total ?? '';
	couponForm.max_usage_per_user = coupon.max_usage_per_user ?? '';
	couponForm.jenjang_target = coupon.jenjang_target ?? '';
	couponForm.starts_at = coupon.starts_at ? String(coupon.starts_at).slice(0, 10) : '';
	couponForm.ends_at = coupon.ends_at ? String(coupon.ends_at).slice(0, 10) : '';
	couponForm.is_active = coupon.is_active ?? true;
	modalOpen.value = true;
};

const submitCoupon = () => {
	couponForm.post(formAction.value, {
		preserveScroll: true,
		onSuccess: () => {
			modalOpen.value = false;
		},
	});
};

// Watch type to clear value when free
watch(() => couponForm.type, (newType) => {
	if (newType === 'free') {
		couponForm.value = '';
	}
});

// --- Keyboard ---
const onKeydown = (event) => {
	if (event.key === 'Escape' && modalOpen.value) {
		modalOpen.value = false;
	}
};

onMounted(() => {
	formAction.value = route('superadmin.coupons.store');
	document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
	document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
	<Head title="Kupon" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Kupon Diskon</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Kelola kupon diskon untuk transaksi pembayaran. Kupon aktif dapat diverifikasi saat checkout ujian publik.
					</p>
				</div>
				<div class="flex gap-2">
					<button type="button" class="btn-primary" @click="openCreate">
						<i class="fa-solid fa-plus mr-2"></i>
						Tambah Kupon
					</button>
				</div>
			</div>

			<!-- Filters -->
			<div class="card">
				<form class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between" @submit.prevent="applyFilters">
					<div class="flex-1">
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Cari Kode</label>
						<input
							v-model="filterForm.search"
							type="text"
							class="input w-full"
							placeholder="Cari kode kupon..."
						>
					</div>
					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status</label>
						<select v-model="filterForm.active" class="input w-full sm:w-48">
							<option value="">Semua</option>
							<option value="1">Aktif</option>
							<option value="0">Nonaktif</option>
						</select>
					</div>
					<div class="flex gap-2">
						<button type="submit" class="btn-primary whitespace-nowrap">
							<i class="fa-solid fa-magnifying-glass mr-2"></i>
							Filter
						</button>
						<button type="button" class="btn-secondary whitespace-nowrap" @click="resetFilters">
							<i class="fa-solid fa-rotate-left mr-2"></i>
							Reset
						</button>
					</div>
				</form>
			</div>

			<!-- Table -->
			<div class="card">
				<div class="text-xs font-semibold uppercase tracking-wide text-muted">Daftar</div>
				<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Kupon</div>

				<div class="mt-4 table-container">
					<table class="table-ujion min-w-[960px]">
						<thead>
							<tr>
								<th>Kode</th>
								<th>Tipe</th>
								<th>Nilai</th>
								<th>Min. Transaksi</th>
								<th>Batas Penggunaan</th>
								<th>Jenjang</th>
								<th>Periode</th>
								<th>Status</th>
								<th class="text-right">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="couponItems.length > 0">
								<tr v-for="coupon in couponItems" :key="coupon.id">
									<td>
										<span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ coupon.code }}</span>
									</td>
									<td>
										<span :class="typeBadge(coupon.type).class">{{ typeBadge(coupon.type).label }}</span>
									</td>
									<td class="font-semibold">{{ formatValue(coupon) }}</td>
									<td class="text-sm">
										{{ coupon.min_transaction ? `Rp${nf(coupon.min_transaction)}` : '-' }}
									</td>
									<td class="text-sm">
										<div v-if="coupon.max_usage_total">Total: {{ nf(coupon.max_usage_total) }}</div>
										<div v-if="coupon.max_usage_per_user">Per user: {{ nf(coupon.max_usage_per_user) }}</div>
										<span v-if="!coupon.max_usage_total && !coupon.max_usage_per_user" class="text-muted">Tanpa batas</span>
									</td>
									<td>
										<span v-if="coupon.jenjang_target" class="badge-info">{{ coupon.jenjang_target }}</span>
										<span v-else class="text-muted">Semua</span>
									</td>
									<td class="text-xs text-textSecondary dark:text-slate-300">
										<div v-if="coupon.starts_at || coupon.ends_at">
											<div v-if="coupon.starts_at">{{ formatDate(coupon.starts_at) }}</div>
											<div v-if="coupon.ends_at" class="text-muted">s/d {{ formatDate(coupon.ends_at) }}</div>
										</div>
										<span v-else class="text-muted">Tanpa batas waktu</span>
									</td>
									<td>
										<span v-if="coupon.is_active" class="badge-success">Aktif</span>
										<span v-else class="badge-danger">Nonaktif</span>
									</td>
									<td class="text-right">
										<div class="relative inline-block text-left" data-action-menu>
											<button
												type="button"
												class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-all duration-200 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
												data-action-menu-toggle
												aria-expanded="false"
												title="Buka aksi"
											>
												<i class="fa-solid fa-ellipsis"></i>
											</button>

											<div
												class="invisible absolute right-0 top-full z-20 mt-2 min-w-56 translate-y-2 rounded-2xl border border-slate-200/80 bg-white p-2 opacity-0 shadow-modal transition-all duration-200 dark:border-slate-800 dark:bg-slate-950"
												data-action-menu-panel
											>
												<button
													type="button"
													class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
													@click="openEdit(coupon)"
												>
													<i class="fa-solid fa-pen w-4"></i>
													Edit
												</button>

												<form method="POST" :action="route('superadmin.coupons.toggle-active', coupon.id)">
													<input type="hidden" name="_token" :value="$page.props.csrf_token">
													<button
														type="submit"
														class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-amber-50 hover:text-amber-700 dark:text-slate-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300"
													>
														<i class="fa-solid fa-eye w-4"></i>
														Aktif / Nonaktif
													</button>
												</form>

												<form method="POST" :action="route('superadmin.coupons.destroy', coupon.id)">
													<input type="hidden" name="_token" :value="$page.props.csrf_token">
													<button
														type="submit"
														class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700 dark:text-slate-200 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
														data-confirm="Hapus kupon ini?"
														data-confirm-title="Hapus Kupon"
													>
														<i class="fa-solid fa-trash w-4"></i>
														Hapus
													</button>
												</form>
											</div>
										</div>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="9" class="py-12 text-center text-muted">
									<i class="fa-solid fa-ticket text-4xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
									Belum ada kupon. Klik "Tambah Kupon" untuk mulai.
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div v-if="coupons.last_page > 1" class="mt-4">
					<PaginationLinks :paginator="coupons" />
				</div>
			</div>
		</div>

		<!-- Create/Edit Modal -->
		<div
			id="coupon-modal"
			class="fixed inset-0 z-50 items-center justify-center bg-slate-950/70 px-4"
			:class="modalOpen ? 'flex' : 'hidden'"
			@click.self="modalOpen = false"
			@keydown.escape="modalOpen = false"
			tabindex="-1"
			ref="modalEl"
		>
			<div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-base font-bold text-slate-900 dark:text-slate-100">{{ editing ? 'Edit Kupon' : 'Tambah Kupon' }}</div>
						<div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Isi data kupon diskon untuk transaksi.</div>
					</div>
					<button type="button" class="btn-secondary" @click="modalOpen = false">Tutup</button>
				</div>

				<form id="coupon-form" class="mt-5 space-y-4" @submit.prevent="submitCoupon">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kode Kupon</label>
							<input
								id="coupon-code"
								ref="codeInput"
								v-model="couponForm.code"
								class="input mt-1 font-mono uppercase"
								name="code"
								placeholder="DISKON50"
								required
							>
							<div v-if="couponForm.errors.code" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.code }}</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Tipe Diskon</label>
							<select v-model="couponForm.type" class="input mt-1" name="type" required>
								<option value="percentage">Persen (%)</option>
								<option value="nominal">Nominal (Rp)</option>
								<option value="free">Gratis</option>
							</select>
							<div v-if="couponForm.errors.type" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.type }}</div>
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-if="couponForm.type !== 'free'">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">
								{{ couponForm.type === 'percentage' ? 'Persen Diskon (%)' : 'Nominal Diskon (Rp)' }}
							</label>
							<input
								v-model="couponForm.value"
								class="input mt-1"
								name="value"
								:type="couponForm.type === 'percentage' ? 'number' : 'number'"
								step="any"
								:placeholder="couponForm.type === 'percentage' ? '50' : '10000'"
								required
							>
							<div v-if="couponForm.errors.value" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.value }}</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Min. Transaksi (Rp)</label>
							<input
								v-model="couponForm.min_transaction"
								class="input mt-1"
								name="min_transaction"
								type="number"
								step="any"
								placeholder="Opsional, contoh: 50000"
							>
							<div v-if="couponForm.errors.min_transaction" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.min_transaction }}</div>
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Max. Penggunaan Total</label>
							<input
								v-model="couponForm.max_usage_total"
								class="input mt-1"
								name="max_usage_total"
								type="number"
								placeholder="Opsional, contoh: 100"
							>
							<div v-if="couponForm.errors.max_usage_total" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.max_usage_total }}</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Max. Penggunaan per User</label>
							<input
								v-model="couponForm.max_usage_per_user"
								class="input mt-1"
								name="max_usage_per_user"
								type="number"
								placeholder="Opsional, contoh: 1"
							>
							<div v-if="couponForm.errors.max_usage_per_user" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.max_usage_per_user }}</div>
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Target Jenjang</label>
							<select v-model="couponForm.jenjang_target" class="input mt-1" name="jenjang_target">
								<option value="">Semua Jenjang</option>
								<option value="SD">SD</option>
								<option value="SMP">SMP</option>
								<option value="SMA">SMA</option>
							</select>
							<div v-if="couponForm.errors.jenjang_target" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.jenjang_target }}</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mulai Berlaku</label>
							<input
								v-model="couponForm.starts_at"
								class="input mt-1"
								name="starts_at"
								type="date"
							>
							<div v-if="couponForm.errors.starts_at" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.starts_at }}</div>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Berakhir</label>
							<input
								v-model="couponForm.ends_at"
								class="input mt-1"
								name="ends_at"
								type="date"
							>
							<div v-if="couponForm.errors.ends_at" class="mt-1 text-xs text-rose-500">{{ couponForm.errors.ends_at }}</div>
						</div>
					</div>

					<div>
						<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-950/40">
							<input
								v-model="couponForm.is_active"
								type="checkbox"
								name="is_active"
								value="1"
								class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
							>
							<span>
								<span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Aktif</span>
								<span class="mt-0.5 block text-xs text-textSecondary dark:text-slate-400">Kupon aktif dapat diverifikasi saat checkout.</span>
							</span>
						</label>
					</div>

					<div class="flex items-center justify-end gap-3">
						<button type="button" class="btn-secondary" @click="resetForm">Reset</button>
						<button class="btn-primary" type="submit" :disabled="couponForm.processing">
							<i class="fa-solid fa-floppy-disk mr-2"></i> {{ editing ? 'Simpan Perubahan' : 'Simpan' }}
						</button>
					</div>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
