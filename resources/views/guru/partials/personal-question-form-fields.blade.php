@php
    $questionData = $questionData ?? [];
    $modeKey = $modeKey ?? 'create';
    $isEditMode = $isEditMode ?? false;
    $imagePreviewUrl = $imagePreviewUrl ?? null;

    $typeValue = $questionData['tipe'] ?? 'PG';
    $questionText = $questionData['pertanyaan'] ?? '';
    $categoryValue = $questionData['kategori'] ?? '';
    $answerValue = $questionData['jawaban_benar'] ?? '';
    $explanationValue = $questionData['pembahasan'] ?? '';
    $statusValue = $questionData['status'] ?? 'draft';
    $optionsValue = $questionData['opsi'] ?? ['', '', '', ''];
    $optionsValue = is_array($optionsValue) && count($optionsValue) ? array_slice($optionsValue, 0, 5) : ['', '', '', ''];
@endphp

<div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
    <div>
        <label class="text-xs font-bold">Jenjang</label>
        <input class="input w-full bg-slate-100" value="{{ $user->jenjang }}" readonly>
    </div>
    <div>
        <label class="text-xs font-bold">Kategori</label>
        <input name="kategori" class="input w-full" value="{{ $categoryValue }}" required>
    </div>
    <div>
        <label class="text-xs font-bold">Tipe Soal</label>
        <select
            name="tipe"
            class="input w-full"
            @if($isEditMode)
                data-edit-question-type
            @else
                id="guru-personal-question-type"
            @endif
            required>
            <option value="PG" @selected($typeValue === 'PG')>Pilihan Ganda</option>
            <option value="Checklist" @selected($typeValue === 'Checklist')>Checklist</option>
            <option value="Singkat" @selected($typeValue === 'Singkat')>Jawaban Singkat</option>
        </select>
    </div>
    <div>
        <label class="text-xs font-bold">Pertanyaan</label>
        <textarea name="pertanyaan" class="input w-full" required>{{ $questionText }}</textarea>
    </div>
    <div
        class="md:col-span-2"
        @if($isEditMode)
            data-edit-objective-options
        @else
            data-objective-options
        @endif>
        <div class="flex items-center justify-between gap-3">
            <label class="text-xs font-bold">Opsi Jawaban</label>
            <button
                type="button"
                class="btn-secondary px-3 py-2 text-xs"
                @if($isEditMode)
                    data-edit-option-add
                @else
                    data-option-add="guru-personal"
                @endif>
                <i class="fa-solid fa-plus mr-2"></i> Tambah Jawaban
            </button>
        </div>
        <div
            class="mt-2 grid grid-cols-1 gap-2"
            @if($isEditMode)
                data-edit-option-list
            @else
                data-option-list="guru-personal"
            @endif>
            @foreach($optionsValue as $index => $option)
            <div class="flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ $optionLabels[$index] ?? 'O' . ($index + 1) }}</span>
                <input name="options[]" class="input border-0 bg-transparent px-0" value="{{ $option }}" placeholder="Tulis jawaban {{ $optionLabels[$index] ?? 'O' . ($index + 1) }}">
            </div>
            @endforeach
        </div>
        <p class="mt-1 text-[10px] text-muted italic">Gunakan untuk tipe `PG` atau `Checklist`.</p>
    </div>
    <div>
        <label class="text-xs font-bold">Jawaban Benar</label>
        <select
            name="jawaban_benar"
            class="input w-full"
            @if($isEditMode)
                data-edit-answer-select
                data-current-answer="{{ $answerValue }}"
            @else
                id="guru-personal-answer-key-select"
            @endif>
            <option value="">Pilih jawaban benar</option>
            @unless($isEditMode)
                @foreach (range(0, min(count($optionsValue), 5) - 1) as $index)
                <option value="{{ $optionLabels[$index] }}">{{ $optionLabels[$index] }}</option>
                @endforeach
            @endunless
        </select>
        <input
            name="jawaban_benar"
            class="input w-full {{ $isEditMode ? 'hidden' : 'hidden' }}"
            @if($isEditMode)
                data-edit-answer-text
            @else
                id="guru-personal-answer-key-text"
            @endif
            placeholder="Tulis jawaban singkat yang benar"
            value="{{ $typeValue === 'Singkat' ? $answerValue : '' }}">
        <div
            class="mt-1 text-[10px] text-muted italic"
            @if($isEditMode)
                data-edit-answer-help
            @else
                id="guru-personal-answer-key-help"
            @endif>
            Pilih huruf jawaban yang benar sesuai opsi aktif.
        </div>
    </div>
    <div>
        <label class="text-xs font-bold">Pembahasan</label>
        <textarea name="pembahasan" class="input w-full">{{ $explanationValue }}</textarea>
    </div>
    <div>
        <label class="text-xs font-bold">Gambar (opsional)</label>
        <input type="file" name="image" accept="image/*" class="input w-full" data-image-input="{{ $modeKey }}">
        <div class="mt-1 text-[10px] text-muted italic">Maksimal 2 MB. Preview akan tampil sebelum dikirim.</div>
        <div class="mt-2 {{ $imagePreviewUrl ? '' : 'hidden' }} rounded-2xl border border-slate-200 bg-white p-3" data-image-preview-wrap="{{ $modeKey }}">
            <img src="{{ $imagePreviewUrl ?? '' }}" alt="Preview gambar" class="max-h-48 rounded-2xl object-contain" data-image-preview="{{ $modeKey }}">
        </div>
        @if($isEditMode && !empty($questionData['image_path']))
        <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
            <input type="checkbox" name="remove_image" value="1">
            Hapus gambar lama
        </label>
        @endif
    </div>
    <div>
        <label class="text-xs font-bold">Status</label>
        <select name="status" class="input w-full" required>
            <option value="draft" @selected($statusValue === 'draft')>Draft</option>
            <option value="terbit" @selected($statusValue === 'terbit')>Terbit</option>
        </select>
    </div>
</div>

