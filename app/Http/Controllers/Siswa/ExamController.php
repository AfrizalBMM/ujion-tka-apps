<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\JawabanSiswa;
use App\Models\MapelPaket;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Support\SurveyAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    // ─── Mulai (identitas form submit) ─────────────────────────────────────────

    public function mulai(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'wa'   => 'nullable|string|max:20',
        ]);

        $examId  = session('siswa_exam_id');
        $mapelId = session('siswa_mapel_id');

        if (! $examId || ! $mapelId) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Sesi ujian telah habis. Masukkan token kembali.']);
        }

        $exam  = Exam::with('paketSoal')->find($examId);
        $mapel = MapelPaket::find($mapelId);

        if (! $exam || ! $mapel || ! $exam->paketSoal) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Ujian atau mapel tidak ditemukan.']);
        }

        // Cek apakah sudah ada sesi sebelumnya untuk WA yang sama di mapel ini
        $existingSesi = null;
        if ($request->wa) {
            $existingSesi = UjianSesi::where('exam_id', $examId)
                ->where('mapel_paket_id', $mapelId)
                ->where('nomor_wa', $request->wa)
                ->whereIn('status', ['menunggu', 'mengerjakan'])
                ->latest()
                ->first();
        }

        if ($existingSesi) {
            session(['participant_token' => $existingSesi->session_token]);
            return redirect()->route('siswa.petunjuk');
        }

        $timerState = [
            $mapel->id => [
                'duration_seconds'  => $mapel->durasi_menit * 60,
                'remaining_seconds' => $mapel->durasi_menit * 60,
                'started_at'        => null,
                'finished_at'       => null,
            ],
        ];

        $sesi = UjianSesi::create([
            'exam_id'        => $exam->id,
            'paket_soal_id'  => $exam->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'nama'           => $request->nama,
            'nomor_wa'       => $request->wa,
            'session_token'  => Str::random(60),
            'status'         => 'menunggu',
            'timer_state'    => $timerState,
        ]);

        session(['participant_token' => $sesi->session_token]);

        return redirect()->route('siswa.petunjuk');
    }

    // ─── Petunjuk ────────────────────────────────────────────────────────────────

    public function petunjuk(): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();
        if (! $sesi) {
            return redirect()->route('siswa.login');
        }

        $sesi->load(['exam', 'paketSoal', 'mapelPaket']);

        return view('ujian.mulai', [
            'session' => $sesi,
            'exam'    => $sesi->exam,
            'paket'   => $sesi->paketSoal,
            'mapel'   => $sesi->mapelPaket,
        ]);
    }

    // ─── Show Ujian ──────────────────────────────────────────────────────────────

    public function showUjian(Request $request): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();
        if (! $sesi) {
            return redirect()->route('siswa.login');
        }

        $sesi->load(['exam', 'paketSoal', 'mapelPaket', 'jawabanSiswas']);

        if ($sesi->status === 'selesai') {
            return redirect()->route('siswa.selesai');
        }

        if ($sesi->status === 'menunggu') {
            $sesi->update([
                'status'      => 'mengerjakan',
                'waktu_mulai' => now(),
            ]);
            $sesi->refresh();
        }

        $mapel = $sesi->mapelPaket;

        if (! $mapel) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Mapel tidak ditemukan pada sesi ini.']);
        }

        // Tandai timer mulai untuk mapel ini
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
                    'id'                  => $soal->id,
                    'nomor_soal'          => $soal->nomor_soal,
                    'tipe_soal'           => $soal->tipe_soal,
                    'jenis_instrumen'     => $soal->jenis_instrumen,
                    'indikator'           => $soal->indikator,
                    'dimensi'             => $soal->dimensi,
                    'subdimensi'          => $soal->subdimensi,
                    'pertanyaan'          => $soal->pertanyaan,
                    'gambar_url'          => $soal->gambar_url,
                    'teks_bacaan'         => $soal->teksBacaan ? [
                        'judul'  => $soal->teksBacaan->judul,
                        'konten' => $soal->teksBacaan->konten,
                    ] : null,
                    'pilihan'             => $soal->pilihanJawabans->map(fn ($item) => [
                        'kode'      => $item->kode,
                        'teks'      => $item->teks,
                        'gambar_url'=> $item->gambar_url,
                    ])->values(),
                    'pasangan'            => $soal->pasanganMenjodohkans->map(fn ($item) => [
                        'id'        => $item->id,
                        'teks_kiri' => $item->teks_kiri,
                        'teks_kanan'=> $item->teks_kanan,
                    ])->values(),
                    'matching_options'    => $matchingOptions,
                    'jawaban_pg'          => $jawaban?->jawaban_pg,
                    'jawaban_menjodohkan' => $jawaban?->jawaban_menjodohkan,
                    'is_ragu'             => $jawaban?->is_ragu ?? false,
                ];
            });

        if ($questions->isEmpty()) {
            return redirect()->route('siswa.petunjuk')->withErrors(['ujian' => 'Soal ujian belum siap untuk mapel ini.']);
        }

        $timerMapel = $sesi->timer_state[$mapel->id] ?? [
            'duration_seconds'  => $mapel->durasi_menit * 60,
            'remaining_seconds' => $mapel->durasi_menit * 60,
        ];

        return view('ujian.pengerjaan', [
            'exam'         => $sesi->exam,
            'session'      => $sesi,
            'paket'        => $sesi->paketSoal,
            'mapel'        => $mapel,
            'questions'    => $questions,
            'timer'        => $timerMapel,
        ]);
    }

    // ─── API Save Answer ─────────────────────────────────────────────────────────

    public function apiSaveAnswer(Request $request): JsonResponse
    {
        $sesi = $this->getActiveSession();

        if (! $sesi || $sesi->status === 'selesai') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'question_id'                  => 'required|exists:soals,id',
            'mapel_paket_id'               => 'required|exists:mapel_pakets,id',
            'tipe_soal'                    => 'required|in:pilihan_ganda,menjodohkan',
            'jawaban_pg'                   => 'nullable|string|max:5',
            'jawaban_menjodohkan'          => 'nullable|array',
            'jawaban_menjodohkan.*.pair_id'=> 'nullable|integer',
            'jawaban_menjodohkan.*.match_id'=> 'nullable|integer',
            'is_ragu'                      => 'nullable|boolean',
            'remaining_seconds'            => 'nullable|integer|min:0',
        ]);

        $soal = Soal::where('id', $validated['question_id'])
            ->where('mapel_paket_id', $validated['mapel_paket_id'])
            ->firstOrFail();

        JawabanSiswa::updateOrCreate(
            ['ujian_sesi_id' => $sesi->id, 'soal_id' => $soal->id],
            [
                'tipe_soal'           => $validated['tipe_soal'],
                'jawaban_pg'          => $validated['tipe_soal'] === 'pilihan_ganda' ? ($validated['jawaban_pg'] ?? null) : null,
                'jawaban_menjodohkan' => $validated['tipe_soal'] === 'menjodohkan' ? ($validated['jawaban_menjodohkan'] ?? []) : null,
                'is_ragu'             => (bool) ($validated['is_ragu'] ?? false),
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

    // ─── Selesai ─────────────────────────────────────────────────────────────────

    public function selesai(): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();

        if ($sesi && $sesi->status !== 'selesai') {
            $sesi->load([
                'mapelPaket.soals.pilihanJawabans',
                'mapelPaket.soals.pasanganMenjodohkans',
                'jawabanSiswas',
            ]);

            $profilRingkasan = $sesi->mapelPaket?->usesProfiling()
                ? SurveyAnalytics::sessionProfile($sesi)
                : null;

            $sesi->update([
                'status'        => 'selesai',
                'waktu_selesai' => now(),
                'skor'          => $this->calculateScore($sesi),
                'profil_ringkasan' => $profilRingkasan,
            ]);

            session()->forget(['siswa_mapel_token', 'siswa_exam_id', 'siswa_mapel_id', 'participant_token']);
        }

        return view('ujian.selesai', ['session' => $sesi]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

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
        $mapel           = $sesi->mapelPaket;

        if ($mapel?->usesProfiling()) {
            return SurveyAnalytics::sessionProfile($sesi)['score_percent'];
        }

        $soals           = $mapel?->soals ?? collect();
        $jawabanBySoal   = $sesi->jawabanSiswas->keyBy('soal_id');

        $maxScore    = (float) $soals->sum('bobot');
        $earnedScore = 0.0;

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

            // Menjodohkan: bandingkan pair_id → match_id dengan pair_id → pair->id yg benar
            $answers = collect($jawaban->jawaban_menjodohkan ?? [])
                ->mapWithKeys(fn ($item) => [($item['pair_id'] ?? null) => ($item['match_id'] ?? null)]);

            $allCorrect = $soal->pasanganMenjodohkans->every(function ($pair) use ($answers) {
                // jawaban benar: match_id === id pasangan yang sesuai (teks_kiri → teks_kanan-nya sendiri)
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
