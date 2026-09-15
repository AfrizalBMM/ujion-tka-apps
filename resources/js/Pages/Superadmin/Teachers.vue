<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

const props = defineProps({
	teachers: {
		type: Array,
		default: () => [],
	},
	notificationTemplates: {
		type: Array,
		default: () => [],
	},
	paymentSummary: {
		type: Object,
		default: () => ({}),
	},
	search: {
		type: String,
		default: '',
	},
	paymentStatus: {
		type: String,
		default: '',
	},
	accountStatus: {
		type: String,
		default: '',
	},
});

const page = usePage();

const showAdminFlowModal = ref(false);
const copiedTokenId = ref(null);
const copiedTemplateIndex = ref(null);

const paymentStatusLabel = (status) => {
	switch (status) {
		case 'submitted':
			return 'Menunggu review';
		case 'rejected':
			return 'Ditolak';
		case 'awaiting_payment':
			return 'Belum upload bukti';
		case 'approved':
			return 'Disetujui';
		default:
			return 'Semua status pembayaran';
	}
};

const accountStatusLabel = (status) => {
	switch (status) {
		case 'pending':
			return 'Pending';
		case 'active':
			return 'Aktif';
		case 'suspend':
			return 'Ditangguhkan';
		default:
			return 'Semua status akun';
	}
};

const copyToken = async (teacher) => {
	try {
		await copyTextToClipboard(teacher.access_token);
		copiedTokenId.value = teacher.id;
	} catch (error) {
		console.error('Failed to copy teacher token.', error);
	}

	window.setTimeout(() => {
		copiedTokenId.value = null;
	}, 1200);
};

const copyTemplate = async (index, body) => {
	try {
		await copyTextToClipboard(body);
		copiedTemplateIndex.value = index;
	} catch (error) {
		console.error('Failed to copy notification template.', error);
	}

	window.setTimeout(() => {
		copiedTemplateIndex.value = null;
	}, 1200);
};
</script>

