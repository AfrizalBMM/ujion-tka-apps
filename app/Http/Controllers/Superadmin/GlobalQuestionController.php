<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Material;
use App\Support\SpreadsheetTable;
use App\Support\SpreadsheetTemplateExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GlobalQuestionController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'question_type' => trim((string) $request->query('question_type', '')),
            'status' => trim((string) $request->query('status', '')),
            'material_curriculum' => trim((string) $request->query('material_curriculum', '')),
        ];

        $globalQuestions = GlobalQuestion::with('material')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('question_text', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('answer_key', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('explanation', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_subelement', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_unit', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_sub_unit', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when(in_array($filters['question_type'], ['multiple_choice', 'short_answer'], true), fn ($query) => $query->where('question_type', $filters['question_type']))
            ->when(in_array($filters['status'], ['active', 'draft'], true), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['material_curriculum'] !== '', fn ($query) => $query->where('material_curriculum', $filters['material_curriculum']))
            ->latest()
            ->get();

        $materials = \App\Models\Material::all();
        return view('superadmin.questions', compact('globalQuestions', 'materials', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question_type' => ['required', 'string', 'max:40'],
            'question_text' => ['required', 'string'],
            'material_curriculum' => ['nullable', 'string', 'max:255'],
            'material_subelement' => ['nullable', 'string', 'max:255'],
            'material_unit' => ['nullable', 'string', 'max:255'],
            'material_sub_unit' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
            'options_raw' => ['nullable', 'string'],
            'answer_key' => ['nullable', 'string', 'max:40'],
            'explanation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $options = $this->normalizeOptionsInput($validated['options'] ?? null, $validated['options_raw'] ?? null);

        GlobalQuestion::create([
            'material_id' => $this->resolveMaterialIdFromAttributes($validated),
            'question_type' => $validated['question_type'],
            'question_text' => $validated['question_text'],
            'material_curriculum' => $this->normalizeNullableString($validated['material_curriculum'] ?? null),
            'material_subelement' => $this->normalizeNullableString($validated['material_subelement'] ?? null),
            'material_unit' => $this->normalizeNullableString($validated['material_unit'] ?? null),
            'material_sub_unit' => $this->normalizeNullableString($validated['material_sub_unit'] ?? null),
            'options' => $options,
            'answer_key' => $this->normalizeAnswerKey($validated['question_type'], $validated['answer_key'] ?? null, $options),
            'explanation' => $validated['explanation'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil ditambahkan.']);
    }

    public function destroy(GlobalQuestion $globalQuestion): RedirectResponse
    {
        $globalQuestion->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil dihapus.']);
    }

    public function destroyAll(): RedirectResponse
    {
        $count = GlobalQuestion::count();
        GlobalQuestion::truncate();

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Berhasil menghapus semua bank soal global ({$count} data).",
        ]);
    }

    public function update(Request $request, GlobalQuestion $globalQuestion): RedirectResponse
    {
        $validated = $request->validate([
            'question_type' => ['required', 'string', 'max:40'],
            'question_text' => ['required', 'string'],
            'material_curriculum' => ['nullable', 'string', 'max:255'],
            'material_subelement' => ['nullable', 'string', 'max:255'],
            'material_unit' => ['nullable', 'string', 'max:255'],
            'material_sub_unit' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
            'options_raw' => ['nullable', 'string'],
            'answer_key' => ['nullable', 'string', 'max:40'],
            'explanation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $options = $this->normalizeOptionsInput($validated['options'] ?? null, $validated['options_raw'] ?? null);

        $globalQuestion->update([
            'material_id' => $this->resolveMaterialIdFromAttributes($validated),
            'question_type' => $validated['question_type'],
            'question_text' => $validated['question_text'],
            'material_curriculum' => $this->normalizeNullableString($validated['material_curriculum'] ?? null),
            'material_subelement' => $this->normalizeNullableString($validated['material_subelement'] ?? null),
            'material_unit' => $this->normalizeNullableString($validated['material_unit'] ?? null),
            'material_sub_unit' => $this->normalizeNullableString($validated['material_sub_unit'] ?? null),
            'options' => $options,
            'answer_key' => $this->normalizeAnswerKey($validated['question_type'], $validated['answer_key'] ?? null, $options),
            'explanation' => $validated['explanation'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil diperbarui.']);
    }

    public function template(): StreamedResponse
    {
        return SpreadsheetTemplateExporter::download('template-soal-global.xls', [
            'question_type',
            'question_text',
            'material_curriculum',
            'material_subelement',
            'material_unit',
            'material_sub_unit',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'option_e',
            'option_f',
            'answer_key',
            'explanation',
            'is_active',
        ], [
            ['multiple_choice', 'Contoh pertanyaan pilihan ganda?', 'Merdeka', 'Literasi', 'Teks Narasi', 'Mengidentifikasi ide pokok', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', '', 'A', 'Pembahasan singkat...', '1'],
            ['short_answer', 'Sebutkan ibu kota Indonesia.', 'K-13', 'Numerasi', 'Bilangan', 'Operasi hitung dasar', '', '', '', '', '', '', 'Jakarta', 'Jawaban singkat tanpa opsi.', '1'],
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        try {
            $rows = SpreadsheetTable::rowsFromUpload($validated['file']);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $questionText = trim((string) ($row['question_text'] ?? ''));
            if ($questionText === '') {
                $skipped++;
                continue;
            }

            $questionType = $this->normalizeQuestionType($row['question_type'] ?? null);
            if ($questionType === null) {
                $skipped++;
                continue;
            }

            $options = $this->extractOptionsFromImportRow($row);

            GlobalQuestion::create([
                'material_id' => $this->resolveMaterialIdFromRow($row),
                'question_type' => $questionType,
                'question_text' => $questionText,
                'material_curriculum' => $this->normalizeNullableString($row['material_curriculum'] ?? $row['curriculum'] ?? null),
                'material_subelement' => $this->normalizeNullableString($row['material_subelement'] ?? $row['subelement'] ?? null),
                'material_unit' => $this->normalizeNullableString($row['material_unit'] ?? $row['unit'] ?? null),
                'material_sub_unit' => $this->normalizeNullableString($row['material_sub_unit'] ?? $row['sub_unit'] ?? $row['subunit'] ?? null),
                'options' => $options,
                'answer_key' => $this->normalizeAnswerKey($questionType, $row['answer_key'] ?? null, $options),
                'explanation' => trim((string) ($row['explanation'] ?? '')) ?: null,
                'is_active' => $this->toBoolean($row['is_active'] ?? true),
                'created_by' => $request->user()?->id,
            ]);

            $created++;
        }

        return back()->with('flash', [
            'type' => $created > 0 ? 'success' : 'warning',
            'message' => "Import soal selesai. Berhasil: {$created}, dilewati: {$skipped}.",
        ]);
    }

    private function parseOptions(?string $raw): ?array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $separator = str_contains($raw, "\n") || str_contains($raw, "\r") ? '/\r\n|\r|\n/' : '/\s*,\s*/';

        return collect(preg_split($separator, $raw))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function normalizeOptionsInput(?array $options, ?string $optionsRaw = null): ?array
    {
        $normalized = collect($options ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        if ($normalized !== []) {
            return $normalized;
        }

        return $this->parseOptions($optionsRaw);
    }

    private function extractOptionsFromImportRow(array $row): ?array
    {
        $optionColumns = collect($row)
            ->filter(fn ($value, $key) => str_starts_with((string) $key, 'option_'))
            ->sortKeys()
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        if ($optionColumns !== []) {
            return $optionColumns;
        }

        return $this->parseOptions((string) ($row['options'] ?? ''));
    }

    private function normalizeAnswerKey(string $questionType, mixed $answerKey, ?array $options): ?string
    {
        $rawAnswer = trim((string) $answerKey);

        if ($rawAnswer === '') {
            return null;
        }

        if ($questionType !== 'multiple_choice' || empty($options)) {
            return $rawAnswer;
        }

        $labels = range('A', 'Z');
        $upperAnswer = strtoupper($rawAnswer);
        $labelIndex = array_search($upperAnswer, $labels, true);

        if ($labelIndex !== false && array_key_exists($labelIndex, $options)) {
            return $options[$labelIndex];
        }

        return $rawAnswer;
    }

    private function resolveMaterialIdFromRow(array $row): ?int
    {
        if (! Schema::hasTable('materials')) {
            return null;
        }

        $materialId = (int) trim((string) ($row['material_id'] ?? ''));
        if ($materialId > 0 && Material::query()->whereKey($materialId)->exists()) {
            return $materialId;
        }

        $curriculum = $this->normalizeNullableString($row['material_curriculum'] ?? $row['curriculum'] ?? null);
        $subelement = $this->normalizeNullableString($row['material_subelement'] ?? $row['subelement'] ?? null);
        $unit = $this->normalizeNullableString($row['material_unit'] ?? $row['unit'] ?? null);
        $subUnit = $this->normalizeNullableString($row['material_sub_unit'] ?? $row['sub_unit'] ?? $row['subunit'] ?? null);

        if (! $curriculum || ! $subelement || ! $unit || ! $subUnit) {
            return null;
        }

        return Material::query()
            ->where('curriculum', $curriculum)
            ->where('subelement', $subelement)
            ->where('unit', $unit)
            ->where('sub_unit', $subUnit)
            ->value('id');
    }

    private function resolveMaterialIdFromAttributes(array $attributes): ?int
    {
        if (! Schema::hasTable('materials')) {
            return null;
        }

        $curriculum = $this->normalizeNullableString($attributes['material_curriculum'] ?? null);
        $subelement = $this->normalizeNullableString($attributes['material_subelement'] ?? null);
        $unit = $this->normalizeNullableString($attributes['material_unit'] ?? null);
        $subUnit = $this->normalizeNullableString($attributes['material_sub_unit'] ?? null);

        if (! $curriculum || ! $subelement || ! $unit || ! $subUnit) {
            return null;
        }

        return Material::query()
            ->where('curriculum', $curriculum)
            ->where('subelement', $subelement)
            ->where('unit', $unit)
            ->where('sub_unit', $subUnit)
            ->value('id');
    }

    private function normalizeQuestionType(mixed $value): ?string
    {
        $normalized = SpreadsheetTable::normalizeHeader((string) $value);

        return match ($normalized) {
            '', 'multiple_choice', 'pilihan_ganda', 'pg' => 'multiple_choice',
            'short_answer', 'jawaban_singkat', 'singkat' => 'short_answer',
            default => null,
        };
    }

    private function toBoolean(mixed $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return ! in_array($normalized, ['0', 'false', 'tidak', 'nonaktif', 'draft'], true);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
