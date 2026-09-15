<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import { closeAllActionMenus } from '@/core/action-menus';

const props = defineProps({
	tarifJenjangs: {
		type: Array,
		required: true,
	},
	hasJenjangColumn: {
		type: Boolean,
		required: true,
	},
	adminWhatsapp: {
		type: String,
		default: null,
	},
	dokuSettings: {
		type: Object,
		required: true,
	},
	jenjangs: {
		type: Array,
		required: true,
	},
});

const settingsForm = useForm({
	admin_whatsapp: props.adminWhatsapp ?? '',
	doku_enabled: props.dokuSettings.enabled,
	doku_client_id: props.dokuSettings.client_id,
	doku_secret_key: props.dokuSettings.secret_key,
});

const submitSettings = () => {
	settingsForm.post(route('superadmin.finance.settings'));
};

const catatanModalOpen = ref(false);

const tarifModalOpen = ref(false);
const tarifAction = ref('');
const tarifEditing = ref(false);
const tarifNameInput = ref(null);

const tarifForm = useForm({
	name: '',
	jenjang: '',
	description: '',
	price: '',
	subtitle: '',
});

const tarifJenjangLabel = () =>
	tarifForm.jenjang || (props.hasJenjangColumn ? 'Pilih jenjang' : 'Jalankan migrate untuk aktifkan jenjang');

const openTarifModal = async () => {
	closeAllActionMenus();
	tarifModalOpen.value = true;
	await nextTick();
	tarifNameInput.value?.focus();
};

const resetTarifForm = () => {
	tarifAction.value = route('superadmin.tarif-jenjang.store');
	tarifEditing.value = false;
	tarifForm.reset();
};

const openTarifCreate = () => {
	resetTarifForm();
	openTarifModal();
};

const openTarifEdit = (tarifJenjang) => {
	resetTarifForm();
	tarifEditing.value = true;
	tarifAction.value = route('superadmin.tarif-jenjang.update', tarifJenjang);
	tarifForm.name = tarifJenjang.name ?? '';
	tarifForm.jenjang = tarifJenjang.jenjang ?? '';
	tarifForm.price = tarifJenjang.price ?? '';
	tarifForm.subtitle = tarifJenjang.subtitle ?? '';
	tarifForm.description = tarifJenjang.description ?? '';
	openTarifModal();
};

const submitTarif = () => {
	tarifForm.post(tarifAction.value);
};

const onKeydown = (event) => {
	if (event.key === 'Escape') {
		if (tarifModalOpen.value) {
			tarifModalOpen.value = false;
		}
		if (catatanModalOpen.value) {
			catatanModalOpen.value = false;
		}
	}
};

