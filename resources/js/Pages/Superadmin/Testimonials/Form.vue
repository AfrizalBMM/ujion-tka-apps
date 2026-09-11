<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	testimonial: {
		type: Object,
		required: true,
	},
});

const isEdit = computed(() => Boolean(props.testimonial.id));

const form = useForm({
	name: props.testimonial.name ?? '',
	role: props.testimonial.role ?? '',
	content: props.testimonial.content ?? '',
	rating: props.testimonial.rating ?? 5,
	photo: null,
	sort_order: props.testimonial.sort_order ?? 0,
	is_active: props.testimonial.is_active ?? false,
});

const ratingOptions = [5, 4, 3, 2, 1];

const submit = () => {
	if (isEdit.value) {
		form.post(route('superadmin.testimonials.update', props.testimonial.id));
	} else {
		form.post(route('superadmin.testimonials.store'));
	}
};

const formErrors = computed(() => Object.values(form.errors));
</script>

<template>
	<Head :title="isEdit ? 'Edit Testimoni' : 'Tambah Testimoni'" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">{{ isEdit ? 'Edit Testimoni' : 'Tambah Testimoni' }}</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Feedback pengguna yang akan tampil di landing page section "Mereka sudah mencoba".
					</p>
				</div>
				<Link :href="route('superadmin.testimonials.index')" class="btn-secondary">
					<i class="fa-solid fa-arrow-left mr-2"></i>
					Kembali
				</Link>
			</div>

			<div v-if="formErrors.length > 0" class="rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300">
				<ul class="list-disc space-y-1 pl-5">
					<li v-for="error in formErrors" :key="error">{{ error }}</li>
				</ul>
			</div>

			<form class="card space-y-5" enctype="multipart/form-data" @submit.prevent="submit">
				<div class="grid gap-4 md:grid-cols-2">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nama</label>
						<input v-model="form.name" class="input mt-1 w-full" name="name" placeholder="Contoh: Ibu Siti Aisyah" required maxlength="191">
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jabatan / Jenjang (opsional)</label>
						<input v-model="form.role" class="input mt-1 w-full" name="role" placeholder="Contoh: Guru SD" maxlength="191">
					</div>
				</div>

				<div>
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Rating</label>
					<select v-model="form.rating" name="rating" class="input mt-1 w-full">
						<option v-for="i in ratingOptions" :key="i" :value="i">{{ i }} Bintang</option>
					</select>
				</div>

				<div>
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Isi Testimoni</label>
					<textarea v-model="form.content" class="input mt-1 min-h-28 w-full" name="content" placeholder="Tulis feedback pengguna di sini..." required maxlength="5000"></textarea>
				</div>

				<div>
					<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Foto Guru (opsional)</label>
					<div v-if="testimonial.photo_path" class="mt-2 flex items-center gap-3">
						<img :src="testimonial.photo_url" :alt="testimonial.name" class="h-16 w-16 rounded-2xl object-cover">
						<span class="text-xs text-muted">Upload foto baru untuk mengganti foto lama.</span>
					</div>
					<input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="input mt-2 w-full" @input="form.photo = $event.target.files[0]">
					<div class="mt-1 text-xs text-muted">Format JPG/PNG/WebP, maksimal 4MB.</div>
				</div>

				<div class="grid gap-4 md:grid-cols-2">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
						<input v-model="form.sort_order" type="number" class="input mt-1 w-full" name="sort_order" min="0" max="9999">
						<div class="mt-1 text-xs text-muted">Angka kecil tampil lebih dulu.</div>
					</div>
					<label class="flex items-center gap-3 pt-6">
						<input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="h-4 w-4">
						<span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Tampilkan di landing page</span>
					</label>
				</div>

				<div class="flex items-center justify-end gap-3">
					<button class="btn-primary" type="submit" :disabled="form.processing">
						<i class="fa-solid fa-floppy-disk"></i>
						Simpan
					</button>
				</div>
			</form>
		</div>
	</SuperadminLayout>
</template>
