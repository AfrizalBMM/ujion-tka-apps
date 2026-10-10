<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\UjianSesi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnalisisSiswaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Get exams accessible to this guru
        $exams = Exam::where(function ($q) {
            $q->where('user_id', Auth::id())
                ->orWhereHas('creator', function ($query) {
                    $query->where('role', 'superadmin');
                });
        })
            ->withCount(['ujianSesis as total_peserta' => fn ($q) => $q->whereNull('user_id')->where('status', 'selesai')])
            ->latest()
            ->get()
            ->map(fn (Exam $exam) => [
                'id' => $exam->id,
                'nama' => $exam->judul,
                'total_peserta' => $exam->total_peserta,
            ])
            ->values()
            ->toArray();

        $selectedExamId = $request->query('exam_id', $exams[0]['id'] ?? null);

        $stats = [
            'total_siswa' => 0,
            'rata_rata' => 0,
            'tertinggi' => 0,
            'terendah' => 0,
            'lulus' => 0,
        ];

        $siswa = [];
        $distribution = [
            '700-800' => 0,
            '600-699' => 0,
            '500-599' => 0,
            '400-499' => 0,
            '200-399' => 0,
        ];

        $passingGrade = (int) config('ujion.scoring.passing_grade', 500);

        if ($selectedExamId) {
            $exam = Exam::findOrFail($selectedExamId);
            $this->authorizeOwner($exam);

            $sessions = $exam->ujianSesis()
                ->whereNull('user_id')
                ->where('status', 'selesai')
                ->whereNotNull('skor')
                ->orderByDesc('skor')
                ->orderBy('waktu_selesai')
                ->get();

            $scores = $sessions->pluck('skor')->filter()->values();

            $stats['total_siswa'] = $sessions->count();
            $stats['rata_rata'] = $scores->count() > 0 ? (float) $scores->avg() : 0;
            $stats['tertinggi'] = $scores->count() > 0 ? (float) $scores->max() : 0;
            $stats['terendah'] = $scores->count() > 0 ? (float) $scores->min() : 0;
            $stats['lulus'] = $scores->filter(fn ($s) => $s >= $passingGrade)->count();

            $siswa = $sessions->map(function (UjianSesi $s) {
                $waktuSelesai = $s->waktu_selesai;
                $durasiMenit = null;

                if ($s->waktu_mulai && $waktuSelesai) {
                    $durasiMenit = $s->waktu_mulai->diffInMinutes($waktuSelesai);
                }

                return [
                    'id' => $s->id,
                    'nama' => $s->nama ?? 'Tanpa Nama',
                    'nomor_wa' => $s->nomor_wa,
                    'skor' => $s->skor !== null ? (float) $s->skor : null,
                    'waktu_selesai' => $waktuSelesai?->translatedFormat('d M Y, H:i'),
                    'durasi_menit' => $durasiMenit,
                ];
            })->values()->toArray();

            // Build distribution
            foreach ($scores as $skor) {
                if ($skor >= 700) {
                    $distribution['700-800']++;
                } elseif ($skor >= 600) {
                    $distribution['600-699']++;
                } elseif ($skor >= 500) {
                    $distribution['500-599']++;
                } elseif ($skor >= 400) {
                    $distribution['400-499']++;
                } else {
                    $distribution['200-399']++;
                }
            }
        }

        return Inertia::render('Guru/AnalisisSiswa', [
            'exams' => $exams,
            'selectedExamId' => $selectedExamId ? (int) $selectedExamId : null,
            'stats' => $stats,
            'siswa' => $siswa,
            'distribution' => $distribution,
            'passingGrade' => $passingGrade,
        ]);
    }

    private function authorizeOwner(Exam $exam): void
    {
        $user = Auth::user();

        if ($exam->user_id === $user->id) {
            return;
        }

        if ($exam->creator && $exam->creator->role === 'superadmin') {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke ujian ini.');
    }
}
