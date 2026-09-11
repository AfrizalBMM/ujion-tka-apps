<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuruLayout from '@/Layouts/GuruLayout.vue';

const props = defineProps({
	session: {
		type: Object,
		required: true,
	},
	soalItems: {
		type: Array,
		required: true,
	},
	surveyProfile: {
		type: Object,
		default: null,
	},
});

const summaryState = (item) => {
	if (!item.answered) {
		return 'bg-slate-300';
	}

	return props.session.is_survey ? 'bg-indigo-500' : (item.is_correct ? 'bg-emerald-500' : 'bg-rose-500');
};

const optionStyles = (item, opt) => {
	let bgColor = 'bg-slate-50 border-slate-100';
	let icon = '';

	if (props.session.is_survey && opt.is_chosen) {
		bgColor = 'bg-indigo-50 border-indigo-200 text-indigo-900';
		icon = '<i class="fa-solid fa-circle-check text-indigo-500 mr-2"></i>';
	} else if (opt.is_chosen && opt.is_correct) {
		bgColor = 'bg-emerald-50 border-emerald-200 text-emerald-900';
		icon = '<i class="fa-solid fa-circle-check text-emerald-500 mr-2"></i>';
	} else if (opt.is_chosen && !opt.is_correct) {
		bgColor = 'bg-rose-50 border-rose-200 text-rose-900';
		icon = '<i class="fa-solid fa-circle-xmark text-rose-500 mr-2"></i>';
	} else if (!opt.is_chosen && !props.session.is_survey && opt.is_correct) {
		bgColor = 'bg-emerald-50 border-emerald-200 ring-2 ring-emerald-500/20';
		icon = '<i class="fa-solid fa-circle-check text-emerald-500 mr-2 opacity-50"></i>';
	}

	return { bgColor, icon };
};
</script>

