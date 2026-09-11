<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	template: {
		type: Object,
		required: true,
	},
	mode: {
		type: String,
		required: true,
	},
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
	key: props.template.key ?? '',
	title: props.template.title ?? '',
	description: props.template.description ?? '',
	body: props.template.body ?? '',
	is_active: props.template.is_active ?? false,
});

const submit = () => {
	if (isEdit.value) {
		form.post(route('superadmin.wa-templates.update', props.template.id));
	} else {
		form.post(route('superadmin.wa-templates.store'));
	}
};
</script>

<template>
	<Head :title="isEdit ? 'Edit Pesan Otomatis' : 'Tambah Pesan Otomatis'" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">{{ isEdit ? 'Edit Pesan Otomatis' : 'Tambah Pesan Otomatis' }}</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Gunakan placeholder seperti <span class="font-mono">{name}</span> atau <span class="font-mono">{token}</span> sesuai kebutuhan.
					</p>
				</div>
				<div class="flex gap-2">
					<Link :href="route('superadmin.wa-templates.index')" class="btn-secondary">
						<i class="fa-solid fa-arrow-left mr-2"></i>
						Kembali
					</Link>
				</div>
			</div>

			<div class="card max-w-3xl">
				<div class="text-xs font-semibold uppercase tracking-wide text-muted">Form</div>
				<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Template</div>

				<form class="mt-4 space-y-4" @submit.prevent="submit">
					<div class="grid gap-4 md:grid-cols-2">
						<div>
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Key</label>
							<input v-model="form.key" name="key" class="input w-full font-mono" placeholder="contoh: payment_approved">
							<div v-if="form.errors.key" class="mt-2 text-sm text-rose-600">{{ form.errors.key }}</div>
						</div>

						<div>
							<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status</label>
							<label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
								<input v-model="form.is_active" type="checkbox" name="is_active" value="1">
								<span>Aktif</span>
							</label>
							<div v-if="form.errors.is_active" class="mt-2 text-sm text-rose-600">{{ form.errors.is_active }}</div>
						</div>
					</div>

					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Judul</label>
						<input v-model="form.title" name="title" class="input w-full" placeholder="Contoh: Notifikasi pembayaran diterima">
						<div v-if="form.errors.title" class="mt-2 text-sm text-rose-600">{{ form.errors.title }}</div>
					</div>

					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Deskripsi (opsional)</label>
						<textarea v-model="form.description" name="description" class="input w-full min-h-24" placeholder="Catatan internal admin"></textarea>
						<div v-if="form.errors.description" class="mt-2 text-sm text-rose-600">{{ form.errors.description }}</div>
					</div>

					<div>
						<label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Isi pesan</label>
						<textarea v-model="form.body" name="body" class="input w-full min-h-60" placeholder="Tulis isi pesan WhatsApp..."></textarea>
						<div v-if="form.errors.body" class="mt-2 text-sm text-rose-600">{{ form.errors.body }}</div>
					</div>

					<div class="flex justify-end gap-2">
						<button type="submit" class="btn-primary" :disabled="form.processing">
							<i class="fa-solid fa-floppy-disk mr-2"></i>
							Simpan
						</button>
					</div>
				</form>
			</div>
		</div>
	</SuperadminLayout>
</template>
