<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
	question: { type: Object, required: true },
});

const emit = defineEmits(['edit']);

const page = usePage();
const optionLabels = Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i));

const showReadingPassage = ref(false);
const showExplanation = ref(false);

const typeBadge = (question) => (question.question_type === 'matching' ? 'Menjodohkan' : String(question.question_type).replaceAll('_', ' '));

const onEdit = () => {
	emit('edit', props.question);
};
</script>

<template>
	<div class="rounded-card border border-border bg-white p-4 transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900">
		<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
			<div class="flex-1">
				<div class="mb-2 flex flex-wrap items-center gap-3">
					<span class="badge-info text-[10px] font-bold uppercase tracking-wider">
						{{ typeBadge(question) }}
					</span>
					<span v-if="question.jenjang" class="badge-primary bg-indigo-100 text-indigo-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full">
						{{ question.jenjang.nama }}
					</span>
					<span v-if="question.material" class="flex items-center gap-1 text-[10px] text-muted">
						<i class="fa-solid fa-book text-[8px]"></i> {{ question.material.mapel }} | {{ question.material.sub_unit }}
					</span>
					<span v-else-if="question.material_sub_unit" class="flex items-center gap-1 text-[10px] text-muted">
						<i class="fa-solid fa-book text-[8px]"></i> {{ question.material_mapel }} | {{ question.material_sub_unit }}
					</span>
					<span v-if="question.is_active" class="text-[10px] font-bold text-green-500">ACTIVE</span>
					<span v-else class="text-[10px] font-bold text-slate-400">DRAFT</span>
				</div>

				<div class="font-medium leading-relaxed text-slate-800 dark:text-slate-200">
					{{ question.question_text }}
				</div>

				<div v-if="question.reading_passage" class="mt-2">
					<button type="button"
						class="flex items-center gap-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
						@click="showReadingPassage = !showReadingPassage">
						<i class="fa-solid fa-book-open"></i> Lihat Teks Bacaan
						<i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="showReadingPassage ? 'rotate-180' : ''"></i>
					</button>
					<div v-show="showReadingPassage" class="mt-2">
						<div class="rounded-xl bg-blue-50/70 p-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-300">
							{{ question.reading_passage }}
						</div>
					</div>
				</div>

				<div v-if="question.options && question.question_type !== 'matching'" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
					<div v-for="(opt, idx) in question.options" :key="idx"
						class="rounded border p-2 text-xs dark:border-slate-700"
						:class="question.answer_key == opt ? 'border-emerald-200 bg-emerald-100/60 font-bold text-emerald-900 ring-1 ring-emerald-300 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-slate-100 bg-slate-50 dark:bg-slate-800'">
						<span class="mr-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/80 text-[10px] font-bold text-slate-700 dark:bg-slate-900/80 dark:text-slate-200">{{ optionLabels[idx] ?? `O${idx + 1}` }}</span>
						{{ opt }}
					</div>
				</div>

				<div v-if="question.answer_key" class="mt-3 text-xs">
					<span class="font-bold text-blue-600">Kunci:</span> {{ question.answer_key }}
				</div>

				<div v-if="question.explanation" class="mt-2 text-xs">
					<button type="button"
						class="flex items-center gap-2 font-semibold text-emerald-600 hover:text-emerald-700"
						@click="showExplanation = !showExplanation">
						<i class="fa-solid fa-lightbulb"></i> Lihat Pembahasan
						<i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="showExplanation ? 'rotate-180' : ''"></i>
					</button>
					<div v-show="showExplanation" class="mt-2">
						<div class="rounded-xl bg-emerald-50/70 p-3 italic leading-relaxed text-slate-700 dark:bg-emerald-900/10 dark:text-slate-300">
							{{ question.explanation }}
						</div>
					</div>
				</div>
			</div>

			<div class="flex flex-row gap-2 lg:flex-col">
				<form method="POST" :action="route('superadmin.global-questions.destroy', question.id)">
					<input type="hidden" name="_token" :value="page.props.csrf_token" />
					<button class="btn-danger p-2" type="submit" data-confirm="Hapus soal ini dari bank soal?" title="Hapus">
						<i class="fa-solid fa-trash-can"></i>
					</button>
				</form>

				<button
					class="btn-secondary p-2"
					type="button"
					title="Edit"
					@click="onEdit"
				>
					<i class="fa-solid fa-pen-to-square"></i>
				</button>
			</div>
		</div>
	</div>
</template>