<template>
	<Head title="Manajemen Guru & Operator" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Manajemen Guru & Akses</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Aktivasi akun, bagikan token akses, dan tangani akun guru yang perlu ditinjau ulang.</p>
				</div>
				<button
					type="button"
					class="icon-button shrink-0"
					@click="showAdminFlowModal = true"
					title="Lihat alur kerja admin"
					aria-label="Lihat alur kerja admin"
				>
					<i class="fa-solid fa-circle-info"></i>
				</button>
			</div>

			<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu review</div>
					<div class="mt-2 text-2xl font-bold text-blue-700">{{ paymentSummary.submitted ?? 0 }}</div>
					<div class="mt-1 text-sm text-muted">Guru yang sudah upload bukti pembayaran.</div>
				</div>
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu kirim ulang</div>
					<div class="mt-2 text-2xl font-bold text-rose-700">{{ paymentSummary.rejected ?? 0 }}</div>
					<div class="mt-1 text-sm text-muted">Masih menunggu bukti yang diperbaiki.</div>
				</div>
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Belum bayar</div>
					<div class="mt-2 text-2xl font-bold text-amber-700">{{ paymentSummary.awaiting_payment ?? 0 }}</div>
					<div class="mt-1 text-sm text-muted">Sudah daftar tetapi belum upload bukti.</div>
				</div>
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Sudah disetujui</div>
					<div class="mt-2 text-2xl font-bold text-green-700">{{ paymentSummary.approved ?? 0 }}</div>
					<div class="mt-1 text-sm text-muted">Pembayaran selesai dan akun siap dipakai.</div>
				</div>
			</div>

			<div class="card">
				<form method="GET" :action="route('superadmin.teachers.index')" class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,2fr)_1fr_1fr_auto]">
					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Cari guru</label>
						<input type="text" name="q" :value="search" class="input w-full" placeholder="Nama, email, WhatsApp, atau sekolah">
					</div>
					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status pembayaran</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="payment_status" :value="paymentStatus">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ paymentStatusLabel(paymentStatus) }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="paymentStatus === '' ? 'ssd-selected' : ''" data-value="">Semua status pembayaran</div>
									<div class="ssd-option" :class="paymentStatus === 'submitted' ? 'ssd-selected' : ''" data-value="submitted">Menunggu review</div>
									<div class="ssd-option" :class="paymentStatus === 'rejected' ? 'ssd-selected' : ''" data-value="rejected">Ditolak</div>
									<div class="ssd-option" :class="paymentStatus === 'awaiting_payment' ? 'ssd-selected' : ''" data-value="awaiting_payment">Belum upload bukti</div>
									<div class="ssd-option" :class="paymentStatus === 'approved' ? 'ssd-selected' : ''" data-value="approved">Disetujui</div>
								</div>
							</div>
						</div>
					</div>
					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status akun</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="account_status" :value="accountStatus">
							<button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ accountStatusLabel(accountStatus) }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="accountStatus === '' ? 'ssd-selected' : ''" data-value="">Semua status akun</div>
									<div class="ssd-option" :class="accountStatus === 'pending' ? 'ssd-selected' : ''" data-value="pending">Pending</div>
									<div class="ssd-option" :class="accountStatus === 'active' ? 'ssd-selected' : ''" data-value="active">Aktif</div>
									<div class="ssd-option" :class="accountStatus === 'suspend' ? 'ssd-selected' : ''" data-value="suspend">Ditangguhkan</div>
								</div>
							</div>
						</div>
					</div>
					<div class="flex items-end gap-2">
						<button type="submit" class="btn-primary w-full lg:w-auto">Terapkan</button>
						<a :href="route('superadmin.teachers.index')" class="btn-secondary w-full text-center lg:w-auto">Reset</a>
					</div>
				</form>

				<div class="table-container">
					<table class="table-ujion min-w-[1080px]">
						<thead>
							<tr>
								<th>Nama Lengkap</th>
								<th>Email</th>
								<th>Status Akun</th>
								<th>Token</th>
								<th class="text-right">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="teachers.length > 0">
								<tr v-for="teacher in teachers" :key="teacher.id" class="hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
									<td>
										<div class="mb-2">
											<span v-if="teacher.account_status === 'active'" class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-green-700 dark:bg-green-500/15 dark:text-green-300">
												<i class="fa-solid fa-circle-check text-[9px]"></i>
												Aktif
											</span>
											<span v-else-if="teacher.account_status === 'pending'" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
												<i class="fa-solid fa-clock text-[9px]"></i>
												Menunggu Aktivasi
											</span>
											<span v-else class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
												<i class="fa-solid fa-ban text-[9px]"></i>
												Ditangguhkan
											</span>
										</div>
										<div class="font-bold">{{ teacher.name }}</div>
										<div class="text-xs text-muted">Terdaftar: {{ teacher.created_at_formatted }}</div>
										<div class="mt-2 text-xs text-muted">{{ teacher.satuan_pendidikan || '-' }}</div>
									</td>
									<td class="text-textSecondary dark:text-slate-300">
										<div>{{ teacher.email }}</div>
										<div class="mt-1 text-xs text-muted">{{ teacher.no_wa || '-' }}</div>
									</td>
									<td>
										<div class="mt-2">
											<span v-if="teacher.payment_status === 'approved'" class="badge-success">Pembayaran Disetujui</span>
											<span v-else-if="teacher.payment_status === 'submitted'" class="badge-info">Menunggu Review Pembayaran</span>
											<span v-else-if="teacher.payment_status === 'rejected'" class="badge-danger">Pembayaran Ditolak</span>
											<span v-else class="badge-warning">Belum Bayar</span>
										</div>
										<div v-if="teacher.payment_rejection_reason" class="mt-2 text-xs text-rose-600">Catatan: {{ teacher.payment_rejection_reason }}</div>
									</td>
									<td>
										<button
											v-if="teacher.access_token"
											type="button"
											class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 font-mono text-sm font-semibold text-blue-700 transition-all duration-200 hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300"
											@click="copyToken(teacher)"
											title="Klik untuk menyalin token"
										>
											<i :class="copiedTokenId === teacher.id ? 'fa-solid fa-check text-xs' : 'fa-regular fa-copy text-xs'"></i>
											<span>{{ copiedTokenId === teacher.id ? 'Token tersalin' : teacher.access_token }}</span>
										</button>
										<div v-if="teacher.access_token" class="mt-2 text-xs text-muted">Klik token untuk menyalin.</div>
										<span v-if="!teacher.access_token" class="text-muted italic">Belum aktif</span>
									</td>
									<td class="text-right">
										<div class="relative inline-block text-left" data-action-menu>
											<button
												type="button"
												class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-all duration-200 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
												data-action-menu-toggle
												aria-expanded="false"
												title="Buka aksi manajemen"
											>
												<i class="fa-solid fa-ellipsis"></i>
											</button>

											<div
												class="invisible absolute right-0 top-full z-20 mt-2 min-w-56 translate-y-2 rounded-2xl border border-slate-200/80 bg-white p-2 opacity-0 shadow-modal transition-all duration-200 dark:border-slate-800 dark:bg-slate-950"
												data-action-menu-panel
											>
												<form v-if="teacher.account_status !== 'active'" method="POST" :action="route('superadmin.teachers.activate', teacher.id)">
													<input type="hidden" name="_token" :value="page.props.csrf_token" />
													<button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-primary/8 hover:text-primary dark:text-slate-200 dark:hover:bg-primary/10">
														<i class="fa-solid fa-user-check w-4"></i>
														Aktifkan akun
													</button>
												</form>

												<form method="POST" :action="route('superadmin.teachers.refresh-token', teacher.id)">
													<input type="hidden" name="_token" :value="page.props.csrf_token" />
													<button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
														<i class="fa-solid fa-rotate w-4"></i>
														Refresh token
													</button>
												</form>

												<form v-if="teacher.account_status !== 'suspend'" method="POST" :action="route('superadmin.teachers.suspend', teacher.id)">
													<input type="hidden" name="_token" :value="page.props.csrf_token" />
													<button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700 dark:text-slate-200 dark:hover:bg-rose-500/10 dark:hover:text-rose-300" data-confirm="Tangguhkan akses guru ini?">
														<i class="fa-solid fa-user-slash w-4"></i>
														Tangguhkan akses
													</button>
												</form>
											</div>
										</div>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="5" class="text-center py-12">
									<i class="fa-solid fa-users-slash text-4xl text-slate-200 mb-3 block"></i>
									<span class="text-muted dark:text-slate-400 italic text-lg">Belum ada user dengan role guru untuk saat ini.</span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="flex items-start justify-between gap-4">
					<div>
						<h2 class="text-lg font-bold">Template Pesan Siap Pakai</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Gunakan template ini saat proses verifikasi dan aktivasi masih dilakukan manual.</p>
					</div>
				</div>

				<div class="mt-6 grid gap-4 lg:grid-cols-2">
					<div v-for="(template, index) in notificationTemplates" :key="template.title" class="rounded-card border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
						<div class="flex items-center justify-between gap-3">
							<div class="font-semibold">{{ template.title }}</div>
							<div class="flex items-center gap-2">
								<span class="badge-info">{{ template.audience }}</span>
								<button
									type="button"
									class="btn-secondary text-xs"
									@click="copyTemplate(index, template.body)"
								>
									{{ copiedTemplateIndex === index ? 'Tersalin' : 'Copy' }}
								</button>
							</div>
						</div>
						<pre class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ template.body }}</pre>
					</div>
				</div>
			</div>
		</div>

		<div id="admin-flow-modal" class="fixed inset-0 z-50 items-center justify-center bg-slate-950/70 px-4" :class="showAdminFlowModal ? 'flex' : 'hidden'">
			<div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-sm font-semibold text-muted">Panduan singkat</div>
						<div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Alur kerja yang disarankan untuk admin</div>
					</div>
					<button type="button" class="btn-secondary" @click="showAdminFlowModal = false">Tutup</button>
				</div>

				<div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50/80 p-4 text-sm text-blue-900 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-100">
					<div class="flex items-start gap-3">
						<i class="fa-solid fa-circle-info mt-0.5"></i>
						<div>
							<p class="font-semibold">Urutan kerja yang paling aman</p>
							<p class="mt-1">Pembayaran guru via Doku terverifikasi otomatis. Untuk kasus khusus, aktifkan akun manual lewat menu aksi, lalu kirim token akses melalui kanal yang aman seperti WhatsApp resmi admin.</p>
						</div>
					</div>
				</div>

				<div class="mt-4 space-y-3 text-sm text-textSecondary dark:text-slate-300">
					<div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/40">
						<div class="font-semibold text-slate-900 dark:text-slate-100">1. Cek status pembayaran</div>
						<div class="mt-1">Pembayaran yang sukses otomatis mengaktifkan akun guru. Cek menu Riwayat Transaksi untuk detailnya.</div>
					</div>
					<div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/40">
						<div class="font-semibold text-slate-900 dark:text-slate-100">2. Aktivasi manual bila perlu</div>
						<div class="mt-1">Untuk kasus khusus (misal pembayaran di luar sistem), gunakan menu aksi "Aktifkan akun".</div>
					</div>
					<div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/40">
						<div class="font-semibold text-slate-900 dark:text-slate-100">3. Bagikan token lewat kanal aman</div>
						<div class="mt-1">Setelah aktif, kirim token terbaru dan minta guru memakai token yang paling baru saat login.</div>
					</div>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
