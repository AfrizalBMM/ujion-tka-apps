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

        $tipeSoal = $mapel->isSurvey() ? 'pilihan_ganda' : ($request->string('tipe_soal')->toString() ?: 'pilihan_ganda');
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

    /**
     * Import soal dari bank soal Ujion (GlobalQuestion) ke mapel paket guru.
     */
    public function importFromUjion(Request $request, PaketSoal $paket, MapelPaket $mapel)
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $data = $request->validate([
            'global_question_ids'   => ['required', 'array', 'min:1'],
            'global_question_ids.*' => ['integer', 'exists:global_questions,id'],
        ]);

        $selectedIds = array_values(array_unique($data['global_question_ids']));
        $bankSoals   = \App\Models\GlobalQuestion::whereIn('id', $selectedIds)->get()->keyBy('id');

        $nextNomor = ((int) $mapel->soals()->max('nomor_soal'));
        $imported  = 0;
        $skipped   = 0;

        \DB::transaction(function () use ($mapel, $bankSoals, $selectedIds, &$nextNomor, &$imported, &$skipped) {
            $currentCount = $mapel->soals()->count();
            $maxSoal      = $mapel->jumlah_soal;

            foreach ($selectedIds as $gqId) {
                /** @var \App\Models\GlobalQuestion|null $gq */
                $gq = $bankSoals->get($gqId);
                if (! $gq) {
                    $skipped++;
                    continue;
                }

                if ($currentCount >= $maxSoal) {
                    $skipped++;
                    continue;
                }

                $nextNomor++;
                $currentCount++;

                // Clone ke Soal guru
                $soal = $mapel->soals()->create([
                    'nomor_soal' => $nextNomor,
                    'tipe_soal' => $gq->question_type ?? 'pilihan_ganda',
                    'indikator' => $gq->indikator ?? null,
                    'pertanyaan' => $gq->question_text,
                    'dimensi' => $gq->dimensi ?? null,
                    'subdimensi' => $gq->subdimensi ?? null,
                    'kategori_profil' => $gq->kategori_profil ?? null,
                    'arah_skor' => $gq->arah_skor ?? null,
                    'bobot' => $gq->bobot ?? null,
                ]);

                // Pilihan ganda
                if ($soal->isPilihanGanda() && is_array($gq->options)) {
                    foreach ($gq->options as $idx => $opt) {
                        $soal->pilihanJawabans()->create([
                            'kode' => chr(65 + $idx),
                            'teks' => $opt,
                            'is_benar' => isset($gq->answer_key) && $gq->answer_key === chr(65 + $idx),
                        ]);
                    }
                }
                // Menjodohkan
                if ($soal->isMenjodohkan() && is_array($gq->options)) {
                    foreach ($gq->options as $idx => $opt) {
                        $soal->pasanganMenjodohkans()->create([
                            'urutan' => $idx + 1,
                            'pernyataan' => $opt['left'] ?? '',
                            'jawaban' => $opt['right'] ?? '',
                        ]);
                    }
                }
                $imported++;
            }
        });

        return redirect()->route('guru.soal.index', [$paket, $mapel])
            ->with('flash', ['type' => 'success', 'message' => "Import $imported soal dari Ujion berhasil. $skipped dilewati."]);
    }
}
