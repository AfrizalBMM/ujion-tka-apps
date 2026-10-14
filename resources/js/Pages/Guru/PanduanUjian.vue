<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';
import { copyTextToClipboard } from '@/utils/copy-text';

defineProps({
	exams: {
		type: Array,
		required: true,
	},
	loginUrl: {
		type: String,
		required: true,
	},
});

const copied = ref(null);

const copy = async (value, key) => {
	if (!value) return;

	try {
		await copyTextToClipboard(value);
		copied.value = key;

		window.setTimeout(() => {
			copied.value = null;
		}, 2000);
	} catch (error) {
		console.error('Gagal menyalin.', error);
	}
};

const langkah = [
	{
		ikon: 'fa-solid fa-layer-group',
		judul: '1. Siapkan paket soal',
		teks: 'Buka menu Soal, pilih paket, lalu pastikan setiap mata pelajaran sudah berisi soal. Paket tanpa soal tidak akan bisa dikerjakan siswa.',
	},
	{
		ikon: 'fa-solid fa-key',
		judul: '2. Ambil token ujian',
		teks: 'Setelah paket siap, buka menu Ujian. Setiap mata pelajaran punya satu token unik. Token inilah yang menjadi "kunci masuk" bagi siswa.',
	},
	{
		ikon: 'fa-solid fa-share-nodes',
		judul: '3. Bagikan token & link ke siswa',
		teks: 'Salin token dan link di bawah, lalu kirim ke siswa lewat WhatsApp, grup kelas, atau cara lain yang biasa Anda pakai.',
	},
	{
		ikon: 'fa-solid fa-right-to-bracket',
		judul: '4. Siswa masuk & mengerjakan',
		teks: 'Siswa membuka link, memasukkan token, mengisi identitas, lalu mulai mengerjakan. Pastikan siswa mengerjakan sampai selesai agar nilainya tersimpan.',
	},
	{
		ikon: 'fa-solid fa-chart-line',
		judul: '5. Pantau hasil',
		teks: 'Hasil dan pembahasan bisa dilihat di menu Hasil / Analisis Siswa. Di menu Ujian ada juga Riwayat Simulasi bila Anda ingin mencoba sendiri dulu.',
	},
];
</script>

