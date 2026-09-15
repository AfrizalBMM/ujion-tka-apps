<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

const props = defineProps({
	tab: {
		type: String,
		required: true,
	},
	tarifJenjangs: {
		type: Array,
		default: () => [],
	},
	hasJenjangColumn: {
		type: Boolean,
		default: false,
	},
	sectionActives: {
		type: Object,
		required: true,
	},
	hero: {
		type: Object,
		required: true,
	},
	faqs: {
		type: Array,
		default: () => [],
	},
	editFaq: {
		type: Object,
		default: null,
	},
	logoUrl: {
		type: String,
		required: true,
	},
	branding: {
		type: Object,
		default: null,
	},
	heroMockups: {
		type: Array,
		default: () => [],
	},
	editHeroMockup: {
		type: Object,
		default: null,
	},
	materialTotal: {
		type: Number,
		default: 0,
	},
	questionTotal: {
		type: Number,
		default: 0,
	},
	faqTotal: {
		type: Number,
		default: 0,
	},
	pricingTotal: {
		type: Number,
		default: 0,
	},
	stats: {
		type: Object,
		default: () => ({}),
	},
	jenjangs: {
		type: Array,
		default: () => [],
	},
});

const tabs = [
	{ key: 'hero', label: 'Hero', icon: 'fa-wand-magic-sparkles' },
	{ key: 'content', label: 'Konten Landing', icon: 'fa-pen-to-square' },
	{ key: 'branding', label: 'Logo', icon: 'fa-image' },
	{ key: 'faq', label: 'FAQ', icon: 'fa-circle-question' },
	{ key: 'pricing', label: 'Pricing', icon: 'fa-tags' },
	{ key: 'stats', label: 'Statistik', icon: 'fa-chart-column' },
];

const nf = (value) => Number(value).toLocaleString('id-ID');

const heroMockupForm = useForm({
	badge: props.editHeroMockup?.badge ?? '',
	title: props.editHeroMockup?.title ?? '',
	description: props.editHeroMockup?.description ?? '',
	image: null,
	sort_order: props.editHeroMockup?.sort_order ?? 0,
	is_featured: props.editHeroMockup?.is_featured ?? false,
	is_active: props.editHeroMockup?.is_active ?? true,
});

const submitHeroMockup = () => {
	if (props.editHeroMockup) {
		heroMockupForm.post(route('superadmin.landing-settings.hero-mockups.update', props.editHeroMockup.id));
	} else {
		heroMockupForm.post(route('superadmin.landing-settings.hero-mockups.store'));
	}
};

const heroMockupFormErrors = computed(() => Object.values(heroMockupForm.errors));

const contentForm = useForm({
	kicker: props.hero.kicker ?? '',
	title: props.hero.title ?? '',
	body: props.hero.body ?? '',
	button_text: props.hero.button_text ?? '',
	button_url: props.hero.button_url ?? '',
	seo_title: props.hero.seo_title ?? '',
	seo_description: props.hero.seo_description ?? '',
});

const submitContent = () => {
	contentForm.post(route('superadmin.landing-settings.content'));
};

const logoForm = useForm({
	logo: null,
});

const submitLogo = () => {
	logoForm.post(route('superadmin.landing-settings.logo'));
};

const faqForm = useForm({
	question: props.editFaq?.question ?? '',
	answer: props.editFaq?.answer ?? '',
	sort_order: props.editFaq?.sort_order ?? 0,
	is_active: props.editFaq?.is_active ?? true,
});

const submitFaq = () => {
	if (props.editFaq) {
		faqForm.post(route('superadmin.landing-settings.faq.update', props.editFaq.id));
	} else {
		faqForm.post(route('superadmin.landing-settings.faq.store'));
	}
};

const tarifForm = useForm({
	name: '',
	jenjang: '',
	description: '',
	price: '',
	subtitle: '',
});

const tarifJenjangLabel = () =>
	tarifForm.jenjang || (props.hasJenjangColumn ? 'Pilih jenjang' : 'Jalankan migrate untuk aktifkan jenjang');

