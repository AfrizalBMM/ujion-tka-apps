<script setup>
import { computed } from 'vue';

const props = defineProps({
	userJenjang: {
		type: String,
		default: null,
	},
	optionLabels: {
		type: Array,
		required: true,
	},
	modeKey: {
		type: String,
		default: 'create',
	},
	isEditMode: {
		type: Boolean,
		default: false,
	},
	questionData: {
		type: Object,
		required: true,
	},
	imagePreviewUrl: {
		type: String,
		default: null,
	},
});

const typeValue = computed(() => props.questionData.tipe ?? 'PG');
const questionText = computed(() => props.questionData.pertanyaan ?? '');
const categoryValue = computed(() => props.questionData.kategori ?? '');
const answerValue = computed(() => props.questionData.jawaban_benar ?? '');
const explanationValue = computed(() => props.questionData.pembahasan ?? '');
const statusValue = computed(() => props.questionData.status ?? 'draft');
const optionsValue = computed(() => {
	const value = props.questionData.opsi ?? ['', '', '', ''];

	return Array.isArray(value) && value.length ? value.slice(0, 5) : ['', '', '', ''];
});

const typeLabel = computed(() => ({
	PG: 'Pilihan Ganda',
	Checklist: 'Checklist',
	Singkat: 'Jawaban Singkat',
}[typeValue.value] ?? 'Pilihan Ganda'));

const optionLabel = (index) => props.optionLabels[index] ?? `O${index + 1}`;

const answerOptionCount = computed(() => Math.min(optionsValue.value.length, 5));
</script>

