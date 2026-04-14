<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum' => ['required', 'in:K-13,Merdeka'],
            'subelement' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:120'],
            'sub_unit' => ['required', 'string', 'max:120'],
        ]);

        Material::create($validated);

        return back()->with('flash', ['type' => 'success', 'message' => 'Materi berhasil ditambahkan.']);
    }

    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Materi berhasil dihapus.']);
    }

    public function index() {
        $materials = \App\Models\Material::all();
        return view('superadmin.materials', compact('materials'));
    }
}
