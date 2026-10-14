<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Material;
use App\Support\SpreadsheetTable;
use App\Support\SpreadsheetTemplateExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function template(Request $request): StreamedResponse
    {
        $defaultJenjang = $this->normalizeJenjang($request->input('jenjang'));

        $examples = $this->templateExamples($defaultJenjang);

        $filename = $defaultJenjang
            ? 'template-materi-'.strtolower($defaultJenjang).'.xls'
            : 'template-materi.xls';

        return SpreadsheetTemplateExporter::download($filename, [
            'jenjang',
            'mapel',
            'curriculum',
            'subelement',
            'unit',
            'sub_unit',
            'link',
        ], $examples);
    }

    public function destroyAll(): RedirectResponse
    {
        $this->authorize('deleteAll', Material::class);

        $count = Material::count();
        Material::query()->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Berhasil menghapus semua materi ($count data).",
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('manage', Material::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,xml', 'max:5120'],
            'default_jenjang' => ['nullable', 'in:SD,SMP,SMA'],
        ]);

        try {
            $rows = SpreadsheetTable::rowsFromUpload($validated['file']);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedNoMapel = 0;
        $skippedIncomplete = 0;

        $defaultJenjang = $this->normalizeJenjang($validated['default_jenjang'] ?? null);

        foreach ($rows as $row) {
            $payload = [
                'jenjang' => $this->normalizeJenjang($row['jenjang'] ?? null) ?? $defaultJenjang,
                'mapel' => trim((string) ($row['mapel'] ?? '')),
                'curriculum' => $this->normalizeCurriculum($row['curriculum'] ?? null),
                'subelement' => trim((string) ($row['subelement'] ?? '')),
                'unit' => trim((string) ($row['unit'] ?? '')),
                'sub_unit' => trim((string) ($row['sub_unit'] ?? ($row['subunit'] ?? ''))),
                'link' => trim((string) ($row['link'] ?? '')) ?: null,
            ];

            if ($payload['mapel'] === '') {
                $skipped++;
                $skippedNoMapel++;

                continue;
            }

            if (
                $payload['curriculum'] === null
                || $payload['subelement'] === ''
                || $payload['unit'] === ''
                || $payload['sub_unit'] === ''
            ) {
                $skipped++;
                $skippedIncomplete++;

                continue;
            }

            if (! Schema::hasColumn('materials', 'jenjang')) {
                unset($payload['jenjang']);
            }

            if (! Schema::hasColumn('materials', 'link')) {
                unset($payload['link']);
            }

            $matchKeys = [
                'jenjang' => $payload['jenjang'] ?? null,
                'mapel' => $payload['mapel'],
                'curriculum' => $payload['curriculum'],
                'subelement' => $payload['subelement'],
                'unit' => $payload['unit'],
                'sub_unit' => $payload['sub_unit'],
            ];
            $updateValues = ['link' => $payload['link'] ?? null];

            $material = Material::firstOrNew($matchKeys);
            if ($material->exists) {
                $material->fill($updateValues)->save();
                $updated++;
            } else {
                $material->fill($updateValues)->save();
                $created++;
            }
        }

        $reasons = [];
        if ($skippedNoMapel > 0) {
            $reasons[] = "kolom mapel kosong: {$skippedNoMapel}";
        }
        if ($skippedIncomplete > 0) {
            $reasons[] = "kolom wajib kosong (curriculum/subelement/unit/sub_unit): {$skippedIncomplete}";
        }
        $skipDetail = $reasons !== [] ? ' — '.implode(', ', $reasons) : '';

        return back()->with('flash', [
            'type' => ($created + $updated) > 0 ? 'success' : 'warning',
            'message' => "Import materi selesai. Ditambah: {$created}, diperbarui: {$updated}, dilewati: {$skipped}{$skipDetail}.",
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenjang' => ['nullable', 'in:SD,SMP,SMA'],
            'mapel' => ['required', 'string', 'max:120'],
            'curriculum' => ['required', 'in:K-13,Merdeka'],
            'subelement' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:120'],
            'sub_unit' => ['required', 'string', 'max:120'],
            'link' => ['nullable', 'string', 'max:500'],
        ]);

        if (! Schema::hasColumn('materials', 'jenjang')) {
            unset($validated['jenjang']);
        }
        if (! Schema::hasColumn('materials', 'link')) {
            unset($validated['link']);
        }

        Material::create($validated);

        return back()->with('flash', ['type' => 'success', 'message' => 'Materi berhasil ditambahkan.']);
    }

    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Materi berhasil dihapus.']);
    }

    public function index(Request $request): InertiaResponse
    {
        $filter = $request->query('jenjang');
        $mapel = $request->query('mapel');
        $curriculum = $request->query('curriculum');
        $subelement = $request->query('subelement');
        $unit = $request->query('unit');
        $subUnit = $request->query('sub_unit');
        $search = trim((string) $request->query('search'));

        $materialsQuery = Material::query();

        // Jenjang filter (via sidebar context)
        if (Schema::hasColumn('materials', 'jenjang') && in_array($filter, ['SD', 'SMP', 'SMA', 'GLOBAL'], true)) {
            if ($filter === 'GLOBAL') {
                $materialsQuery->whereNull('jenjang');
            } else {
                $materialsQuery->where('jenjang', $filter);
            }
        }

        // Dropdown filters
        if ($mapel) {
            $materialsQuery->where('mapel', $mapel);
        }
        if ($curriculum) {
            $materialsQuery->where('curriculum', $curriculum);
        }
        if ($subelement) {
            $materialsQuery->where('subelement', $subelement);
        }
        if ($unit) {
            $materialsQuery->where('unit', $unit);
        }
        if ($subUnit) {
            $materialsQuery->where('sub_unit', $subUnit);
        }

        // Search (mapel, subelement, unit, sub_unit)
        if ($search !== '') {
            $materialsQuery->where(function ($q) use ($search) {
                $q->where('mapel', 'like', "%{$search}%")
                    ->orWhere('subelement', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%")
                    ->orWhere('sub_unit', 'like', "%{$search}%");
            });
        }

        $bankQuestionCountSub = GlobalQuestion::query()
            ->selectRaw('COUNT(*)')
            ->where('global_questions.is_active', true)
            ->where('global_questions.question_type', 'multiple_choice')
            ->where(function ($q) {
                $q->whereColumn('global_questions.material_id', 'materials.id')
                    ->orWhere(function ($inner) {
                        $inner->whereNull('global_questions.material_id')
                            ->whereRaw("LOWER(TRIM(REPLACE(COALESCE(global_questions.material_mapel, ''), '_', ' '))) = LOWER(TRIM(REPLACE(COALESCE(materials.mapel, ''), '_', ' ')))")
                            ->whereColumn('global_questions.material_curriculum', 'materials.curriculum');
                    });
            });

        $materials = $materialsQuery
            ->select('materials.*')
            ->selectSub($bankQuestionCountSub, 'bank_question_count')
            ->orderBy('curriculum')
            ->orderBy('subelement')
            ->orderBy('unit')
            ->orderBy('sub_unit')
            ->get();

        // Build distinct option lists from the SAME jenjang context (before search/filter)
        $baseQuery = Material::query();
        if (Schema::hasColumn('materials', 'jenjang') && in_array($filter, ['SD', 'SMP', 'SMA', 'GLOBAL'], true)) {
            if ($filter === 'GLOBAL') {
                $baseQuery->whereNull('jenjang');
            } else {
                $baseQuery->where('jenjang', $filter);
            }
        }
        $mapels = $baseQuery->clone()->distinct()->whereNotNull('mapel')->where('mapel', '!=', '')->pluck('mapel')->sort()->values();
        $curriculums = $baseQuery->clone()->distinct()->pluck('curriculum')->sort()->values();
        $subelements = $baseQuery->clone()->distinct()->pluck('subelement')->sort()->values();
        $units = $baseQuery->clone()->distinct()->pluck('unit')->sort()->values();
        $subUnits = $baseQuery->clone()->distinct()->pluck('sub_unit')->sort()->values();

        $materials = $materials->map(fn (Material $m) => [
            'id' => $m->id,
            'curriculum' => $m->curriculum,
            'mapel' => $m->mapel,
            'jenjang' => $m->jenjang,
            'subelement' => $m->subelement,
            'unit' => $m->unit,
            'sub_unit' => $m->sub_unit,
            'bank_question_count' => (int) ($m->bank_question_count ?? 0),
        ])->values()->all();

        return Inertia::render('Superadmin/Materials', [
            'materials' => $materials,
            'filter' => $filter,
            'mapel' => $mapel,
            'curriculum' => $curriculum,
            'subelement' => $subelement,
            'subelementLimited' => $subelement ? Str::limit($subelement, 26) : null,
            'unit' => $unit,
            'unitLimited' => $unit ? Str::limit($unit, 26) : null,
            'subUnit' => $subUnit,
            'subUnitLimited' => $subUnit ? Str::limit($subUnit, 26) : null,
            'search' => $search,
            'mapels' => $mapels,
            'curriculums' => $curriculums,
            'subelements' => $subelements,
            'units' => $units,
            'subUnits' => $subUnits,
        ]);
    }

    private function templateExamples(?string $jenjang): array
    {
        $query = Material::query()
            ->whereNotNull('mapel')->where('mapel', '!=', '')
            ->whereNotNull('curriculum')->where('curriculum', '!=', '')
            ->whereNotNull('subelement')->where('subelement', '!=', '')
            ->whereNotNull('unit')->where('unit', '!=', '')
            ->whereNotNull('sub_unit')->where('sub_unit', '!=', '');

        if ($jenjang !== null) {
            $query->whereRaw('upper(jenjang) = ?', [$jenjang]);
        }

        $rows = $query
            ->orderByRaw('case when jenjang is null or jenjang = \'\' then 1 else 0 end')
            ->orderBy('jenjang')
            ->orderBy('mapel')
            ->orderBy('curriculum')
            ->orderBy('subelement')
            ->orderBy('unit')
            ->orderBy('sub_unit')
            ->get(['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit', 'link']);

        if ($rows->isEmpty()) {
            return $this->fallbackExamples($jenjang);
        }

        $byJenjang = $rows->groupBy(fn ($row) => strtoupper((string) $row->jenjang) ?: 'UMUM');

        $ordered = $jenjang !== null
            ? $byJenjang
            : $byJenjang->sortKeys()->values()->keyBy(fn ($group) => $group->first()->jenjang ?: 'UMUM');

        $examples = [];

        foreach ($ordered as $group) {
            $seen = [];
            foreach ($group as $row) {
                $key = implode('|', [$row->mapel, $row->curriculum, $row->subelement, $row->unit]);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $examples[] = [
                    (string) ($row->jenjang ?? ''),
                    (string) $row->mapel,
                    (string) $row->curriculum,
                    (string) $row->subelement,
                    (string) $row->unit,
                    (string) $row->sub_unit,
                    (string) ($row->link ?? ''),
                ];

                if (count($examples) >= 6) {
                    break 2;
                }
            }
        }

        return $examples;
    }

    private function fallbackExamples(?string $jenjang): array
    {
        $all = [
            ['SD',  'Matematika',       'K-13', 'Bilangan',   'Bulat Negatif', 'Penjumlahan & Pengurangan Bilangan Bulat', 'https://buku.kemdikbud.go.id/katalog/matematika-kelas-vi'],
            ['SD',  'Matematika',       'Merdeka', 'Bilangan', 'Desimal',      'Nilai Tempat Bilangan Persepuluhan',       ''],
            ['SD',  'Bahasa Indonesia', 'K-13', 'Membaca',    'Teks Laporan',  'Ide Pokok Paragraf Laporan',              ''],
            ['SD',  'Bahasa Indonesia', 'Merdeka', 'Literasi', 'Teks Narasi',  'Menemukan Pesan Tersirat',                ''],
            ['SMP', 'Matematika',       'K-13', 'Aljabar',    'Persamaan',     'Persamaan linear satu variabel',          ''],
            ['SMA', 'Matematika',       'Merdeka', 'Aljabar', 'Fungsi',        'Fungsi kuadrat',                          ''],
        ];

        if ($jenjang === null) {
            return $all;
        }

        $filtered = array_values(array_filter($all, fn ($row) => strtoupper($row[0]) === $jenjang));

        return $filtered !== [] ? $filtered : $all;
    }

    private function normalizeJenjang(?string $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['SD', 'SMP', 'SMA'], true) ? $normalized : null;
    }

    private function normalizeCurriculum(?string $value): ?string
    {
        $normalized = strtoupper(str_replace([' ', '.'], '', trim((string) $value)));

        return match ($normalized) {
            'MERDEKA', 'KURIKULUMMERDEKA' => 'Merdeka',
            'K13', 'K-13', 'KURIKULUM2013' => 'K-13',
            default => null,
        };
    }
}
