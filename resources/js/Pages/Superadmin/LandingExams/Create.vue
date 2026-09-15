<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	examOptions: {
		type: Array,
		default: () => [],
	},
	hasExams: {
		type: Boolean,
		default: false,
	},
});

const form = useForm({
	exam_id: '',
	jenjang: 'SD',
	slug: '',
	short_description: '',
	description: '',
	is_active: true,
	mapels: [],
});

const jenjangOptions = ['SD', 'SMP', 'SMA'];

const selectedExam = computed(() => props.examOptions.find((exam) => String(exam.id) === String(form.exam_id)) || null);

const selectedMapels = computed(() => selectedExam.value?.mapels || []);

const onExamChange = (event) => {
	form.exam_id = event.target.value;
	form.mapels = selectedMapels.value.map((m) => ({
		mapel_paket_id: m.id,
		price: 0,
		original_price: '',
	}));
};

const submit = () => {
	form.post(route('superadmin.landing-exams.store'));
};
</script>

<template>
	<Head title="Tambah Ujian Publik" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="section-heading">
				<div>
					<h1 class="text-2xl font-bold">Tambah Ujian Publik</h1>
					<p class="text-sm text-textSecondary mt-1">Pilih ujian yang sudah ada, tentukan harga per mapel.</p>
				</div>
				<Link :href="route('superadmin.landing-exams.index')" class="btn-secondary">Kembali</Link>
			</div>

			<div v-if="!hasExams" class="card text-center py-12">
				<i class="fa-solid fa-circle-info text-3xl text-amber-400 mb-3 block"></i>
				<p class="text-textSecondary">Tidak ada ujian yang tersedia. Pastikan sudah membuat ujian aktif (status: terbit) di menu Manajemen Ujian.</p>
				<Link :href="route('superadmin.exams.index')" class="btn-primary mt-4 inline-flex">Ke Manajemen Ujian</Link>
			</div>
			<form v-else class="card space-y-6" @submit.prevent="submit">
				<div class="grid gap-4 md:grid-cols-2">
					<div class="input-group md:col-span-2">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Pilih Ujian</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="exam_id" id="exam_id" :value="form.exam_id" required @change="onExamChange">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">Pilih ujian...</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari ujian..."></div>
								<div class="ssd-list">
									<div
										v-for="exam in examOptions"
										:key="exam.id"
										class="ssd-option"
										:class="String(form.exam_id) === String(exam.id) ? 'ssd-selected' : ''"
										:data-value="exam.id"
									>
										{{ exam.judul }} ({{ exam.jenjang_kode }})
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
						<div class="ssd-wrap mt-1">
							<input type="hidden" name="jenjang" :value="form.jenjang" @change="form.jenjang = $event.target.value">
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ form.jenjang }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
								<div class="ssd-list">
									<div
										v-for="j in jenjangOptions"
										:key="j"
										class="ssd-option"
										:class="form.jenjang === j ? 'ssd-selected' : ''"
										:data-value="j"
									>
										{{ j }}
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Slug (opsional)</label>
						<input v-model="form.slug" type="text" name="slug" class="input" placeholder="otomatis dari judul jika kosong">
					</div>
				</div>

				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Singkat</label>
					<input v-model="form.short_description" type="text" name="short_description" class="input" placeholder="Satu kalimat untuk card listing" maxlength="500">
				</div>

				<div class="input-group">
					<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Deskripsi Lengkap</label>
					<textarea v-model="form.description" name="description" class="input min-h-32" placeholder="Deskripsi ujian yang tampil di halaman detail"></textarea>
				</div>

				<div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-5 dark:border-slate-800 dark:bg-slate-900/60">
					<h3 class="text-sm font-bold uppercase tracking-[0.18em] text-textSecondary mb-4">Harga per Mapel</h3>
					<div id="mapel-pricing" class="space-y-3">
						<template v-if="form.exam_id">
							<p v-if="selectedMapels.length === 0" class="text-sm text-textSecondary">Ujian ini tidak memiliki mapel.</p>
							<div
								v-for="(m, i) in selectedMapels"
								:key="m.id"
								class="grid gap-3 rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/70 md:grid-cols-[1fr_auto_auto]"
							>
								<div>
									<div class="font-semibold text-sm">{{ m.label }}</div>
									<div class="text-xs text-textSecondary mt-0.5">{{ m.jumlah }} soal — {{ m.durasi }} menit</div>
									<input type="hidden" :name="`mapels[${i}][mapel_paket_id]`" :value="m.id">
								</div>
								<div class="input-group">
									<label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga (Rp)</label>
									<input v-model="form.mapels[i].price" type="number" :name="`mapels[${i}][price]`" class="input" min="0" required>
								</div>
								<div class="input-group">
									<label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga Coret</label>
									<input v-model="form.mapels[i].original_price" type="number" :name="`mapels[${i}][original_price]`" class="input" min="0" placeholder="opsional">
								</div>
							</div>
						</template>
						<p v-else class="text-sm text-textSecondary">Pilih ujian di atas untuk menampilkan daftar mapel.</p>
					</div>
				</div>

				<label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950/70">
					<input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="h-4 w-4 text-primary">
					<span>Aktifkan (tampilkan di landing page)</span>
				</label>

				<div class="flex gap-3">
					<button type="submit" class="btn-primary" :disabled="form.processing">Simpan</button>
					<Link :href="route('superadmin.landing-exams.index')" class="btn-secondary">Batal</Link>
				</div>
			</form>
		</div>
	</SuperadminLayout>
</template>
