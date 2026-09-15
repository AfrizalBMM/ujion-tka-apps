<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	canManage: {
		type: Boolean,
		required: true,
	},
	teksBacaans: {
		type: Array,
		required: true,
	},
});

const storeForm = useForm({
	judul: '',
	konten: '',
});

const storeSubmit = () => {
	storeForm.post(route('guru.teks-bacaan.store', [props.paket.id, props.mapel.id]));
};

const showModal = ref(false);
const editingId = ref(null);
const editForm = useForm({
	judul: '',
	konten: '',
});

const openEdit = (bacaan) => {
	editingId.value = bacaan.id;
	editForm.judul = bacaan.judul ?? '';
	editForm.konten = bacaan.konten ?? '';
	showModal.value = true;
};

const closeModal = () => {
	showModal.value = false;
};

const editSubmit = () => {
	editForm.put(route('guru.teks-bacaan.update', [props.paket.id, props.mapel.id, editingId.value]));
};

const deleteBacaan = (bacaan) => {
	router.delete(route('guru.teks-bacaan.destroy', [props.paket.id, props.mapel.id, bacaan.id]));
};
</script>

<template>
	<Head title="Teks Bacaan" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }}</span>
				<h1 class="page-title">Teks Bacaan &middot; {{ mapel.nama_label }}</h1>
				<p class="page-description">
					{{ canManage
						? 'Teks bacaan dapat dipakai oleh beberapa soal sekaligus.'
						: 'Teks bacaan pada paket milik superadmin hanya dapat dilihat dari akun guru.' }}
				</p>
				<div class="page-actions">
					<Link :href="route('guru.soal.index', [paket.id, mapel.id])" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Kembali ke Soal</Link>
				</div>
			</section>

			<section class="grid gap-6 lg:grid-cols-12">
				<div class="lg:col-span-4">
					<div class="card lg:sticky lg:top-6">
						<template v-if="canManage">
							<div class="font-bold text-lg mb-4">Tambah Teks Bacaan</div>
							<form class="space-y-4" @submit.prevent="storeSubmit">
								<div class="input-group">
									<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Judul (opsional)</label>
									<input name="judul" class="input" v-model="storeForm.judul">
								</div>
								<div class="input-group">
									<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Konten</label>
									<textarea name="konten" class="input min-h-48" required v-model="storeForm.konten"></textarea>
								</div>
								<button class="btn-primary w-full" type="submit" :disabled="storeForm.processing">Simpan</button>
							</form>
						</template>
						<div v-else class="rounded-[24px] border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">
							Paket ini dikelola oleh superadmin, jadi teks bacaan hanya bisa dilihat dari akun guru.
						</div>
					</div>
				</div>

				<div class="lg:col-span-8 space-y-4">
					<div class="card">
						<div class="font-bold text-lg">Daftar Teks Bacaan ({{ teksBacaans.length }})</div>
						<div class="mt-5 space-y-4">
							<template v-if="teksBacaans.length > 0">
								<div v-for="bacaan in teksBacaans" :key="bacaan.id" class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
									<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
										<div class="min-w-0 flex-1">
											<div class="text-sm font-bold text-slate-900 dark:text-white">{{ bacaan.judul || `Teks Bacaan #${bacaan.id}` }}</div>
											<div class="mt-2 whitespace-pre-line text-sm text-textSecondary">{{ bacaan.konten_limited }}</div>
										</div>
										<div class="flex gap-2 md:flex-col">
											<template v-if="canManage">
												<button class="btn-secondary px-3 py-2 text-xs" type="button" @click="openEdit(bacaan)">Edit</button>
												<form @submit.prevent="deleteBacaan(bacaan)">
													<button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
												</form>
											</template>
										</div>
									</div>
								</div>
							</template>
							<div v-else class="empty-state">Belum ada teks bacaan.</div>
						</div>
					</div>
				</div>
			</section>
		</div>

		<div v-if="canManage" id="edit-modal" class="fixed inset-0 z-50 items-center justify-center bg-black/50 p-4" :class="showModal ? '' : 'hidden'" @click.self="closeModal">
			<div class="w-full max-w-2xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
						<div class="mt-2 text-xl font-bold">Teks Bacaan</div>
					</div>
					<button type="button" class="icon-button" data-close-modal @click="closeModal"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<form id="edit-form" class="mt-5 space-y-4" @submit.prevent="editSubmit">
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Judul</label>
						<input name="judul" class="input" id="edit-judul" v-model="editForm.judul">
					</div>
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Konten</label>
						<textarea name="konten" class="input min-h-56" id="edit-konten" required v-model="editForm.konten"></textarea>
					</div>
					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit" :disabled="editForm.processing">Simpan Perubahan</button>
						<button class="btn-secondary" type="button" data-close-modal @click="closeModal">Batal</button>
					</div>
				</form>
			</div>
		</div>
	</GuruLayout>
</template>
