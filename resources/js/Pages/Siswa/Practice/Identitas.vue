<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
	nama: '',
	wa: '',
});

const submit = () => {
	form.post(route('materi.mulai'));
};
</script>

<template>
	<Head title="Identitas Peserta — Latihan" />

	<GuestLayout hide-showcase>
		<div class="w-full max-w-md space-y-5">
			<div class="text-center">
				<div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-xl font-black text-white shadow-lg">U</div>
				<h1 class="text-2xl font-bold text-slate-900">Lengkapi Identitas</h1>
				<p class="mt-1 text-sm text-textSecondary">Data ini dipakai untuk menyimpan hasil latihan</p>
			</div>

			<div v-if="Object.keys(form.errors).length > 0" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
				{{ Object.values(form.errors)[0] }}
			</div>

			<form class="space-y-4" @submit.prevent="submit">
				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-widest text-textSecondary" for="nama">Nama Lengkap <span class="text-red-500">*</span></label>
					<input
						id="nama"
						v-model="form.nama"
						type="text"
						name="nama"
						required
						autofocus
						autocomplete="name"
						class="input"
						placeholder="Nama lengkap"
					>
				</div>
				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-widest text-textSecondary" for="wa">No. WhatsApp <span class="text-textSecondary font-normal">(opsional)</span></label>
					<input
						id="wa"
						v-model="form.wa"
						type="tel"
						name="wa"
						autocomplete="tel"
						class="input"
						placeholder="08xxxxxxxxxx"
					>
					<p class="text-xs text-textSecondary">Jika diisi, sistem bisa menemukan sesi Anda lagi.</p>
				</div>
				<button type="submit" class="btn-primary w-full py-3 text-base font-bold" :disabled="form.processing">
					Masuk &rarr;
				</button>
			</form>
		</div>
	</GuestLayout>
</template>
