<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
	jenjangOptions: {
		type: Array,
		default: () => ['SD', 'SMP', 'SMA'],
	},
	trialDays: {
		type: Number,
		default: 7,
	},
	defaults: {
		type: Object,
		default: () => ({}),
	},
});

const page = usePage();

// Multi-step state
const currentStep = ref(1);
const totalSteps = 2;

const form = useForm({
	name: props.defaults?.name || '',
	jenjang: props.defaults?.jenjang || '',
	satuan_pendidikan: props.defaults?.satuan_pendidikan || '',
	no_wa: props.defaults?.no_wa || '',
	avatar: null,
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0 || Object.keys(page.props.errors || {}).length > 0);

const fieldError = (field) => form.errors[field] || page.props.errors?.[field] || null;

const fieldClass = (field) =>
	fieldError(field)
		? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/40 dark:bg-rose-500/10'
		: 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80';

// Avatar preview
const avatarPreviewUrl = ref(null);
let objectUrl = null;

const onAvatarChange = (event) => {
	const file = event.target.files?.[0] || null;

	if (!file) {
		form.avatar = null;
		avatarPreviewUrl.value = null;
		return;
	}

	form.avatar = file;

	if (objectUrl) {
		URL.revokeObjectURL(objectUrl);
	}

	objectUrl = URL.createObjectURL(file);
	avatarPreviewUrl.value = objectUrl;
};

onBeforeUnmount(() => {
	if (objectUrl) {
		URL.revokeObjectURL(objectUrl);
	}
});

// Step validation
const step1Valid = computed(() =>
	form.name.trim().length > 0 &&
	form.jenjang.length > 0
);

const step2Valid = computed(() =>
	form.satuan_pendidikan.trim().length > 0 &&
	form.no_wa.trim().length > 0
);

const canProceed = computed(() => currentStep.value === 1 ? step1Valid.value : step2Valid.value);

const nextStep = () => {
	if (step1Valid.value && currentStep.value < totalSteps) {
		currentStep.value++;
	}
};

const prevStep = () => {
	if (currentStep.value > 1) {
		currentStep.value--;
	}
};

const submit = () => {
	// ASSUMPTION: route('guru.trial.profile.complete') — POST endpoint
	// Expects: name, jenjang, satuan_pendidikan, no_wa, avatar (file, optional)
	// On success: activates trial, redirects to guru.dashboard with flash banner
	form.post(route('guru.trial.profile.complete'), {
		forceFormData: true,
		preserveScroll: true,
	});
};

// Progress indicator
const progressPercent = computed(() => Math.round((currentStep.value / totalSteps) * 100));
</script>

<template>
	<Head title="Lengkapi Profil — Trial Aktif" />

	<GuestLayout hide-showcase>
		<div class="w-full max-w-lg space-y-6">
			<!-- Header -->
			<div class="text-center">
				<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-primary text-white shadow-glow">
					<i class="fa-solid fa-user-gear text-2xl"></i>
				</div>
				<h1 class="text-2xl font-bold text-slate-900">Lengkapi Profil Anda</h1>
				<p class="mt-2 text-sm text-textSecondary">
					Selesaikan profil untuk mengaktifkan trial <span class="font-bold text-primary">{{ trialDays }} hari</span> gratis.
				</p>
			</div>

			<!-- Progress Bar -->
			<div class="flex items-center gap-2">
				<div v-for="step in totalSteps" :key="step"
					class="flex-1 rounded-full transition-all duration-300"
					:class="currentStep >= step ? 'bg-primary h-1.5' : 'bg-slate-200 h-1.5'">
				</div>
			</div>
			<div class="flex items-center justify-between text-xs font-semibold text-textSecondary">
				<span :class="currentStep >= 1 ? 'text-primary' : ''">Langkah 1: Identitas</span>
				<span :class="currentStep >= 2 ? 'text-primary' : ''">Langkah 2: Sekolah</span>
			</div>

			<!-- Error banner -->
			<div v-if="hasErrors" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
				<div class="flex items-start gap-2">
					<i class="fa-solid fa-circle-exclamation mt-0.5"></i>
					<span>Ada beberapa data yang perlu diperiksa kembali. Lihat detail di bawah.</span>
				</div>
			</div>

			<!-- Form -->
			<form class="rounded-[28px] border border-white/80 bg-white/90 p-6 shadow-card space-y-6 sm:p-8" @submit.prevent="submit">
				<!-- Step 1: Identitas -->
				<div v-show="currentStep === 1" class="space-y-5">
					<div class="space-y-2">
						<label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
							Nama Lengkap <span class="text-rose-500">*</span>
						</label>
						<input
							id="name"
							v-model="form.name"
							type="text"
							required
							:class="fieldClass('name')"
							class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
							placeholder="Masukkan nama lengkap"
						>
						<p v-if="fieldError('name')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('name') }}</p>
					</div>

					<div class="space-y-2">
						<label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
							Jenjang <span class="text-rose-500">*</span>
						</label>
						<div class="grid grid-cols-3 gap-3">
							<label v-for="jenjang in jenjangOptions" :key="jenjang"
								class="flex cursor-pointer items-center justify-center rounded-2xl border-2 px-4 py-3 text-sm font-bold transition-all"
								:class="form.jenjang === jenjang
									? 'border-primary bg-primary/5 text-primary'
									: 'border-slate-200 bg-slate-50/50 text-slate-600 hover:border-slate-300'">
								<input
									type="radio"
									v-model="form.jenjang"
									:value="jenjang"
									class="sr-only"
								>
								{{ jenjang }}
							</label>
						</div>
						<p v-if="fieldError('jenjang')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('jenjang') }}</p>
					</div>

					<!-- Next button -->
					<button
						type="button"
						@click="nextStep"
						:disabled="!step1Valid"
						class="btn-primary w-full justify-center py-3 font-bold"
						:class="!step1Valid ? 'opacity-50 cursor-not-allowed' : ''"
					>
						Lanjut
						<i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
					</button>
				</div>

				<!-- Step 2: Sekolah & Kontak -->
				<div v-show="currentStep === 2" class="space-y-5">
					<div class="space-y-2">
						<label for="satuan_pendidikan" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
							Satuan Pendidikan (Nama Sekolah) <span class="text-rose-500">*</span>
						</label>
						<input
							id="satuan_pendidikan"
							v-model="form.satuan_pendidikan"
							type="text"
							required
							:class="fieldClass('satuan_pendidikan')"
							class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
							placeholder="Contoh: SMP Negeri 1 Jakarta"
						>
						<p v-if="fieldError('satuan_pendidikan')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('satuan_pendidikan') }}</p>
					</div>

					<div class="space-y-2">
						<label for="no_wa" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
							Nomor WhatsApp <span class="text-rose-500">*</span>
						</label>
						<div class="relative">
							<i class="fa-brands fa-whatsapp absolute left-4 top-1/2 -translate-y-1/2 text-lg text-emerald-500"></i>
							<input
								id="no_wa"
								v-model="form.no_wa"
								type="tel"
								required
								:class="fieldClass('no_wa')"
								class="w-full rounded-2xl border py-3 pl-12 pr-4 text-sm text-slate-800 transition focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/10 dark:bg-slate-900 dark:text-slate-100"
								placeholder="08xxxxxxxxxx"
							>
						</div>
						<p v-if="fieldError('no_wa')" class="text-sm text-rose-600 dark:text-rose-300">{{ fieldError('no_wa') }}</p>
						<p class="text-xs text-textSecondary">Nomor ini digunakan untuk login dan komunikasi dengan admin.</p>
					</div>

					<!-- Foto Profil (optional) -->
					<div class="space-y-2">
						<label for="avatar" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
							Foto Profil <span class="text-textSecondary font-normal">(opsional)</span>
						</label>
						<div class="flex items-center gap-4">
							<div v-if="avatarPreviewUrl" class="relative">
								<img :src="avatarPreviewUrl" alt="Preview" class="h-16 w-16 rounded-2xl border border-white object-cover shadow-md dark:border-slate-700">
							</div>
							<div v-else class="flex h-16 w-16 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
								<i class="fa-solid fa-camera text-slate-400"></i>
							</div>
							<div class="flex-1">
								<input
									id="avatar"
									type="file"
									accept="image/*"
									class="block w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-teal-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:file:bg-teal-500"
									@change="onAvatarChange"
								>
								<p class="mt-1 text-xs text-textSecondary">JPG, PNG, atau WEBP. Maks 2 MB.</p>
								<p v-if="fieldError('avatar')" class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ fieldError('avatar') }}</p>
							</div>
						</div>
					</div>

					<!-- Actions -->
					<div class="flex gap-3">
						<button type="button" class="btn-secondary flex-1 justify-center py-3 font-bold" @click="prevStep">
							<i class="fa-solid fa-arrow-left mr-2 text-xs"></i>
							Kembali
						</button>
						<button type="submit" :disabled="form.processing" class="btn-primary flex-1 justify-center py-3 font-bold">
							<span v-if="form.processing" class="flex items-center gap-2">
								<i class="fa-solid fa-spinner fa-spin"></i>
								Menyimpan...
							</span>
							<span v-else class="flex items-center gap-2">
								<i class="fa-solid fa-rocket"></i>
								Aktifkan Trial
							</span>
						</button>
					</div>
				</div>
			</form>

			<!-- Footer note -->
			<p class="text-center text-xs text-textSecondary">
				Sudah punya akun?
				<Link :href="route('login')" class="font-semibold text-primary hover:text-primaryHover">Login di sini</Link>
			</p>
		</div>
	</GuestLayout>
</template>
