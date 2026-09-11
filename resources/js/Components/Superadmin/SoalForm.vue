<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref } from 'vue';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	soal: {
		type: Object,
		default: null,
	},
	teksBacaans: {
		type: Array,
		default: () => [],
	},
	tipeSoal: {
		type: String,
		default: 'pilihan_ganda',
	},
	nextNomor: {
		type: Number,
		default: 1,
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

const isSurvey = computed(() => props.mapel.is_survey);

const defaultPilihan = isSurvey.value
	? [
		{ kode: 'A', teks: '', nilai_survey: 4, profil_label: 'Sangat Sesuai' },
		{ kode: 'B', teks: '', nilai_survey: 3, profil_label: 'Sesuai' },
		{ kode: 'C', teks: '', nilai_survey: 2, profil_label: 'Tidak Sesuai' },
		{ kode: 'D', teks: '', nilai_survey: 1, profil_label: 'Sangat Tidak Sesuai' },
	]
	: [
		{ kode: 'A', teks: '' },
		{ kode: 'B', teks: '' },
		{ kode: 'C', teks: '' },
		{ kode: 'D', teks: '' },
	];

const form = useForm({
	nomor_soal: props.soal?.nomor_soal ?? props.nextNomor ?? 1,
	tipe_soal: props.soal?.tipe_soal ?? props.tipeSoal ?? 'pilihan_ganda',
	teks_bacaan_id: props.soal?.teks_bacaan_id ?? '',
	bobot: props.soal?.bobot ?? 1,
	indikator: props.soal?.indikator ?? '',
	dimensi: props.soal?.dimensi ?? '',
	subdimensi: props.soal?.subdimensi ?? '',
	kategori_profil: props.soal?.kategori_profil ?? '',
	arah_skor: props.soal?.arah_skor ?? 'positif',
	pertanyaan: props.soal?.pertanyaan ?? '',
	pembahasan: props.soal?.pembahasan ?? '',
	gambar: null,
	pilihan: props.soal?.pilihan?.length ? props.soal.pilihan : defaultPilihan,
	pilihan_gambar: {},
	jawaban_benar: props.soal?.jawaban_benar ?? '',
	pasangan: props.soal?.pasangan?.length
		? props.soal.pasangan
		: [
			{ teks_kiri: '', teks_kanan: '' },
			{ teks_kiri: '', teks_kanan: '' },
			{ teks_kiri: '', teks_kanan: '' },
		],
});

const isPilihanGanda = computed(() => form.tipe_soal === 'pilihan_ganda');
const isMenjodohkan = computed(() => form.tipe_soal === 'menjodohkan' && !isSurvey.value);

const selectedBacaan = computed(() => props.teksBacaans?.find((b) => b.id == form.teks_bacaan_id) ?? null);
const bacaanLabel = computed(() => (selectedBacaan.value ? selectedBacaan.value.label : 'Tanpa teks bacaan'));

const submit = () => {
	if (props.soal) {
		form.put(route('superadmin.soal.update', [props.paket.id, props.mapel.id, props.soal.id]));
	} else {
		form.post(route('superadmin.soal.store', [props.paket.id, props.mapel.id]));
	}
};

const addPair = () => {
	form.pasangan.push({ teks_kiri: '', teks_kanan: '' });
};

const removePair = (index) => {
	if (form.pasangan.length <= 3) {
		window.alert('Minimal tiga pasangan harus tersedia.');
		return;
	}
	form.pasangan.splice(index, 1);
};

const gambarPreviewUrl = ref(props.soal?.gambar_url ?? null);
const onGambarChange = (event) => {
	const file = event.target.files?.[0];
	form.gambar = file ?? null;
	if (gambarPreviewUrl.value?.startsWith('blob:')) {
		URL.revokeObjectURL(gambarPreviewUrl.value);
	}
	gambarPreviewUrl.value = file ? URL.createObjectURL(file) : props.soal?.gambar_url ?? null;
};

const pilihanPreviewUrls = reactive({});
const onPilihanGambarChange = (kode, event) => {
	const file = event.target.files?.[0];
	form.pilihan_gambar[kode] = file ?? null;
	if (pilihanPreviewUrls[kode]?.startsWith('blob:')) {
		URL.revokeObjectURL(pilihanPreviewUrls[kode]);
	}
	const fallback = props.soal?.pilihan?.find((p) => p.kode === kode)?.gambar_url ?? null;
	pilihanPreviewUrls[kode] = file ? URL.createObjectURL(file) : fallback;
};

	onBeforeUnmount(() => {
		if (gambarPreviewUrl.value?.startsWith('blob:')) {
			URL.revokeObjectURL(gambarPreviewUrl.value);
		}
		Object.values(pilihanPreviewUrls).forEach((url) => {
			if (url?.startsWith('blob:')) {
				URL.revokeObjectURL(url);
			}
		});
	});
</script>

<template>
	<form class="space-y-6" data-soal-form @submit.prevent="submit">
		<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Nomor Soal</label>
				<input type="number" name="nomor_soal" min="1" :max="mapel.jumlah_soal" class="input" v-model="form.nomor_soal" required>
			</div>
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tipe Soal</label>
				<div class="ssd-wrap mt-1">
					<input type="hidden" name="tipe_soal" :value="form.tipe_soal" required :disabled="isSurvey" data-soal-type @change="form.tipe_soal = $event.target.value">
					<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full" :disabled="isSurvey">
						<span class="ssd-label">{{ form.tipe_soal === 'pilihan_ganda' ? 'Pilihan Ganda' : 'Menjodohkan' }}</span>
						<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
					</button>
					<div class="ssd-panel">
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari tipe..."></div>
						<div class="ssd-list">
							<div class="ssd-option" :class="form.tipe_soal === 'pilihan_ganda' ? 'ssd-selected' : ''" data-value="pilihan_ganda">Pilihan Ganda</div>
							<div v-if="!isSurvey" class="ssd-option" :class="form.tipe_soal === 'menjodohkan' ? 'ssd-selected' : ''" data-value="menjodohkan">Menjodohkan</div>
						</div>
					</div>
				</div>
			</div>
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Teks Bacaan</label>
				<div class="ssd-wrap mt-1">
					<input type="hidden" name="teks_bacaan_id" :value="form.teks_bacaan_id" @change="form.teks_bacaan_id = $event.target.value">
					<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
						<span class="ssd-label">{{ bacaanLabel }}</span>
						<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
					</button>
					<div class="ssd-panel">
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari bacaan..."></div>
						<div class="ssd-list">
							<div class="ssd-option" :class="!form.teks_bacaan_id ? 'ssd-selected' : ''" data-value="">Tanpa teks bacaan</div>
							<div v-for="bacaan in teksBacaans" :key="bacaan.id" class="ssd-option" :class="form.teks_bacaan_id == bacaan.id ? 'ssd-selected' : ''" :data-value="bacaan.id">{{ bacaan.label }}</div>
						</div>
					</div>
				</div>
			</div>
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Bobot</label>
				<input type="number" name="bobot" min="1" class="input" v-model="form.bobot" :disabled="isSurvey">
			</div>
		</div>

		<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<div class="input-group xl:col-span-2">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">{{ isSurvey ? 'Indikator / Tujuan Butir' : 'Indikator' }}</label>
				<textarea name="indikator" class="input min-h-28" v-model="form.indikator" required></textarea>
			</div>
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">{{ isSurvey ? 'Dimensi Survey' : 'Dimensi' }}</label>
				<input type="text" name="dimensi" class="input" v-model="form.dimensi" :required="isSurvey">
			</div>
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Subdimensi</label>
				<input type="text" name="subdimensi" class="input" v-model="form.subdimensi">
			</div>
		</div>

		<div class="grid gap-4 md:grid-cols-2">
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kategori Profil</label>
				<input type="text" name="kategori_profil" class="input" v-model="form.kategori_profil" :placeholder="isSurvey ? 'Contoh: Disiplin diri' : 'Opsional'">
			</div>
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Arah Skor</label>
				<div class="ssd-wrap mt-1">
					<input type="hidden" name="arah_skor" :value="form.arah_skor" @change="form.arah_skor = $event.target.value">
					<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
						<span class="ssd-label">{{ form.arah_skor === 'negatif' ? 'Negatif' : 'Positif' }}</span>
						<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
					</button>
					<div class="ssd-panel">
						<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
						<div class="ssd-list">
							<div class="ssd-option" :class="form.arah_skor === 'positif' ? 'ssd-selected' : ''" data-value="positif">Positif</div>
							<div class="ssd-option" :class="form.arah_skor === 'negatif' ? 'ssd-selected' : ''" data-value="negatif">Negatif</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="input-group">
			<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">{{ isSurvey ? 'Teks Soal / Pernyataan' : 'Pertanyaan' }}</label>
			<textarea name="pertanyaan" class="input min-h-36" v-model="form.pertanyaan" required></textarea>
		</div>

		<div v-if="!isSurvey" class="input-group">
			<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Pembahasan <span class="text-textSecondary font-normal normal-case tracking-normal">(opsional — tampil di halaman hasil ujian publik)</span></label>
			<textarea name="pembahasan" class="input min-h-28" v-model="form.pembahasan"></textarea>
		</div>

		<div class="grid gap-4 md:grid-cols-[1fr_auto]">
			<div class="input-group">
				<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Gambar Soal</label>
				<input type="file" name="gambar" accept="image/*" class="input" data-image-input="utama" @change="onGambarChange($event)">
			</div>
			<div class="rounded-[24px] border border-dashed border-slate-300/80 bg-slate-50/70 p-3 text-center dark:border-slate-700 dark:bg-slate-900/50">
				<div class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Preview</div>
				<img :src="gambarPreviewUrl" alt="Preview gambar soal" class="mx-auto mt-3 max-h-32 rounded-xl" :class="gambarPreviewUrl ? '' : 'hidden'" data-image-preview="utama">
			</div>
		</div>

		<section :class="isPilihanGanda ? '' : 'hidden'" data-type-panel="pilihan_ganda">
			<div class="section-heading mb-4">
				<div>
					<h3 class="section-title">Pilihan Jawaban</h3>
					<p class="section-description">{{ isSurvey ? 'Isi empat opsi respons dan atur skor tiap pilihan untuk analisis profil.' : 'Isi empat opsi dan tentukan satu jawaban benar.' }}</p>
				</div>
			</div>
			<div class="space-y-4">
				<div v-for="(pilihan, index) in form.pilihan" :key="pilihan.kode" class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-4 dark:border-slate-800 dark:bg-slate-900/60">
					<div class="grid gap-4 lg:grid-cols-[auto_1fr_220px]" :class="isSurvey ? 'xl:grid-cols-[auto_1fr_180px_180px]' : ''">
						<div class="input-group lg:max-w-24">
							<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kode</label>
							<input type="text" readonly :name="`pilihan[${index}][kode]`" class="input bg-slate-100 dark:bg-slate-800" :value="pilihan.kode">
						</div>
						<div class="input-group">
							<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Teks Pilihan {{ pilihan.kode }}</label>
							<textarea :name="`pilihan[${index}][teks]`" class="input min-h-24" v-model="form.pilihan[index].teks"></textarea>
						</div>
						<template v-if="isSurvey">
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Nilai Survey</label>
								<input type="number" :name="`pilihan[${index}][nilai_survey]`" min="1" max="4" class="input" v-model="form.pilihan[index].nilai_survey" required>
							</div>
							<div class="input-group">
								<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Label Profil</label>
								<input type="text" :name="`pilihan[${index}][profil_label]`" class="input" v-model="form.pilihan[index].profil_label" placeholder="Contoh: Sangat sesuai">
							</div>
						</template>
						<div class="space-y-3">
							<label v-if="!isSurvey" class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950/70">
								<input type="radio" name="jawaban_benar" :value="pilihan.kode" class="h-4 w-4 text-primary" v-model="form.jawaban_benar">
								<span>Jawaban benar</span>
							</label>
							<input type="file" :name="`pilihan_gambar[${pilihan.kode}]`" accept="image/*" class="input" :data-image-input="`pilihan-${pilihan.kode}`" @change="onPilihanGambarChange(pilihan.kode, $event)">
							<img :src="pilihanPreviewUrls[pilihan.kode] ?? soal?.pilihan?.find((p) => p.kode === pilihan.kode)?.gambar_url" alt="Preview pilihan" class="max-h-24 rounded-xl" :class="(pilihanPreviewUrls[pilihan.kode] ?? soal?.pilihan?.find((p) => p.kode === pilihan.kode)?.gambar_url) ? '' : 'hidden'" :data-image-preview="`pilihan-${pilihan.kode}`">
						</div>
					</div>
				</div>
			</div>
		</section>

		<section :class="isMenjodohkan ? '' : 'hidden'" data-type-panel="menjodohkan">
			<div class="section-heading mb-4">
				<div>
					<h3 class="section-title">Pasangan Menjodohkan</h3>
					<p class="section-description">Minimal tiga pasangan kiri dan kanan.</p>
				</div>
				<button type="button" class="btn-secondary" @click="addPair">
					<i class="fa-solid fa-plus"></i>
					Tambah Pasangan
				</button>
			</div>
			<div class="space-y-3" data-pair-list>
				<div v-for="(pasangan, index) in form.pasangan" :key="index" class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-4 md:grid-cols-2 dark:border-slate-800 dark:bg-slate-900/60" data-pair-row>
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kolom Kiri</label>
						<textarea :name="`pasangan[${index}][teks_kiri]`" class="input min-h-24" v-model="form.pasangan[index].teks_kiri"></textarea>
					</div>
					<div class="input-group">
						<label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Kolom Kanan</label>
						<textarea :name="`pasangan[${index}][teks_kanan]`" class="input min-h-24" v-model="form.pasangan[index].teks_kanan"></textarea>
					</div>
					<div class="md:col-span-2">
						<button type="button" class="btn-danger px-3 py-2 text-xs" @click="removePair(index)">Hapus Baris</button>
					</div>
				</div>
			</div>
		</section>

		<div class="flex flex-wrap gap-3">
			<button class="btn-primary" type="submit" :disabled="form.processing">{{ submitLabel }}</button>
			<Link :href="cancelUrl" class="btn-secondary">Kembali</Link>
		</div>
	</form>
</template>