<template>
	<Head title="Panduan Ujian untuk Siswa" />

	<GuruLayout>
		<div class="space-y-6">
			<div>
				<h1 class="text-2xl font-bold">Panduan Ujian untuk Siswa</h1>
				<p class="mt-2 text-textSecondary dark:text-slate-300">
					Halaman ini menjelaskan cara meneruskan ujian kepada siswa, dari menyiapkan paket sampai siswa mengerjakan
					dan Anda melihat hasilnya.
				</p>
			</div>

			<div class="card p-4 border-l-4 border-primary">
				<div class="flex items-start gap-3">
					<i class="fa-solid fa-circle-info text-primary text-lg mt-0.5"></i>
					<p class="text-sm text-textSecondary dark:text-slate-300">
						<b class="text-textPrimary dark:text-slate-100">Ujian dikerjakan oleh siswa, bukan oleh guru.</b>
						Guru bertugas menyiapkan soal dan membagikan token. Menu
						<b>Simulasi Ujian</b> hanya untuk Anda mencoba alur siswa sendiri sebelum dipakai ke siswa.
					</p>
				</div>
			</div>

			<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
				<div v-for="l in langkah" :key="l.judul" class="card p-5 space-y-2">
					<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
						<i :class="l.ikon"></i>
					</div>
					<div class="font-semibold text-textPrimary dark:text-slate-100">{{ l.judul }}</div>
					<p class="text-sm text-textSecondary dark:text-slate-300">{{ l.teks }}</p>
				</div>
			</div>

			<div class="card p-5 space-y-3">
				<h2 class="font-semibold">Alamat untuk Siswa</h2>
				<p class="text-sm text-textSecondary dark:text-slate-300">
					Bagikan alamat ini ke siswa. Siswa memasukkan token pada halaman tersebut untuk mulai mengerjakan.
				</p>
				<div class="flex flex-wrap items-center gap-2">
					<code
						class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-900"
					>
						{{ loginUrl }}
					</code>
					<button
						type="button"
						class="btn-secondary px-3 py-2 text-sm"
						@click="copy(loginUrl, 'login-url')"
					>
						<i :class="copied === 'login-url' ? 'fa-solid fa-check text-emerald-600' : 'fa-regular fa-copy'"></i>
						{{ copied === 'login-url' ? 'Tersalin' : 'Salin alamat' }}
					</button>
				</div>
			</div>

			<div class="card p-5 space-y-4">
				<div>
					<h2 class="font-semibold">Token Ujian per Mata Pelajaran</h2>
					<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">
						Salin token di bawah dan kirim ke siswa. Satu token berlaku untuk satu mata pelajaran.
					</p>
				</div>

				<div v-if="exams.length === 0" class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-textSecondary dark:border-slate-700 dark:text-slate-400">
					Belum ada ujian yang terbit untuk jenjang Anda. Hubungi admin bila ujian seharusnya sudah tersedia.
				</div>

				<div v-for="exam in exams" :key="exam.id" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
					<div class="flex flex-wrap items-baseline justify-between gap-2">
						<div class="font-semibold text-textPrimary dark:text-slate-100">{{ exam.judul }}</div>
						<div class="text-xs text-textSecondary dark:text-slate-400">{{ exam.paket_nama }} · {{ exam.tanggal_terbit }}</div>
					</div>

					<div v-if="exam.tokens.length === 0" class="mt-3 text-sm text-amber-600 dark:text-amber-400">
						<i class="fa-solid fa-triangle-exclamation mr-1"></i>
						Belum ada token untuk ujian ini. Hubungi admin.
					</div>

					<div v-else class="mt-3 space-y-2">
						<div
							v-for="mt in exam.tokens"
							:key="mt.id"
							class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-900"
						>
							<span class="text-sm font-semibold text-textSecondary dark:text-slate-300">{{ mt.mapel_label }}</span>
							<div class="flex items-center gap-2">
								<code class="rounded-md border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-sm font-bold tracking-widest text-primary">
									{{ mt.token }}
								</code>
								<button
									type="button"
									class="btn-secondary px-2 py-1 text-xs"
									@click="copy(mt.token, `token-${mt.id}`)"
								>
									<i :class="copied === `token-${mt.id}` ? 'fa-solid fa-check text-emerald-600' : 'fa-regular fa-copy'"></i>
									{{ copied === `token-${mt.id}` ? 'Tersalin' : 'Salin token' }}
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="card p-5 space-y-2">
				<h2 class="font-semibold">Contoh pesan untuk siswa</h2>
				<p class="text-sm text-textSecondary dark:text-slate-300">
					Salin contoh di bawah, ganti bagian dalam kurung siku sesuai ujian Anda, lalu kirim ke siswa.
				</p>
				<pre class="overflow-x-auto rounded-xl bg-slate-900 p-4 text-xs leading-6 text-slate-100">{{ `Halo, ujian TKA akan dilaksanakan.\n\n1. Buka: ${loginUrl}\n2. Masukkan token: [TOKEN]\n3. Isi identitas dengan benar.\n4. Kerjakan sampai selesai, lalu kirim.\n\nSelamat mengerjakan!` }}</pre>
			</div>

			<div class="flex flex-wrap gap-3">
				<Link :href="route('guru.exams')" class="btn-primary">
					<i class="fa-solid fa-file-pen"></i>
					Buka Menu Ujian
				</Link>
				<Link :href="route('guru.guide')" class="btn-secondary">
					<i class="fa-solid fa-circle-info"></i>
					Panduan Umum
				</Link>
			</div>
		</div>
	</GuruLayout>
</template>
