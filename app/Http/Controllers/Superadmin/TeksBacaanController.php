<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeksBacaanRequest;
use App\Http\Requests\UpdateTeksBacaanRequest;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\TeksBacaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeksBacaanController extends Controller
{
    public function index(PaketSoal $paket, MapelPaket $mapel): View
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('view', $paket);

        $teksBacaans = $mapel->teksBacaans()->latest()->get();

        return view('superadmin.teks-bacaan.index', compact('paket', 'mapel', 'teksBacaans'));
    }

    public function store(StoreTeksBacaanRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [\App\Models\Soal::class, $mapel]);

        $mapel->teksBacaans()->create($request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Teks bacaan ditambahkan.']);
    }

    public function update(UpdateTeksBacaanRequest $request, PaketSoal $paket, MapelPaket $mapel, TeksBacaan $bacaan): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $bacaan->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('create', [\App\Models\Soal::class, $mapel]);

        $bacaan->update($request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Teks bacaan diperbarui.']);
    }

    public function destroy(PaketSoal $paket, MapelPaket $mapel, TeksBacaan $bacaan): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $bacaan->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('create', [\App\Models\Soal::class, $mapel]);

        $bacaan->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Teks bacaan dihapus.']);
    }
}

