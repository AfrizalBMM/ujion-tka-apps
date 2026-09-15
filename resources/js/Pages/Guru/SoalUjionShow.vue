<script setup>
import { inject } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

const props = defineProps({
	question: {
		type: Object,
		required: true,
	},
	isBookmarked: {
		type: Boolean,
		default: false,
	},
});

const route = inject('route');

const type = String(props.question.question_type ?? 'multiple_choice');
const typeLabel = {
	multiple_choice: 'Pilihan Ganda',
	essay: 'Uraian',
	matching: 'Menjodohkan',
}[type] ?? type.replaceAll('_', ' ').toUpperCase();

const answerKeyRaw = String(props.question.answer_key ?? '').trim();
const answerKey = answerKeyRaw.toUpperCase();
const options = Array.isArray(props.question.options) ? props.question.options : [];

let correctIndex = null;
if (answerKey !== '' && /^[A-Z]$/.test(answerKey)) {
	const candidate = answerKey.charCodeAt(0) - 'A'.charCodeAt(0);
	if (candidate >= 0 && candidate < options.length) {
		correctIndex = candidate;
	}
} else if (answerKey !== '' && /^[0-9]+$/.test(answerKey)) {
	const candidate = Number(answerKey) - 1;
	if (candidate >= 0 && candidate < options.length) {
		correctIndex = candidate;
	}
} else if (answerKeyRaw !== '' && options.length) {
	for (let idx = 0; idx < options.length; idx++) {
		if (String(options[idx]).trim() === answerKeyRaw) {
			correctIndex = idx;
			break;
		}
	}
}

const optionLetter = (index) => String.fromCharCode('A'.charCodeAt(0) + Number(index));

const strLimit = (value, limit) => {
	const text = String(value ?? '');
	return text.length > limit ? `${text.slice(0, limit)}...` : text;
};

const escapeHtml = (value) => String(value ?? '')
	.replaceAll('&', '&amp;')
	.replaceAll('<', '&lt;')
	.replaceAll('>', '&gt;')
	.replaceAll('"', '&quot;')
	.replaceAll("'", '&#039;');

const explanationHtml = props.question.explanation
	? escapeHtml(props.question.explanation).replaceAll('\n', '<br />')
	: '';

const toggleBookmark = () => {
	router.post(
		route(props.isBookmarked ? 'guru.soal-ujion.unbookmark' : 'guru.soal-ujion.bookmark', props.question.id),
		{},
		{ preserveScroll: true }
	);
};
</script>

