<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
	token: '',
});

const onInput = (event) => {
	form.token = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
};

const submit = () => {
	form.post(route('materi.token.validate'), {
		preserveState: true,
	});
};
</script>

<template>
	<Head title="Masuk Latihan — Ujion" />

	<GuestLayout hide-showcase>
		<div class="w-full max-w-md space-y-5">
			<div class="text-center">
				<div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-xl font-black text-white shadow-lg">U</div>
				<h1 class="text-2xl font-bold text-slate-900">Masuk Latihan</h1>
				<p class="mt-1 text-sm text-textSecondary">Masukkan token latihan materi yang diberikan guru</p>
			</div>

			<div v-if="form.errors.token" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
				{{ form.errors.token }}
			</div>

			<form class="space-y-4" @submit.prevent="submit">
				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-widest text-textSecondary" for="token">Token Latihan</label>
					<input
						id="token"
						v-model="form.token"
						type="text"
						name="token"
						required
						autofocus
						autocomplete="off"
						maxlength="10"
						class="input text-center font-mono text-xl font-bold uppercase tracking-[0.4em]"
						placeholder="— — — — — —"
						@input="onInput"
					>
					<p class="text-xs text-textSecondary">Token berisi 8 karakter huruf dan angka.</p>
				</div>
				<button type="submit" class="btn-primary w-full py-3 text-base font-bold" :disabled="form.processing">
					Lanjut &rarr;
				</button>
			</form>

			<div class="text-center text-xs text-textSecondary">
				Sudah punya token ujian? <Link class="font-semibold text-indigo-700 hover:underline" :href="route('siswa.login')">Masuk ujian</Link>
			</div>
		</div>
	</GuestLayout>
</template>
