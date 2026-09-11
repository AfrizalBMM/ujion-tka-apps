<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
	session: {
		type: Object,
		required: true,
	},
	token: {
		type: Object,
		required: true,
	},
	packages: {
		type: Array,
		required: true,
	},
	attemptsByPackageId: {
		type: Object,
		required: true,
	},
	telaahQuestions: {
		type: Array,
		required: true,
	},
	telaahAnswersByQuestionId: {
		type: Object,
		required: true,
	},
});

const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));
const optionLabel = (idx) => optionLabels[idx] ?? `O${idx + 1}`;

const telaahAnswerCount = computed(() => Object.keys(props.telaahAnswersByQuestionId).length);
const finishedPackageCount = computed(
	() => Object.values(props.attemptsByPackageId).filter((attempt) => attempt.status === 'selesai').length
);

const telaahRows = computed(() =>
	props.telaahQuestions.map((row) => {
		const q = row.globalQuestion;
		const answer = q ? (props.telaahAnswersByQuestionId[q.id] ?? null) : null;
		return { row, q, answer, isCorrect: answer ? answer.is_correct : null };
	})
);

const telaahJawaban = reactive({});
Object.entries(props.telaahAnswersByQuestionId).forEach(([questionId, answer]) => {
	telaahJawaban[questionId] = answer.jawaban ?? '';
});

const telaahForm = useForm({ jawaban: '' });

const submitTelaah = (q) => {
	telaahForm.jawaban = telaahJawaban[q.id] ?? '';
	telaahForm.post(route('materi.telaah.submit', q.id));
};

const packageRows = computed(() =>
	props.packages.map((pkg) => {
		const attempt = props.attemptsByPackageId[pkg.id] ?? null;
		return { pkg, attempt, done: !!(attempt && attempt.status === 'selesai') };
	})
);
</script>

