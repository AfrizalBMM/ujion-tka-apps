<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
});

const form = useForm({
	jumlah_soal: props.mapel.jumlah_soal,
	durasi_menit: props.mapel.durasi_menit,
	urutan: props.mapel.urutan,
});

const submit = () => {
	form.put(route('guru.mapel.update', [props.paket.id, props.mapel.id]));
};
</script>

<template>
	<form
		class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-3 dark:border-slate-800 dark:bg-slate-900/60"
		@submit.prevent="submit"
	>
		<div class="input-group">
			<label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Jumlah Soal</label>
			<input type="number" name="jumlah_soal" class="input" v-model="form.jumlah_soal" min="1" max="200" required>
		</div>
		<div class="input-group">
			<label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Durasi (menit)</label>
			<input type="number" name="durasi_menit" class="input" v-model="form.durasi_menit" min="1" max="600" required>
		</div>
		<div class="input-group">
			<label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Urutan</label>
			<input type="number" name="urutan" class="input" v-model="form.urutan" min="1" max="10" required>
		</div>
		<div class="md:col-span-3">
			<button class="btn-secondary px-4 py-2 text-xs" type="submit" :disabled="form.processing">Simpan Konfigurasi</button>
		</div>
	</form>
</template>
