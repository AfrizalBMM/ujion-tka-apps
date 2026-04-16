<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Concerns\ManagesSoalCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoalRequest;
use App\Http\Requests\UpdateSoalRequest;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SoalGuruController extends Controller
{
    use ManagesSoalCrud;

    public function index(PaketSoal $paket, MapelPaket $mapel): View
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('view', $paket);

        $mapel->load([
            'paketSoal.jenjang',
            'teksBacaans',
            'soals.teksBacaan',
            'soals.pilihanJawabans',
            'soals.pasanganMenjodohkans',
        ]);

        return view('guru.soal.index', compact('paket', 'mapel'));
    }

    public function create(Request $request, PaketSoal $paket, MapelPaket $mapel): View
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $tipeSoal = $request->string('tipe_soal')->toString() ?: 'pilihan_ganda';
        $teksBacaans = $mapel->teksBacaans()->latest()->get();
        $nextNomor = ((int) $mapel->soals()->max('nomor_soal')) + 1;

        return view('guru.soal.create', compact('paket', 'mapel', 'tipeSoal', 'teksBacaans', 'nextNomor'));
    }

    public function store(StoreSoalRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $this->persistSoal($request, $mapel);

        return redirect()->route('guru.soal.index', [$paket, $mapel])
            ->with('flash', ['type' => 'success', 'message' => 'Soal berhasil ditambahkan.']);
    }

    public function edit(PaketSoal $paket, MapelPaket $mapel, Soal $soal): View
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $soal->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('update', $soal);

        $soal->load(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan']);
        $teksBacaans = $mapel->teksBacaans()->latest()->get();

        return view('guru.soal.edit', compact('paket', 'mapel', 'soal', 'teksBacaans'));
    }

    public function update(UpdateSoalRequest $request, PaketSoal $paket, MapelPaket $mapel, Soal $soal): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $soal->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('update', $soal);

        $this->persistSoal($request, $mapel, $soal);

        return redirect()->route('guru.soal.index', [$paket, $mapel])
            ->with('flash', ['type' => 'success', 'message' => 'Soal berhasil diperbarui.']);
    }

    public function destroy(PaketSoal $paket, MapelPaket $mapel, Soal $soal): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $soal->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('delete', $soal);

        $soal->load('pilihanJawabans');
        $this->deleteSoalAssets($soal);
        $soal->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal dihapus.']);
    }
}
