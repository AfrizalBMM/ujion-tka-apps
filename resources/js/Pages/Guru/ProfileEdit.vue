<script setup>
import { computed, inject, onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

const props = defineProps({
	user: {
		type: Object,
		required: true,
	},
	avatarUrl: {
		type: String,
		required: true,
	},
});

const route = inject('route');
const page = usePage();

const form = useForm({
	name: props.user.name,
	email: props.user.email,
	satuan_pendidikan: props.user.satuan_pendidikan,
	no_wa: props.user.no_wa,
	avatar: null,
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0 || Object.keys(page.props.errors || {}).length > 0);

const fieldError = (field) => form.errors[field] || page.props.errors?.[field] || null;

const fieldClass = (field) =>
	fieldError(field)
		? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10'
		: 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80';

const avatarPreviewUrl = ref(props.avatarUrl);
let objectUrl = null;

const onAvatarChange = (event) => {
	const file = event.target.files?.[0] || null;

	if (!file) {
		form.avatar = null;
		return;
	}

	form.avatar = file;

	if (objectUrl) {
		URL.revokeObjectURL(objectUrl);
	}

	objectUrl = URL.createObjectURL(file);
	avatarPreviewUrl.value = objectUrl;
};

onBeforeUnmount(() => {
	if (objectUrl) {
		URL.revokeObjectURL(objectUrl);
	}
});

const submit = () => {
	form.post(route('guru.profile.update'), {
		forceFormData: true,
	});
};
</script>

<template>
	<Head title="Edit Profil Guru" />

	<GuruLayout>
		<div class="w-full space-y-6">
			<section class="page-hero">
				<div class="flex items-center justify-between gap-4">
					<div>
						<span class="page-kicker">Akun Guru</span>
						<h1 class="page-title">Edit &amp; Lengkapi Profil</h1>
					</div>
					<Link :href="route('guru.profile')" class="btn-secondary border-white/15 bg-white/10 text-white hover:bg-white/15 hover:text-white">
						<i class="fa-solid fa-arrow-left"></i>
						Kembali
					</Link>
				</div>
			</section>

			<form class="overflow-hidden rounded-[30px] border border-slate-200/70 bg-white shadow-[0_24px_70px_-42px_rgba(15,23,42,0.45)] dark:border-slate-800 dark:bg-slate-950/95" enctype="multipart/form-data" @submit.prevent="submit">
				<div class="border-b border-slate-200/80 px-6 py-5 dark:border-slate-800 sm:px-8">
					<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
						<div>
							<h2 class="text-lg font-bold text-slate-900 dark:text-white">Informasi Profil</h2>
							<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Perbarui data utama akun Anda agar tetap akurat dan mudah dikenali.</p>
						</div>
						<div v-if="hasErrors" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
							Ada beberapa data yang perlu diperiksa kembali.
						</div>
					</div>
				</div>

				<div class="space-y-8 px-6 py-6 sm:px-8 sm:py-8">
					<div class="grid gap-5 md:grid-cols-2">
						<div class="space-y-2">
							<label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Lengkap</label>
							<input id="name" v-model="form.name" name="name" required
								:class="fieldClass('name')"
								class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
								placeholder="Masukkan nama lengkap">
							<p v-if="fieldError('name')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('name') }}</p>
						</div>

						<div class="space-y-2">
							<label for="email" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
							<input id="email" v-model="form.email" type="email" name="email" required
								:class="fieldClass('email')"
								class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
								placeholder="nama@email.com">
							<p v-if="fieldError('email')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('email') }}</p>
						</div>

						<div class="space-y-2">
							<label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Jenjang</label>
							<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-3 text-sm font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
								{{ user.jenjang || '-' }}
							</div>
							<p class="text-xs leading-5 text-slate-500 dark:text-slate-400">Jenjang mengikuti data aktivasi akun dan tidak diubah dari halaman profil.</p>
						</div>

						<div class="space-y-2">
							<label for="satuan_pendidikan" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Satuan Pendidikan</label>
							<input id="satuan_pendidikan" v-model="form.satuan_pendidikan" name="satuan_pendidikan" required
								:class="fieldClass('satuan_pendidikan')"
								class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
								placeholder="Contoh: SMP Negeri 1">
							<p v-if="fieldError('satuan_pendidikan')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('satuan_pendidikan') }}</p>
						</div>

						<div class="space-y-2 md:col-span-2">
							<label for="no_wa" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nomor WhatsApp</label>
							<input id="no_wa" v-model="form.no_wa" name="no_wa" required
								:class="fieldClass('no_wa')"
								class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
								placeholder="08xxxxxxxxxx">
							<p v-if="fieldError('no_wa')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('no_wa') }}</p>
						</div>
					</div>

					<div class="rounded-[28px] border border-slate-200/80 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-900/60">
						<div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
							<div class="flex items-center gap-4">
								<img id="avatar-preview" :src="avatarPreviewUrl" :alt="`Preview avatar ${user.name}`" class="h-20 w-20 rounded-3xl border border-white object-cover shadow-md dark:border-slate-700">
								<div>
									<h3 class="text-base font-bold text-slate-900 dark:text-white">Foto Profil</h3>
									<p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Unggah foto yang jelas agar akun Anda lebih mudah dikenali oleh siswa dan admin.</p>
									<button v-if="user.has_avatar" type="button"
										class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-rose-500/30 dark:hover:text-rose-400"
										data-confirm-form="delete-avatar-form"
										data-confirm-title="Hapus Foto Profil"
										data-confirm="Apakah Anda yakin ingin menghapus foto profil ini?">
										<i class="fa-solid fa-trash-can text-xs"></i>
										Hapus Foto
									</button>
								</div>
							</div>

							<div class="w-full lg:max-w-sm">
								<label for="avatar" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Unggah Avatar Baru</label>
								<input id="avatar" type="file" name="avatar" accept="image/*"
									class="block w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-teal-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:file:bg-teal-500 dark:hover:file:bg-teal-400"
									@change="onAvatarChange">
								<p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format gambar: JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.</p>
								<p v-if="fieldError('avatar')" class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ fieldError('avatar') }}</p>
							</div>
						</div>
					</div>
				</div>

				<div class="flex flex-col gap-3 border-t border-slate-200/80 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-950/80 sm:flex-row sm:items-center sm:justify-between sm:px-8">
					<p class="text-sm text-slate-500 dark:text-slate-400">Pastikan email dan nomor WhatsApp aktif agar komunikasi dengan admin tetap lancar.</p>
					<div class="flex items-center gap-3">
						<Link :href="route('guru.profile')" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
							Batal
						</Link>
						<button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400">
							<i class="fa-solid fa-check text-xs"></i>
							Simpan Profil
						</button>
					</div>
				</div>
			</form>

			<form id="delete-avatar-form" method="POST" :action="route('guru.profile.avatar.delete')" class="hidden">
				<input type="hidden" name="_token" :value="page.props.csrf_token">
			</form>
		</div>
	</GuruLayout>
</template>
