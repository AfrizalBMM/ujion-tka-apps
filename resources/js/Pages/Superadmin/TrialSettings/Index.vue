<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	currentDays: {
		type: Number,
		default: 7,
	},
	options: {
		type: Array,
		default: () => [],
	},
});

const form = useForm({
	trial_default_days: props.currentDays,
});

const submit = () => {
	form.post(route('superadmin.trial-settings.update'), {
		preserveScroll: true,
	});
};
</script>

<template>
	<Head title="Pengaturan Trial" />

	<SuperadminLayout>
		<div class="max-w-2xl space-y-6">
			<div>
				<h1 class="text-2xl font-bold">Pengaturan Trial</h1>
				<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
					Atur berapa lama masa trial gratis yang otomatis aktif setelah guru melengkapi profil. Durasi ini berlaku untuk semua pendaftaran baru.
				</p>
			</div>

			<form class="card space-y-6 p-6" @submit.prevent="submit">
				<div>
					<label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
						Durasi Trial Default
					</label>
					<p class="mt-1 text-xs text-textSecondary dark:text-slate-400">
						Saat ini: <span class="font-bold text-primary">{{ currentDays }} hari</span>
					</p>
				</div>

				<div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
					<label
						v-for="option in options"
						:key="option.value"
						class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 px-4 py-4 text-center transition-all"
						:class="form.trial_default_days === option.value
							? 'border-primary bg-primary/5 text-primary'
							: 'border-slate-200 bg-slate-50/50 text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300'"
					>
						<input
							v-model="form.trial_default_days"
							type="radio"
							:value="option.value"
							class="sr-only"
						>
						<span class="text-2xl font-bold">{{ option.value }}</span>
						<span class="mt-1 text-xs font-semibold uppercase tracking-wide">Hari</span>
					</label>
				</div>

				<p v-if="form.errors.trial_default_days" class="text-sm text-rose-600 dark:text-rose-300">
					{{ form.errors.trial_default_days }}
				</p>

				<div class="flex items-center gap-3 border-t border-slate-200/60 pt-4 dark:border-slate-700/60">
					<button
						type="submit"
						class="btn-primary"
						:disabled="form.processing || form.trial_default_days === currentDays"
						:class="(form.processing || form.trial_default_days === currentDays) ? 'opacity-50 cursor-not-allowed' : ''"
					>
						<i class="fa-solid fa-floppy-disk mr-2"></i>
						Simpan Pengaturan
					</button>
					<span v-if="form.trial_default_days !== currentDays" class="text-sm font-semibold text-amber-600 dark:text-amber-300">
						<i class="fa-solid fa-circle-exclamation mr-1"></i>
						Belum disimpan
					</span>
				</div>
			</form>

			<div class="card space-y-3 p-6">
				<h2 class="font-semibold">Bagaimana trial bekerja</h2>
				<ul class="list-disc space-y-1 pl-6 text-sm text-textSecondary dark:text-slate-300">
					<li>Guru daftar (email atau Google) → wajib lengkapi profil dulu.</li>
					<li>Setelah profil lengkap, trial <b>otomatis aktif</b> sesuai durasi di atas.</li>
					<li>Selama trial, guru punya akses penuh ke semua fitur (latihan soal, ujian, analisis, chat).</li>
					<li>Dashboard menampilkan banner sisa hari trial.</li>
					<li>H-1 sebelum habis, guru dapat reminder otomatis via WhatsApp.</li>
					<li>Setelah trial habis, guru diarahkan ke halaman paket langganan.</li>
				</ul>
			</div>
		</div>
	</SuperadminLayout>
</template>
