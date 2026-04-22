<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\MapelPaket;
use App\Models\UjianSesi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $exams = Exam::where(function($q) {
            $q->where('user_id', Auth::id())
              ->orWhereHas('creator', function($query) {
                  $query->where('role', 'superadmin');
              });
        })
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('judul', 'like', '%' . $search . '%')
                    ->orWhereHas('paketSoal', fn ($paketQuery) => $paketQuery
                        ->where('nama', 'like', '%' . $search . '%')
                        ->orWhere('tahun_ajaran', 'like', '%' . $search . '%'));
            });
        })
        ->withCount(['ujianSesis as total_peserta'])
        ->with(['paketSoal', 'creator'])
        ->latest()
        ->get();

        return view('guru.results.index', compact('exams', 'search'));
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
        $isSurvey = $mapel->isSurvey();

        $sessions = UjianSesi::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapel->id)
            ->where('status', 'selesai')
            ->orderBy('skor', 'desc')
            ->get();

        $stats = [
            'total'   => $sessions->count(),
            'avg'     => round($sessions->avg('skor') ?? 0, 2),
            'max'     => round($sessions->max('skor') ?? 0, 2),
            'min'     => round($sessions->min('skor') ?? 0, 2),
            'pass'    => $sessions->where('skor', '>=', $isSurvey ? 100 : 70)->count(),
        ];

        // Question Analysis (Heatmap)
        $mapel->load('soals.jawabanSiswas');
        $questionStats = $mapel->soals->map(function ($soal) use ($sessions) {
            $sessionIds = $sessions->pluck('id');
            $answeredCount = $soal->jawabanSiswas()
                ->whereIn('ujian_sesi_id', $sessionIds)
                ->get()
                ->filter(function ($j) use ($soal) {
                    if ($soal->tipe_soal === 'pilihan_ganda') {
                        return filled($j->jawaban_pg);
                    }
                    return filled($j->jawaban_menjodohkan);
                })->count();

            return [
                'nomor'   => $soal->nomor_soal,
                'correct' => $answeredCount,
                'percent' => $sessions->count() > 0 ? round(($answeredCount / $sessions->count()) * 100, 1) : 0,
            ];
        });

        return view('guru.results.mapel', compact('exam', 'mapel', 'sessions', 'stats', 'questionStats', 'isSurvey'));
    }

    public function studentDetail(UjianSesi $session)
    {
        $exam = $session->exam;
        $this->authorizeOwner($exam);

        $session->load([
            'mapelPaket.soals.pilihanJawabans',
            'mapelPaket.soals.pasanganMenjodohkans',
            'jawabanSiswas',
            'exam',
        ]);

        $answers = $session->jawabanSiswas->keyBy('soal_id');

        return view('guru.results.student', compact('session', 'answers'));
    }

    public function export(Exam $exam, MapelPaket $mapel)
    {
        $this->authorizeOwner($exam);

        $sessions = UjianSesi::where('exam_id', $exam->id)
            ->where('mapel_paket_id', $mapel->id)
            ->where('status', 'selesai')
            ->orderBy('skor', 'desc')
            ->get();

        $fileName = "Hasil_{$exam->judul}_{$mapel->nama_label}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Peringkat', 'Nama Siswa', 'Nomor WA', 'Waktu Mulai', 'Waktu Selesai', $mapel->isSurvey() ? 'Kelengkapan' : 'Skor'];

        $callback = function() use($sessions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($sessions as $index => $s) {
                fputcsv($file, [
                    $index + 1,
                    $s->nama,
                    $s->nomor_wa ?? '-',
                    $s->waktu_mulai?->format('H:i:s') ?? '-',
                    $s->waktu_selesai?->format('H:i:s') ?? '-',
                    $s->skor
                ]);
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
