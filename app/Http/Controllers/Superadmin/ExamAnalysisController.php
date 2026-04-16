<?php
namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamAnalysisController extends Controller {
    public function show(Exam $exam): View {
        return view('superadmin.exam-analysis', $this->buildAnalysis($exam));
    }

    public function exportCsv(Exam $exam): StreamedResponse
    {
        $analysis = $this->buildAnalysis($exam);

        $callback = function () use ($analysis): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['metric', 'value']);
            fputcsv($out, ['participants_count', $analysis['participantsCount']]);
            fputcsv($out, ['average_score', $analysis['averageScore']]);
            fputcsv($out, []);
            fputcsv($out, ['rank', 'name', 'score']);

            foreach ($analysis['ranking'] as $index => $row) {
                fputcsv($out, [$index + 1, $row['name'], $row['score']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['range', 'count']);
            foreach ($analysis['distribution'] as $range => $count) {
                fputcsv($out, [$range, $count]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, 'analisis-ujian-'.$exam->id.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Exam $exam): View
    {
        return view('superadmin.exports.exam-analysis-print', $this->buildAnalysis($exam));
    }

    private function buildAnalysis(Exam $exam): array
    {
        $sessions = $exam->ujianSesis()
            ->where('status', 'selesai')
            ->orderByDesc('skor')
            ->orderBy('waktu_selesai')
            ->get();

        $ranking = $sessions->values()->map(fn ($session) => [
            'name' => $session->nama,
            'score' => number_format((float) $session->skor, 2),
            'waktu_selesai' => $session->waktu_selesai,
        ]);

        $distribution = [
            '90-100' => $sessions->filter(fn ($session) => $session->skor >= 90)->count(),
            '80-89' => $sessions->filter(fn ($session) => $session->skor >= 80 && $session->skor < 90)->count(),
            '70-79' => $sessions->filter(fn ($session) => $session->skor >= 70 && $session->skor < 80)->count(),
            '0-69' => $sessions->filter(fn ($session) => $session->skor < 70)->count(),
        ];

        return [
            'exam' => $exam,
            'ranking' => $ranking,
            'distribution' => $distribution,
            'participantsCount' => $sessions->count(),
            'averageScore' => round((float) ($sessions->avg('skor') ?? 0), 2),
        ];
    }
}
