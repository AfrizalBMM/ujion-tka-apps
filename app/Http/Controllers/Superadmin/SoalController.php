<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Concerns\ManagesSoalCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoalRequest;
use App\Http\Requests\UpdateSoalRequest;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PasanganMenjodohkan;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\TeksBacaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SoalController extends Controller
{
    use ManagesSoalCrud;

    public function index(PaketSoal $paket, MapelPaket $mapel): InertiaResponse
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

        return Inertia::render('Superadmin/Soal/Index', [
            'paket' => $this->paketBrief($paket),
            'mapel' => $this->mapelBrief($mapel),
            'soals' => $mapel->soals->map(fn (Soal $soal) => [
                'id' => $soal->id,
                'nomor_soal' => $soal->nomor_soal,
                'tipe_label' => str($soal->tipe_soal)->replace('_', ' ')->headline()->toString(),
                'indikator_limited' => Str::limit((string) $soal->indikator, 100),
                'dimensi' => $soal->dimensi,
                'subdimensi' => $soal->subdimensi,
                'teks_bacaan_judul' => $soal->teksBacaan?->judul,
                'is_pilihan_ganda' => $soal->isPilihanGanda(),
                'pilihan_count' => $soal->pilihanJawabans->count(),
                'pasangan_count' => $soal->pasanganMenjodohkans->count(),
            ])->values()->all(),
        ]);
    }

    public function create(Request $request, PaketSoal $paket, MapelPaket $mapel): InertiaResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $tipeSoal = $mapel->isSurvey() ? 'pilihan_ganda' : ($request->string('tipe_soal')->toString() ?: 'pilihan_ganda');
        $teksBacaans = $mapel->teksBacaans()->latest()->get()
            ->map(fn (TeksBacaan $bacaan) => [
                'id' => $bacaan->id,
                'label' => $bacaan->judul ?: 'Teks bacaan #'.$bacaan->id,
            ])->values()->all();
        $nextNomor = ((int) $mapel->soals()->max('nomor_soal')) + 1;

        return Inertia::render('Superadmin/Soal/Create', [
            'paket' => $this->paketBrief($paket),
            'mapel' => $this->mapelBrief($mapel),
            'tipeSoal' => $tipeSoal,
            'teksBacaans' => $teksBacaans,
            'nextNomor' => $nextNomor,
        ]);
    }

    public function store(StoreSoalRequest $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);

        $this->persistSoal($request, $mapel);

        return redirect()->route('superadmin.soal.index', [$paket, $mapel])
            ->with('flash', ['type' => 'success', 'message' => 'Soal berhasil ditambahkan.']);
    }

    public function edit(PaketSoal $paket, MapelPaket $mapel, Soal $soal): InertiaResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $soal->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('update', $soal);

        $soal->load(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan']);
        $teksBacaans = $mapel->teksBacaans()->latest()->get()
            ->map(fn (TeksBacaan $bacaan) => [
                'id' => $bacaan->id,
                'label' => $bacaan->judul ?: 'Teks bacaan #'.$bacaan->id,
            ])->values()->all();

        return Inertia::render('Superadmin/Soal/Edit', [
            'paket' => $this->paketBrief($paket),
            'mapel' => $this->mapelBrief($mapel),
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
                'jawaban_benar' => $soal->pilihanJawabans->firstWhere('is_benar', true)?->kode,
                'pilihan' => $soal->pilihanJawabans->map(fn ($item) => [
                    'kode' => $item->kode,
                    'teks' => $item->teks,
                    'nilai_survey' => $item->nilai_survey,
                    'profil_label' => $item->profil_label,
                    'gambar_url' => $item->gambar_url,
                ])->values()->all(),
                'pasangan' => $soal->pasanganMenjodohkans->map(fn ($item) => [
                    'teks_kiri' => $item->teks_kiri,
                    'teks_kanan' => $item->teks_kanan,
                ])->values()->all(),
            ],
            'teksBacaans' => $teksBacaans,
        ]);
    }

    public function update(UpdateSoalRequest $request, PaketSoal $paket, MapelPaket $mapel, Soal $soal): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id || $soal->mapel_paket_id !== $mapel->id, 404);
        $this->authorize('update', $soal);

        $this->persistSoal($request, $mapel, $soal);

        return redirect()->route('superadmin.soal.index', [$paket, $mapel])
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

    // ─── Bank Soal Builder ────────────────────────────────────────────────────

    /**
     * Tampilkan halaman builder — pilih soal dari bank global untuk dimasukkan ke mapel paket.
     */
    public function bankBuilder(Request $request, PaketSoal $paket, MapelPaket $mapel): InertiaResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);
        abort_if($mapel->isSurvey(), 404);

        $paket->load('jenjang');
        $mapel->load('paketSoal');

        $existingGlobalIds = [];

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'question_type' => trim((string) $request->query('question_type', '')),
            'material_mapel' => trim((string) $request->query('material_mapel', '')),
            'jenjang_id' => trim((string) $request->query('jenjang_id', '')),
            'material_curriculum' => trim((string) $request->query('material_curriculum', '')),
            'material_subelement' => trim((string) $request->query('material_subelement', '')),
            'material_unit' => trim((string) $request->query('material_unit', '')),
            'material_sub_unit' => trim((string) $request->query('material_sub_unit', '')),
        ];

        $bankSoals = GlobalQuestion::where('is_active', true)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('question_text', 'like', '%'.$filters['search'].'%')
                        ->orWhere('reading_passage', 'like', '%'.$filters['search'].'%')
                        ->orWhere('material_sub_unit', 'like', '%'.$filters['search'].'%')
                        ->orWhere('material_unit', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when(in_array($filters['question_type'], ['multiple_choice', 'matching', 'short_answer'], true), fn ($q) => $q->where('question_type', $filters['question_type']))
            ->when($filters['material_mapel'] !== '', fn ($q) => $q->where('material_mapel', $filters['material_mapel']))
            ->when($filters['jenjang_id'] !== '', fn ($q) => $q->where('jenjang_id', $filters['jenjang_id']))
            ->when($filters['material_curriculum'] !== '', fn ($q) => $q->where('material_curriculum', $filters['material_curriculum']))
            ->when($filters['material_subelement'] !== '', fn ($q) => $q->where('material_subelement', $filters['material_subelement']))
            ->when($filters['material_unit'] !== '', fn ($q) => $q->where('material_unit', $filters['material_unit']))
            ->when($filters['material_sub_unit'] !== '', fn ($q) => $q->where('material_sub_unit', $filters['material_sub_unit']))
            ->latest()
            ->get();

        $jenjangId = $paket->jenjang_id;

        $jenjangs = Jenjang::orderBy('nama')->get();

        $mapels = GlobalQuestion::where('is_active', true)
            ->whereNotNull('material_mapel')
            ->where('material_mapel', '!=', '')
            ->distinct()->pluck('material_mapel');

        $curriculums = GlobalQuestion::where('is_active', true)
            ->whereNotNull('material_curriculum')
            ->distinct()->pluck('material_curriculum');
        $subelements = GlobalQuestion::where('is_active', true)
            ->whereNotNull('material_subelement')
            ->distinct()->pluck('material_subelement');
        $units = GlobalQuestion::where('is_active', true)
            ->whereNotNull('material_unit')
            ->distinct()->pluck('material_unit');
        $subUnits = GlobalQuestion::where('is_active', true)
            ->whereNotNull('material_sub_unit')
            ->distinct()->pluck('material_sub_unit');

        $soalCount = $mapel->soals()->count();
        $maxSoal = $mapel->jumlah_soal;
        $slotSisa = max(0, $maxSoal - $soalCount);

        return Inertia::render('Superadmin/Soal/BankBuilder', [
            'paket' => $this->paketBrief($paket),
            'mapel' => $this->mapelBrief($mapel),
            'bankSoals' => $bankSoals->map(fn (GlobalQuestion $gq) => [
                'id' => $gq->id,
                'question_type' => $gq->question_type,
                'type_label' => match ($gq->question_type) {
                    'multiple_choice' => 'Pilihan Ganda',
                    'matching' => 'Menjodohkan',
                    default => 'Jawaban Singkat',
                },
                'type_badge_class' => match ($gq->question_type) {
                    'multiple_choice' => 'badge-info',
                    'matching' => 'badge-warning',
                    default => 'badge-success',
                },
                'material_curriculum' => $gq->material_curriculum,
                'material_sub_unit' => $gq->material_sub_unit,
                'answer_key_limited' => $gq->answer_key ? Str::limit((string) $gq->answer_key, 30) : null,
                'question_text_limited' => Str::limit(strip_tags((string) $gq->question_text), 180),
                'reading_passage' => $gq->reading_passage,
                'options' => $gq->options,
                'material_subelement' => $gq->material_subelement,
                'material_unit' => $gq->material_unit,
            ])->values()->all(),
            'filters' => $filters,
            'filtersLimited' => [
                'material_sub_unit' => $filters['material_sub_unit'] !== '' ? Str::limit($filters['material_sub_unit'], 26) : null,
            ],
            'jenjangs' => $jenjangs,
            'mapels' => $mapels,
            'curriculums' => $curriculums,
            'subUnits' => $subUnits,
            'soalCount' => $soalCount,
            'maxSoal' => $maxSoal,
            'slotSisa' => $slotSisa,
        ]);
    }

    /**
     * Import soal yang dipilih dari bank global ke mapel paket (clone/copy).
     */
    public function importFromBank(Request $request, PaketSoal $paket, MapelPaket $mapel): RedirectResponse
    {
        abort_if($mapel->paket_soal_id !== $paket->id, 404);
        $this->authorize('create', [Soal::class, $mapel]);
        abort_if($mapel->isSurvey(), 404);

        $data = $request->validate([
            'global_question_ids' => ['required', 'array', 'min:1'],
            'global_question_ids.*' => ['integer', 'exists:global_questions,id'],
        ]);

        $selectedIds = array_values(array_unique($data['global_question_ids']));
        $bankSoals = GlobalQuestion::whereIn('id', $selectedIds)->get()->keyBy('id');

        $nextNomor = ((int) $mapel->soals()->max('nomor_soal'));
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($mapel, $bankSoals, $selectedIds, &$nextNomor, &$imported, &$skipped) {
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

                // Tentukan tipe soal yang kompatibel dengan tabel soals
                if ($mapel->isSurvey()) {
                    $skipped++;

                    continue;
                }

                $tipeSoal = match ($gq->question_type) {
                    'multiple_choice', 'short_answer' => 'pilihan_ganda',
                    'matching' => 'menjodohkan',
                    default => 'pilihan_ganda',
                };

                // Buat TeksBacaan terlebih dahulu jika ada reading_passage
                $teksBacaanId = null;
                if ($tipeSoal === 'pilihan_ganda' && $gq->reading_passage) {
                    $teksBacaan = TeksBacaan::create([
                        'mapel_paket_id' => $mapel->id,
                        'judul' => Str::limit($gq->question_text, 60),
                        'konten' => $gq->reading_passage,
                    ]);
                    $teksBacaanId = $teksBacaan->id;
                }

                // Buat soal
                $soal = Soal::create([
                    'mapel_paket_id' => $mapel->id,
                    'teks_bacaan_id' => $teksBacaanId,
                    'nomor_soal' => $nextNomor,
                    'tipe_soal' => $tipeSoal,
                    'indikator' => $gq->material_sub_unit ?? $gq->material_unit ?? 'Dari bank soal',
                    'pertanyaan' => $gq->question_text,
                    'bobot' => 1,
                ]);

                // Buat pilihan jawaban (untuk PG)
                if ($tipeSoal === 'pilihan_ganda' && ! empty($gq->options)) {
                    $kodes = ['A', 'B', 'C', 'D', 'E', 'F'];
                    $answerKeyText = $gq->answer_key;

                    foreach (array_values($gq->options) as $idx => $optionText) {
                        if ($idx > 5) {
                            break; // Maksimal 6 opsi, tapi schema hanya A-D → simpan hanya 4
                        }
                        $kode = $kodes[$idx] ?? null;

                        // Skip jika melebihi pilihan yang didukung DB (A-D)
                        if (! in_array($kode, ['A', 'B', 'C', 'D'], true)) {
                            break;
                        }

                        PilihanJawaban::create([
                            'soal_id' => $soal->id,
                            'kode' => $kode,
                            'teks' => (string) $optionText,
                            'is_benar' => (string) $optionText === $answerKeyText,
                        ]);
                    }
                }

                // Buat pasangan menjodohkan
                if ($tipeSoal === 'menjodohkan' && ! empty($gq->options)) {
                    foreach (array_values($gq->options) as $urutan => $pair) {
                        if (! is_array($pair) || empty($pair['left']) || empty($pair['right'])) {
                            continue;
                        }

                        PasanganMenjodohkan::create([
                            'soal_id' => $soal->id,
                            'teks_kiri' => $pair['left'],
                            'teks_kanan' => $pair['right'],
                            'urutan' => $urutan + 1,
                        ]);
                    }
                }

                $imported++;
            }
        });

        $message = "Berhasil menambahkan {$imported} soal ke mapel {$mapel->nama_label}.";
        if ($skipped > 0) {
            $message .= " {$skipped} soal dilewati (batas soal tercapai atau tidak ditemukan).";
        }

        return redirect()->route('superadmin.soal.index', [$paket, $mapel])
            ->with('flash', ['type' => 'success', 'message' => $message]);
    }

    private function paketBrief(PaketSoal $paket): array
    {
        return [
            'id' => $paket->id,
            'nama' => $paket->nama,
            'jenjang_kode' => $paket->jenjang?->kode,
        ];
    }

    private function mapelBrief(MapelPaket $mapel): array
    {
        return [
            'id' => $mapel->id,
            'nama_label' => $mapel->nama_label,
            'is_survey' => $mapel->isSurvey(),
            'jumlah_soal' => $mapel->jumlah_soal,
            'durasi_menit' => $mapel->durasi_menit,
            'soal_count' => $mapel->soals->count(),
        ];
    }
}
