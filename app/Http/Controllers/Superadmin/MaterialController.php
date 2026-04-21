<?php

namespace App\Http\Controllers\Superadmin;

use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Support\SpreadsheetTable;
use App\Support\SpreadsheetTemplateExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MaterialController extends Controller
{
    public function template(Request $request): StreamedResponse
    {
        $defaultJenjang = $this->normalizeJenjang($request->input('jenjang'));

        $examples = match ($defaultJenjang) {
            'SD' => [
                ['SD', 'Matematika',       'Merdeka', 'Numerasi',  'Bilangan',     'Bilangan Cacah',                    'https://contoh-materi.test/bilangan-cacah'],
                ['SD', 'Matematika',       'K-13',    'Numerasi',  'Bilangan',     'Operasi hitung dasar',              ''],
                ['SD', 'Bahasa Indonesia', 'Merdeka', 'Literasi',  'Teks Narasi',  'Mengidentifikasi ide pokok',        'https://contoh-materi.test/ide-pokok'],
                ['SD', 'Bahasa Indonesia', 'K-13',    'Literasi',  'Teks Narasi',  'Menentukan gagasan utama',          ''],
            ],
            'SMP' => [
                ['SMP', 'Matematika',       'Merdeka', 'Numerasi',  'Perbandingan', 'Skala dan rasio',                  'https://contoh-materi.test/skala-rasio'],
                ['SMP', 'Matematika',       'K-13',    'Numerasi',  'Aljabar',      'Persamaan linear satu variabel',   ''],
                ['SMP', 'Bahasa Indonesia', 'Merdeka', 'Literasi',  'Teks Informasi','Menentukan gagasan utama',        'https://contoh-materi.test/gagasan-utama'],
                ['SMP', 'Bahasa Indonesia', 'K-13',    'Literasi',  'Teks Deskripsi','Mengidentifikasi informasi tersurat',''],
            ],
            'SMA' => [
                ['SMA', 'Matematika',       'Merdeka', 'Numerasi',  'Fungsi Kuadrat','Mencari nilai maksimum/minimum',  'https://contoh-materi.test/fungsi-kuadrat'],
                ['SMA', 'Matematika',       'K-13',    'Numerasi',  'Trigonometri',  'Nilai trigonometri sudut istimewa',''],
                ['SMA', 'Bahasa Indonesia', 'Merdeka', 'Literasi',  'Teks Artikel',  'Menganalisis argumen kompleks',   'https://contoh-materi.test/argumen-kompleks'],
                ['SMA', 'Bahasa Indonesia', 'K-13',    'Literasi',  'Teks Eksposisi','Menentukan tesis dan argumen',    ''],
            ],
            default => [
                ['SD',  'Matematika',       'Merdeka', 'Numerasi',  'Bilangan',      'Bilangan Cacah',                  'https://contoh-materi.test/bilangan-cacah'],
                ['SD',  'Bahasa Indonesia', 'K-13',    'Literasi',  'Teks Narasi',   'Mengidentifikasi ide pokok',      ''],
                ['SMP', 'Matematika',       'Merdeka', 'Numerasi',  'Perbandingan',  'Skala dan rasio',                 'https://contoh-materi.test/skala-rasio'],
                ['SMP', 'Bahasa Indonesia', 'K-13',    'Literasi',  'Teks Informasi','Menentukan gagasan utama',        ''],
                ['SMA', 'Matematika',       'Merdeka', 'Numerasi',  'Fungsi Kuadrat','Mencari nilai maksimum/minimum',  'https://contoh-materi.test/fungsi-kuadrat'],
                ['SMA', 'Bahasa Indonesia', 'K-13',    'Literasi',  'Teks Artikel',  'Menganalisis argumen kompleks',   ''],
            ],
        };

        $filename = $defaultJenjang
            ? 'template-materi-' . strtolower($defaultJenjang) . '.xls'
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
        $count = Material::count();
        Material::query()->delete();
        return back()->with('flash', [
            'type' => 'success',
            'message' => "Berhasil menghapus semua materi ($count data)."
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
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

            if (
                $payload['curriculum'] === null
                || $payload['subelement'] === ''
                || $payload['unit'] === ''
                || $payload['sub_unit'] === ''
            ) {
                $skipped++;
                continue;
            }

            if (! Schema::hasColumn('materials', 'jenjang')) {
                unset($payload['jenjang']);
            }

            if (! Schema::hasColumn('materials', 'link')) {
                unset($payload['link']);
            }

            $matchKeys = [
                'jenjang'    => $payload['jenjang'] ?? null,
                'mapel'      => $payload['mapel'],
                'curriculum' => $payload['curriculum'],
                'subelement' => $payload['subelement'],
                'unit'       => $payload['unit'],
                'sub_unit'   => $payload['sub_unit'],
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

        return back()->with('flash', [
            'type' => ($created + $updated) > 0 ? 'success' : 'warning',
            'message' => "Import materi selesai. Ditambah: {$created}, diperbarui: {$updated}, dilewati: {$skipped}.",
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

        if (!Schema::hasColumn('materials', 'jenjang')) {
            unset($validated['jenjang']);
        }
        if (!Schema::hasColumn('materials', 'link')) {
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

    public function index(Request $request): View {
        $filter     = $request->query('jenjang');
        $mapel      = $request->query('mapel');
        $curriculum = $request->query('curriculum');
        $subelement = $request->query('subelement');
        $unit       = $request->query('unit');
        $subUnit    = $request->query('sub_unit');
        $search     = trim((string) $request->query('search'));

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

        $materials = $materialsQuery
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
        $mapels      = $baseQuery->clone()->distinct()->whereNotNull('mapel')->where('mapel', '!=', '')->pluck('mapel')->sort()->values();
        $curriculums = $baseQuery->clone()->distinct()->pluck('curriculum')->sort()->values();
        $subelements = $baseQuery->clone()->distinct()->pluck('subelement')->sort()->values();
        $units       = $baseQuery->clone()->distinct()->pluck('unit')->sort()->values();
        $subUnits    = $baseQuery->clone()->distinct()->pluck('sub_unit')->sort()->values();

        return view('superadmin.materials', compact(
            'materials', 'filter',
            'mapel', 'curriculum', 'subelement', 'unit', 'subUnit', 'search',
            'mapels', 'curriculums', 'subelements', 'units', 'subUnits'
        ));
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
