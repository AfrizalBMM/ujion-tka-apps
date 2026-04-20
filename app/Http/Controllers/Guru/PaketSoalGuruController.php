<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PaketSoal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaketSoalGuruController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', PaketSoal::class);

        $paketSoals = PaketSoal::query()
            ->with(['jenjang', 'mapelPakets'])
            ->whereHas('jenjang', fn ($query) => $query->where('kode', $user->jenjang))
            ->latest()
            ->get();

        return view('guru.paket-soal.index', compact('paketSoals'));
    }

    public function show(PaketSoal $paket): View
    {
        $this->authorize('view', $paket);

        $paket->load([
            'jenjang',
            'mapelPakets.teksBacaans',
            'mapelPakets.soals.pilihanJawabans',
            'mapelPakets.soals.pasanganMenjodohkans',
            'exams' => fn ($q) => $q->with('examMapelTokens.mapelPaket')
                                    ->where('status', 'terbit')
                                    ->where('is_active', true)
                                    ->orderByDesc('tanggal_terbit'),
        ]);

        return view('guru.paket-soal.show', compact('paket'));
    }
}
