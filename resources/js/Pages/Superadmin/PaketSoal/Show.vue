<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
});

const rootEl = ref(null);

const copiedToken = ref(null);

const copyToken = async (token) => {
	try {
		await copyTextToClipboard(token);
		copiedToken.value = token;
	} catch (error) {
		console.error('Failed to copy paket detail token.', error);
	}

	window.setTimeout(() => {
		copiedToken.value = null;
	}, 2000);
};

onMounted(() => {
	const root = rootEl.value;
	if (!root) return;

	root.querySelectorAll('[id^="delete-trigger-wrap-"]').forEach((wrap) => {
		const mapelId = wrap.id.replace('delete-trigger-wrap-', '');
		const deleteForm = document.getElementById(`delete-form-${mapelId}`);
		if (!deleteForm || wrap.childElementCount > 0) return;

		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'btn-danger px-4 py-2 text-xs flex items-center gap-1.5';
		button.innerHTML = '<i class="fa-solid fa-trash-can"></i> Hapus Semua Soal';
		button.setAttribute('data-confirm', deleteForm.dataset.confirm || 'Yakin hapus semua soal?');
		button.setAttribute('data-confirm-title', deleteForm.dataset.confirmTitle || 'Hapus Semua Soal');

		button.addEventListener('click', () => {
			const modal = document.querySelector('[data-confirm-modal]');
			const titleElement = modal?.querySelector('[data-confirm-modal-title]');
			const messageElement = modal?.querySelector('[data-confirm-modal-message]');
			const confirmButton = modal?.querySelector('[data-confirm-modal-confirm]');

			if (!modal || !confirmButton) {
				deleteForm.submit();
				return;
			}

			if (titleElement) {
				titleElement.textContent = deleteForm.dataset.confirmTitle || 'Hapus Semua Soal';
			}

			if (messageElement) {
				messageElement.textContent = deleteForm.dataset.confirm || 'Yakin hapus semua soal?';
			}

			const confirmHandler = () => {
				deleteForm.submit();
				confirmButton.removeEventListener('click', confirmHandler);
			};

			confirmButton.addEventListener('click', confirmHandler);
			modal.classList.remove('hidden');
			modal.setAttribute('aria-hidden', 'false');
			confirmButton.focus();
		});

		wrap.appendChild(button);
	});
});
</script>

