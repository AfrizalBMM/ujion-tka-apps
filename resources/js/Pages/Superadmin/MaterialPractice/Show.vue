<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	material: {
		type: Object,
		required: true,
	},
	token: {
		type: Object,
		default: null,
	},
	telaah: {
		type: Array,
		default: () => [],
	},
	bankQuestions: {
		type: Array,
		default: () => [],
	},
	bankQuestionCount: {
		type: Number,
		default: 0,
	},
	jenjangFilter: {
		type: String,
		default: null,
	},
});

const backHref = computed(() => route('superadmin.materials.index', props.jenjangFilter ? { jenjang: props.jenjangFilter } : {}));

const tokenForm = useForm({
	jumlah_soal_per_paket: props.token?.jumlah_soal_per_paket ?? 10,
});

const submitToken = () => {
	tokenForm.post(route('superadmin.materials.practice.token', props.material.id));
};

const telaahForm = useForm({
	question_ids: [
		props.telaah[0]?.global_question_id ?? '',
		props.telaah[1]?.global_question_id ?? '',
	],
});

const submitTelaah = () => {
	telaahForm.post(route('superadmin.materials.practice.telaah', props.material.id));
};

const selectedBacaanInfo = (index) => {
	const selectedId = telaahForm.question_ids[index];
	if (!selectedId) return '-';
	const selected = props.bankQuestions.find((q) => Number(q.id) === Number(selectedId));
	return selected?.has_reading_passage ? 'Ya' : 'Tidak';
};
</script>

<template>
	<Head title="Latihan Materi" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ material.jenjang || '-' }} &middot; {{ material.mapel || '-' }} &middot; {{ material.curriculum }}</span>
				<h1 class="page-title">Latihan Materi</h1>
				<p class="page-description">
					Konfigurasi 2 soal telaah (PG) dan token latihan (3 paket acak) untuk materi: <span class="font-semibold">{{ material.subelement }} &rarr; {{ material.unit }} &rarr; {{ material.sub_unit }}</span>.
				</p>
				<div class="page-actions">
					<a :href="backHref" class="btn-secondary">Kembali</a>
				</div>
			</section>

			<section class="card">
				<div class="section-heading mb-5">
					<div>
						<h2 class="section-title">Token Latihan</h2>
						<p class="section-description">Satu token per materi (berisi telaah + paket 1-3). Paket latihan akan menjadi snapshot dan dipakai semua siswa.</p>
					</div>
				</div>

				<div v-if="bankQuestionCount === 0" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-white p-4 text-sm text-textSecondary dark:bg-slate-900">
					<div>
						<span class="badge-warning">Bank soal PG aktif: 0</span>
						<span class="ml-2">Token/paket latihan tidak bisa digenerate sebelum bank soal untuk materi ini tersedia.</span>
					</div>
					<a :href="route('superadmin.global-questions.index')" class="btn-secondary">Buka Bank Soal</a>
				</div>

				<div class="grid gap-4 lg:grid-cols-2">
					<div class="rounded-2xl border border-border bg-white p-4 dark:bg-slate-900">
						<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Token</div>
						<div class="mt-2 flex items-center gap-3">
							<template v-if="token">
								<code class="rounded bg-indigo-50 px-3 py-2 text-lg font-black text-indigo-700">{{ token.token }}</code>
								<span class="badge-success">Aktif</span>
							</template>
							<span v-else class="badge-warning">Belum dibuat</span>
						</div>
						<div class="mt-3 text-xs text-textSecondary">Jumlah soal per paket: <span class="font-semibold">{{ token?.jumlah_soal_per_paket ?? '-' }}</span></div>
					</div>

					<div class="rounded-2xl border border-border bg-white p-4 dark:bg-slate-900">
						<form class="space-y-3" @submit.prevent="submitToken">
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jumlah Soal per Paket</label>
								<select name="jumlah_soal_per_paket" class="input" v-model="tokenForm.jumlah_soal_per_paket" required>
									<option v-for="n in [10, 15]" :key="n" :value="n">{{ n }}</option>
								</select>
								<p class="mt-1 text-[11px] text-textSecondary">Aksi ini akan (re)generate paket 1-3 dari bank soal PG aktif pada materi ini.</p>
							</div>
							<button class="btn-primary" type="submit" :disabled="tokenForm.processing">{{ token ? 'Update & Regenerate Paket' : 'Generate Token & Paket' }}</button>
						</form>

						<form v-if="token" method="POST" :action="route('superadmin.materials.practice.packages.regenerate', material.id)" class="mt-3">
							<input type="hidden" name="_token" :value="$page.props.csrf_token" />
							<button class="btn-secondary" type="submit" data-confirm="Acak ulang paket 1-3? Paket siswa akan berubah." data-confirm-title="Acak Ulang Paket">Acak Ulang Paket</button>
						</form>
					</div>
				</div>
			</section>

			<section class="card">
				<div class="section-heading mb-5">
					<div>
						<h2 class="section-title">Telaah Soal (2 Butir)</h2>
						<p class="section-description">Telaah dipakai untuk pegangan guru mengajar, dan akan tampil di halaman siswa saat token dimasukkan.</p>
					</div>
					<div class="flex items-center gap-2">
						<span class="badge-info">Bank PG aktif: {{ bankQuestionCount }}</span>
						<span v-if="bankQuestionCount > 200" class="badge-warning">Menampilkan 200 terbaru</span>
					</div>
				</div>

				<div v-if="bankQuestionCount === 0" class="rounded-2xl border border-border bg-white p-4 text-sm text-textSecondary dark:bg-slate-900">
					Belum ada bank soal PG aktif untuk materi ini, sehingga dropdown telaah akan kosong.
					<div class="mt-3">
						<a class="btn-secondary" :href="route('superadmin.global-questions.index')">Buka Bank Soal</a>
					</div>
				</div>
				<form v-else class="grid gap-4 lg:grid-cols-2" @submit.prevent="submitTelaah">
					<div v-for="i in 2" :key="i" class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Soal Telaah {{ i }}</label>
						<select name="question_ids[]" class="input" v-model="telaahForm.question_ids[i - 1]" required>
							<option value="">Pilih soal...</option>
							<option v-for="q in bankQuestions" :key="q.id" :value="q.id">
								{{ q.label }}
							</option>
						</select>
						<p class="mt-1 text-[11px] text-textSecondary">Opsional bacaan: {{ selectedBacaanInfo(i - 1) }}</p>
					</div>
					<div class="lg:col-span-2">
						<button class="btn-primary" type="submit" :disabled="telaahForm.processing">Simpan Telaah</button>
					</div>
				</form>

				<div v-if="telaah.length === 2" class="mt-6 rounded-2xl border border-border bg-slate-50/70 p-4 dark:bg-slate-900/60">
					<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Preview</div>
					<div class="mt-3 grid gap-3 lg:grid-cols-2">
						<div v-for="row in telaah" :key="row.urutan" class="rounded-2xl border border-border bg-white p-4 dark:bg-slate-900">
							<div class="flex items-center justify-between gap-3">
								<div class="font-semibold">Telaah {{ row.urutan }}</div>
								<span class="badge-info">ID #{{ row.global_question_id }}</span>
							</div>
							<template v-if="row.has_reading_passage">
								<div class="mt-3 text-xs font-semibold text-textSecondary">Bacaan</div>
								<div class="mt-1 text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ row.reading_passage_limited }}</div>
							</template>
							<div class="mt-3 text-xs font-semibold text-textSecondary">Pertanyaan</div>
							<div class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ row.question_text_limited }}</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	</SuperadminLayout>
</template>