<template>
	<div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
		<div>
			<label class="text-xs font-bold">Jenjang</label>
			<input class="input w-full bg-slate-100" :value="userJenjang" readonly>
		</div>
		<div>
			<label class="text-xs font-bold">Kategori</label>
			<input name="kategori" class="input w-full" :value="categoryValue" required>
		</div>
		<div>
			<label class="text-xs font-bold">Tipe Soal</label>
			<div class="ssd-wrap mt-1">
				<input v-if="isEditMode" type="hidden" name="tipe" :value="typeValue" required data-edit-question-type>
				<input v-else type="hidden" name="tipe" :value="typeValue" required id="guru-personal-question-type">
				<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
					<span class="ssd-label">{{ typeLabel }}</span>
					<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
				</button>
				<div class="ssd-panel">
					<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari tipe..."></div>
					<div class="ssd-list">
						<div class="ssd-option" :class="typeValue === 'PG' ? ' ssd-selected' : ''" data-value="PG">Pilihan Ganda</div>
						<div class="ssd-option" :class="typeValue === 'Checklist' ? ' ssd-selected' : ''" data-value="Checklist">Checklist</div>
						<div class="ssd-option" :class="typeValue === 'Singkat' ? ' ssd-selected' : ''" data-value="Singkat">Jawaban Singkat</div>
					</div>
				</div>
			</div>
		</div>
		<div>
			<label class="text-xs font-bold">Pertanyaan</label>
			<textarea name="pertanyaan" class="input w-full" required>{{ questionText }}</textarea>
		</div>
		<div v-if="isEditMode" class="md:col-span-2" data-edit-objective-options>
			<div class="flex items-center justify-between gap-3">
				<label class="text-xs font-bold">Opsi Jawaban</label>
				<button type="button" class="btn-secondary px-3 py-2 text-xs" data-edit-option-add>
					<i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
				</button>
			</div>
			<div class="mt-2 grid grid-cols-1 gap-2" data-edit-option-list>
				<div v-for="(option, index) in optionsValue" :key="index" class="flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ optionLabel(index) }}</span>
					<input name="options[]" class="input border-0 bg-transparent px-0" :value="option" :placeholder="`Tulis jawaban ${optionLabel(index)}`">
				</div>
			</div>
			<p class="mt-1 text-[10px] text-muted italic">Gunakan untuk tipe `PG` atau `Checklist`.</p>
		</div>
		<div v-else class="md:col-span-2" data-objective-options>
			<div class="flex items-center justify-between gap-3">
				<label class="text-xs font-bold">Opsi Jawaban</label>
				<button type="button" class="btn-secondary px-3 py-2 text-xs" data-option-add="guru-personal">
					<i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
				</button>
			</div>
			<div class="mt-2 grid grid-cols-1 gap-2" data-option-list="guru-personal">
				<div v-for="(option, index) in optionsValue" :key="index" class="flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ optionLabel(index) }}</span>
					<input name="options[]" class="input border-0 bg-transparent px-0" :value="option" :placeholder="`Tulis jawaban ${optionLabel(index)}`">
				</div>
			</div>
			<p class="mt-1 text-[10px] text-muted italic">Gunakan untuk tipe `PG` atau `Checklist`.</p>
		</div>
		<div>
			<label class="text-xs font-bold">Jawaban Benar</label>
			<div
				class="ssd-wrap mt-1"
				:class="typeValue === 'Singkat' ? 'hidden' : ''"
				:data-edit-answer-select-ssd="isEditMode ? '' : undefined"
				:id="isEditMode ? undefined : 'guru-personal-answer-key-select-ssd'"
			>
				<input
					v-if="isEditMode"
					type="hidden"
					name="jawaban_benar"
					:value="typeValue !== 'Singkat' ? answerValue : ''"
					data-edit-answer-select
					:data-current-answer="answerValue"
				>
				<input
					v-else
					type="hidden"
					name="jawaban_benar"
					:value="typeValue !== 'Singkat' ? answerValue : ''"
					id="guru-personal-answer-key-select"
				>
				<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
					<span class="ssd-label">{{ answerValue || 'Pilih jawaban benar' }}</span>
					<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
				</button>
				<div class="ssd-panel">
					<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jawaban..."></div>
					<div class="ssd-list">
						<div class="ssd-option" :class="!answerValue ? ' ssd-selected' : ''" data-value="">Pilih jawaban benar</div>
						<div
							v-for="n in answerOptionCount"
							:key="`answer-${n - 1}`"
							class="ssd-option"
							:class="answerValue === optionLabels[n - 1] ? ' ssd-selected' : ''"
							:data-value="optionLabels[n - 1]"
						>
							{{ optionLabels[n - 1] }}
						</div>
					</div>
				</div>
			</div>
			<input
				v-if="isEditMode"
				name="jawaban_benar"
				class="input w-full hidden"
				data-edit-answer-text
				placeholder="Tulis jawaban singkat yang benar"
				:value="typeValue === 'Singkat' ? answerValue : ''"
			>
			<input
				v-else
				name="jawaban_benar"
				class="input w-full hidden"
				id="guru-personal-answer-key-text"
				placeholder="Tulis jawaban singkat yang benar"
				:value="typeValue === 'Singkat' ? answerValue : ''"
			>
			<div
				v-if="isEditMode"
				class="mt-1 text-[10px] text-muted italic"
				data-edit-answer-help
			>
				Pilih huruf jawaban yang benar sesuai opsi aktif.
			</div>
			<div
				v-else
				class="mt-1 text-[10px] text-muted italic"
				id="guru-personal-answer-key-help"
			>
				Pilih huruf jawaban yang benar sesuai opsi aktif.
			</div>
		</div>
		<div>
			<label class="text-xs font-bold">Pembahasan</label>
			<textarea name="pembahasan" class="input w-full">{{ explanationValue }}</textarea>
		</div>
		<div>
			<label class="text-xs font-bold">Gambar (opsional)</label>
			<input type="file" name="image" accept="image/*" class="input w-full" :data-image-input="modeKey">
			<div class="mt-1 text-[10px] text-muted italic">Maksimal 2 MB. Preview akan tampil sebelum dikirim.</div>
			<div class="mt-2 rounded-2xl border border-slate-200 bg-white p-3" :class="imagePreviewUrl ? '' : 'hidden'" :data-image-preview-wrap="modeKey">
				<img :src="imagePreviewUrl || ''" alt="Preview gambar" class="max-h-48 rounded-2xl object-contain" :data-image-preview="modeKey">
			</div>
			<label v-if="isEditMode && questionData.image_path" class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
				<input type="checkbox" name="remove_image" value="1">
				Hapus gambar lama
			</label>
		</div>
		<div>
			<label class="text-xs font-bold">Status</label>
			<div class="ssd-wrap mt-1">
				<input type="hidden" name="status" :value="statusValue" required>
				<button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
					<span class="ssd-label">{{ statusValue === 'terbit' ? 'Terbit' : 'Draft' }}</span>
					<i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
				</button>
				<div class="ssd-panel">
					<div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari..."></div>
					<div class="ssd-list">
						<div class="ssd-option" :class="statusValue === 'draft' ? ' ssd-selected' : ''" data-value="draft">Draft</div>
						<div class="ssd-option" :class="statusValue === 'terbit' ? ' ssd-selected' : ''" data-value="terbit">Terbit</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