onMounted(() => {
	tarifAction.value = route('superadmin.tarif-jenjang.store');
	document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
	document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
	<Head title="Keuangan" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex items-start justify-between gap-4">
				<div>
					<h1 class="flex items-center gap-2 text-2xl font-bold">
						Keuangan
						<button
							type="button"
							class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-xs text-slate-500 transition hover:border-primary hover:text-primary dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
							title="Catatan Operasional"
							aria-label="Catatan Operasional"
							@click="catatanModalOpen = true"
						>
							<i class="fa-solid fa-info"></i>
						</button>
					</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Kelola nomor WhatsApp admin, payment gateway Doku, dan tarif aktivasi per jenjang.</p>
				</div>
			</div>

			<div class="card">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
					<div>
						<h2 class="text-lg font-bold">Pengaturan Pembayaran</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Nomor WhatsApp admin untuk redirect konfirmasi dan payment gateway Doku untuk pembayaran otomatis (QRIS, Virtual Account, e-wallet, kartu, dll).</p>
					</div>
					<div class="flex shrink-0 gap-2">
						<span v-if="(adminWhatsapp ?? '').trim() !== ''" class="badge-success">WA Tersimpan</span>
						<span v-else class="badge-warning">WA Belum diisi</span>
						<span v-if="dokuSettings.enabled" class="badge-success">Doku Aktif</span>
						<span v-else class="badge-warning">Doku Nonaktif</span>
					</div>
				</div>

				<form class="mt-5 space-y-5" @submit.prevent="submitSettings">
					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Nomor WhatsApp Admin</label>
						<input v-model="settingsForm.admin_whatsapp" class="input w-full sm:max-w-md" name="admin_whatsapp" placeholder="62812xxxxxxx / 08xxxxxxx">
						<p class="mt-1 text-xs text-textSecondary dark:text-slate-300">Nomor ini dipakai untuk tombol hubungi admin di halaman pembayaran dan notifikasi WA.</p>
					</div>

					<div class="border-t border-border pt-5 dark:border-slate-800">
						<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-950/40">
							<input
								v-model="settingsForm.doku_enabled"
								type="checkbox"
								name="doku_enabled"
								value="1"
								class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
							>
							<span>
								<span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">Aktifkan pembayaran</span>
								<span class="mt-0.5 block text-xs text-textSecondary dark:text-slate-400">Transaksi sukses tercatat otomatis dan akun guru langsung aktif. Jika nonaktif, guru hanya bisa menghubungi admin via WhatsApp untuk pembayaran.</span>
							</span>
						</label>

						<div class="mt-3 grid gap-3 sm:grid-cols-2">
							<div>
								<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Client-Id</label>
								<input v-model="settingsForm.doku_client_id" class="input w-full font-mono text-xs" name="doku_client_id" placeholder="BRN-xxxx" autocomplete="off">
							</div>
							<div>
								<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Secret-Key</label>
								<input v-model="settingsForm.doku_secret_key" class="input w-full font-mono text-xs" name="doku_secret_key" placeholder="SK-xxxx" autocomplete="off">
								<p class="mt-1 text-xs text-textSecondary dark:text-slate-300">Ambil dari Dashboard Doku → Settings → API Keys. Webhook notifikasi (set juga di dashboard Doku): <code>{{ route('api.payments.doku.notification') }}</code></p>
							</div>
						</div>
					</div>

					<div class="flex justify-end border-t border-border pt-4 dark:border-slate-800">
						<button type="submit" class="btn-primary whitespace-nowrap" :disabled="settingsForm.processing">
							<i class="fa-solid fa-floppy-disk mr-2"></i>
							Simpan Pengaturan
						</button>
					</div>
				</form>
			</div>

			<div class="card">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
					<div>
						<h2 class="text-lg font-bold">Tarif Aktivasi per Jenjang</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tambah, edit, dan aktif/nonaktifkan tarif untuk tiap jenjang.</p>
					</div>
					<button type="button" class="btn-primary whitespace-nowrap" @click="openTarifCreate">
						<i class="fa-solid fa-plus mr-2"></i>
						Tambah Tarif
					</button>
				</div>

				<div class="mt-4 table-container">
					<table class="table-ujion min-w-[980px]">
						<thead>
							<tr>
								<th>Jenjang</th>
								<th>Judul</th>
								<th>Nominal</th>
								<th>Status</th>
								<th class="text-right">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="tarifJenjangs.length > 0">
								<tr v-for="tarifJenjang in tarifJenjangs" :key="tarifJenjang.id">
									<td>
										<span class="badge-info">{{ tarifJenjang.jenjang || '-' }}</span>
									</td>
									<td>
										<div class="font-semibold">{{ tarifJenjang.name }}</div>
										<div v-if="tarifJenjang.subtitle" class="mt-1 text-xs text-muted">{{ tarifJenjang.subtitle }}</div>
									</td>
									<td class="font-semibold">Rp {{ Number(tarifJenjang.price).toLocaleString('id-ID') }}</td>
									<td>
										<span v-if="tarifJenjang.is_active" class="badge-success">Aktif</span>
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
													@click="openTarifEdit(tarifJenjang)"
												>
													<i class="fa-solid fa-pen w-4"></i>
													Edit
												</button>

												<form method="POST" :action="route('superadmin.tarif-jenjang.toggle-active', tarifJenjang.id)">
													<input type="hidden" name="_token" :value="$page.props.csrf_token">
													<button
														type="submit"
														class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-amber-50 hover:text-amber-700 dark:text-slate-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300"
													>
														<i class="fa-solid fa-eye w-4"></i>
														Aktif / Nonaktif
													</button>
												</form>

												<form method="POST" :action="route('superadmin.tarif-jenjang.destroy', tarifJenjang.id)">
													<input type="hidden" name="_token" :value="$page.props.csrf_token">
													<button
														type="submit"
														class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-700 dark:text-slate-200 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
														data-confirm="Hapus tarif ini?"
														data-confirm-title="Hapus Tarif"
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
								<td colspan="5" class="py-12 text-center text-muted">Belum ada tarif yang terinput.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div
			id="catatan-operasional-modal"
			class="fixed inset-0 z-50 items-center justify-center bg-slate-950/70 px-4"
			:class="catatanModalOpen ? 'flex' : 'hidden'"
			@click.self="catatanModalOpen = false"
		>
			<div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-sm font-semibold uppercase tracking-wide text-muted">Catatan Operasional</div>
						<div class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Keuangan</div>
					</div>
					<button type="button" class="btn-secondary" @click="catatanModalOpen = false">Tutup</button>
				</div>

				<div class="mt-5 space-y-5">
					<div>
						<div class="text-sm font-bold text-slate-900 dark:text-slate-100">Tarif &amp; Pembayaran</div>
						<ul class="mt-2 space-y-2 text-sm text-textSecondary dark:text-slate-300">
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Tarif ditentukan per jenjang (SD/SMP/SMA) sesuai pilihan saat daftar.</span></li>
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Nominal akan dipakai otomatis pada halaman pembayaran.</span></li>
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Transaksi sukses tercatat otomatis di menu Riwayat Transaksi.</span></li>
						</ul>
					</div>

					<div>
						<div class="text-sm font-bold text-slate-900 dark:text-slate-100">Alur Doku</div>
						<ul class="mt-2 space-y-2 text-sm text-textSecondary dark:text-slate-300">
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Aktifkan checkbox Doku lalu isi Client-Id &amp; Secret-Key dari Dashboard Doku.</span></li>
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Semua kanal pembayaran yang aktif di akun Doku otomatis tampil di halaman checkout.</span></li>
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Transaksi sukses tercatat otomatis dan akun guru langsung aktif tanpa review admin.</span></li>
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Jika Doku nonaktif, halaman pembayaran menampilkan tombol hubungi admin via WhatsApp.</span></li>
							<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Webhook notifikasi (set di dashboard Doku): <code class="break-all">{{ route('api.payments.doku.notification') }}</code></span></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div
			id="tarif-modal"
			class="fixed inset-0 z-50 items-center justify-center bg-slate-950/70 px-4"
			:class="tarifModalOpen ? 'flex' : 'hidden'"
			@click.self="tarifModalOpen = false"
		>
			<div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-base font-bold text-slate-900 dark:text-slate-100">{{ tarifEditing ? 'Edit Tarif' : 'Tambah Tarif' }}</div>
						<div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Isi tarif aktivasi per jenjang yang dipakai sebagai nominal pembayaran.</div>
					</div>
					<button type="button" class="btn-secondary" @click="tarifModalOpen = false">Tutup</button>
				</div>

				<form id="tarif-form" class="mt-5 space-y-4" @submit.prevent="submitTarif">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
							<input id="tarif-name" ref="tarifNameInput" v-model="tarifForm.name" class="input mt-1" name="name" placeholder="Contoh: Aktivasi Guru SD" required>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang</label>
							<div class="ssd-wrap mt-1">
								<input
									type="hidden"
									name="jenjang"
									id="tarif-jenjang"
									:value="tarifForm.jenjang"
									:required="hasJenjangColumn"
									:disabled="!hasJenjangColumn"
									@change="tarifForm.jenjang = $event.target.value"
								>
								<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
									<span class="ssd-label">{{ tarifJenjangLabel() }}</span>
									<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
								</button>
								<div class="ssd-panel">
									<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
									<div class="ssd-list">
										<div class="ssd-option" :class="!tarifForm.jenjang ? 'ssd-selected' : ''" data-value="">Pilih jenjang</div>
										<div
											v-for="jenjang in jenjangs"
											:key="jenjang"
											class="ssd-option"
											:class="tarifForm.jenjang === jenjang ? 'ssd-selected' : ''"
											:data-value="jenjang"
										>
											{{ jenjang }}
										</div>
									</div>
								</div>
							</div>
							<p v-if="!hasJenjangColumn" class="mt-1 text-xs text-muted">Kolom `jenjang` belum ada di DB. Jalankan `php artisan migrate` untuk mengaktifkan tarif per jenjang.</p>
						</div>
						<div class="md:col-span-2">
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Keterangan</label>
							<textarea id="tarif-description" v-model="tarifForm.description" class="input mt-1 min-h-24" name="description" placeholder="Contoh: Aktivasi akun guru/operator untuk jenjang ini."></textarea>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nominal</label>
							<input id="tarif-price" v-model="tarifForm.price" class="input mt-1" name="price" placeholder="99000" required>
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subtitle (opsional)</label>
							<input id="tarif-subtitle" v-model="tarifForm.subtitle" class="input mt-1" name="subtitle" placeholder="Contoh: Akses akun guru / operator">
						</div>
					</div>

					<div class="flex items-center justify-end gap-3">
						<button type="button" class="btn-secondary" @click="resetTarifForm">Reset</button>
						<button id="tarif-submit" class="btn-primary" type="submit" :disabled="tarifForm.processing">
							<i class="fa-solid fa-floppy-disk mr-2"></i> {{ tarifEditing ? 'Simpan Perubahan' : 'Simpan' }}
						</button>
					</div>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
