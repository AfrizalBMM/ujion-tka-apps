<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

const props = defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	canManage: {
		type: Boolean,
		required: true,
	},
	soals: {
		type: Array,
		required: true,
	},
	bankSoals: {
		type: Array,
		default: () => [],
	},
	existingIds: {
		type: Array,
		default: () => [],
	},
});

const importForm = useForm({
	global_question_ids: [],
});

const importSubmit = () => {
	importForm.post(route('guru.soal.import-ujion', [props.paket.id, props.mapel.id]));
};

const deleteSoal = (soal) => {
	router.delete(route('guru.soal.destroy', [props.paket.id, props.mapel.id, soal.id]));
};
</script>

<template>
	<Head title="Kelola Soal" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }}</span>
				<h1 class="page-title">{{ mapel.nama_label }}</h1>
				<p class="page-description">
					{{ canManage
						? (mapel.is_survey ? 'Kelola butir survey siswa dengan teks bacaan, teks soal, dan pilihan ganda profil.' : 'Kelola bank soal mapel ini dengan tipe pilihan ganda atau menjodohkan.')
						: 'Soal pada paket milik superadmin hanya dapat dilihat dari akun guru.' }}
				</p>
				<div class="page-actions">
					<template v-if="canManage">
						<Link :href="route('guru.soal.create', { paket: paket.id, mapel: mapel.id, tipe_soal: 'pilihan_ganda' })"
							class="btn-primary">Tambah PG</Link>
						<Link v-if="!mapel.is_survey" :href="route('guru.soal.create', { paket: paket.id, mapel: mapel.id, tipe_soal: 'menjodohkan' })"
							class="btn-secondary">Tambah Menjodohkan</Link>
						<Link :href="route('guru.teks-bacaan.index', [paket.id, mapel.id])" class="btn-secondary">Teks Bacaan</Link>
					</template>
				</div>
			</section>

			<section class="card">
				<div class="table-container">
					<table class="table-ujion min-w-[920px]">
						<thead>
							<tr>
								<th>No</th>
								<th>Tipe</th>
								<th>Indikator</th>
								<th>Teks Bacaan</th>
								<th>Isi Jawaban</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="soals.length > 0">
								<tr v-for="soal in soals" :key="soal.id">
									<td>{{ soal.nomor_soal }}</td>
									<td>{{ soal.tipe_label }}</td>
									<td>
										<div>{{ soal.indikator_limited }}</div>
										<div v-if="mapel.is_survey && soal.dimensi" class="mt-1 text-xs text-textSecondary">
											{{ soal.dimensi }}{{ soal.subdimensi ? ' · ' + soal.subdimensi : '' }}</div>
									</td>
									<td>{{ soal.teks_bacaan_judul }}</td>
									<td>
										{{ soal.jawaban_label }}
									</td>
									<td>
										<div v-if="canManage" class="flex flex-wrap gap-2">
											<Link :href="route('guru.soal.edit', [paket.id, mapel.id, soal.id])"
												class="btn-secondary px-3 py-2 text-xs">Edit</Link>
											<form @submit.prevent="deleteSoal(soal)">
												<button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
											</form>
										</div>
										<span v-else class="text-xs text-textSecondary">Read only</span>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="6" class="text-center text-textSecondary">Belum ada soal pada mapel ini.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</section>
			<section v-if="canManage && !mapel.is_survey" class="card mt-6">
				<h2 class="font-bold text-lg mb-2 flex items-center gap-2">
					<i class="fa-solid fa-layer-group"></i>
					Ambil Soal dari Ujion
				</h2>
				<form @submit.prevent="importSubmit">
					<div class="overflow-x-auto">
						<table class="table-ujion min-w-[800px]">
							<thead>
								<tr>
									<th></th>
									<th>Pertanyaan</th>
									<th>Mapel</th>
									<th>Kurikulum</th>
									<th>Jenjang</th>
								</tr>
							</thead>
							<tbody>
								<template v-if="bankSoals.length > 0">
									<tr v-for="gq in bankSoals" :key="gq.id">
										<td>
											<input v-if="existingIds.includes(gq.id)" type="checkbox" name="global_question_ids[]" :value="gq.id" disabled checked>
											<input v-else type="checkbox" name="global_question_ids[]" :value="gq.id" v-model="importForm.global_question_ids">
										</td>
										<td v-html="gq.question_text_limited"></td>
										<td>{{ gq.material_mapel }}</td>
										<td>{{ gq.material_curriculum }}</td>
										<td>{{ gq.jenjang_nama }}</td>
									</tr>
								</template>
								<tr v-else>
									<td colspan="5" class="text-center text-textSecondary">Tidak ada soal Ujion untuk mapel ini.</td>
								</tr>
							</tbody>
						</table>
					</div>
					<button class="btn-primary mt-4" type="submit" :disabled="importForm.processing">Import Soal Terpilih</button>
				</form>
			</section>
		</div>
	</GuruLayout>
</template>
