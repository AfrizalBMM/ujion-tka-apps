<?php

namespace App\Http\Controllers;

use App\Models\ExamMapelToken;
use App\Models\LandingExam;
use App\Models\LandingExamOrder;
use App\Models\PasanganMenjodohkan;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandingExamController extends Controller
{
    public function index(): View
    {
        $jenjangs = ['SD', 'SMP', 'SMA'];

        $counts = LandingExam::where('is_active', true)
            ->selectRaw('jenjang, count(*) as total')
            ->groupBy('jenjang')
            ->pluck('total', 'jenjang')
            ->all();

        return view('ujian-online.index', compact('jenjangs', 'counts'));
    }

    public function jenjang(string $jenjang): View
    {
        abort_unless(in_array(strtoupper($jenjang), ['SD', 'SMP', 'SMA']), 404);

        $jenjang = strtoupper($jenjang);

        $landingExams = LandingExam::with([
            'exam.paketSoal.jenjang',
            'mapels.mapelPaket',
        ])
            ->where('is_active', true)
            ->where('jenjang', $jenjang)
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return view('ujian-online.jenjang', compact('jenjang', 'landingExams'));
    }

    public function show(string $jenjang, LandingExam $landingExam): View
    {
        abort_unless(strtoupper($jenjang) === $landingExam->jenjang, 404);
        abort_unless($landingExam->is_active, 404);

        $landingExam->load([
            'exam.paketSoal.jenjang',
            'exam.paketSoal.mapelPakets',
            'mapels.mapelPaket.soals',
            'mapels' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ]);

        return view('ujian-online.detail', compact('landingExam'));
    }

    public function register(Request $request, string $jenjang, LandingExam $landingExam)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:20',
            'mapel_id' => 'required|exists:landing_exam_mapels,id',
        ]);

        $mapel = $landingExam->mapels()->findOrFail($validated['mapel_id']);
        abort_unless($mapel->is_active, 404);

        $nomorWa = PhoneNumber::normalizeIndonesian($validated['nomor_wa']);
        if ($nomorWa === '') {
            return back()->withErrors(['nomor_wa' => 'Nomor WhatsApp tidak valid.'])->withInput();
        }

        $existing = LandingExamOrder::where('landing_exam_mapel_id', $mapel->id)
            ->where('nomor_wa', PhoneNumber::toLocalFormat($nomorWa))
            ->where('nama', $validated['nama'])
            ->whereIn('status', [
                LandingExamOrder::STATUS_PENDING_PAYMENT,
                LandingExamOrder::STATUS_PAID,
                LandingExamOrder::STATUS_EXAM_STARTED,
            ])
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('ujian-online.pending', $existing->session_token);
        }

        $order = LandingExamOrder::create([
            'landing_exam_mapel_id' => $mapel->id,
            'nama' => $validated['nama'],
            'nomor_wa' => PhoneNumber::toLocalFormat($nomorWa),
            'status' => LandingExamOrder::STATUS_PENDING_PAYMENT,
            'amount' => $mapel->price,
        ]);

        return redirect()->route('ujian-online.pending', $order->session_token);
    }

    public function pending(string $orderToken): View
    {
        $order = LandingExamOrder::where('session_token', $orderToken)->firstOrFail();
        $order->load(['landingExamMapel.landingExam.exam.paketSoal.jenjang', 'landingExamMapel.mapelPaket']);

        return view('ujian-online.pending', compact('order'));
    }

    public function startExam(string $orderToken): RedirectResponse
    {
        $order = LandingExamOrder::where('session_token', $orderToken)->firstOrFail();
        abort_unless($order->isPaid(), 403, 'Pembayaran belum selesai.');

        $mapel = $order->landingExamMapel;
        $landingExam = $mapel->landingExam;
        $exam = $landingExam->exam;
        $mapelPaket = $mapel->mapelPaket;

        if (! $exam || ! $mapelPaket || ! $exam->paketSoal) {
            return redirect()->route('landing')->with('flash', ['type' => 'error', 'message' => 'Data ujian tidak ditemukan.']);
        }

        $sesi = $order->ujianSesi;

        if (! $sesi) {
            $timerState = [
                $mapelPaket->id => [
                    'duration_seconds' => $mapelPaket->durasi_menit * 60,
                    'remaining_seconds' => $mapelPaket->durasi_menit * 60,
                    'started_at' => null,
                    'finished_at' => null,
                ],
            ];

            $sesi = UjianSesi::create([
                'exam_id' => $exam->id,
                'paket_soal_id' => $exam->paket_soal_id,
                'mapel_paket_id' => $mapelPaket->id,
                'landing_exam_order_id' => $order->id,
                'nama' => $order->nama,
                'nomor_wa' => $order->nomor_wa,
                'session_token' => Str::random(60),
                'status' => 'menunggu',
                'timer_state' => $timerState,
            ]);

            if ($order->status === LandingExamOrder::STATUS_PAID) {
                $order->update(['status' => LandingExamOrder::STATUS_EXAM_STARTED]);
            }
        }

        session()->forget([
            'participant_token',
            'siswa_mapel_token',
            'siswa_exam_id',
            'siswa_mapel_id',
            'siswa_practice_token',
            'siswa_practice_token_id',
            'material_practice_session_token',
        ]);
        session()->regenerate();

        $examMapelToken = ExamMapelToken::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapelPaket->id)
            ->first();

        session([
            'siswa_mapel_token' => $examMapelToken?->token ?? '',
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapelPaket->id,
            'participant_token' => $sesi->session_token,
        ]);

        return redirect()->route('siswa.petunjuk');
    }

    public function result(string $orderToken): View
    {
        $order = LandingExamOrder::where('session_token', $orderToken)->firstOrFail();
        abort_unless($order->isCompleted(), 403, 'Hasil ujian belum tersedia.');

        $sesi = $order->ujianSesi;
        abort_unless($sesi && $sesi->status === 'selesai', 404);

        $sesi->load([
            'mapelPaket.soals.pilihanJawabans',
            'mapelPaket.soals.pasanganMenjodohkans',
            'mapelPaket.soals.teksBacaan',
            'jawabanSiswas',
        ]);

        $mapelPaket = $sesi->mapelPaket;

        $soals = $mapelPaket?->soals?->sortBy('nomor_soal') ?? collect();
        $jawabanBySoal = $sesi->jawabanSiswas->keyBy('soal_id');
        $seed = $sesi->session_token;

        $questions = $soals->map(function (Soal $soal) use ($jawabanBySoal) {
            $jawaban = $jawabanBySoal->get($soal->id);

            $correctOption = $soal->pilihanJawabans->firstWhere('is_benar', true);

            $correctPairs = $soal->pasanganMenjodohkans->map(fn ($pair) => [
                'teks_kiri' => $pair->teks_kiri,
                'teks_kanan' => $pair->teks_kanan,
            ])->values();

            $studentAnswerPg = $jawaban?->jawaban_pg;
            $studentAnswerMatching = collect($jawaban?->jawaban_menjodohkan ?? [])
                ->mapWithKeys(fn ($item) => [
                    ($item['pair_id'] ?? null) => ($item['match_id'] ?? null),
                ]);

            $pairDetails = $soal->pasanganMenjodohkans->map(function ($pair) use ($studentAnswerMatching) {
                $studentMatchId = $studentAnswerMatching->get($pair->id);
                $studentPair = $studentMatchId
                    ? PasanganMenjodohkan::find($studentMatchId)
                    : null;

                return [
                    'teks_kiri' => $pair->teks_kiri,
                    'correct_teks_kanan' => $pair->teks_kanan,
                    'student_teks_kanan' => $studentPair?->teks_kanan,
                    'is_correct' => $studentMatchId !== null && (int) $studentMatchId === (int) $pair->id,
                ];
            })->values();

            return [
                'nomor_soal' => $soal->nomor_soal,
                'tipe_soal' => $soal->tipe_soal,
                'pertanyaan' => $soal->pertanyaan,
                'gambar_url' => $soal->gambar_url,
                'pembahasan' => $soal->pembahasan,
                'teks_bacaan' => $soal->teksBacaan ? [
                    'judul' => $soal->teksBacaan->judul,
                    'konten' => $soal->teksBacaan->konten,
                ] : null,
                'pilihan' => $soal->pilihanJawabans->map(fn ($p) => [
                    'kode' => $p->kode,
                    'teks' => $p->teks,
                    'gambar_url' => $p->gambar_url,
                    'is_benar' => $p->is_benar,
                    'is_student_choice' => $studentAnswerPg === $p->kode,
                ])->values(),
                'correct_kode' => $correctOption?->kode,
                'student_jawaban_pg' => $studentAnswerPg,
                'is_correct_pg' => $correctOption && $studentAnswerPg === $correctOption->kode,
                'pair_details' => $pairDetails,
                'is_survey' => $soal->isSurvey(),
            ];
        });

        $totalSoal = $soals->count();
        $dijawab = $jawabanBySoal->filter(fn ($j) => ! empty($j->jawaban_pg) || ! empty($j->jawaban_menjodohkan))->count();

        return view('ujian-online.result', compact('order', 'sesi', 'mapelPaket', 'questions', 'totalSoal', 'dijawab'));
    }
}