const submitTarif = () => {
	tarifForm.post(route('superadmin.tarif-jenjang.store'));
};
</script>

<template>
	<Head title="Pengaturan Landing" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
				<div>
					<h1 class="text-2xl font-bold">Pengaturan Landing</h1>
					<p class="mt-2 text-textSecondary dark:text-slate-300">Satu menu dengan beberapa tab untuk mengelola bagian landing page.</p>
				</div>
				<a :href="route('landing')" target="_blank" class="btn-secondary whitespace-nowrap">
					<i class="fa-solid fa-arrow-up-right-from-square"></i>
					Buka Landing
				</a>
			</div>

			<div class="flex flex-wrap gap-2">
				<Link
					v-for="item in tabs"
					:key="item.key"
					:href="route('superadmin.landing-settings.index', { tab: item.key })"
					:class="tab === item.key ? 'btn-primary' : 'btn-secondary'"
				>
					<i class="fa-solid" :class="item.icon"></i>
					{{ item.label }}
				</Link>
			</div>

			<div v-if="tab === 'hero'" class="grid gap-6">
				<div class="card">
					<div class="flex items-start justify-between gap-4">
						<div>
							<h2 class="text-lg font-bold">{{ editHeroMockup ? 'Edit Mockup Hero' : 'Tambah Mockup Hero' }}</h2>
							<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Upload PNG final untuk mengganti placeholder ruang mockup produk.</p>
						</div>
						<Link v-if="editHeroMockup" :href="route('superadmin.landing-settings.index', { tab: 'hero' })" class="btn-secondary px-3" title="Batal edit">
							<i class="fa-solid fa-xmark"></i>
						</Link>
					</div>

					<div v-if="heroMockupFormErrors.length > 0" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
						<div class="font-bold">Upload belum berhasil</div>
						<ul class="mt-2 list-disc space-y-1 pl-5">
							<li v-for="error in heroMockupFormErrors" :key="error">{{ error }}</li>
						</ul>
					</div>

					<form class="mt-5 space-y-4" enctype="multipart/form-data" @submit.prevent="submitHeroMockup">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Badge</label>
							<input v-model="heroMockupForm.badge" class="input mt-1 w-full" name="badge" placeholder="Contoh: Mockup 1">
						</div>

						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
							<input v-model="heroMockupForm.title" class="input mt-1 w-full" name="title" placeholder="Contoh: Dashboard guru / analytics" required>
						</div>

						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Deskripsi</label>
							<textarea v-model="heroMockupForm.description" class="input mt-1 min-h-24 w-full" name="description" placeholder="Keterangan singkat mockup"></textarea>
						</div>

						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Gambar PNG</label>
							<input class="input mt-1 w-full" type="file" name="image" accept="image/png,image/jpeg,image/webp" :required="!editHeroMockup" @input="heroMockupForm.image = $event.target.files[0]">
							<div class="mt-1 text-xs text-muted">Disarankan PNG landscape rasio 16:10 atau 4:3, maksimal 10MB.</div>
							<img v-if="editHeroMockup && editHeroMockup.image_path" :src="editHeroMockup.image_url" :alt="editHeroMockup.title" class="mt-3 h-32 w-full rounded-2xl border border-slate-200 object-cover dark:border-slate-700">
						</div>

						<div class="grid gap-4 sm:grid-cols-2">
							<div>
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
								<input v-model="heroMockupForm.sort_order" class="input mt-1 w-full" type="number" min="0" max="9999" name="sort_order">
							</div>
							<div class="space-y-3 pt-1">
								<label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
									<input v-model="heroMockupForm.is_featured" type="checkbox" name="is_featured" value="1">
									Jadikan mockup utama
								</label>
								<label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
									<input v-model="heroMockupForm.is_active" type="checkbox" name="is_active" value="1">
									Aktif tampil di landing
								</label>
							</div>
						</div>

						<div class="flex justify-end">
							<button type="submit" class="btn-primary" :disabled="heroMockupForm.processing">
								<i class="fa-solid fa-floppy-disk"></i>
								{{ editHeroMockup ? 'Simpan Perubahan' : 'Tambah Mockup' }}
							</button>
						</div>
					</form>
				</div>

				<div class="card">
					<div class="flex items-start justify-between gap-4">
						<div>
							<h2 class="text-lg font-bold">Daftar Mockup Hero</h2>
							<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Mockup aktif akan menggantikan kartu placeholder di landing page.</p>
						</div>
						<span class="badge-info">{{ nf(heroMockups.length) }} item</span>
					</div>

					<div class="mt-5 table-container">
						<table class="table-ujion min-w-[900px]">
							<thead>
								<tr>
									<th>Preview</th>
									<th>Konten</th>
									<th>Urutan</th>
									<th>Status</th>
									<th class="text-right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<template v-if="heroMockups.length > 0">
									<tr v-for="mockup in heroMockups" :key="mockup.id">
										<td>
											<img :src="mockup.image_url" :alt="mockup.title" class="h-20 w-32 rounded-xl object-cover">
										</td>
										<td>
											<div class="flex flex-wrap items-center gap-2">
												<span v-if="mockup.badge" class="badge-info">{{ mockup.badge }}</span>
												<span v-if="mockup.is_featured" class="badge-success">Utama</span>
											</div>
											<div class="mt-2 font-semibold">{{ mockup.title }}</div>
											<div v-if="mockup.description" class="mt-1 max-w-xl text-xs text-muted">{{ mockup.description_limited }}</div>
										</td>
										<td>{{ mockup.sort_order }}</td>
										<td>
											<span v-if="mockup.is_active" class="badge-success">Aktif</span>
											<span v-else class="badge-danger">Nonaktif</span>
										</td>
										<td class="text-right">
											<div class="flex justify-end gap-2">
												<Link :href="route('superadmin.landing-settings.index', { tab: 'hero', hero_mockup_id: mockup.id })" class="btn-secondary px-3" title="Edit">
													<i class="fa-solid fa-pen"></i>
												</Link>
												<form method="POST" :action="route('superadmin.landing-settings.hero-mockups.toggle', mockup.id)">
													<input type="hidden" name="_token" :value="$page.props.csrf_token">
													<button type="submit" class="btn-secondary px-3" title="Aktif / Nonaktif">
														<i class="fa-solid fa-eye"></i>
													</button>
												</form>
												<form method="POST" :action="route('superadmin.landing-settings.hero-mockups.destroy', mockup.id)">
													<input type="hidden" name="_token" :value="$page.props.csrf_token">
													<button type="submit" class="btn-danger px-3" data-confirm="Hapus mockup Hero ini?" data-confirm-title="Hapus Mockup Hero">
														<i class="fa-solid fa-trash"></i>
													</button>
												</form>
											</div>
										</td>
									</tr>
								</template>
								<tr v-else>
									<td colspan="5" class="py-10 text-center text-muted">Belum ada mockup Hero. Placeholder default masih dipakai.</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div v-if="tab === 'content'" class="card">
				<div class="flex items-start justify-between gap-4">
					<div>
						<h2 class="text-lg font-bold">Hero Section</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Mengatur teks pembuka di bagian atas landing.</p>
					</div>
					<div class="flex items-center gap-2">
						<span v-if="(sectionActives.hero ?? true) === true" class="badge-success">Aktif</span>
						<span v-else class="badge-danger">Nonaktif</span>
						<form method="POST" :action="route('superadmin.landing-settings.sections.toggle', { section: 'hero' })">
							<input type="hidden" name="_token" :value="$page.props.csrf_token">
							<button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan hero">
								<i class="fa-solid fa-power-off"></i>
							</button>
						</form>
					</div>
				</div>

				<form class="mt-5 space-y-4" @submit.prevent="submitContent">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kicker</label>
						<input v-model="contentForm.kicker" class="input mt-1 w-full" name="kicker" placeholder="Kalimat pendek di atas judul">
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
						<input v-model="contentForm.title" class="input mt-1 w-full" name="title" placeholder="Judul utama hero">
					</div>

					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Deskripsi</label>
						<textarea v-model="contentForm.body" class="input mt-1 min-h-28 w-full" name="body" placeholder="Paragraf penjelasan"></textarea>
					</div>

					<div class="grid gap-4 md:grid-cols-2">
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Teks Tombol</label>
							<input v-model="contentForm.button_text" class="input mt-1 w-full" name="button_text" placeholder="Contoh: Coba Sebagai Guru">
						</div>
						<div>
							<label class="text-xs font-bold text-textSecondary dark:text-slate-300">URL Tombol (opsional)</label>
							<input v-model="contentForm.button_url" class="input mt-1 w-full" name="button_url" placeholder="Kosongkan untuk default ke halaman daftar guru">
							<div class="mt-1 text-xs text-muted">Boleh isi URL penuh (https://...) atau path (/register/...)</div>
						</div>
					</div>

					<div class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-700/60">
						<div class="text-xs font-bold uppercase tracking-wide text-textSecondary dark:text-slate-300">SEO &amp; Meta (Google)</div>
						<div class="mt-3 space-y-4">
							<div>
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Meta Title (opsional)</label>
								<input v-model="contentForm.seo_title" class="input mt-1 w-full" name="seo_title" placeholder="Contoh: Platform Ujian TKA Online untuk Guru &amp; Sekolah" maxlength="120">
								<div class="mt-1 text-xs text-muted">Ideal 50-60 karakter. Kosongkan untuk memakai nama aplikasi + judul hero.</div>
							</div>
							<div>
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Meta Description (opsional)</label>
								<textarea v-model="contentForm.seo_description" class="input mt-1 min-h-20 w-full" name="seo_description" placeholder="Deskripsi yang tampil di hasil pencarian Google" maxlength="300"></textarea>
								<div class="mt-1 text-xs text-muted">Ideal 150-160 karakter. Kosongkan untuk memakai kicker hero.</div>
							</div>
						</div>
					</div>

					<div class="flex items-center justify-end gap-3">
						<button class="btn-primary" type="submit" :disabled="contentForm.processing">
							<i class="fa-solid fa-floppy-disk"></i>
							Simpan
						</button>
					</div>
				</form>
			</div>

			<div v-if="tab === 'branding'" class="card">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
					<div>
						<h2 class="text-lg font-bold">Logo Landing</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Logo ini dipakai di header landing.</p>
					</div>
					<div class="flex items-center gap-2">
						<template v-if="branding && branding.logo_path">
							<span v-if="branding.is_active" class="badge-success">Aktif</span>
							<span v-else class="badge-danger">Nonaktif</span>
							<form method="POST" :action="route('superadmin.landing-settings.branding.toggle')">
								<input type="hidden" name="_token" :value="$page.props.csrf_token">
								<button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan logo custom">
									<i class="fa-solid fa-power-off"></i>
								</button>
							</form>
						</template>
						<span v-else class="badge-info">Default</span>
					</div>
				</div>

				<div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center">
					<div class="h-16 w-16 overflow-hidden rounded-2xl border border-border bg-white dark:bg-slate-900">
						<img :src="logoUrl" alt="Logo" class="h-full w-full object-cover">
					</div>
					<div class="text-sm text-textSecondary dark:text-slate-300">
						<div class="font-semibold text-slate-900 dark:text-white">Preview logo saat ini</div>
						<div class="mt-1">Upload gambar untuk mengganti.</div>
					</div>
				</div>

				<form class="mt-5 space-y-4" enctype="multipart/form-data" @submit.prevent="submitLogo">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">File Logo</label>
						<input type="file" class="input mt-1 w-full" name="logo" accept="image/*" required @input="logoForm.logo = $event.target.files[0]">
						<div class="mt-1 text-xs text-muted">Disarankan PNG/SVG rasio persegi (mis. 256x256).</div>
					</div>
					<div class="flex items-center justify-end gap-3">
						<button class="btn-primary" type="submit" :disabled="logoForm.processing">
							<i class="fa-solid fa-upload"></i>
							Upload Logo
						</button>
					</div>
				</form>
			</div>

			<div v-if="tab === 'faq'" class="card">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
					<div>
						<h2 class="text-lg font-bold">FAQ Landing</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Kelola daftar pertanyaan yang tampil di section FAQ.</p>
					</div>
					<div class="flex flex-wrap items-center justify-end gap-2">
						<span v-if="(sectionActives.faq ?? true) === true" class="badge-success">Aktif</span>
						<span v-else class="badge-danger">Nonaktif</span>
						<form method="POST" :action="route('superadmin.landing-settings.sections.toggle', { section: 'faq' })">
							<input type="hidden" name="_token" :value="$page.props.csrf_token">
							<button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan section FAQ">
								<i class="fa-solid fa-power-off"></i>
							</button>
						</form>
						<Link :href="route('superadmin.landing-settings.index', { tab: 'faq' })" class="btn-secondary whitespace-nowrap">
							<i class="fa-solid fa-plus"></i>
							Tambah Baru
						</Link>
					</div>
				</div>

				<div class="mt-5 grid gap-4 lg:grid-cols-2">
					<div class="rounded-2xl border border-border bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
						<div class="text-sm font-bold text-slate-900 dark:text-white">{{ editFaq ? 'Edit FAQ' : 'Tambah FAQ' }}</div>

						<form class="mt-4 space-y-3" @submit.prevent="submitFaq">
							<div>
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Pertanyaan</label>
								<input v-model="faqForm.question" class="input mt-1 w-full" name="question" required>
							</div>
							<div>
								<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jawaban</label>
								<textarea v-model="faqForm.answer" class="input mt-1 min-h-24 w-full" name="answer" required></textarea>
							</div>
							<div class="grid grid-cols-2 gap-3">
								<div>
									<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
									<input v-model="faqForm.sort_order" type="number" min="0" class="input mt-1 w-full" name="sort_order">
								</div>
								<div class="flex items-end">
									<label class="inline-flex items-center gap-2 text-sm">
										<input v-model="faqForm.is_active" type="checkbox" name="is_active" value="1" class="rounded">
										<span class="text-textSecondary dark:text-slate-300">Aktif</span>
									</label>
								</div>
							</div>

							<div class="flex items-center justify-end gap-3">
								<Link v-if="editFaq" :href="route('superadmin.landing-settings.index', { tab: 'faq' })" class="btn-secondary">Batal</Link>
								<button class="btn-primary" type="submit" :disabled="faqForm.processing">
									<i class="fa-solid fa-floppy-disk"></i>
									Simpan
								</button>
							</div>
						</form>
					</div>

					<div class="rounded-2xl border border-border bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
						<div class="text-sm font-bold text-slate-900 dark:text-white">Daftar FAQ</div>

						<div class="mt-4 table-container">
							<table class="table-ujion min-w-full">
								<thead>
									<tr>
										<th>Pertanyaan</th>
										<th>Urut</th>
										<th>Status</th>
										<th class="text-right">Aksi</th>
									</tr>
								</thead>
								<tbody>
									<template v-if="faqs.length > 0">
										<tr v-for="faq in faqs" :key="faq.id ?? faq.question">
											<td>
												<div class="font-semibold">{{ faq.question }}</div>
												<div class="mt-1 text-xs text-muted line-clamp-2">{{ faq.answer }}</div>
											</td>
											<td class="font-semibold">{{ faq.is_model ? faq.sort_order : '-' }}</td>
											<td>
												<template v-if="faq.is_model">
													<span v-if="faq.is_active" class="badge-success">Aktif</span>
													<span v-else class="badge-danger">Nonaktif</span>
												</template>
												<span v-else class="badge-info">Default</span>
											</td>
											<td class="text-right">
												<div v-if="faq.is_model" class="flex justify-end gap-2">
													<Link :href="route('superadmin.landing-settings.index', { tab: 'faq', faq_id: faq.id })" class="btn-secondary px-3">
														<i class="fa-solid fa-pen"></i>
													</Link>
													<form method="POST" :action="route('superadmin.landing-settings.faq.toggle', faq.id)">
														<input type="hidden" name="_token" :value="$page.props.csrf_token">
														<button type="submit" class="btn-secondary px-3" title="Aktif/Nonaktif">
															<i class="fa-solid fa-eye"></i>
														</button>
													</form>
													<form method="POST" :action="route('superadmin.landing-settings.faq.destroy', faq.id)">
														<input type="hidden" name="_token" :value="$page.props.csrf_token">
														<button type="submit" class="btn-danger px-3" data-confirm="Hapus FAQ ini?" data-confirm-title="Hapus FAQ">
															<i class="fa-solid fa-trash"></i>
														</button>
													</form>
												</div>
												<span v-else class="text-xs text-muted">Jalankan migrate untuk edit</span>
											</td>
										</tr>
									</template>
									<tr v-else>
										<td colspan="4" class="py-10 text-center text-muted">Belum ada FAQ.</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<div v-if="tab === 'pricing'" class="card">
				<div>
					<h2 class="text-lg font-bold">Pricing / Tarif Jenjang</h2>
					<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tab ini memakai tabel pricing_plans yang sama dengan menu Keuangan. Tarif hanya dipakai untuk flow aktivasi guru dan tidak lagi ditampilkan di landing publik.</p>
				</div>

				<form class="mt-5 grid gap-4 md:grid-cols-2" enctype="multipart/form-data" @submit.prevent="submitTarif">
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Judul</label>
						<input v-model="tarifForm.name" class="input mt-1 w-full" name="name" placeholder="Contoh: Aktivasi Guru SD" required>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Jenjang</label>
						<div class="ssd-wrap mt-1">
							<input
								type="hidden"
								name="jenjang"
								:value="tarifForm.jenjang"
								:required="hasJenjangColumn"
								:disabled="!hasJenjangColumn"
								@change="tarifForm.jenjang = $event.target.value"
							>
							<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
								<span class="ssd-label">{{ tarifJenjangLabel() }}</span>
								<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
							</button>
							<div class="ssd-panel">
								<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
								<div class="ssd-list">
									<div class="ssd-option" :class="!tarifForm.jenjang ? 'ssd-selected' : ''" data-value="">Pilih jenjang</div>
									<div
										v-for="jenjang in jenjangs"
										:key="jenjang"
										class="ssd-option"
										:class="tarifForm.jenjang === jenjang ? 'ssd-selected' : ''"
										:data-value="jenjang"
									>
										{{ jenjang }}
									</div>
								</div>
							</div>
						</div>
						<div v-if="!hasJenjangColumn" class="mt-1 text-xs text-muted">Kolom `jenjang` belum ada di DB.</div>
					</div>
					<div class="md:col-span-2">
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Keterangan (opsional)</label>
						<textarea v-model="tarifForm.description" class="input mt-1 min-h-20 w-full" name="description" placeholder="Deskripsi singkat"></textarea>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nominal</label>
						<input v-model="tarifForm.price" class="input mt-1 w-full" name="price" placeholder="99000" required>
					</div>
					<div>
						<label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subtitle (opsional)</label>
						<input v-model="tarifForm.subtitle" class="input mt-1 w-full" name="subtitle" placeholder="Contoh: Akses akun guru / operator">
					</div>

					<div class="md:col-span-2 flex items-center justify-end">
						<button class="btn-primary" type="submit" :disabled="tarifForm.processing">
							<i class="fa-solid fa-floppy-disk"></i>
							Simpan
						</button>
					</div>
				</form>

				<div class="mt-6 table-container">
					<table class="table-ujion min-w-[980px]">
						<thead>
							<tr>
								<th>Jenjang</th>
								<th>Judul</th>
								<th>Nominal</th>
								<th>Status</th>
								<th class="text-right">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="tarifJenjangs.length > 0">
								<tr v-for="tarif in tarifJenjangs" :key="tarif.id">
									<td><span class="badge-info">{{ tarif.jenjang || '-' }}</span></td>
									<td>
										<div class="font-semibold">{{ tarif.name }}</div>
										<div v-if="tarif.subtitle" class="mt-1 text-xs text-muted">{{ tarif.subtitle }}</div>
									</td>
									<td class="font-semibold">Rp {{ nf(tarif.price) }}</td>
									<td>
										<span v-if="tarif.is_active" class="badge-success">Aktif</span>
										<span v-else class="badge-danger">Nonaktif</span>
									</td>
									<td class="text-right">
										<div class="flex justify-end gap-2">
											<form method="POST" :action="route('superadmin.tarif-jenjang.toggle-active', tarif.id)">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-secondary px-3" title="Aktif/Nonaktif">
													<i class="fa-solid fa-eye"></i>
												</button>
											</form>
											<form method="POST" :action="route('superadmin.tarif-jenjang.destroy', tarif.id)">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-danger px-3" data-confirm="Hapus tarif ini?" data-confirm-title="Hapus Tarif">
													<i class="fa-solid fa-trash"></i>
												</button>
											</form>
										</div>
									</td>
								</tr>
							</template>
							<tr v-else>
								<td colspan="5" class="py-10 text-center text-muted">Belum ada tarif.</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div v-if="tab === 'stats'" class="grid gap-4 lg:grid-cols-3">
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Total Materi</div>
					<div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ nf(materialTotal) }}</div>
				</div>
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Total Bank Soal</div>
					<div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ nf(questionTotal) }}</div>
				</div>
				<div class="card">
					<div class="text-xs font-semibold uppercase tracking-wide text-muted">Konten Landing</div>
					<div class="mt-2 text-sm text-textSecondary dark:text-slate-300">FAQ: <span class="font-bold text-slate-900 dark:text-white">{{ nf(faqTotal) }}</span></div>
					<div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Pricing plan: <span class="font-bold text-slate-900 dark:text-white">{{ nf(pricingTotal) }}</span></div>
				</div>
			</div>

			<div v-if="tab === 'stats'" class="card">
				<div class="flex items-start justify-between gap-4">
					<div>
						<h2 class="text-lg font-bold">Ringkasan Materi &amp; Soal</h2>
						<p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Ini sama dengan statistik yang tampil di landing.</p>
					</div>
					<div class="flex items-center gap-2">
						<span v-if="(sectionActives.stats ?? true) === true" class="badge-success">Aktif</span>
						<span v-else class="badge-danger">Nonaktif</span>
						<form method="POST" :action="route('superadmin.landing-settings.sections.toggle', { section: 'stats' })">
							<input type="hidden" name="_token" :value="$page.props.csrf_token">
							<button type="submit" class="btn-secondary px-3" title="Aktifkan / Nonaktifkan section statistik">
								<i class="fa-solid fa-power-off"></i>
							</button>
						</form>
					</div>
				</div>

				<div class="mt-4 space-y-8">
					<template v-if="Object.keys(stats).length > 0">
						<div v-for="(mapels, jenjang) in stats" :key="jenjang">
							<div class="mb-3 flex items-center gap-3">
								<span class="badge-info">{{ jenjang }}</span>
								<div class="font-bold text-slate-900 dark:text-white">Materi &amp; Soal</div>
							</div>
							<div class="table-container">
								<table class="table-ujion min-w-full">
									<thead>
										<tr>
											<th>Mapel</th>
											<th>Materi</th>
											<th>Soal</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(data, mapel) in mapels" :key="mapel">
											<td class="font-semibold">{{ mapel }}</td>
											<td>{{ data.materials ?? 0 }}</td>
											<td>{{ data.questions ?? 0 }}</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</template>
					<div v-else class="text-sm text-muted">Belum ada data statistik.</div>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