<template>
	<Head title="Latihan Materi — Ujion" />

	<GuestLayout wide hide-showcase>
		<div class="w-full space-y-6">
			<div class="rounded-3xl border border-border bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
					<div>
						<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Latihan Materi</div>
						<h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ token.material?.sub_unit ?? '-' }}</h1>
						<p class="mt-1 text-sm text-textSecondary">
							{{ token.material?.subelement ?? '-' }} &middot; {{ token.material?.unit ?? '-' }}
						</p>
						<p class="mt-2 text-sm text-textSecondary">Peserta: <span class="font-semibold">{{ session.nama }}</span></p>
					</div>
					<div class="text-right">
						<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Token</div>
						<div class="mt-1">
							<code class="rounded bg-indigo-50 px-3 py-2 text-lg font-black text-indigo-700">{{ token.token }}</code>
						</div>
						<div class="mt-2 text-xs text-textSecondary">Status: <span class="font-semibold">{{ session.status }}</span></div>
					</div>
				</div>
			</div>

			<div class="grid gap-4 sm:grid-cols-3">
				<div class="rounded-2xl border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
					<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Telaah</div>
					<div class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">{{ telaahAnswerCount }}/{{ telaahQuestions.length }}</div>
					<div class="mt-1 text-sm text-textSecondary">Jawaban tersimpan</div>
				</div>
				<div class="rounded-2xl border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
					<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Paket</div>
					<div class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">{{ finishedPackageCount }}/{{ packages.length }}</div>
					<div class="mt-1 text-sm text-textSecondary">Paket selesai</div>
				</div>
				<div class="rounded-2xl border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
					<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Status</div>
					<div class="mt-2 text-2xl font-black capitalize text-slate-900 dark:text-slate-100">{{ session.status }}</div>
					<div class="mt-1 text-sm text-textSecondary">Progres latihan</div>
				</div>
			</div>

			<section class="space-y-4">
				<div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
					<div>
						<h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Telaah Soal</h2>
						<p class="mt-1 text-sm text-textSecondary">Jawaban langsung dinilai dan pembahasan tampil setelah menjawab.</p>
					</div>
					<span class="badge-info w-fit">{{ telaahQuestions.length }} soal</span>
				</div>

				<div class="grid gap-4 lg:grid-cols-2">
					<template v-if="telaahRows.length > 0">
						<div v-for="item in telaahRows" :key="item.row.id" class="rounded-2xl border border-border bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
							<div class="flex items-center justify-between gap-3">
								<div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Telaah {{ item.row.urutan }}</div>
								<span v-if="item.answer" :class="item.isCorrect ? 'badge-success' : 'badge-warning'">
									{{ item.isCorrect ? 'Benar' : 'Belum tepat' }}
								</span>
								<span v-else class="badge-secondary">Belum dijawab</span>
							</div>

							<div v-if="item.q?.reading_passage" class="mt-3 rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300 whitespace-pre-line">
								{{ item.q.reading_passage }}
							</div>

							<div class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-200">
								{{ item.q?.question_text }}
							</div>

							<form v-if="Array.isArray(item.q?.options) && item.q.options.length" class="mt-3 space-y-3" @submit.prevent="submitTelaah(item.q)">
								<div class="space-y-2">
									<label v-for="(opt, idx) in item.q.options" :key="idx" class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
										<input v-model="telaahJawaban[item.q.id]" type="radio" name="jawaban" :value="opt" class="mt-1" required>
										<div>
											<div class="font-semibold text-slate-900 dark:text-slate-100">{{ optionLabel(idx) }}</div>
											<div class="text-slate-700 dark:text-slate-300">{{ opt }}</div>
										</div>
									</label>
								</div>
								<button type="submit" class="btn-primary" :disabled="telaahForm.processing">Cek Jawaban</button>
							</form>
							<div v-else class="mt-3 text-sm text-textSecondary">Pilihan jawaban belum tersedia.</div>

							<div v-if="item.answer && item.q?.explanation" class="mt-3 rounded-xl bg-emerald-50/70 p-3 text-sm text-slate-700 dark:bg-emerald-900/10 dark:text-slate-300 whitespace-pre-line">
								<div class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">Pembahasan</div>
								{{ item.q.explanation }}
							</div>
						</div>
					</template>
					<div v-else class="rounded-2xl border border-border bg-white p-5 text-sm text-textSecondary dark:border-slate-800 dark:bg-slate-900">
						Telaah belum diset oleh admin untuk materi ini.
					</div>
				</div>
			</section>

			<section class="space-y-4">
				<div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
					<div>
						<h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Paket Latihan</h2>
						<p class="mt-1 text-sm text-textSecondary">Tiap paket hanya bisa dikumpulkan sekali.</p>
					</div>
					<span class="badge-info w-fit">{{ packages.length }}/3</span>
				</div>

				<div class="grid gap-4 md:grid-cols-3">
					<div v-for="row in packageRows" :key="row.pkg.id" class="flex min-h-[180px] flex-col justify-between rounded-2xl border border-border bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
						<div>
							<div class="flex items-center justify-between gap-3">
								<div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Paket</div>
								<span :class="row.done ? 'badge-success' : (row.attempt ? 'badge-warning' : 'badge-secondary')">
									{{ row.done ? 'Selesai' : (row.attempt ? 'Proses' : 'Baru') }}
								</span>
							</div>
							<div class="mt-4 text-3xl font-black text-slate-900 dark:text-slate-100">{{ row.pkg.paket_no }}</div>
							<div v-if="row.done" class="mt-2 text-sm text-textSecondary">Skor: <span class="font-semibold">{{ row.attempt.skor }}</span></div>
							<div v-else-if="row.attempt" class="mt-2 text-sm text-textSecondary">Sedang dikerjakan</div>
							<div v-else class="mt-2 text-sm text-textSecondary">Belum dimulai</div>
						</div>
						<div class="mt-5">
							<button v-if="row.done" type="button" class="btn-secondary w-full justify-center" disabled>Sudah Selesai</button>
							<Link v-else class="btn-primary w-full justify-center" :href="route('materi.paket.show', { paketNo: row.pkg.paket_no })">Kerjakan</Link>
						</div>
					</div>
				</div>
			</section>

			<div class="text-center text-xs text-textSecondary">
				Jika Anda salah token, kembali ke <Link class="font-semibold text-indigo-700 hover:underline" :href="route('materi.login')">halaman login latihan</Link>.
			</div>
		</div>
	</GuestLayout>
</template>
