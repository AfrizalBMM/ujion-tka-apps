<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';

const props = defineProps({
	auditLogs: {
		type: Array,
		default: () => [],
	},
	auditPaginator: {
		type: Object,
		default: null,
	},
	summary: {
		type: Object,
		required: true,
	},
	users: {
		type: Array,
		default: () => [],
	},
	filters: {
		type: Object,
		required: true,
	},
});

const nf = (value) => Number(value).toLocaleString('id-ID');

const filterForm = reactive({
	q: props.filters.q,
	method: props.filters.method,
	user_id: String(props.filters.user_id || ''),
	from: props.filters.from,
	to: props.filters.to,
});

const submitFilters = () => {
	router.get(route('superadmin.audit-logs.index'), filterForm, { preserveState: true });
};

const methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

const userFilterLabel = computed(() => {
	if (!filterForm.user_id) return 'Semua user';
	const user = props.users.find((candidate) => candidate.id === Number(filterForm.user_id));
	return user?.name ?? `User ${filterForm.user_id}`;
});

const methodBadgeClass = (method) => {
	if (method === 'GET') return 'badge-info';
	if (['POST', 'PUT', 'PATCH'].includes(method)) return 'badge-warning';
	return 'badge-danger';
};

const ucfirst = (value) => (value ? value.charAt(0).toUpperCase() + value.slice(1) : value);

const cleanupOpen = ref(false);
const cleanupConfirm = ref(false);
const cleanupMode = ref('older_than_30d');

const cleanupTitle = computed(() => (cleanupMode.value === 'all' ? 'Hapus SEMUA audit log' : 'Hapus log lebih tua dari 30 hari'));
const cleanupDescription = computed(() =>
	cleanupMode.value === 'all'
		? 'Seluruh rekam audit log akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.'
		: 'Log yang lebih tua dari 30 hari akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.'
);

const openCleanup = (mode = 'older_than_30d') => {
	cleanupMode.value = mode || 'older_than_30d';
	cleanupConfirm.value = false;
	cleanupOpen.value = true;
};

const cleanupForm = useForm({
	mode: 'older_than_30d',
});

const submitCleanup = () => {
	cleanupForm.mode = cleanupMode.value;
	cleanupForm.post(route('superadmin.audit-logs.cleanup'), {
		preserveScroll: true,
		onSuccess: () => {
			cleanupOpen.value = false;
			cleanupConfirm.value = false;
		},
	});
};
</script>

