<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
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
            'search'              => trim((string) $request->query('search', '')),
            'question_type'       => trim((string) $request->query('question_type', '')),
            'status'              => trim((string) $request->query('status', '')),
            'material_mapel'      => trim((string) $request->query('material_mapel', '')),
            'material_curriculum' => trim((string) $request->query('material_curriculum', '')),
            'jenjang_id'          => $request->query('jenjang_id'),
            'per_page'            => in_array($request->query('per_page'), [10, 20, 30, 50]) ? (int) $request->query('per_page') : 10,
        ];

        $globalQuestions = GlobalQuestion::with('material')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('question_text', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('answer_key', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('explanation', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('reading_passage', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_mapel', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_subelement', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_unit', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('material_sub_unit', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when(in_array($filters['question_type'], ['multiple_choice', 'short_answer', 'matching'], true), fn ($query) => $query->where('question_type', $filters['question_type']))
            ->when(in_array($filters['status'], ['active', 'draft'], true), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['material_mapel'] !== '', fn ($query) => $query->where('material_mapel', $filters['material_mapel']))
            ->when($filters['material_curriculum'] !== '', fn ($query) => $query->where('material_curriculum', $filters['material_curriculum']))
            ->when($filters['jenjang_id'], fn ($query) => $query->where('jenjang_id', $filters['jenjang_id']))
            ->latest()
            ->paginate($filters['per_page'])
            ->withQueryString();

        $materials = \App\Models\Material::all();
        $jenjangs = Jenjang::orderBy('urutan')->get();
        return view('superadmin.questions', compact('globalQuestions', 'materials', 'jenjangs', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenjang_id'          => ['required', 'integer', 'exists:jenjangs,id'],
            'question_type'       => ['required', 'string', 'max:40'],
            'reading_passage'     => ['nullable', 'string'],
            'question_text'       => ['required', 'string'],
            'material_mapel'      => ['nullable', 'string', 'max:255'],
            'material_curriculum' => ['nullable', 'string', 'max:255'],
            'material_subelement' => ['nullable', 'string', 'max:255'],
            'material_unit'       => ['nullable', 'string', 'max:255'],
            'material_sub_unit'   => ['nullable', 'string', 'max:255'],
            'options'             => ['nullable', 'array'],
            'options.*'           => ['nullable', 'string', 'max:255'],
            'options_raw'         => ['nullable', 'string'],
            'answer_key'          => ['nullable', 'string', 'max:40'],
            'explanation'         => ['nullable', 'string'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $options = $this->normalizeOptionsInput($validated['options'] ?? null, $validated['options_raw'] ?? null);

        GlobalQuestion::create([
            'jenjang_id'          => $validated['jenjang_id'],
            'material_id'         => $this->resolveMaterialIdFromAttributes($validated),
            'question_type'       => $validated['question_type'],
            'reading_passage'     => $this->normalizeNullableString($validated['reading_passage'] ?? null),
            'question_text'       => $validated['question_text'],
            'material_mapel'      => $this->normalizeNullableString($validated['material_mapel'] ?? null),
            'material_curriculum' => $this->normalizeNullableString($validated['material_curriculum'] ?? null),
            'material_subelement' => $this->normalizeNullableString($validated['material_subelement'] ?? null),
            'material_unit'       => $this->normalizeNullableString($validated['material_unit'] ?? null),
            'material_sub_unit'   => $this->normalizeNullableString($validated['material_sub_unit'] ?? null),
            'options'             => $options,
            'answer_key'          => $this->normalizeAnswerKey($validated['question_type'], $validated['answer_key'] ?? null, $options),
            'explanation'         => $validated['explanation'] ?? null,
            'is_active'           => (bool) ($validated['is_active'] ?? true),
            'created_by'          => $request->user()?->id,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil ditambahkan.']);
    }

    public function destroy(GlobalQuestion $globalQuestion): RedirectResponse
    {
        $globalQuestion->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil dihapus.']);
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $this->authorize('deleteAll', GlobalQuestion::class);

        $validated = $request->validate([
            'confirm_text' => ['required', 'string', 'max:50'],
        ]);

        $confirm = strtoupper(trim((string) ($validated['confirm_text'] ?? '')));
        if ($confirm !== 'HAPUS SEMUA') {
            return back()
                ->withErrors(['confirm_text' => 'Konfirmasi tidak sesuai. Ketik: HAPUS SEMUA'])
                ->withInput();
        }

        $count = GlobalQuestion::count();
        GlobalQuestion::query()->delete();

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Berhasil menghapus semua bank soal global ({$count} data).",
        ]);
    }

    public function update(Request $request, GlobalQuestion $globalQuestion): RedirectResponse
    {
        $validated = $request->validate([
            'jenjang_id'          => ['required', 'integer', 'exists:jenjangs,id'],
            'question_type'       => ['required', 'string', 'max:40'],
            'reading_passage'     => ['nullable', 'string'],
            'question_text'       => ['required', 'string'],
            'material_mapel'      => ['nullable', 'string', 'max:255'],
            'material_curriculum' => ['nullable', 'string', 'max:255'],
            'material_subelement' => ['nullable', 'string', 'max:255'],
            'material_unit'       => ['nullable', 'string', 'max:255'],
            'material_sub_unit'   => ['nullable', 'string', 'max:255'],
            'options'             => ['nullable', 'array'],
            'options.*'           => ['nullable', 'string', 'max:255'],
            'options_raw'         => ['nullable', 'string'],
            'answer_key'          => ['nullable', 'string', 'max:40'],
            'explanation'         => ['nullable', 'string'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $options = $this->normalizeOptionsInput($validated['options'] ?? null, $validated['options_raw'] ?? null);

        $globalQuestion->update([
            'jenjang_id'          => $validated['jenjang_id'],
            'material_id'         => $this->resolveMaterialIdFromAttributes($validated),
            'question_type'       => $validated['question_type'],
            'reading_passage'     => $this->normalizeNullableString($validated['reading_passage'] ?? null),
            'question_text'       => $validated['question_text'],
            'material_mapel'      => $this->normalizeNullableString($validated['material_mapel'] ?? null),
            'material_curriculum' => $this->normalizeNullableString($validated['material_curriculum'] ?? null),
            'material_subelement' => $this->normalizeNullableString($validated['material_subelement'] ?? null),
            'material_unit'       => $this->normalizeNullableString($validated['material_unit'] ?? null),
            'material_sub_unit'   => $this->normalizeNullableString($validated['material_sub_unit'] ?? null),
            'options'             => $options,
            'answer_key'          => $this->normalizeAnswerKey($validated['question_type'], $validated['answer_key'] ?? null, $options),
            'explanation'         => $validated['explanation'] ?? null,
            'is_active'           => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil diperbarui.']);
    }

    // ─── Templates ──────────────────────────────────────────────────────────────

    public function template(): StreamedResponse
    {
        return $this->templatePG();
    }

    public function templatePG(): StreamedResponse
    {
        return SpreadsheetTemplateExporter::download('template-soal-pg.xls', [
            'jenjang_id',
            'question_type',
            'reading_passage',
            'question_text',
            'material_mapel',
            'material_curriculum',
            'material_subelement',
            'material_unit',
            'material_sub_unit',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'option_e',
            'answer_key',
            'explanation',
            'is_active',
        ], [
            ['1', 'multiple_choice', 'Bacaan opsional, boleh dikosongkan.', 'Contoh pertanyaan pilihan ganda?', 'Matematika', 'Merdeka', 'Literasi', 'Teks Narasi', 'Mengidentifikasi ide pokok', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A', 'Pembahasan singkat...', '1'],
            ['2', 'multiple_choice', '', 'Ibu kota Indonesia adalah...?', 'Bahasa Indonesia', 'K-13', 'Literasi', 'Teks Deskripsi', 'Menentukan informasi tersurat', 'Jakarta', 'Bandung', 'Surabaya', 'Bali', '', 'A', '', '1'],
        ]);
    }

    public function templateMenjodohkan(): StreamedResponse
    {
        return SpreadsheetTemplateExporter::download('template-soal-menjodohkan.xls', [
            'jenjang_id',
            'question_type',
            'question_text',
            'material_mapel',
            'material_curriculum',
            'material_subelement',
            'material_unit',
            'material_sub_unit',
            'pair_1_left',
            'pair_1_right',
            'pair_2_left',
            'pair_2_right',
            'pair_3_left',
            'pair_3_right',
            'pair_4_left',
            'pair_4_right',
            'explanation',
            'is_active',
        ], [
            ['1', 'matching', 'Jodohkan kata dengan artinya!', 'Bahasa Indonesia', 'Merdeka', 'Literasi', 'Kosakata', 'Makna kata', 'Dinamis', 'Bergerak', 'Statis', 'Diam', 'Eksplisit', 'Jelas/Tersurat', 'Implisit', 'Tersirat', '', '1'],
            ['2', 'matching', 'Pasangkan bilangan dengan hasil kuadratnya!', 'Matematika', 'K-13', 'Numerasi', 'Bilangan', 'Operasi hitung', '2', '4', '3', '9', '4', '16', '5', '25', '', '1'],
        ]);
    }

    // ─── Imports ─────────────────────────────────────────────────────────────────

    public function import(Request $request): RedirectResponse
    {
        return $this->importPG($request);
    }

    public function importPG(Request $request): RedirectResponse
    {
        $this->authorize('manage', GlobalQuestion::class);

        $validated = $request->validate([
            'jenjang_id' => ['nullable', 'integer', 'exists:jenjangs,id'],
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        try {
            $rows = SpreadsheetTable::rowsFromUpload($validated['file']);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        $created = 0;
        $skipped = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $request, $validated, &$created, &$skipped) {
            foreach ($rows as $row) {
                $questionText = trim((string) ($row['question_text'] ?? ''));
                if ($questionText === '') {
                    $skipped++;
                    continue;
                }

                $rawType = SpreadsheetTable::normalizeHeader((string) ($row['question_type'] ?? ''));
                $questionType = match ($rawType) {
                    '', 'multiple_choice', 'pilihan_ganda', 'pg' => 'multiple_choice',
                    'short_answer', 'jawaban_singkat', 'singkat'  => 'short_answer',
                    default                                        => null,
                };

                if ($questionType === null) {
                    $skipped++;
                    continue;
                }

                // Ambil jenjang_id dari file jika ada, jika tidak ada pakai input fallback
                $jenjangId = isset($row['jenjang_id']) && trim((string)$row['jenjang_id']) !== ''
                    ? (int) $row['jenjang_id']
                    : ($validated['jenjang_id'] ?? null);
                if (!$jenjangId || !\App\Models\Jenjang::find($jenjangId)) {
                    $skipped++;
                    continue;
                }

                $options = $this->extractOptionsFromImportRow($row);
                $readingPassage = $this->normalizeNullableString($row['reading_passage'] ?? $row['bacaan'] ?? null);

                GlobalQuestion::create([
                    'jenjang_id'          => $jenjangId,
                    'material_id'         => $this->resolveMaterialIdFromRow($row),
                    'question_type'       => $questionType,
                    'reading_passage'     => $readingPassage,
                    'question_text'       => $questionText,
                    'material_mapel'      => $this->normalizeNullableString($row['material_mapel'] ?? $row['mapel'] ?? null),
                    'material_curriculum' => $this->normalizeNullableString($row['material_curriculum'] ?? $row['curriculum'] ?? null),
                    'material_subelement' => $this->normalizeNullableString($row['material_subelement'] ?? $row['subelement'] ?? null),
                    'material_unit'       => $this->normalizeNullableString($row['material_unit'] ?? $row['unit'] ?? null),
                    'material_sub_unit'   => $this->normalizeNullableString($row['material_sub_unit'] ?? $row['sub_unit'] ?? $row['subunit'] ?? null),
                    'options'             => $options,
                    'answer_key'          => $this->normalizeAnswerKey($questionType, $row['answer_key'] ?? null, $options),
                    'explanation'         => trim((string) ($row['explanation'] ?? '')) ?: null,
                    'is_active'           => $this->toBoolean($row['is_active'] ?? true),
                    'created_by'          => $request->user()?->id,
                ]);

                $created++;
            }
        });

        return back()->with('flash', [
            'type'    => $created > 0 ? 'success' : 'warning',
            'message' => "Import soal PG selesai. Berhasil: {$created}, dilewati: {$skipped}.",
        ]);
    }

    public function importMenjodohkan(Request $request): RedirectResponse
    {
        $this->authorize('manage', GlobalQuestion::class);

        $validated = $request->validate([
            'jenjang_id' => ['required', 'integer', 'exists:jenjangs,id'],
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        try {
            $rows = SpreadsheetTable::rowsFromUpload($validated['file']);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        $created = 0;
        $skipped = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $validated, $request, &$created, &$skipped) {
            foreach ($rows as $row) {
                $questionText = trim((string) ($row['question_text'] ?? ''));
                if ($questionText === '') {
                    $skipped++;
                    continue;
                }

                // Bangun pasangan dari kolom pair_1_left, pair_1_right, pair_2_left, dst.
                $pairs = [];
                for ($i = 1; $i <= 8; $i++) {
                    $left  = $this->normalizeNullableString($row["pair_{$i}_left"] ?? null);
                    $right = $this->normalizeNullableString($row["pair_{$i}_right"] ?? null);
                    if ($left !== null && $right !== null) {
                        $pairs[] = ['left' => $left, 'right' => $right];
                    }
                }

                if (empty($pairs)) {
                    $skipped++;
                    continue;
                }

                GlobalQuestion::create([
                    'jenjang_id'          => $validated['jenjang_id'],
                    'material_id'         => $this->resolveMaterialIdFromRow($row),
                    'question_type'       => 'matching',
                    'reading_passage'     => null,
                    'question_text'       => $questionText,
                    'material_mapel'      => $this->normalizeNullableString($row['material_mapel'] ?? $row['mapel'] ?? null),
                    'material_curriculum' => $this->normalizeNullableString($row['material_curriculum'] ?? $row['curriculum'] ?? null),
                    'material_subelement' => $this->normalizeNullableString($row['material_subelement'] ?? $row['subelement'] ?? null),
                    'material_unit'       => $this->normalizeNullableString($row['material_unit'] ?? $row['unit'] ?? null),
                    'material_sub_unit'   => $this->normalizeNullableString($row['material_sub_unit'] ?? $row['sub_unit'] ?? $row['subunit'] ?? null),
                    'options'             => $pairs,
                    'answer_key'          => null,
                    'explanation'         => trim((string) ($row['explanation'] ?? '')) ?: null,
                    'is_active'           => $this->toBoolean($row['is_active'] ?? true),
                    'created_by'          => $request->user()?->id,
                ]);

                $created++;
            }
        });

        return back()->with('flash', [
            'type'    => $created > 0 ? 'success' : 'warning',
            'message' => "Import soal Menjodohkan selesai. Berhasil: {$created}, dilewati: {$skipped}.",
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────────

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

        $labels      = range('A', 'Z');
        $upperAnswer = strtoupper($rawAnswer);
        $labelIndex  = array_search($upperAnswer, $labels, true);

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
        $mapel      = $this->normalizeNullableString($row['material_mapel'] ?? $row['mapel'] ?? null);
        $subelement = $this->normalizeNullableString($row['material_subelement'] ?? $row['subelement'] ?? null);
        $unit       = $this->normalizeNullableString($row['material_unit'] ?? $row['unit'] ?? null);
        $subUnit    = $this->normalizeNullableString($row['material_sub_unit'] ?? $row['sub_unit'] ?? $row['subunit'] ?? null);

        if (! $curriculum || ! $mapel || ! $subelement || ! $unit || ! $subUnit) {
            return null;
        }

        return Material::query()
            ->where('curriculum', $curriculum)
            ->where('mapel', $mapel)
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
        $mapel      = $this->normalizeNullableString($attributes['material_mapel'] ?? null);
        $subelement = $this->normalizeNullableString($attributes['material_subelement'] ?? null);
        $unit       = $this->normalizeNullableString($attributes['material_unit'] ?? null);
        $subUnit    = $this->normalizeNullableString($attributes['material_sub_unit'] ?? null);

        if (! $curriculum || ! $mapel || ! $subelement || ! $unit || ! $subUnit) {
            return null;
        }

        return Material::query()
            ->where('curriculum', $curriculum)
            ->where('mapel', $mapel)
            ->where('subelement', $subelement)
            ->where('unit', $unit)
            ->where('sub_unit', $subUnit)
            ->value('id');
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
