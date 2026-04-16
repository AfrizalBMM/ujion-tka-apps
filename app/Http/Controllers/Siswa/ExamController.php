<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\JawabanSiswa;
use App\Models\MapelPaket;
use App\Models\Soal;
use App\Models\UjianSesi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function mulai(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'wa' => 'nullable|string|max:20',
        ]);

        $token = session('siswa_token');
        if (!$token) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Sesi ujian telah habis']);
        }

        $exam = Exam::with('paketSoal.mapelPakets')->where('token', $token)->where('is_active', true)->first();
        if (! $exam || ! $exam->paketSoal || $exam->paketSoal->mapelPakets->isEmpty()) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Ujian tidak ditemukan atau tidak aktif']);
        }

        $timerState = $exam->paketSoal->mapelPakets
            ->mapWithKeys(fn (MapelPaket $mapel) => [
                $mapel->id => [
                    'duration_seconds' => $mapel->durasi_menit * 60,
                    'remaining_seconds' => $mapel->durasi_menit * 60,
                    'started_at' => null,
                    'finished_at' => null,
                ],
            ])
            ->all();

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => $request->nama,
            'nomor_wa' => $request->wa,
            'session_token' => Str::random(60),
            'status' => 'menunggu',
            'timer_state' => $timerState,
        ]);

        session(['participant_token' => $sesi->session_token]);

        return redirect()->route('siswa.petunjuk');
    }

    public function petunjuk(): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();
        if (! $sesi) {
            return redirect()->route('siswa.login');
        }

        $sesi->load('paketSoal.mapelPakets', 'exam');

        return view('ujian.mulai', ['session' => $sesi, 'exam' => $sesi->exam, 'paket' => $sesi->paketSoal]);
    }

    public function showUjian(Request $request): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();
        if (! $sesi) {
            return redirect()->route('siswa.login');
        }

        $sesi->load([
            'exam',
            'paketSoal.jenjang',
            'paketSoal.mapelPakets',
            'jawabanSiswas',
        ]);

        if ($sesi->status === 'menunggu') {
            $sesi->update([
                'status' => 'mengerjakan',
                'waktu_mulai' => now(),
            ]);
        } elseif ($sesi->status === 'selesai') {
            return redirect()->route('siswa.selesai');
        }

        $mapels = $sesi->paketSoal->mapelPakets->sortBy('urutan')->values();
        $mapel = $mapels->firstWhere('id', (int) $request->query('mapel')) ?? $mapels->first();

        if (! $mapel) {
            return redirect()->route('siswa.petunjuk')->withErrors(['ujian' => 'Mapel pada paket belum tersedia.']);
        }

        $timerState = $sesi->timer_state ?? [];
        if (blank($timerState[$mapel->id]['started_at'] ?? null)) {
            $timerState[$mapel->id]['started_at'] = now()->toIso8601String();
            $sesi->update(['timer_state' => $timerState]);
            $sesi->refresh();
        }

        $questions = Soal::with(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan'])
            ->where('mapel_paket_id', $mapel->id)
            ->orderBy('nomor_soal')
            ->get()
            ->map(function (Soal $soal) use ($sesi) {
                $jawaban = $sesi->jawabanSiswas->firstWhere('soal_id', $soal->id);
                $matchingOptions = $soal->pasanganMenjodohkans
                    ->map(fn ($pair) => ['id' => $pair->id, 'label' => $pair->teks_kanan])
                    ->shuffle()
                    ->values();

                return [
                    'id' => $soal->id,
                    'nomor_soal' => $soal->nomor_soal,
                    'tipe_soal' => $soal->tipe_soal,
                    'indikator' => $soal->indikator,
                    'pertanyaan' => $soal->pertanyaan,
                    'gambar_url' => $soal->gambar_url,
                    'teks_bacaan' => $soal->teksBacaan ? [
                        'judul' => $soal->teksBacaan->judul,
                        'konten' => $soal->teksBacaan->konten,
                    ] : null,
                    'pilihan' => $soal->pilihanJawabans->map(fn ($item) => [
                        'kode' => $item->kode,
                        'teks' => $item->teks,
                        'gambar_url' => $item->gambar_url,
                    ])->values(),
                    'pasangan' => $soal->pasanganMenjodohkans->map(fn ($item) => [
                        'id' => $item->id,
                        'teks_kiri' => $item->teks_kiri,
                        'teks_kanan' => $item->teks_kanan,
                    ])->values(),
                    'matching_options' => $matchingOptions,
                    'jawaban_pg' => $jawaban?->jawaban_pg,
                    'jawaban_menjodohkan' => $jawaban?->jawaban_menjodohkan,
                    'is_ragu' => $jawaban?->is_ragu ?? false,
                ];
            });

        if ($questions->isEmpty()) {
            return redirect()->route('siswa.petunjuk')->withErrors(['ujian' => 'Soal ujian belum siap.']);
        }

        return view('ujian.pengerjaan', [
            'exam' => $sesi->exam,
            'session' => $sesi,
            'paket' => $sesi->paketSoal,
            'mapels' => $mapels,
            'currentMapel' => $mapel,
            'questions' => $questions,
            'timer' => $sesi->timer_state[$mapel->id],
        ]);
    }

    public function apiSaveAnswer(Request $request): JsonResponse
    {
        $sesi = $this->getActiveSession();

        if (! $sesi || $sesi->status === 'selesai') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'question_id' => 'required|exists:soals,id',
            'mapel_paket_id' => 'required|exists:mapel_pakets,id',
            'tipe_soal' => 'required|in:pilihan_ganda,menjodohkan',
            'jawaban_pg' => 'nullable|string|max:5',
            'jawaban_menjodohkan' => 'nullable|array',
            'jawaban_menjodohkan.*.pair_id' => 'nullable|integer',
            'jawaban_menjodohkan.*.match_id' => 'nullable|integer',
            'is_ragu' => 'nullable|boolean',
            'remaining_seconds' => 'nullable|integer|min:0',
        ]);

        $soal = Soal::where('id', $validated['question_id'])
            ->where('mapel_paket_id', $validated['mapel_paket_id'])
            ->firstOrFail();

        JawabanSiswa::updateOrCreate(
            ['ujian_sesi_id' => $sesi->id, 'soal_id' => $soal->id],
            [
                'tipe_soal' => $validated['tipe_soal'],
                'jawaban_pg' => $validated['tipe_soal'] === 'pilihan_ganda' ? ($validated['jawaban_pg'] ?? null) : null,
                'jawaban_menjodohkan' => $validated['tipe_soal'] === 'menjodohkan' ? ($validated['jawaban_menjodohkan'] ?? []) : null,
                'is_ragu' => (bool) ($validated['is_ragu'] ?? false),
            ]
        );

        if (isset($validated['remaining_seconds'])) {
            $timerState = $sesi->timer_state ?? [];
            $timerState[$validated['mapel_paket_id']]['remaining_seconds'] = $validated['remaining_seconds'];
            if ($validated['remaining_seconds'] <= 0) {
                $timerState[$validated['mapel_paket_id']]['finished_at'] = now()->toIso8601String();
            }

            $sesi->update(['timer_state' => $timerState]);
        }

        return response()->json(['status' => 'success']);
    }

    public function selesai(): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();

        if ($sesi && $sesi->status !== 'selesai') {
            $sesi->load([
                'paketSoal.mapelPakets.soals.pilihanJawabans',
                'paketSoal.mapelPakets.soals.pasanganMenjodohkans',
                'jawabanSiswas',
            ]);

            $sesi->update([
                'status' => 'selesai',
                'waktu_selesai' => now(),
                'skor' => $this->calculateScore($sesi),
            ]);

            session()->forget(['siswa_token', 'participant_token']);
        }

        return view('ujian.selesai', ['session' => $sesi]);
    }

    private function getActiveSession(): ?UjianSesi
    {
        $participantToken = session('participant_token');

        if (! $participantToken) {
            return null;
        }

        return UjianSesi::where('session_token', $participantToken)->first();
    }

    private function calculateScore(UjianSesi $sesi): float
    {
        $jawabanBySoal = $sesi->jawabanSiswas->keyBy('soal_id');
        $soals = $sesi->paketSoal->mapelPakets
            ->flatMap(fn ($mapel) => $mapel->soals);

        $maxScore = (float) $soals->sum('bobot');
        $earnedScore = 0.0;

        /** @var Soal $soal */
        foreach ($soals as $soal) {
            $jawaban = $jawabanBySoal->get($soal->id);
            if (! $jawaban) {
                continue;
            }

            if ($soal->isPilihanGanda()) {
                $correct = $soal->pilihanJawabans->firstWhere('is_benar', true)?->kode;
                if ($correct && $jawaban->jawaban_pg === $correct) {
                    $earnedScore += $soal->bobot;
                }
                continue;
            }

            $answers = collect($jawaban->jawaban_menjodohkan ?? [])
                ->mapWithKeys(fn ($item) => [($item['pair_id'] ?? null) => ($item['match_id'] ?? null)]);

            $allCorrect = $soal->pasanganMenjodohkans->every(function ($pair) use ($answers) {
                return (int) $answers->get($pair->id) === (int) $pair->id;
            });

            if ($allCorrect) {
                $earnedScore += $soal->bobot;
            }
        }

        if ($maxScore <= 0) {
            return 0.0;
        }

        return round(($earnedScore / $maxScore) * 100, 2);
    }
}
