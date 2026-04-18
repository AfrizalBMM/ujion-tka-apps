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
                ['SD', 'Merdeka', 'Literasi', 'Teks Narasi', 'Mengidentifikasi ide pokok', 'https://contoh-materi.test/ide-pokok'],
                ['SD', 'K-13', 'Numerasi', 'Bilangan', 'Operasi hitung dasar', ''],
            ],
            'SMP' => [
                ['SMP', 'Merdeka', 'Literasi', 'Teks Informasi', 'Menentukan gagasan utama', 'https://contoh-materi.test/gagasan-utama'],
                ['SMP', 'K-13', 'Numerasi', 'Perbandingan', 'Skala dan rasio', ''],
            ],
            default => [
                ['SD', 'Merdeka', 'Literasi', 'Teks Narasi', 'Mengidentifikasi ide pokok', 'https://contoh-materi.test/ide-pokok'],
                ['SMP', 'K-13', 'Numerasi', 'Perbandingan', 'Skala dan rasio', ''],
            ],
        };

        $filename = $defaultJenjang
            ? 'template-materi-' . strtolower($defaultJenjang) . '.xls'
            : 'template-materi.xls';

        return SpreadsheetTemplateExporter::download($filename, [
            'jenjang',
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
        Material::truncate();
        return back()->with('flash', [
            'type' => 'success',
            'message' => "Berhasil menghapus semua materi ($count data)."
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'default_jenjang' => ['nullable', 'in:SD,SMP'],
        ]);

        try {
            $rows = SpreadsheetTable::rowsFromUpload($validated['file']);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        $created = 0;
        $skipped = 0;

        $defaultJenjang = $this->normalizeJenjang($validated['default_jenjang'] ?? null);

        foreach ($rows as $row) {
            $payload = [
                'jenjang' => $this->normalizeJenjang($row['jenjang'] ?? null) ?? $defaultJenjang,
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

            Material::create($payload);
            $created++;
        }

        return back()->with('flash', [
            'type' => $created > 0 ? 'success' : 'warning',
            'message' => "Import materi selesai. Berhasil: {$created}, dilewati: {$skipped}.",
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenjang' => ['nullable', 'in:SD,SMP'],
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
        $filter = $request->query('jenjang');

        $materialsQuery = Material::query();
        if (Schema::hasColumn('materials', 'jenjang') && in_array($filter, ['SD', 'SMP', 'GLOBAL'], true)) {
            if ($filter === 'GLOBAL') {
                $materialsQuery->whereNull('jenjang');
            } else {
                $materialsQuery->where('jenjang', $filter);
            }
        }

        $materials = $materialsQuery->orderBy('curriculum')->orderBy('subelement')->orderBy('unit')->orderBy('sub_unit')->get();

        return view('superadmin.materials', compact('materials', 'filter'));
    }

    private function normalizeJenjang(?string $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['SD', 'SMP'], true) ? $normalized : null;
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
