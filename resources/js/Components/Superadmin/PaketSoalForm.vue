<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
	paket: {
		type: Object,
		default: null,
	},
	jenjangs: {
		type: Array,
		default: () => [],
	},
	submitLabel: {
		type: String,
		required: true,
	},
	cancelUrl: {
		type: String,
		required: true,
	},
});

const isEdit = computed(() => !!props.paket);

const form = useForm({
	jenjang_id: props.paket?.jenjang_id ?? null,
	tahun_ajaran: props.paket?.tahun_ajaran ?? '',
	nama: props.paket?.nama ?? '',
	is_active: props.paket?.is_active ?? false,
});

const selectedJenjang = computed(() => props.jenjangs.find((j) => j.id == form.jenjang_id));

const submit = () => {
	if (isEdit.value) {
		form.put(route('superadmin.paket-soal.update', props.paket.id));
	} else {
		form.post(route('superadmin.paket-soal.store'));
	}
};
</script>

<template>
	<form class="space-y-5" @submit.prevent="submit">
		<div class="grid gap-4 md:grid-cols-2">
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
				<div class="ssd-wrap mt-1">
					<input type="hidden" name="jenjang_id" :value="form.jenjang_id" required @change="form.jenjang_id = $event.target.value">
					<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
						<span class="ssd-label">{{ selectedJenjang ? `${selectedJenjang.kode} - ${selectedJenjang.nama}` : 'Pilih jenjang' }}</span>
						<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
					</button>
					<div class="ssd-panel">
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
						<div class="ssd-list">
							<div class="ssd-option" :class="!form.jenjang_id ? 'ssd-selected' : ''" data-value="">Pilih jenjang</div>
							<div v-for="jenjang in jenjangs" :key="jenjang.id" class="ssd-option" :class="form.jenjang_id == jenjang.id ? 'ssd-selected' : ''" :data-value="jenjang.id">{{ jenjang.kode }} - {{ jenjang.nama }}</div>
						</div>
					</div>
				</div>
				<span v-if="form.errors.jenjang_id" class="text-xs text-red-500">{{ form.errors.jenjang_id }}</span>
			</div>

			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tahun Ajaran</label>
				<input type="text" name="tahun_ajaran" class="input" v-model="form.tahun_ajaran" placeholder="2025/2026" required>
				<span v-if="form.errors.tahun_ajaran" class="text-xs text-red-500">{{ form.errors.tahun_ajaran }}</span>
			</div>
		</div>

		<div class="input-group">
			<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Nama Paket Soal</label>
			<input type="text" name="nama" class="input" v-model="form.nama" placeholder="Paket TKA SMP Gelombang 1" required>
			<span v-if="form.errors.nama" class="text-xs text-red-500">{{ form.errors.nama }}</span>
		</div>

		<label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900/70">
			<input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary">
			<span>Jadikan paket aktif untuk jenjang ini</span>
		</label>

		<div class="flex flex-wrap gap-3">
			<button class="btn-primary" type="submit" :disabled="form.processing">
				<i class="fa-solid fa-floppy-disk"></i>
				{{ submitLabel }}
			</button>
			<Link :href="cancelUrl" class="btn-secondary">Batal</Link>
		</div>
	</form>
</template>
