<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\MaterialPracticeToken;
use App\Models\MapelPaket;
use App\Models\UjianSesi;
use App\Support\SurveyAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = in_array($request->query('tab'), ['ujian', 'materi'], true)
            ? $request->query('tab')
            : 'ujian';

        $exams = Exam::where(function($q) {
            $q->where('user_id', Auth::id())
              ->orWhereHas('creator', function($query) {
                  $query->where('role', 'superadmin');
              });
        })
        ->withCount(['ujianSesis as total_peserta'])
        ->with(['paketSoal', 'creator'])
        ->latest()
        ->get();

        $jenjangUser = Auth::user()?->jenjang;
        $practiceTokens = MaterialPracticeToken::query()
            ->with(['material', 'sessions.packageAttempts'])
            ->withCount(['sessions', 'packages'])
            ->when(Schema::hasColumn('materials', 'jenjang'), function ($q) use ($jenjangUser) {
                $q->whereHas('material', fn ($mq) => $mq->where('jenjang', $jenjangUser));
            })
            ->orderByDesc('id')
            ->get()
            ->map(function (MaterialPracticeToken $token) {
                $attempts = $token->sessions->flatMap->packageAttempts->where('status', 'selesai');
                $completedSessions = $token->sessions->filter(function ($session) {
                    return $session->status === 'selesai'
                        || $session->packageAttempts->where('status', 'selesai')->count() >= 3;
                })->count();

                $token->avg_score = $attempts->avg('skor');
                $token->completed_sessions_count = $completedSessions;

                return $token;
            });

        return view('guru.results.index', compact('exams', 'practiceTokens', 'activeTab'));
    }

    public function show(Exam $exam)
    {
        $this->authorizeOwner($exam);

        $exam->load(['paketSoal.mapelPakets', 'ujianSesis']);

        return view('guru.results.show', compact('exam'));
    }

    public function mapel(Exam $exam, MapelPaket $mapel)
    {
        $this->authorizeOwner($exam);

        $sessions = UjianSesi::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapel->id)
            ->where('status', 'selesai')
            ->orderBy('skor', 'desc')
            ->get();

        if ($mapel->isSurvey()) {
            $sessions->load('jawabanSiswas');
            $overview = SurveyAnalytics::mapelOverview($mapel, $sessions);

            $stats = [
                'total' => $sessions->count(),
                'avg' => round($sessions->avg('skor') ?? 0, 2),
                'max' => round($sessions->max('skor') ?? 0, 2),
                'min' => round($sessions->min('skor') ?? 0, 2),
                'pass' => 0,
            ];

            return view('guru.results.mapel', [
                'exam' => $exam,
                'mapel' => $mapel,
                'sessions' => $sessions,
                'stats' => $stats,
                'questionStats' => [],
                'isSurvey' => true,
                'surveyOverview' => $overview,
            ]);
        }

        $stats = [
            'total'   => $sessions->count(),
            'avg'     => round($sessions->avg('skor') ?? 0, 2),
            'max'     => round($sessions->max('skor') ?? 0, 2),
            'min'     => round($sessions->min('skor') ?? 0, 2),
            'pass'    => $sessions->where('skor', '>=', 70)->count(), // Example threshold
        ];

        // Question Analysis (Heatmap)
        $mapel->load('soals.jawabanSiswas', 'soals.pilihanJawabans');
        $questionStats = $mapel->soals->map(function ($soal) use ($sessions) {
            $sessionIds = $sessions->pluck('id');
            $correctCount = $soal->jawabanSiswas()
                ->whereIn('ujian_sesi_id', $sessionIds)
                ->get()
                ->filter(function ($j) use ($soal) {
                    if ($soal->tipe_soal === 'pilihan_ganda') {
                        return $j->jawaban_pg === $soal->pilihanJawabans->where('is_benar', true)->first()?->kode;
                    }
                    // Simplified for now, can add matching logic if needed
                    return false;
                })->count();

            return [
                'nomor'   => $soal->nomor_soal,
                'correct' => $correctCount,
                'percent' => $sessions->count() > 0 ? round(($correctCount / $sessions->count()) * 100, 1) : 0,
            ];
        });

        return view('guru.results.mapel', [
            'exam' => $exam,
            'mapel' => $mapel,
            'sessions' => $sessions,
            'stats' => $stats,
            'questionStats' => $questionStats,
            'isSurvey' => false,
            'surveyOverview' => null,
        ]);
    }

    public function studentDetail(UjianSesi $session)
    {
        $exam = $session->exam;
        $this->authorizeOwner($exam);

        $session->load([
            'mapelPaket.soals.pilihanJawabans',
            'mapelPaket.soals.pasanganMenjodohkans',
            'jawabanSiswas'
        ]);

        $answers = $session->jawabanSiswas->keyBy('soal_id');
        $surveyProfile = $session->mapelPaket?->isSurvey()
            ? ($session->profil_ringkasan ?: SurveyAnalytics::sessionProfile($session))
            : null;

        return view('guru.results.student', compact('session', 'answers', 'surveyProfile'));
    }

    public function export(Exam $exam, MapelPaket $mapel)
    {
        $this->authorizeOwner($exam);

        $sessions = UjianSesi::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapel->id)
            ->where('status', 'selesai')
            ->orderBy('skor', 'desc')
            ->get();

        $fileName = 'Hasil_' . ($exam->judul ?? $exam->nama ?? 'ujian') . '_' . $mapel->nama_label . '.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sessions, $mapel) {
            $file = fopen('php://output', 'w');

            if ($mapel->isSurvey()) {
                fputcsv($file, ['Nama Siswa', 'Nomor WA', 'Waktu Mulai', 'Waktu Selesai', 'Indeks Respons', 'Kategori Profil']);
                foreach ($sessions as $s) {
                    $profile = $s->profil_ringkasan ?: SurveyAnalytics::sessionProfile($s);
                    fputcsv($file, [
                        $s->nama,
                        $s->nomor_wa ?? '-',
                        $s->waktu_mulai?->format('H:i:s') ?? '-',
                        $s->waktu_selesai?->format('H:i:s') ?? '-',
                        $s->skor,
                        $profile['overall_category'] ?? '-',
                    ]);
                }
            } else {
                fputcsv($file, ['Peringkat', 'Nama Siswa', 'Nomor WA', 'Waktu Mulai', 'Waktu Selesai', 'Skor']);

                foreach ($sessions as $index => $s) {
                    fputcsv($file, [
                        $index + 1,
                        $s->nama,
                        $s->nomor_wa ?? '-',
                        $s->waktu_mulai?->format('H:i:s') ?? '-',
                        $s->waktu_selesai?->format('H:i:s') ?? '-',
                        $s->skor,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function authorizeOwner(Exam $exam)
    {
        // Izinkan jika guru adalah pemilik exam
        if ($exam->user_id === Auth::id()) {
            return;
        }

        // Izinkan jika exam dibuat oleh superadmin (Ujian Resmi)
        if ($exam->creator && $exam->creator->role === 'superadmin') {
            return;
        }

        abort(403, 'Akses ditolak.');
    }
}
