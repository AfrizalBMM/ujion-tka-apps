<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppBlast;
use App\Models\Exam;
use App\Models\JawabanSiswa;
use App\Models\LandingExamOrder;
use App\Models\MapelPaket;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Services\WaMessageTemplateService;
use App\Support\MatchingKey;
use App\Support\NameMatcher;
use App\Support\SurveyAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    // ─── Mulai (identitas form submit) ─────────────────────────────────────────

    public function mulai(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'wa' => 'nullable|string|max:20',
        ]);

        $examId = session('siswa_exam_id');
        $mapelId = session('siswa_mapel_id');

        if (! $examId || ! $mapelId) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Sesi ujian telah habis. Masukkan token kembali.']);
        }

        $exam = Exam::with('paketSoal')->find($examId);
        $mapel = MapelPaket::find($mapelId);

        if (! $exam || ! $mapel || ! $exam->paketSoal) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Ujian atau mapel tidak ditemukan.']);
        }

        // Cek apakah sudah ada sesi sebelumnya untuk WA yang sama di mapel ini.
        // Resume hanya jika WA + nama keduanya cocok (anti impersonasi antar siswa).
        $existingSesi = null;
        if ($request->wa) {
            $existingSesi = UjianSesi::where('exam_id', $examId)
                ->where('mapel_paket_id', $mapelId)
                ->where('nomor_wa', $request->wa)
                ->whereIn('status', ['menunggu', 'mengerjakan'])
                ->latest()
                ->first();

            if ($existingSesi && ! NameMatcher::matches($existingSesi->nama, $request->nama)) {
                $existingSesi = null;
            }
        }

        if ($existingSesi) {
            session(['participant_token' => $existingSesi->session_token]);

            return redirect()->route('siswa.petunjuk');
        }

        if ($exam->max_peserta && $exam->max_peserta > 0) {
            $jumlahPeserta = UjianSesi::where('exam_id', $exam->id)->count();

            if ($jumlahPeserta >= $exam->max_peserta) {
                return redirect()->route('siswa.identitas')->withErrors([
                    'nama' => 'Kuota peserta ujian ini sudah penuh. Silakan hubungi guru/pengawas Anda.',
                ]);
            }
        }

        $timerState = [
            $mapel->id => [
                'duration_seconds' => $mapel->durasi_menit * 60,
                'remaining_seconds' => $mapel->durasi_menit * 60,
                'started_at' => null,
                'finished_at' => null,
            ],
        ];

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'nama' => $request->nama,
            'nomor_wa' => $request->wa,
            'session_token' => Str::random(60),
            'status' => 'menunggu',
            'timer_state' => $timerState,
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
            'exam' => $sesi->exam,
            'paket' => $sesi->paketSoal,
            'mapel' => $sesi->mapelPaket,
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
                'status' => 'mengerjakan',
                'waktu_mulai' => now(),
            ]);
            $sesi->refresh();
        }

        $mapel = $sesi->mapelPaket;

        if (! $mapel) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Mapel tidak ditemukan pada sesi ini.']);
        }

        $timerMapel = $this->syncTimerStateForMapel($sesi, $mapel, true);

        if (($timerMapel['remaining_seconds'] ?? 0) <= 0) {
            return redirect()->route('siswa.selesai');
        }

        $seed = $sesi->session_token;

        $questions = Soal::with(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan'])
            ->where('mapel_paket_id', $mapel->id)
            ->orderBy('nomor_soal')
            ->get()
            ->map(function (Soal $soal) use ($sesi, $seed) {
                $jawaban = $sesi->jawabanSiswas->firstWhere('soal_id', $soal->id);

                // Opsi menjodohkan: urutan stabil per sesi (seeded), tanpa id mentah.
                $matchingOptions = $soal->pasanganMenjodohkans
                    ->sortBy(fn ($pair) => sha1('shuffle:'.$pair->id.':'.$seed))
                    ->values()
                    ->map(fn ($pair) => [
                        'key' => MatchingKey::optKey((int) $pair->id, $seed),
                        'label' => $pair->teks_kanan,
                    ])
                    ->values();

                // Jawaban tersimpan (pair_id/match_id) dikonversi ke key opaque.
                $jawabanMenjodohkan = collect($jawaban?->jawaban_menjodohkan ?? [])
                    ->map(fn ($item) => [
                        'row_key' => MatchingKey::rowKey((int) ($item['pair_id'] ?? 0), $seed),
                        'opt_key' => MatchingKey::optKey((int) ($item['match_id'] ?? 0), $seed),
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $soal->id,
                    'nomor_soal' => $soal->nomor_soal,
                    'tipe_soal' => $soal->tipe_soal,
                    'jenis_instrumen' => $soal->jenis_instrumen,
                    'indikator' => $soal->indikator,
                    'dimensi' => $soal->dimensi,
                    'subdimensi' => $soal->subdimensi,
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
                        'key' => MatchingKey::rowKey((int) $item->id, $seed),
                        'teks_kiri' => $item->teks_kiri,
                    ])->values(),
                    'matching_options' => $matchingOptions,
                    'jawaban_pg' => $jawaban?->jawaban_pg,
                    'jawaban_menjodohkan' => $jawabanMenjodohkan,
                    'is_ragu' => $jawaban?->is_ragu ?? false,
                ];
            });

        if ($questions->isEmpty()) {
            return redirect()->route('siswa.petunjuk')->withErrors(['ujian' => 'Soal ujian belum siap untuk mapel ini.']);
        }

        return view('ujian.pengerjaan', [
            'exam' => $sesi->exam,
            'session' => $sesi,
            'paket' => $sesi->paketSoal,
            'mapel' => $mapel,
            'questions' => $questions,
            'timer' => $timerMapel,
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
            'question_id' => 'required|exists:soals,id',
            'mapel_paket_id' => 'required|exists:mapel_pakets,id',
            'tipe_soal' => 'required|in:pilihan_ganda,menjodohkan',
            'jawaban_pg' => 'nullable|string|max:5',
            'jawaban_menjodohkan' => 'nullable|array',
            'jawaban_menjodohkan.*.row_key' => 'nullable|string|max:64',
            'jawaban_menjodohkan.*.opt_key' => 'nullable|string|max:64',
            'is_ragu' => 'nullable|boolean',
        ]);

        if ((int) $validated['mapel_paket_id'] !== (int) $sesi->mapel_paket_id) {
            return response()->json(['error' => 'Mapel tidak sesuai dengan sesi ujian Anda.'], 403);
        }

        $soal = Soal::with('pasanganMenjodohkans')
            ->where('id', $validated['question_id'])
            ->where('mapel_paket_id', $validated['mapel_paket_id'])
            ->firstOrFail();

        $mapel = MapelPaket::findOrFail($validated['mapel_paket_id']);
        $timerMapel = $this->syncTimerStateForMapel($sesi, $mapel, true);

        if (($timerMapel['remaining_seconds'] ?? 0) <= 0) {
            return response()->json([
                'error' => 'Waktu ujian sudah habis.',
                'status' => 'expired',
                'remaining_seconds' => 0,
                'redirect_url' => route('siswa.selesai'),
            ], 409);
        }

        // Resolve key opaque menjodohkan kembali ke pair id sebelum disimpan.
        $jawabanMenjodohkan = [];
        if ($validated['tipe_soal'] === 'menjodohkan') {
            $seed = $sesi->session_token;
            $rowLookup = MatchingKey::rowKeyLookup($soal->pasanganMenjodohkans, $seed);
            $optLookup = MatchingKey::optKeyLookup($soal->pasanganMenjodohkans, $seed);

            foreach ($validated['jawaban_menjodohkan'] ?? [] as $item) {
                $rowKey = (string) ($item['row_key'] ?? '');
                $optKey = (string) ($item['opt_key'] ?? '');

                if ($rowKey === '' || $optKey === '') {
                    continue;
                }

                if (! isset($rowLookup[$rowKey]) || ! isset($optLookup[$optKey])) {
                    return response()->json(['error' => 'Jawaban menjodohkan tidak valid.'], 422);
                }

                $jawabanMenjodohkan[] = [
                    'pair_id' => $rowLookup[$rowKey],
                    'match_id' => $optLookup[$optKey],
                ];
            }
        }

        JawabanSiswa::updateOrCreate(
            ['ujian_sesi_id' => $sesi->id, 'soal_id' => $soal->id],
            [
                'tipe_soal' => $validated['tipe_soal'],
                'jawaban_pg' => $validated['tipe_soal'] === 'pilihan_ganda' ? ($validated['jawaban_pg'] ?? null) : null,
                'jawaban_menjodohkan' => $validated['tipe_soal'] === 'menjodohkan' ? $jawabanMenjodohkan : null,
                'is_ragu' => (bool) ($validated['is_ragu'] ?? false),
            ]
        );

        $timerMapel = $this->syncTimerStateForMapel($sesi, $mapel, false);

        return response()->json([
            'status' => 'success',
            'remaining_seconds' => $timerMapel['remaining_seconds'] ?? 0,
            'time_expired' => ($timerMapel['remaining_seconds'] ?? 0) <= 0,
            'redirect_url' => ($timerMapel['remaining_seconds'] ?? 0) <= 0 ? route('siswa.selesai') : null,
        ]);
    }

    // ─── Selesai ─────────────────────────────────────────────────────────────────

    public function selesai(): View|RedirectResponse
    {
        $sesi = $this->getActiveSession();

        if ($sesi && $sesi->status !== 'selesai') {
            $mapel = $sesi->mapelPaket;

            if ($mapel) {
                $timerMapel = $this->syncTimerStateForMapel($sesi, $mapel, false);
                $remaining = (int) ($timerMapel['remaining_seconds'] ?? 0);

                if ($remaining > 0) {
                    $sesi->load(['exam', 'paketSoal', 'mapelPaket.soals', 'jawabanSiswas']);

                    return view('ujian.konfirmasi-selesai', [
                        'session' => $sesi,
                        'exam' => $sesi->exam,
                        'mapel' => $mapel,
                        'remainingSeconds' => $remaining,
                    ]);
                }
            }

            $sesi = $this->finalizeSession($sesi);
        }

        if ($sesi && $sesi->status === 'selesai' && ! $sesi->relationLoaded('mapelPaket')) {
            $sesi->load(['mapelPaket.soals', 'jawabanSiswas', 'landingExamOrder']);
        }

        return view('ujian.selesai', ['session' => $sesi]);
    }

    public function submitSelesai(Request $request): RedirectResponse
    {
        $sesi = $this->getActiveSession();

        if (! $sesi) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Sesi ujian telah habis. Masukkan token kembali.']);
        }

        if ($sesi->status === 'selesai') {
            return redirect()->route('siswa.selesai');
        }

        $this->finalizeSession($sesi);

        return redirect()->route('siswa.selesai');
    }

    private function finalizeSession(UjianSesi $sesi): UjianSesi
    {
        return DB::transaction(function () use ($sesi): UjianSesi {
            $locked = UjianSesi::whereKey($sesi->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'selesai') {
                return $locked;
            }

            $locked->load([
                'mapelPaket.soals.pilihanJawabans',
                'mapelPaket.soals.pasanganMenjodohkans',
                'jawabanSiswas',
            ]);

            $profilRingkasan = $locked->mapelPaket?->usesProfiling()
                ? SurveyAnalytics::sessionProfile($locked)
                : null;

            $locked->update([
                'status' => 'selesai',
                'waktu_selesai' => now(),
                'skor' => $this->calculateScore($locked),
                'profil_ringkasan' => $profilRingkasan,
            ]);

            session()->forget(['siswa_mapel_token', 'siswa_exam_id', 'siswa_mapel_id']);

            $this->handlePublicExamCompletion($locked);

            return $locked->refresh();
        });
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

    private function handlePublicExamCompletion(UjianSesi $sesi): void
    {
        if (! $sesi->landing_exam_order_id) {
            return;
        }

        $order = LandingExamOrder::with(['landingExamMapel.landingExam.exam', 'landingExamMapel.mapelPaket'])
            ->find($sesi->landing_exam_order_id);

        if (! $order) {
            return;
        }

        $order->update(['status' => LandingExamOrder::STATUS_EXAM_COMPLETED]);

        $exam = $order->landingExamMapel?->landingExam?->exam;
        $mapelPaket = $order->landingExamMapel?->mapelPaket;

        if (! $exam || ! $mapelPaket || blank($order->nomor_wa)) {
            return;
        }

        $waBody = app(WaMessageTemplateService::class)->render('event_public_exam_completed', [
            'name' => $order->nama,
            'exam_title' => $exam->judul,
            'mapel_label' => $mapelPaket->nama_label,
            'score' => number_format((float) $sesi->skor, 1),
            'result_url' => route('ujian-online.result', $order->session_token),
        ]);

        SendWhatsAppBlast::dispatch($order->nomor_wa, $waBody)->onQueue('high');
    }

    private function calculateScore(UjianSesi $sesi): float
    {
        $mapel = $sesi->mapelPaket;

        if ($mapel?->usesProfiling()) {
            return SurveyAnalytics::sessionProfile($sesi)['score_percent'];
        }

        $soals = $mapel?->soals ?? collect();
        $jawabanBySoal = $sesi->jawabanSiswas->keyBy('soal_id');

        $maxScore = (float) $soals->sum('bobot');
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

            $totalPairs = $soal->pasanganMenjodohkans->count();
            if ($totalPairs > 0) {
                $correctPairs = $soal->pasanganMenjodohkans->filter(function ($pair) use ($answers) {
                    // jawaban benar: match_id === id pasangan yang sesuai (teks_kiri → teks_kanan-nya sendiri)
                    return (int) $answers->get($pair->id) === (int) $pair->id;
                })->count();

                $earnedScore += ($correctPairs / $totalPairs) * $soal->bobot;
            }
        }

        if ($maxScore <= 0) {
            return 0.0;
        }

        return round(($earnedScore / $maxScore) * 100, 2);
    }

    private function syncTimerStateForMapel(UjianSesi $sesi, MapelPaket $mapel, bool $startIfMissing): array
    {
        $timerState = $sesi->timer_state ?? [];
        $mapelState = $timerState[$mapel->id] ?? [];

        $durationSeconds = (int) ($mapelState['duration_seconds'] ?? ($mapel->durasi_menit * 60));
        $startedAtRaw = $mapelState['started_at'] ?? null;

        if ($startIfMissing && blank($startedAtRaw)) {
            $startedAtRaw = ($sesi->waktu_mulai ?? now())->toIso8601String();
        }

        $remainingSeconds = $durationSeconds;
        if (filled($startedAtRaw)) {
            $startedAt = Carbon::parse($startedAtRaw);
            $elapsedSeconds = max(0, $startedAt->diffInSeconds(now()));
            $remainingSeconds = max($durationSeconds - $elapsedSeconds, 0);
        }

        $mapelState = [
            'duration_seconds' => $durationSeconds,
            'remaining_seconds' => $remainingSeconds,
            'started_at' => $startedAtRaw,
            'finished_at' => $remainingSeconds <= 0
                ? ($mapelState['finished_at'] ?? now()->toIso8601String())
                : ($mapelState['finished_at'] ?? null),
        ];

        $existingState = $sesi->timer_state ?? [];
        if (($existingState[$mapel->id] ?? null) !== $mapelState) {
            $existingState[$mapel->id] = $mapelState;
            $sesi->update(['timer_state' => $existingState]);
            $sesi->refresh();
        }

        return $mapelState;
    }
}
