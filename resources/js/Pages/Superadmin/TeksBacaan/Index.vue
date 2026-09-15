<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	teksBacaans: {
		type: Array,
		default: () => [],
	},
});

const page = usePage();

const createForm = useForm({
	judul: '',
	konten: '',
});

const submitCreate = () => {
	createForm.post(route('superadmin.teks-bacaan.store', [props.paket.id, props.mapel.id]), {
		onSuccess: () => createForm.reset(),
	});
};

const editOpen = ref(false);
const editJudul = ref('');
const editKonten = ref('');
const editBacaanId = ref(null);

const editAction = computed(() =>
	editBacaanId.value
		? route('superadmin.teks-bacaan.update', [props.paket.id, props.mapel.id, editBacaanId.value])
		: '#'
);

const openEdit = (bacaan) => {
	editBacaanId.value = bacaan.id;
	editJudul.value = bacaan.judul ?? '';
	editKonten.value = bacaan.konten ?? '';
	editOpen.value = true;
};
</script>

<template>
	<Head title="Teks Bacaan" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }}</span>
				<h1 class="page-title">Teks Bacaan &middot; {{ mapel.nama_label }}</h1>
				<p class="page-description">Teks bacaan dapat dipakai oleh beberapa soal sekaligus melalui dropdown pada form soal.</p>
				<div class="page-actions">
					<Link :href="route('superadmin.soal.index', [paket.id, mapel.id])" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Kembali ke Soal</Link>
				</div>
			</section>

			<section class="grid gap-6 lg:grid-cols-12">
				<div class="lg:col-span-4">
					<div class="card lg:sticky lg:top-6">
						<div class="font-bold text-lg mb-4">Tambah Teks Bacaan</div>
						<form class="space-y-4" @submit.prevent="submitCreate">
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Judul (opsional)</label>
								<input name="judul" class="input" v-model="createForm.judul" placeholder="Teks 1: Kisah Singkat">
							</div>
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Konten</label>
								<textarea name="konten" class="input min-h-48" v-model="createForm.konten" required></textarea>
							</div>
							<button class="btn-primary w-full" type="submit" :disabled="createForm.processing">Simpan</button>
						</form>
					</div>
				</div>

				<div class="lg:col-span-8 space-y-4">
					<div class="card">
						<div class="font-bold text-lg">Daftar Teks Bacaan ({{ teksBacaans.length }})</div>
						<div class="mt-5 space-y-4">
							<template v-for="bacaan in teksBacaans" :key="bacaan.id">
								<div class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
									<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
										<div class="min-w-0 flex-1">
											<div class="text-sm font-bold text-slate-900 dark:text-white">{{ bacaan.judul || `Teks Bacaan #${bacaan.id}` }}</div>
											<div class="mt-2 whitespace-pre-line text-sm text-textSecondary">{{ bacaan.konten_limited }}</div>
										</div>
										<div class="flex gap-2 md:flex-col">
											<button class="btn-secondary px-3 py-2 text-xs" type="button" @click="openEdit(bacaan)">Edit</button>
											<form method="POST" :action="route('superadmin.teks-bacaan.destroy', [paket.id, mapel.id, bacaan.id])">
												<input type="hidden" name="_token" :value="page.props.csrf_token" />
												<input type="hidden" name="_method" value="DELETE" />
												<button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
											</form>
										</div>
									</div>
								</div>
							</template>
							<div v-if="teksBacaans.length === 0" class="empty-state">Belum ada teks bacaan.</div>
						</div>
					</div>
				</div>
			</section>
		</div>

		<div id="edit-modal" class="fixed inset-0 z-50 items-center justify-center bg-black/50 p-4" :class="editOpen ? 'flex' : 'hidden'">
			<div class="w-full max-w-2xl rounded-[28px] border border-white/80 bg-white/95 p-6 shadow-modal">
				<div class="flex items-start justify-between gap-4">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Edit</div>
						<div class="mt-2 text-xl font-bold">Teks Bacaan</div>
					</div>
					<button type="button" class="icon-button" @click="editOpen = false"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<form id="edit-form" method="POST" class="mt-5 space-y-4" :action="editAction">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<input type="hidden" name="_method" value="PUT" />
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Judul</label>
						<input name="judul" class="input" id="edit-judul" v-model="editJudul">
					</div>
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Konten</label>
						<textarea name="konten" class="input min-h-56" id="edit-konten" v-model="editKonten" required></textarea>
					</div>
					<div class="flex flex-wrap gap-3">
						<button class="btn-primary" type="submit">Simpan Perubahan</button>
						<button class="btn-secondary" type="button" @click="editOpen = false">Batal</button>
					</div>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
