<?php

namespace App\Http\Controllers;

use App\Models\Jenjang;
use App\Models\Material;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class KisiKisiController extends Controller
{
    public function index(): View
    {
        $jenjangs = Jenjang::query()->orderBy('urutan')->orderBy('id')->get();

        $stats = Material::query()
            ->selectRaw('jenjang, count(distinct mapel) as mapel_count, count(*) as topic_count')
            ->groupBy('jenjang')
            ->get()
            ->keyBy(fn ($row) => strtoupper((string) $row->jenjang));

        return view('kisi-kisi.index', [
            'jenjangs' => $jenjangs,
            'stats' => $stats,
        ]);
    }

    public function jenjang(string $jenjang): View
    {
        $jenjangModel = $this->resolveJenjang($jenjang);

        $mapels = Material::query()
            ->whereRaw('upper(jenjang) = ?', [strtoupper($jenjangModel->kode)])
            ->selectRaw('mapel, count(*) as topic_count')
            ->groupBy('mapel')
            ->orderBy('mapel')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->mapel,
                'slug' => Str::slug($row->mapel),
                'topic_count' => $row->topic_count,
            ]);

        return view('kisi-kisi.jenjang', [
            'jenjang' => $jenjangModel,
            'mapels' => $mapels,
        ]);
    }

    public function mapel(string $jenjang, string $mapel): View
    {
        $jenjangModel = $this->resolveJenjang($jenjang);

        $mapelName = Material::query()
            ->whereRaw('upper(jenjang) = ?', [strtoupper($jenjangModel->kode)])
            ->select('mapel')
            ->distinct()
            ->pluck('mapel')
            ->first(fn ($name) => Str::slug((string) $name) === $mapel);

        abort_if($mapelName === null, 404);

        $materials = Material::query()
            ->whereRaw('upper(jenjang) = ?', [strtoupper($jenjangModel->kode)])
            ->where('mapel', $mapelName)
            ->orderBy('curriculum')
            ->orderBy('subelement')
            ->orderBy('unit')
            ->orderBy('sub_unit')
            ->orderBy('id')
            ->get();

        return view('kisi-kisi.mapel', [
            'jenjang' => $jenjangModel,
            'mapelName' => $mapelName,
            'mapelSlug' => $mapel,
            'materials' => $materials,
        ]);
    }

    private function resolveJenjang(string $jenjang): Jenjang
    {
        return Jenjang::query()
            ->whereRaw('lower(kode) = ?', [strtolower($jenjang)])
            ->firstOrFail();
    }
}