<template>
	<Head title="Detail Soal Ujion" />

	<GuruLayout>
		<div class="space-y-6">
			<section class="page-hero">
				<span class="page-kicker">Soal dari Ujion</span>
				<h1 class="page-title">Detail Soal</h1>
				<p class="page-description">Soal ini hanya dapat dilihat. Untuk menggunakan, silakan pilih saat membuat paket soal.</p>
			</section>

			<div class="flex flex-wrap items-center justify-between gap-3">
				<div class="flex flex-wrap items-center gap-2">
					<Link :href="route('guru.soal-ujion.index')" class="btn-secondary inline-flex items-center gap-2">
						<i class="fa-solid fa-arrow-left"></i>
						Kembali
					</Link>
					<form v-if="isBookmarked" method="POST" :action="route('guru.soal-ujion.unbookmark', question.id)" @submit.prevent="toggleBookmark">
						<button type="submit" class="btn-secondary inline-flex items-center gap-2 border-danger/30 bg-danger/10 text-danger hover:bg-danger/15" title="Hapus Bookmark">
							<i class="fa-solid fa-bookmark"></i>
							Tersimpan
						</button>
					</form>
					<form v-else method="POST" :action="route('guru.soal-ujion.bookmark', question.id)" @submit.prevent="toggleBookmark">
						<button type="submit" class="btn-secondary inline-flex items-center gap-2" title="Bookmark Soal">
							<i class="fa-regular fa-bookmark"></i>
							Bookmark
						</button>
					</form>
				</div>
				<div class="flex flex-wrap items-center gap-2">
					<span :class="question.is_active ? 'badge-success' : 'badge-danger'">
						<i class="fa-solid" :class="question.is_active ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
						{{ question.is_active ? 'Aktif' : 'Nonaktif' }}
					</span>
					<span class="badge-info">
						<i class="fa-solid fa-database"></i>
						Soal Ujion
					</span>
					<span class="badge-warning">
						<i class="fa-solid fa-list-check"></i>
						{{ typeLabel }}
					</span>
					<span v-if="question.jenjang?.nama" class="badge">
						<i class="fa-solid fa-graduation-cap"></i>
						{{ question.jenjang.nama }}
					</span>
					<span v-if="question.material_mapel" class="badge">
						<i class="fa-solid fa-book"></i>
						{{ question.material_mapel }}
					</span>
				</div>
			</div>

			<div class="grid gap-6 lg:grid-cols-3">
				<div class="space-y-6 lg:col-span-2">
					<div v-if="question.reading_passage" class="card p-5 sm:p-6">
						<div class="flex items-center justify-between gap-3">
							<div>
								<div class="text-xs font-bold uppercase tracking-wider text-primary">Teks Bacaan</div>
								<div class="mt-1 text-sm text-textSecondary">Jika soal mengacu pada bacaan, lihat bagian ini terlebih dahulu.</div>
							</div>
							<span class="badge-info">
								<i class="fa-solid fa-scroll"></i>
								Bacaan
							</span>
						</div>
						<div class="mt-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 leading-7 text-slate-800 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-100">
							<div v-html="question.reading_passage"></div>
						</div>
					</div>

					<div class="card p-5 sm:p-6">
						<div class="flex flex-wrap items-start justify-between gap-3">
							<div class="min-w-0">
								<div class="text-xs font-bold uppercase tracking-wider text-primary">Pertanyaan</div>
								<div class="mt-1 text-sm text-textSecondary">ID: <span class="font-mono">#{{ question.id }}</span></div>
							</div>
							<div class="flex flex-wrap items-center gap-2">
								<span v-if="question.material_curriculum" class="badge">
									<i class="fa-solid fa-layer-group"></i>
									{{ question.material_curriculum }}
								</span>
							</div>
						</div>

						<div class="mt-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 leading-7 text-slate-800 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-100">
							<div v-html="question.question_text"></div>
						</div>

						<div v-if="options.length" class="mt-6">
							<div class="flex items-center justify-between gap-3">
								<div class="font-bold text-slate-900 dark:text-slate-100">Pilihan Jawaban</div>
								<div class="text-xs text-textSecondary">Klik untuk menyeleksi saat membuat paket soal (di menu paket soal).</div>
							</div>
							<div class="mt-3 space-y-2">
								<div v-for="(opt, idx) in options" :key="idx" class="flex gap-3 rounded-2xl border p-4" :class="correctIndex !== null && Number(idx) === Number(correctIndex) ? 'border-success/40 bg-success/10 dark:bg-success/15' : 'border-slate-200/80 bg-white/70 dark:border-slate-800 dark:bg-slate-950/30'">
									<div class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border text-xs font-bold" :class="correctIndex !== null && Number(idx) === Number(correctIndex) ? 'border-success/40 bg-white text-success dark:bg-slate-950 dark:text-success' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200'">
										{{ optionLetter(idx) }}
									</div>
									<div class="min-w-0 flex-1">
										<div class="whitespace-pre-wrap text-sm leading-6 text-slate-800 dark:text-slate-100">{{ opt }}</div>
										<div v-if="correctIndex !== null && Number(idx) === Number(correctIndex)" class="mt-2 inline-flex items-center gap-2 text-xs font-bold text-success">
											<i class="fa-solid fa-circle-check"></i>
											Kunci Jawaban
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="space-y-6">
					<div class="card p-5 sm:p-6">
						<div class="font-bold text-slate-900 dark:text-slate-100">Ringkasan</div>
						<div class="mt-1 text-sm text-textSecondary">Informasi cepat untuk memudahkan guru memahami konteks soal.</div>

						<div class="mt-4 space-y-3 text-sm">
							<div class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Jenjang</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ question.jenjang?.nama ?? '-' }}</div>
							</div>
							<div class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Mapel</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ question.material_mapel ?? '-' }}</div>
							</div>
							<div class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Kurikulum</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ question.material_curriculum ?? '-' }}</div>
							</div>
							<div v-if="question.material_subelement" class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Subelemen</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ question.material_subelement }}</div>
							</div>
							<div v-if="question.material_unit" class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Unit</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ question.material_unit }}</div>
							</div>
							<div v-if="question.material_sub_unit" class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Sub Unit</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ question.material_sub_unit }}</div>
							</div>
							<div class="flex items-start justify-between gap-3">
								<div class="text-textSecondary">Tipe</div>
								<div class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ typeLabel }}</div>
							</div>
						</div>
					</div>

					<div class="card p-5 sm:p-6">
						<div class="flex items-center justify-between gap-3">
							<div class="font-bold text-slate-900 dark:text-slate-100">Kunci & Pembahasan</div>
							<span v-if="question.answer_key" class="badge-success">
								<i class="fa-solid fa-key"></i>
								<template v-if="correctIndex !== null">{{ optionLetter(correctIndex) }}</template>
								<template v-else>{{ strLimit(question.answer_key, 18) }}</template>
							</span>
						</div>

						<div v-if="!question.answer_key" class="mt-3 rounded-2xl border border-dashed border-slate-200/80 bg-slate-50/80 p-4 text-sm text-textSecondary dark:border-slate-800 dark:bg-slate-950/40">
							Kunci jawaban belum tersedia pada soal ini.
						</div>

						<div v-if="question.explanation" class="mt-4 text-sm leading-6 text-slate-800 dark:text-slate-100">
							<div v-html="explanationHtml"></div>
						</div>
						<div v-else class="mt-4 text-sm text-textSecondary">Pembahasan belum tersedia.</div>
					</div>
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