<template>
	<Head title="Detail Paket Soal" />

	<SuperadminLayout>
		<div ref="rootEl" class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.jenjang_kode }} &middot; {{ paket.tahun_ajaran }}</span>
				<h1 class="page-title">{{ paket.nama }}</h1>
				<p class="page-description">Kelola komponen akademik dan survey untuk menyusun soal, bacaan, serta struktur paket ujian siswa.</p>
				<div class="page-actions">
					<Link :href="route('superadmin.paket-soal.edit', paket.id)" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Edit Metadata</Link>
				</div>
			</section>

			<section class="grid gap-4 xl:grid-cols-2">
				<article v-for="mapel in paket.mapels" :key="mapel.id" class="card">
					<div class="section-heading mb-5">
						<div>
							<h2 class="section-title">{{ mapel.nama_label }}</h2>
							<p class="section-description">
								{{ mapel.soal_count }}/{{ mapel.jumlah_soal }} butir &middot; {{ mapel.durasi_menit }} menit
								&middot; {{ mapel.is_survey ? 'Survey Profiling' : 'Akademik' }}
							</p>
						</div>
						<div class="flex items-center gap-2">
							<Link :href="route('superadmin.soal.create', [paket.id, mapel.id])"
								  class="btn-primary px-4 py-2 text-xs">
								<i class="fa-solid fa-pen-to-square mr-1.5"></i>Buat Manual
							</Link>
							<Link v-if="!mapel.is_survey"
								  :href="route('superadmin.soal.bank-builder', [paket.id, mapel.id]) + '?' + new URLSearchParams(mapel.bank_builder_params).toString()"
								  class="btn-secondary px-4 py-2 text-xs">
								<i class="fa-solid fa-layer-group mr-1.5"></i>Dari Bank Soal
							</Link>
						</div>
					</div>
					<div class="rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/60 space-y-3">

						<form :id="`config-form-${mapel.id}`"
							  method="POST"
							  :action="route('superadmin.mapel.update', [paket.id, mapel.id])">
							<input type="hidden" name="_token" :value="$page.props.csrf_token" />
							<input type="hidden" name="_method" value="PUT" />
							<div class="grid gap-3 md:grid-cols-3">
								<div class="input-group">
									<label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Jumlah Soal</label>
									<input type="number" name="jumlah_soal" class="input" :value="mapel.jumlah_soal" min="1" max="200" required>
								</div>
								<div class="input-group">
									<label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Durasi (menit)</label>
									<input type="number" name="durasi_menit" class="input" :value="mapel.durasi_menit" min="1" max="600" required>
								</div>
								<div class="input-group">
									<label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Urutan</label>
									<input type="number" name="urutan" class="input" :value="mapel.urutan ?? 1" min="1" max="10" required>
								</div>
							</div>

							<div class="mt-3 flex items-center gap-3">
								<button class="btn-secondary px-4 py-2 text-xs" type="submit">Simpan Konfigurasi</button>

								<span v-if="mapel.soal_count > 0" :id="`delete-trigger-wrap-${mapel.id}`"></span>
							</div>
						</form>

						<form v-if="mapel.soal_count > 0"
							  :id="`delete-form-${mapel.id}`"
							  method="POST"
							  :action="route('superadmin.mapel.soal.destroy-all', [paket.id, mapel.id])"
							  class="hidden"
							  :data-confirm="`Hapus semua ${mapel.soal_count} soal pada ${mapel.nama_label}? Tindakan ini tidak bisa dibatalkan.`"
							  data-confirm-title="Hapus Semua Soal">
							<input type="hidden" name="_token" :value="$page.props.csrf_token" />
							<input type="hidden" name="_method" value="DELETE" />
						</form>

					</div>
					<div class="space-y-3">
						<template v-if="mapel.soals_preview.length > 0">
							<div v-for="soal in mapel.soals_preview" :key="soal.nomor_soal" class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
								<div class="flex items-center justify-between gap-3">
									<div class="font-semibold">Soal {{ soal.nomor_soal }}</div>
									<span class="badge-info">{{ soal.tipe_label }}</span>
								</div>
								<p class="mt-2 text-sm text-textSecondary">{{ soal.pertanyaan_limited }}</p>
								<p v-if="mapel.is_survey && soal.dimensi" class="mt-2 text-xs font-medium text-slate-500">{{ soal.dimensi }}{{ soal.subdimensi ? ' &middot; ' + soal.subdimensi : '' }}</p>
							</div>
						</template>
						<div v-else class="empty-state">Belum ada soal pada mapel ini.</div>
					</div>
				</article>
			</section>

			<section class="card">
				<div class="section-heading mb-5">
					<div>
						<h2 class="section-title flex items-center gap-2">
							<i class="fa-solid fa-key text-primary"></i>
							Token Akses Ujian
						</h2>
						<p class="section-description">
							Daftar sesi ujian yang menggunakan paket ini. Bagikan token kepada guru agar bisa melakukan simulasi.
						</p>
					</div>
					<a :href="route('superadmin.exams.index')" class="btn-secondary px-4 py-2 text-xs">
						<i class="fa-solid fa-plus mr-1.5"></i>Buat Ujian Baru
					</a>
				</div>

				<div v-if="paket.exams.length === 0" class="empty-state">
					<i class="fa-solid fa-triangle-exclamation mb-2 text-2xl text-amber-400"></i>
					<p>Belum ada ujian yang dibuat dari paket ini.</p>
					<a :href="route('superadmin.exams.index')" class="btn-primary mt-3 px-4 py-2 text-xs">Buat Ujian Pertama</a>
				</div>
				<div v-else class="table-container">
					<table class="table-ujion min-w-[700px]">
						<thead>
							<tr>
								<th>Judul Ujian</th>
								<th>Tanggal Terbit</th>
								<th>Max Peserta</th>
								<th>Status</th>
								<th class="text-center">Token Akses</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="exam in paket.exams" :key="exam.id">
								<td>
									<div class="font-semibold">{{ exam.judul }}</div>
									<div class="text-xs text-textSecondary">ID #{{ exam.id }}</div>
								</td>
								<td>{{ exam.tanggal_terbit_formatted }}</td>
								<td>{{ exam.max_peserta }} peserta</td>
								<td>
									<span v-if="exam.status === 'terbit' && exam.is_active" class="badge-success">Aktif & Terbit</span>
									<span v-else-if="exam.status === 'terbit'" class="badge-warning">Terbit (Nonaktif)</span>
									<span v-else class="badge-info">Draft</span>
								</td>
								<td>
									<span v-if="exam.mapel_tokens.length === 0" class="badge-warning text-xs">Belum ada token</span>
									<div v-else class="space-y-1.5 flex flex-col items-center">
										<div v-for="mt in exam.mapel_tokens" :key="mt.id" class="flex items-center gap-2">
											<span class="text-[10px] font-bold text-textSecondary w-16 truncate text-right">
												{{ mt.nama_label }}
											</span>
											<span :id="`token-sa-detail-${mt.id}`"
												  class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-xs font-bold tracking-widest text-primary">
												{{ mt.token }}
											</span>
											<button
												type="button"
												:id="`copy-sa-detail-${mt.id}`"
												@click="copyToken(mt.token)"
												class="btn-secondary px-2 py-1 text-[10px]"
												:class="copiedToken === mt.token ? 'text-emerald-600' : ''">
												<i :class="copiedToken === mt.token ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
											</button>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</section>
		</div>
	</SuperadminLayout>
</template>
