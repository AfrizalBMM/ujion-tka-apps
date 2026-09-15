<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\MapelPaket;
use App\Models\MaterialPracticeToken;
use App\Models\UjianSesi;
use App\Support\SurveyAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ExamResultController extends Controller
{
    public function index(Request $request): Response
    {
        $activeTab = in_array($request->query('tab'), ['ujian', 'materi'], true)
            ? $request->query('tab')
            : 'ujian';

        $exams = Exam::where(function ($q) {
            $q->where('user_id', Auth::id())
                ->orWhereHas('creator', function ($query) {
                    $query->where('role', 'superadmin');
                });
        })
            ->withCount(['ujianSesis as total_peserta' => fn ($q) => $q->whereNull('user_id')])
            ->with(['paketSoal', 'creator'])
            ->latest()
            ->get()
            ->map(fn (Exam $exam) => [
                'id' => $exam->id,
                'nama' => $exam->nama,
                'total_peserta' => $exam->total_peserta,
                'is_ujion' => $exam->creator && $exam->creator->role === 'superadmin',
                'paket_nama' => $exam->paketSoal?->nama ?? '-',
                'token_labels' => $exam->examMapelTokens()
                    ->with('mapelPaket')
                    ->get()
                    ->map(fn ($t) => $t->mapelPaket?->nama_label ?? 'Mapel')
                    ->values(),
            ])
            ->values();

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

        $practiceTokens = $practiceTokens->map(function (MaterialPracticeToken $token) {
            return [
                'material_id' => $token->material_id,
                'token' => $token->token,
                'sessions_count' => $token->sessions_count,
                'packages_count' => $token->packages_count,
                'completed_sessions_count' => $token->completed_sessions_count,
                'avg_score' => $token->avg_score,
                'sub_unit' => $token->material?->sub_unit,
                'subelement' => $token->material?->subelement,
                'unit' => $token->material?->unit,
            ];
        })->values();

        return Inertia::render('Guru/Results/Index', compact('exams', 'practiceTokens', 'activeTab'));
    }

    public function show(Exam $exam): Response
    {
        $this->authorizeOwner($exam);

        $exam->load(['paketSoal.mapelPakets']);
        $exam->setRelation('ujianSesis', $exam->ujianSesis()->whereNull('user_id')->get());

        $tokens = $exam->examMapelTokens()
            ->with('mapelPaket')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'token' => $t->token,
                'mapel_paket_id' => $t->mapel_paket_id,
                'nama_label' => $t->mapelPaket?->nama_label ?? 'Komponen',
                'nama_mapel' => $t->mapelPaket?->nama_mapel,
                'is_survey' => (bool) $t->mapelPaket?->isSurvey(),
                'session_count' => $exam->ujianSesis()->where('mapel_paket_id', $t->mapel_paket_id)->count(),
                'avg_score' => round($exam->ujianSesis()
                    ->where('mapel_paket_id', $t->mapel_paket_id)
                    ->where('status', 'selesai')
                    ->avg('skor') ?? 0, 1),
            ])
            ->values();

        return Inertia::render('Guru/Results/Show', [
            'exam' => [
                'id' => $exam->id,
                'judul' => $exam->judul ?? $exam->nama,
            ],
            'tokens' => $tokens,
        ]);
    }

    public function mapel(Exam $exam, MapelPaket $mapel): Response
    {
        $this->authorizeOwner($exam);

        $sessions = UjianSesi::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapel->id)
            ->where('status', 'selesai')
            ->whereNull('user_id')
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

            return Inertia::render('Guru/Results/Mapel', [
                'exam' => [
                    'id' => $exam->id,
                    'judul' => $exam->judul ?? $exam->nama,
                ],
                'mapel' => [
                    'id' => $mapel->id,
                    'nama_label' => $mapel->nama_label,
                ],
                'sessions' => $this->sessionProps($sessions),
                'stats' => $stats,
                'questionStats' => [],
                'isSurvey' => true,
                'surveyOverview' => $this->surveyOverviewProps($overview),
            ]);
        }

        $stats = [
            'total' => $sessions->count(),
            'avg' => round($sessions->avg('skor') ?? 0, 2),
            'max' => round($sessions->max('skor') ?? 0, 2),
            'min' => round($sessions->min('skor') ?? 0, 2),
            'pass' => $sessions->where('skor', '>=', 70)->count(), // Example threshold
        ];

        // Question Analysis (Heatmap)
        $mapel->load('soals.jawabanSiswas', 'soals.pilihanJawabans', 'soals.pasanganMenjodohkans');
        $sessionIds = $sessions->pluck('id');
        $questionStats = $mapel->soals->map(function ($soal) use ($sessionIds) {
            $answers = $soal->jawabanSiswas()
                ->whereIn('ujian_sesi_id', $sessionIds)
                ->get();

            $correctCount = $answers->filter(function ($j) use ($soal) {
                if ($soal->tipe_soal === 'pilihan_ganda') {
                    return $j->jawaban_pg === $soal->pilihanJawabans->where('is_benar', true)->first()?->kode;
                }

                $mapped = collect($j->jawaban_menjodohkan ?? [])
                    ->mapWithKeys(fn ($item) => [($item['pair_id'] ?? null) => ($item['match_id'] ?? null)]);

                $totalPairs = $soal->pasanganMenjodohkans->count();
                if ($totalPairs === 0) {
                    return false;
                }

                return $soal->pasanganMenjodohkans->every(
                    fn ($pair) => (int) $mapped->get($pair->id) === (int) $pair->id
                );
            })->count();

            return [
                'nomor' => $soal->nomor_soal,
                'correct' => $correctCount,
                'percent' => $sessionIds->count() > 0 ? round(($correctCount / $sessionIds->count()) * 100, 1) : 0,
            ];
        })->values();

        return Inertia::render('Guru/Results/Mapel', [
            'exam' => [
                'id' => $exam->id,
                'judul' => $exam->judul ?? $exam->nama,
            ],
            'mapel' => [
                'id' => $mapel->id,
                'nama_label' => $mapel->nama_label,
            ],
            'sessions' => $this->sessionProps($sessions),
            'stats' => $stats,
            'questionStats' => $questionStats,
            'isSurvey' => false,
            'surveyOverview' => null,
        ]);
    }

    public function studentDetail(UjianSesi $session): Response
    {
        $exam = $session->exam;
        $this->authorizeOwner($exam);

        $session->load([
            'mapelPaket.soals.pilihanJawabans',
            'mapelPaket.soals.pasanganMenjodohkans',
            'jawabanSiswas',
        ]);

        $answers = $session->jawabanSiswas->keyBy('soal_id');
        $isSurveyMapel = $session->mapelPaket?->isSurvey();
        $surveyProfile = $isSurveyMapel
            ? ($session->profil_ringkasan ?: SurveyAnalytics::sessionProfile($session))
            : null;

        $soalItems = $session->mapelPaket->soals
            ->map(function ($s) use ($answers, $isSurveyMapel) {
                $ans = $answers->get($s->id);
                $correctOption = $s->pilihanJawabans->where('is_benar', true)->first();
                $isCorrect = ($ans && $s->tipe_soal === 'pilihan_ganda' && ! $isSurveyMapel && $ans->jawaban_pg === $correctOption?->kode);

                return [
                    'id' => $s->id,
                    'nomor_soal' => $s->nomor_soal,
                    'tipe_soal' => $s->tipe_soal,
                    'pertanyaan' => $s->pertanyaan,
                    'indikator' => $s->indikator,
                    'dimensi' => $s->dimensi,
                    'bobot' => $s->bobot,
                    'answered' => (bool) $ans,
                    'is_correct' => (bool) $isCorrect,
                    'options' => $s->pilihanJawabans->map(fn ($opt) => [
                        'kode' => $opt->kode,
                        'teks' => $opt->teks,
                        'is_correct' => (bool) $opt->is_benar,
                        'is_chosen' => (bool) ($ans && $ans->jawaban_pg === $opt->kode),
                        'nilai_survey' => $opt->nilai_survey,
                        'profil_label' => $opt->profil_label,
                    ])->values(),
                    'pasangan' => $s->pasanganMenjodohkans->map(fn ($pair) => [
                        'teks_kiri' => $pair->teks_kiri,
                        'teks_kanan' => $pair->teks_kanan,
                    ])->values(),
                ];
            })
            ->values();

        return Inertia::render('Guru/Results/Student', [
            'session' => [
                'id' => $session->id,
                'nama' => $session->nama,
                'nomor_wa' => $session->nomor_wa,
                'exam_id' => $session->exam_id,
                'mapel_paket_id' => $session->mapel_paket_id,
                'skor' => $session->skor !== null ? (float) $session->skor : null,
                'mapel_nama_label' => $session->mapelPaket?->nama_label,
                'is_survey' => (bool) $isSurveyMapel,
            ],
            'soalItems' => $soalItems,
            'surveyProfile' => $surveyProfile ? [
                'dimension_stats' => collect($surveyProfile['dimension_stats'] ?? [])->values(),
            ] : null,
        ]);
    }

    private function sessionProps($sessions)
    {
        return $sessions
            ->map(fn ($s) => [
                'id' => $s->id,
                'nama' => $s->nama,
                'nomor_wa' => $s->nomor_wa,
                'waktu_mulai' => $s->waktu_mulai?->format('H:i'),
                'waktu_selesai' => $s->waktu_selesai?->format('H:i'),
                'durasi_menit' => $s->waktu_selesai?->diffInMinutes($s->waktu_mulai) ?? 0,
                'skor' => $s->skor !== null ? (float) $s->skor : null,
            ])
            ->values();
    }

    private function surveyOverviewProps(array $overview): array
    {
        return [
            'dimension_stats' => collect($overview['dimension_stats'] ?? [])
                ->map(fn ($dimension) => [
                    'dimensi' => $dimension['dimensi'],
                    'score_percent' => (float) $dimension['score_percent'],
                    'category' => $dimension['category'],
                ])
                ->values(),
            'question_breakdown' => collect($overview['question_breakdown'] ?? [])
                ->map(fn ($question) => [
                    'nomor' => $question['nomor'],
                    'dimensi' => $question['dimensi'],
                    'subdimensi' => $question['subdimensi'],
                    'distribution' => collect($question['distribution'] ?? [])
                        ->map(fn ($option) => [
                            'kode' => $option['kode'],
                            'label' => Str::limit(strip_tags($option['label']), 48),
                            'count' => $option['count'],
                            'percent' => $option['percent'],
                        ])
                        ->values(),
                ])
                ->values(),
            'category_distribution' => $overview['category_distribution'] ?? [],
        ];
    }

    public function export(Exam $exam, MapelPaket $mapel)
    {
        $this->authorizeOwner($exam);

        $sessions = UjianSesi::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapel->id)
            ->where('status', 'selesai')
            ->whereNull('user_id')
            ->orderBy('skor', 'desc')
            ->get();

        $fileName = 'Hasil_'.($exam->judul ?? $exam->nama ?? 'ujian').'_'.$mapel->nama_label.'.csv';

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
