<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MaterialController extends Controller
{
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
}
