<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

defineProps({
	exams: {
		type: Array,
		default: () => [],
	},
	paketSoals: {
		type: Array,
		default: () => [],
	},
});

const page = usePage();

const importOpen = ref(false);
const copiedToken = ref(null);

const copyToken = async (token) => {
	try {
		await copyTextToClipboard(token);
		copiedToken.value = token;
	} catch (error) {
		console.error('Failed to copy exam mapel token.', error);
	}

	window.setTimeout(() => {
		copiedToken.value = null;
	}, 2000);
};
</script>

<template>
	<Head title="Ujian" />

	<SuperadminLayout>
		<div class="space-y-6">
			<h1 class="text-2xl font-bold">Manajemen Ujian</h1>
			<div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
				<div class="card p-4">
					<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
						<form method="POST" :action="route('superadmin.exams.store')" class="flex-1">
							<input type="hidden" name="_token" :value="page.props.csrf_token" />
							<div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
								<div>
									<label class="text-xs font-bold">Paket Soal</label>
									<div class="ssd-wrap mt-1">
										<input type="hidden" name="paket_soal_id" value="" required>
										<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
											<span class="ssd-label">Pilih paket</span>
											<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
										</button>
										<div class="ssd-panel">
											<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari paket..."></div>
											<div class="ssd-list">
												<div class="ssd-option ssd-selected" data-value="">Pilih paket</div>
												<div v-for="paket in paketSoals" :key="paket.id" class="ssd-option" :data-value="paket.id">{{ paket.nama }} &middot; {{ paket.jenjang_kode }} &middot; {{ paket.tahun_ajaran }}</div>
											</div>
										</div>
									</div>
								</div>
								<div>
									<label class="text-xs font-bold">Judul</label>
									<input name="judul" class="input w-full" required>
								</div>
								<div>
									<label class="text-xs font-bold">Tanggal Terbit</label>
									<input type="datetime-local" name="tanggal_terbit" class="input w-full" required>
								</div>
								<div>
									<label class="text-xs font-bold">Max Peserta</label>
									<input type="number" name="max_peserta" class="input w-full" value="50" required>
								</div>
								<div>
									<label class="text-xs font-bold">Timer (menit, opsional)</label>
									<input type="number" name="timer" class="input w-full" placeholder="Auto dari paket">
								</div>
								<div>
									<label class="text-xs font-bold">Status</label>
									<div class="ssd-wrap mt-1">
										<input type="hidden" name="status" value="draft" required>
										<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
											<span class="ssd-label">Draft</span>
											<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
										</button>
										<div class="ssd-panel">
											<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari status..."></div>
											<div class="ssd-list">
												<div class="ssd-option ssd-selected" data-value="draft">Draft</div>
												<div class="ssd-option" data-value="terbit">Terbit</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Buat Ujian</button>
						</form>
						<button class="btn-primary mt-3 md:mt-0 w-full md:w-auto" type="button"
							@click="importOpen = true">
							<i class="fa-solid fa-upload mr-2"></i>Import
						</button>
					</div>
				</div>

				<div id="modal-import" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" :class="importOpen ? '' : 'hidden'">
					<div class="relative w-full max-w-md">
						<div class="bg-white rounded-lg shadow-lg p-6">
							<div class="flex items-center justify-between mb-4">
								<div class="font-bold text-lg">Import Ujian</div>
								<button class="text-gray-500 hover:text-gray-700" type="button" @click="importOpen = false">
									<i class="fa-solid fa-times"></i>
								</button>
							</div>
							<p class="mb-4 text-sm text-gray-600">Download template Excel, isi data ujian, lalu upload kembali.</p>
							<form method="POST" :action="route('superadmin.exams.import')" enctype="multipart/form-data"
								class="flex flex-col gap-3">
								<input type="hidden" name="_token" :value="page.props.csrf_token" />
								<input class="input" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
								<button class="btn-primary" type="submit">
									<i class="fa-solid fa-upload mr-2"></i>Import File
								</button>
							</form>
							<a :href="route('superadmin.exams.template')" class="btn-secondary mt-2">
								<i class="fa-solid fa-file-excel mr-2"></i>Download Template Excel
							</a>
						</div>
					</div>
				</div>
			</div>

			<div class="card p-4">
				<div class="table-container">
					<table class="table-ujion w-full min-w-[760px]">
						<thead>
							<tr>
								<th>Judul</th>
								<th>Paket</th>
								<th>Tanggal Terbit</th>
								<th>Max</th>
								<th>Token per Mapel</th>
								<th>Status</th>
								<th>Aktif</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="exam in exams" :key="exam.id">
								<td>{{ exam.judul }}</td>
								<td>{{ exam.paket_nama }}</td>
								<td>{{ exam.tanggal_terbit_formatted }}</td>
								<td>{{ exam.max_peserta }}</td>
								<td>
									<span v-if="exam.mapel_tokens.length === 0" class="badge-warning text-xs">Belum ada token</span>
									<div v-else class="space-y-1.5">
										<div v-for="mt in exam.mapel_tokens" :key="mt.id" class="flex items-center gap-2">
											<span class="text-[10px] font-bold text-textSecondary w-20 truncate">
												{{ mt.nama_label }}
											</span>
											<span :id="`token-sa-${mt.id}`"
												class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-sm font-bold tracking-widest text-primary">
												{{ mt.token }}
											</span>
											<button type="button" :id="`copy-sa-${mt.id}`"
												@click="copyToken(mt.token)"
												class="btn-secondary px-2 py-1 text-[11px]"
												:class="copiedToken === mt.token ? 'text-emerald-600' : ''">
												<i :class="copiedToken === mt.token ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
											</button>
										</div>
									</div>
								</td>
								<td>{{ exam.status }}</td>
								<td>
									<span v-if="exam.is_active" class="badge-success">Aktif</span>
									<span v-else class="badge-danger">Nonaktif</span>
								</td>
								<td>
									<div class="flex flex-wrap gap-2">
										<form method="POST" :action="route('superadmin.exams.toggle', exam.id)"><input type="hidden" name="_token" :value="page.props.csrf_token" /><button
												class="btn-secondary text-xs px-2 py-1">Toggle</button></form>
										<form v-if="exam.ujian_sesis_count > 0" method="POST" :action="route('superadmin.exams.destroy', exam.id)"
											  :onsubmit="`return confirm('Ujian ini memiliki ${exam.ujian_sesis_count} hasil peserta yang akan DIHAPUS PERMANEN beserta ujiannya. Lanjutkan?')`">
											<input type="hidden" name="_token" :value="page.props.csrf_token" />
											<input type="text" name="confirm_text" placeholder="Ketik HAPUS" required
												   class="w-24 rounded-lg border border-rose-200 px-2 py-1 text-xs uppercase" autocomplete="off">
											<button class="btn-danger text-xs px-2 py-1">Hapus ({{ exam.ujian_sesis_count }} hasil)</button>
										</form>
										<form v-else method="POST" :action="route('superadmin.exams.destroy', exam.id)"><input type="hidden" name="_token" :value="page.props.csrf_token" /><button
												class="btn-danger text-xs px-2 py-1">Hapus</button></form>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