<template>
	<Head :title="`Detail Jawaban: ${session.nama}`" />

	<GuruLayout>
		<div class="mb-8">
			<Link :href="route('guru.results.mapel', [session.exam_id, session.mapel_paket_id])" class="mb-4 inline-flex items-center text-sm font-semibold text-textSecondary hover:text-primary">
				<i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Dashboard Mapel
			</Link>
			<div class="flex flex-wrap items-center justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold text-slate-900">Analisis Per Butir: {{ session.nama }}</h1>
					<p class="mt-1 text-sm text-textSecondary">Review detail pengerjaan siswa pada komponen {{ session.mapel_nama_label }}.</p>
				</div>
				<div class="rounded-2xl bg-white px-6 py-3 shadow-sm border border-slate-100 flex items-center gap-4">
					<div class="text-right">
						<div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">{{ session.is_survey ? 'Indeks Respons' : 'Skor Total' }}</div>
						<div class="text-2xl font-black text-indigo-600">{{ Number(session.skor).toFixed(1) }}</div>
					</div>
					<div class="h-8 w-px bg-slate-200"></div>
					<div>
						<div class="text-[10px] font-bold uppercase tracking-widest text-textSecondary">Status</div>
						<span class="inline-flex rounded-lg bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-emerald-700">Selesai</span>
					</div>
				</div>
			</div>
		</div>

		<div class="mb-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
			<h3 class="mb-4 text-lg font-bold text-slate-900">Ringkasan Jawaban</h3>
			<div class="flex flex-wrap gap-3">
				<div v-for="s in soalItems" :key="s.id" class="flex h-10 w-10 items-center justify-center rounded-xl font-bold text-white shadow-sm" :class="summaryState(s)" :title="`Nomor ${s.nomor_soal}`">
					{{ s.nomor_soal }}
				</div>
			</div>
			<div class="mt-6 flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest text-textSecondary">
				<template v-if="session.is_survey">
					<div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-indigo-500"></div> Sudah Dijawab</div>
				</template>
				<template v-else>
					<div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-emerald-500"></div> Benar</div>
					<div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-rose-500"></div> Salah</div>
				</template>
				<div class="flex items-center gap-1.5"><div class="h-3 w-3 rounded bg-slate-300"></div> Kosong</div>
			</div>
		</div>

		<div v-if="session.is_survey && surveyProfile && (surveyProfile.dimension_stats ?? []).length > 0" class="mb-8 rounded-[32px] border border-white/80 bg-white/80 p-6 shadow-card">
			<h3 class="mb-4 text-lg font-bold text-slate-900">Profil Per Dimensi</h3>
			<div class="grid gap-3 md:grid-cols-2">
				<div v-for="dimension in surveyProfile.dimension_stats" :key="dimension.dimensi" class="rounded-2xl bg-slate-50 p-4">
					<div class="font-semibold text-slate-900">{{ dimension.dimensi }}</div>
					<div class="mt-1 text-sm font-black text-indigo-600">{{ Number(dimension.score_percent).toFixed(1) }}</div>
					<div class="mt-1 text-xs text-textSecondary">{{ dimension.category }}</div>
				</div>
			</div>
		</div>

		<div class="space-y-6">
			<div v-for="s in soalItems" :key="s.id" class="rounded-[32px] border border-white/80 bg-white/80 p-8 shadow-card transition-all duration-300 hover:shadow-hover">
				<div class="mb-6 flex items-center justify-between">
					<span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-1.5 text-xs font-bold text-slate-800">
						Soal Nomor {{ s.nomor_soal }}
					</span>
					<span class="text-xs font-bold" :class="session.is_survey ? 'text-indigo-600' : (s.is_correct ? 'text-emerald-600' : 'text-rose-600')">
						{{ session.is_survey ? (s.dimensi || 'Respons survey') : (s.is_correct ? `BENAR (+${s.bobot})` : 'SALAH (+0)') }}
					</span>
				</div>

				<div class="prose prose-slate max-w-none mb-8 text-slate-900">
					<div v-html="s.pertanyaan"></div>
				</div>

				<div v-if="s.tipe_soal === 'pilihan_ganda'" class="grid gap-3 sm:grid-cols-2">
					<div v-for="opt in s.options" :key="opt.kode" class="relative flex items-center rounded-2xl border p-4 transition-all" :class="optionStyles(s, opt).bgColor">
						<span class="mr-4 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/80 font-black shadow-sm text-sm">{{ opt.kode }}</span>
						<div class="flex-1 text-sm">
							<span v-if="optionStyles(s, opt).icon" v-html="optionStyles(s, opt).icon"></span>
							<span v-html="opt.teks"></span>
							<div v-if="session.is_survey" class="mt-1 text-[11px] text-textSecondary">Nilai {{ opt.nilai_survey }} · {{ opt.profil_label }}</div>
						</div>
					</div>
				</div>
				<div v-else-if="s.tipe_soal === 'menjodohkan'" class="rounded-2xl bg-slate-50 p-6">
					<h4 class="mb-4 text-xs font-bold uppercase tracking-widest text-textSecondary">Analisis Menjodohkan</h4>
					<div class="space-y-3">
						<div v-for="pair in s.pasangan" :key="`${s.id}-${pair.teks_kiri}`" class="flex items-center justify-between rounded-xl bg-white p-3 shadow-sm border border-slate-100">
							<div class="text-sm font-semibold text-slate-800">{{ pair.teks_kiri }}</div>
							<div class="text-sm text-textSecondary">{{ pair.teks_kanan }}</div>
						</div>
					</div>
				</div>

				<div v-if="s.indikator" class="mt-8 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
					<div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-indigo-700">
						<i class="fa-solid fa-lightbulb"></i> {{ session.is_survey ? 'Catatan Butir / Indikator' : 'Pembahasan / Indikator' }}
					</div>
					<div class="text-sm text-slate-700 prose prose-indigo">
						<div v-html="s.indikator"></div>
					</div>
				</div>
			</div>
		</div>
	</GuruLayout>
</template>
