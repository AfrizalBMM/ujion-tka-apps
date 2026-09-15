<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeksBacaanRequest;
use App\Http\Requests\UpdateTeksBacaanRequest;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\TeksBacaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeksBacaanGuruController extends Controller
{
    public function index(Request $request, PaketSoal $paket, MapelPaket $mapel): Response
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('view', $paket);

        $teksBacaans = $mapel->teksBacaans()->latest()->get();

        return Inertia::render('Guru/TeksBacaan/Index', [
            'paket' => [
                'id' => $paket->id,
                'nama' => $paket->nama,
            ],
            'mapel' => [
                'id' => $mapel->id,
                'nama_label' => $mapel->nama_label,
            ],
            'canManage' => $paket->isManagedByGuru($request->user()),
            'teksBacaans' => $teksBacaans->map(fn ($bacaan) => [
                'id' => $bacaan->id,
                'judul' => $bacaan->judul,
                'konten' => $bacaan->konten,
                'konten_limited' => Str::limit($bacaan->konten, 420),
            ])->values(),
        ]);
    }

    public function store(StoreTeksBacaanRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $mapel->teksBacaans()->create($request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Teks bacaan ditambahkan.']);
    }

    public function update(UpdateTeksBacaanRequest $request, PaketSoal $paket, MapelPaket $mapel, TeksBacaan $bacaan): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $bacaan->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $bacaan->update($request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Teks bacaan diperbarui.']);
    }

    public function destroy(PaketSoal $paket, MapelPaket $mapel, TeksBacaan $bacaan): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $bacaan->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        if ($bacaan->soals()->exists()) {
            return back()->with('flash', [
                'type' => 'warning',
                'message' => 'Teks bacaan tidak bisa dihapus karena masih dipakai oleh soal pada mapel ini.',
            ]);
        }

        $bacaan->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Teks bacaan dihapus.']);
    }
}
