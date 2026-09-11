<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

defineProps({
	paket: {
		type: Object,
		required: true,
	},
	mapel: {
		type: Object,
		required: true,
	},
	soals: {
		type: Array,
		default: () => [],
	},
});
</script>

<template>
	<Head title="Kelola Soal" />

	<SuperadminLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">{{ paket.nama }}</span>
				<h1 class="page-title">{{ mapel.nama_label }}</h1>
				<p class="page-description">
					{{ mapel.soal_count }}/{{ mapel.jumlah_soal }} butir terisi, durasi {{ mapel.durasi_menit }} menit.
					{{ mapel.is_survey ? 'Komponen ini hanya memakai teks bacaan, teks soal, dan pilihan ganda profil.' : '' }}
				</p>
				<div class="page-actions">
					<Link :href="route('superadmin.soal.create', [paket.id, mapel.id, { tipe_soal: 'pilihan_ganda' }])" class="btn-primary">Tambah PG</Link>
					<Link v-if="!mapel.is_survey" :href="route('superadmin.soal.create', [paket.id, mapel.id, { tipe_soal: 'menjodohkan' }])" class="btn-secondary">Tambah Menjodohkan</Link>
					<Link :href="route('superadmin.teks-bacaan.index', [paket.id, mapel.id])" class="btn-secondary">Teks Bacaan</Link>
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
							<template v-for="soal in soals" :key="soal.id">
								<tr>
									<td>{{ soal.nomor_soal }}</td>
									<td>{{ soal.tipe_label }}</td>
									<td>
										<div>{{ soal.indikator_limited }}</div>
										<div v-if="mapel.is_survey && soal.dimensi" class="mt-1 text-xs text-textSecondary">{{ soal.dimensi }}{{ soal.subdimensi ? ' &middot; ' + soal.subdimensi : '' }}</div>
									</td>
									<td>{{ soal.teks_bacaan_judul ?? '-' }}</td>
									<td>{{ soal.is_pilihan_ganda ? soal.pilihan_count + ' pilihan' : soal.pasangan_count + ' pasangan' }}</td>
									<td>
										<div class="flex flex-wrap gap-2">
											<Link :href="route('superadmin.soal.edit', [paket.id, mapel.id, soal.id])" class="btn-secondary px-3 py-2 text-xs">Edit</Link>
											<form method="POST" :action="route('superadmin.soal.destroy', [paket.id, mapel.id, soal.id])">
												<input type="hidden" name="_token" :value="$page.props.csrf_token" />
												<input type="hidden" name="_method" value="DELETE" />
												<button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
											</form>
										</div>
									</td>
								</tr>
							</template>
							<tr v-if="soals.length === 0">
								<td colspan="6" class="text-center text-textSecondary">Belum ada soal pada mapel ini.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</section>
		</div>
	</SuperadminLayout>
</template>