<template>
	<Head title="Audit Logs" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
				<div>
					<h1 class="text-2xl font-bold">Audit Logs</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Rekam aktivitas superadmin &amp; guru. Log otomatis dihapus setelah 30 hari.</p>
				</div>
				<div class="flex gap-2">
					<form method="POST" :action="route('superadmin.audit-logs.cleanup')" onsubmit="return false;" data-cleanup-form>
						<input type="hidden" name="_token" :value="$page.props.csrf_token">
						<input type="hidden" name="mode" value="older_than_30d">
						<button type="button" class="btn-secondary whitespace-nowrap" @click="openCleanup('older_than_30d')">
							<i class="fa-solid fa-broom mr-2"></i>
							Hapus Log &gt;30 Hari
						</button>
					</form>
				</div>
			</div>

			<div class="card !p-0 overflow-hidden">
				<div class="grid grid-cols-2 divide-slate-200 dark:divide-slate-800 lg:grid-cols-4 lg:divide-x">
					<div class="flex items-center gap-3 p-3 lg:p-4">
						<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
							<i class="fa-solid fa-database text-sm text-slate-500 dark:text-slate-300"></i>
						</div>
						<div class="min-w-0">
							<div class="text-[10px] font-semibold uppercase tracking-wide text-muted">Total Log</div>
							<div class="text-lg font-bold leading-tight text-slate-900 dark:text-slate-100">{{ nf(summary.total) }}</div>
							<div class="truncate text-[10px] text-muted">30 hari terakhir</div>
						</div>
					</div>
					<div class="flex items-center gap-3 p-3 lg:p-4">
						<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10">
							<i class="fa-solid fa-calendar-day text-sm text-blue-600"></i>
						</div>
						<div class="min-w-0">
							<div class="text-[10px] font-semibold uppercase tracking-wide text-muted">Hari Ini</div>
							<div class="text-lg font-bold leading-tight text-blue-700">{{ nf(summary.today) }}</div>
							<div class="truncate text-[10px] text-muted">Sejak tengah malam</div>
						</div>
					</div>
					<div class="flex items-center gap-3 p-3 lg:p-4">
						<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-500/10">
							<i class="fa-solid fa-user-pen text-sm text-amber-600"></i>
						</div>
						<div class="min-w-0">
							<div class="text-[10px] font-semibold uppercase tracking-wide text-muted">User Paling Aktif</div>
							<div class="truncate text-sm font-bold leading-tight text-slate-900 dark:text-slate-100">{{ summary.top_user?.name ?? '-' }}</div>
							<div class="truncate text-[10px] text-muted">{{ summary.top_user ? `${nf(summary.top_user.total)} aksi` : 'Belum ada data' }}</div>
						</div>
					</div>
					<div class="flex items-center gap-3 p-3 lg:p-4">
						<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-500/10">
							<i class="fa-solid fa-bolt text-sm text-purple-600"></i>
						</div>
						<div class="min-w-0">
							<div class="text-[10px] font-semibold uppercase tracking-wide text-muted">Aksi Terbanyak</div>
							<div class="truncate text-sm font-bold leading-tight text-slate-900 dark:text-slate-100" :title="summary.top_action?.name ?? ''">{{ summary.top_action?.name ?? '-' }}</div>
							<div class="truncate text-[10px] text-muted">{{ summary.top_action ? `${nf(summary.top_action.total)}x dipanggil` : 'Belum ada data' }}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<form method="GET" :action="route('superadmin.audit-logs.index')" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center" @submit.prevent="submitFilters">
					<input v-model="filterForm.q" type="text" name="q" class="input w-full py-1.5 text-sm sm:w-auto sm:flex-1 sm:min-w-[180px]" placeholder="Cari path, route, IP...">
					<div class="ssd-wrap" style="width: 130px;">
						<input type="hidden" name="method" :value="filterForm.method" @change="filterForm.method = $event.target.value">
						<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
							<span class="ssd-label truncate">{{ filterForm.method || 'Semua method' }}</span>
							<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
						</button>
						<div class="ssd-panel">
							<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari method..."></div>
							<div class="ssd-list">
								<div class="ssd-option" :class="filterForm.method === '' ? 'ssd-selected' : ''" data-value="">Semua method</div>
								<div
									v-for="method in methods"
									:key="method"
									class="ssd-option"
									:class="filterForm.method === method ? 'ssd-selected' : ''"
									:data-value="method"
								>
									{{ method }}
								</div>
							</div>
						</div>
					</div>
					<div class="ssd-wrap" style="max-width: 170px;">
						<input type="hidden" name="user_id" :value="filterForm.user_id" @change="filterForm.user_id = $event.target.value">
						<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
							<span class="ssd-label truncate">{{ userFilterLabel }}</span>
							<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
						</button>
						<div class="ssd-panel">
							<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari user..."></div>
							<div class="ssd-list">
								<div class="ssd-option" :class="Number(filterForm.user_id) === 0 ? 'ssd-selected' : ''" data-value="">Semua user</div>
								<div
									v-for="user in users"
									:key="user.id"
									class="ssd-option"
									:class="Number(filterForm.user_id) === user.id ? 'ssd-selected' : ''"
									:data-value="user.id"
								>
									{{ user.name }}
								</div>
							</div>
						</div>
					</div>
					<input v-model="filterForm.from" type="date" name="from" class="input w-full py-1.5 text-sm sm:w-auto" title="Dari tanggal">
					<span class="hidden text-xs text-muted sm:inline">–</span>
					<input v-model="filterForm.to" type="date" name="to" class="input w-full py-1.5 text-sm sm:w-auto" title="Sampai tanggal">
					<div class="flex gap-2">
						<button type="submit" class="btn-primary px-3 py-1.5 text-sm">Filter</button>
						<Link :href="route('superadmin.audit-logs.index')" class="btn-secondary px-3 py-1.5 text-sm">Reset</Link>
					</div>
				</form>

				<div class="table-container">
					<table class="table-ujion min-w-[860px]">
						<thead>
							<tr>
								<th>Waktu</th>
								<th>Method</th>
								<th>Path</th>
								<th>IP</th>
								<th>Route</th>
								<th>User</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="auditLogs.length > 0">
								<tr v-for="log in auditLogs" :key="log.id">
									<td class="text-xs text-muted dark:text-slate-400">{{ log.created_at_formatted }}</td>
									<td>
										<span :class="methodBadgeClass(log.method)">{{ log.method }}</span>
									</td>
									<td class="font-mono text-xs text-textSecondary dark:text-slate-300">{{ log.path || '/' }}</td>
									<td class="text-xs text-textSecondary dark:text-slate-300">{{ log.ip_address || '-' }}</td>
									<td class="text-xs text-muted dark:text-slate-400">
										<div>{{ log.route_name || '-' }}</div>
										<div class="mt-1 text-[11px]">{{ log.user_agent || '-' }}</div>
									</td>
									<td class="text-xs text-muted dark:text-slate-400">
										<template v-if="log.user">
											<div class="font-semibold text-slate-700 dark:text-slate-200">{{ log.user.name }}</div>
											<div class="mt-0.5">{{ ucfirst(log.user.role) }} · ID {{ log.user_id }}</div>
										</template>
										<template v-else>-</template>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="6" class="py-12 text-center text-muted dark:text-slate-400">Tidak ada log yang cocok dengan filter.</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div v-if="auditLogs.length > 0 && auditPaginator" class="mt-4">
					<PaginationLinks :paginator="auditPaginator" />
				</div>
			</div>
		</div>

		<div
			id="cleanup-modal"
			class="fixed inset-0 z-50 items-center justify-center bg-slate-950/70 px-4"
			:class="cleanupOpen ? 'flex' : 'hidden'"
			@click.self="cleanupOpen = false"
		>
			<div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-sm font-semibold uppercase tracking-wide text-muted">Bersihkan Audit Log</div>
						<div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">{{ cleanupTitle }}</div>
					</div>
					<button type="button" class="btn-secondary" @click="cleanupOpen = false">Tutup</button>
				</div>

				<p class="mt-3 text-sm text-textSecondary dark:text-slate-300">{{ cleanupDescription }}</p>

				<form class="mt-5 space-y-4" @submit.prevent="submitCleanup">
					<input type="hidden" name="mode" :value="cleanupMode">

					<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3 dark:border-rose-500/20 dark:bg-rose-500/10">
						<input v-model="cleanupConfirm" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-rose-300 text-rose-600 focus:ring-rose-500">
						<span class="text-sm font-semibold text-rose-900 dark:text-rose-100">Saya mengerti bahwa log yang dihapus tidak bisa dikembalikan.</span>
					</label>

					<div class="flex justify-end gap-3">
						<button type="button" class="btn-secondary" @click="cleanupOpen = false">Batal</button>
						<button type="submit" class="btn-danger" :disabled="!cleanupConfirm || cleanupForm.processing">
							<i class="fa-solid fa-trash mr-2"></i>
							Hapus Log
						</button>
					</div>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
