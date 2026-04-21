<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMapelPaketRequest;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use Illuminate\Http\RedirectResponse;

class MapelPaketController extends Controller
{
    public function update(UpdateMapelPaketRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('update', $paket);

        $mapel->update($request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Konfigurasi mapel diperbarui.']);
    }

    public function destroyAllSoals(PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('update', $paket);

        $count = $mapel->soals()->count();
        $mapel->soals()->each(function ($soal) {
            $soal->pilihanJawabans()->delete();
            $soal->pasanganMenjodohkans()->delete();
            $soal->delete();
        });

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "{$count} soal pada {$mapel->nama_label} berhasil dihapus.",
        ]);
    }
}

