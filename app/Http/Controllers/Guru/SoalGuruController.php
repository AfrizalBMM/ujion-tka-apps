<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Concerns\ManagesSoalCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoalRequest;
use App\Http\Requests\UpdateSoalRequest;
use App\Models\GlobalQuestion;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SoalGuruController extends Controller
{
    use ManagesSoalCrud;

    public function index(Request $request, PaketSoal $paket, MapelPaket $mapel): Response
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

        $canManage = $paket->isManagedByGuru($request->user());
        $isSurvey = $mapel->isSurvey();

        $soals = $mapel->soals
            ->map(fn ($soal) => [
                'id' => $soal->id,
                'nomor_soal' => $soal->nomor_soal,
                'tipe_label' => str($soal->tipe_soal)->replace('_', ' ')->headline()->toString(),
                'indikator_limited' => Str::limit($soal->indikator, 100),
                'dimensi' => $soal->dimensi,
                'subdimensi' => $soal->subdimensi,
                'teks_bacaan_judul' => $soal->teksBacaan?->judul ?? '-',
                'jawaban_label' => $soal->isPilihanGanda()
                    ? $soal->pilihanJawabans->count().' pilihan'
                    : $soal->pasanganMenjodohkans->count().' pasangan',
            ])
            ->values();

        $bankSoals = collect();
        if ($canManage && ! $isSurvey) {
            $bankSoals = GlobalQuestion::where('is_active', true)
                ->where('jenjang_id', $paket->jenjang_id)
                ->where('material_mapel', $mapel->nama_mapel)
                ->latest()->take(30)->get()
                ->map(fn ($gq) => [
                    'id' => $gq->id,
                    'question_text_limited' => Str::limit(strip_tags($gq->question_text), 80),
                    'material_mapel' => $gq->material_mapel,
                    'material_curriculum' => $gq->material_curriculum,
                    'jenjang_nama' => $gq->jenjang?->nama ?? '-',
                ])
                ->values();
        }

        $existingIds = $mapel->soals->pluck('global_question_id')->filter()->values()->all();

        return Inertia::render('Guru/Soal/Index', [
            'paket' => [
                'id' => $paket->id,
                'nama' => $paket->nama,
            ],
            'mapel' => [
                'id' => $mapel->id,
                'nama_label' => $mapel->nama_label,
                'is_survey' => $isSurvey,
            ],
            'canManage' => $canManage,
            'soals' => $soals,
            'bankSoals' => $bankSoals,
            'existingIds' => $existingIds,
        ]);
    }

    public function create(Request $request, PaketSoal $paket, MapelPaket $mapel): Response
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $tipeSoal = $mapel->isSurvey() ? 'pilihan_ganda' : ($request->string('tipe_soal')->toString() ?: 'pilihan_ganda');
        $teksBacaans = $mapel->teksBacaans()->latest()->get();
        $nextNomor = ((int) $mapel->soals()->max('nomor_soal')) + 1;

        return Inertia::render('Guru/Soal/Create', [
            'paket' => [
                'id' => $paket->id,
                'nama' => $paket->nama,
            ],
            'mapel' => [
                'id' => $mapel->id,
                'nama_label' => $mapel->nama_label,
                'jumlah_soal' => $mapel->jumlah_soal,
                'is_survey' => $mapel->isSurvey(),
            ],
            'tipeSoal' => $tipeSoal,
            'teksBacaans' => $teksBacaans->map(fn ($bacaan) => [
                'id' => $bacaan->id,
                'judul' => $bacaan->judul,
            ])->values(),
            'nextNomor' => $nextNomor,
        ]);
    }

    public function store(StoreSoalRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $this->persistSoal($request, $mapel);

        return redirect()->route('guru.soal.index', [$paket, $mapel])
            ->with('flash', ['type' => 'success', 'message' => 'Soal berhasil ditambahkan.']);
    }

    public function edit(PaketSoal $paket, MapelPaket $mapel, Soal $soal): Response
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $soal->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('update', $soal);

        $soal->load(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan']);
        $teksBacaans = $mapel->teksBacaans()->latest()->get();

        return Inertia::render('Guru/Soal/Edit', [
            'paket' => [
                'id' => $paket->id,
                'nama' => $paket->nama,
            ],
            'mapel' => [
                'id' => $mapel->id,
                'nama_label' => $mapel->nama_label,
                'jumlah_soal' => $mapel->jumlah_soal,
                'is_survey' => $mapel->isSurvey(),
            ],
            'soal' => [
                'id' => $soal->id,
                'nomor_soal' => $soal->nomor_soal,
                'tipe_soal' => $soal->tipe_soal,
                'teks_bacaan_id' => $soal->teks_bacaan_id,
                'bobot' => $soal->bobot,
                'indikator' => $soal->indikator,
                'dimensi' => $soal->dimensi,
                'subdimensi' => $soal->subdimensi,
                'kategori_profil' => $soal->kategori_profil,
                'arah_skor' => $soal->arah_skor,
                'pertanyaan' => $soal->pertanyaan,
                'pembahasan' => $soal->pembahasan,
                'gambar_url' => $soal->gambar_url,
                'pilihan' => $soal->pilihanJawabans->map(fn ($item) => [
                    'kode' => $item->kode,
                    'teks' => $item->teks,
                    'nilai_survey' => $item->nilai_survey,
                    'profil_label' => $item->profil_label,
                    'is_benar' => (bool) $item->is_benar,
                    'gambar_url' => $item->gambar_url,
                ])->values(),
                'pasangan' => $soal->pasanganMenjodohkans->map(fn ($item) => [
                    'teks_kiri' => $item->teks_kiri,
                    'teks_kanan' => $item->teks_kanan,
                ])->values(),
            ],
            'teksBacaans' => $teksBacaans->map(fn ($bacaan) => [
                'id' => $bacaan->id,
                'judul' => $bacaan->judul,
            ])->values(),
        ]);
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
            'global_question_ids' => ['required', 'array', 'min:1'],
            'global_question_ids.*' => ['integer', 'exists:global_questions,id'],
        ]);

        $selectedIds = array_values(array_unique($data['global_question_ids']));
        $bankSoals = GlobalQuestion::whereIn('id', $selectedIds)->get()->keyBy('id');

        $nextNomor = ((int) $mapel->soals()->max('nomor_soal'));
        $imported = 0;
        $skipped = 0;

        \DB::transaction(function () use ($mapel, $bankSoals, $selectedIds, &$nextNomor, &$imported, &$skipped) {
            $currentCount = $mapel->soals()->count();
            $maxSoal = $mapel->jumlah_soal;

            foreach ($selectedIds as $gqId) {
                /** @var GlobalQuestion|null $gq */
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
                    'indikator' => $gq->indikator ?? 'Diimpor dari bank soal Ujion',
                    'pertanyaan' => $gq->question_text,
                    'dimensi' => $gq->dimensi,
                    'subdimensi' => $gq->subdimensi,
                    'kategori_profil' => $gq->kategori_profil,
                    'arah_skor' => $gq->arah_skor ?: 'positif',
                    'bobot' => $gq->bobot ?? 1,
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
                            'teks_kiri' => $opt['left'] ?? '',
                            'teks_kanan' => $opt['right'] ?? '',
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
