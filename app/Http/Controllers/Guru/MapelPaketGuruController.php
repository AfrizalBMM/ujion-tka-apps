<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMapelPaketRequest;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use Illuminate\Http\RedirectResponse;

class MapelPaketGuruController extends Controller
{
    public function update(UpdateMapelPaketRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('view', $paket);
        $this->authorize('create', [Soal::class, $mapel]);

        $mapel->update($request->validated());

        return back()->with('flash', ['type' => 'success', 'message' => 'Konfigurasi mapel diperbarui.']);
    }
}
