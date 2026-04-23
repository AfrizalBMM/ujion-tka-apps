<?php
namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Support\SurveyAnalytics;
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
            ->with('mapelPaket')
            ->where('status', 'selesai')
            ->orderByDesc('skor')
            ->orderBy('waktu_selesai')
            ->get();

        $academicSessions = $sessions->filter(fn ($session) => $session->mapelPaket?->isAkademik())->values();
        $surveySessions = $sessions->filter(fn ($session) => $session->mapelPaket?->isSurvey())->values();

        $ranking = $academicSessions->values()->map(fn ($session) => [
            'name' => $session->nama,
            'score' => number_format((float) $session->skor, 2),
            'waktu_selesai' => $session->waktu_selesai,
        ]);

        $distribution = [
            '90-100' => $academicSessions->filter(fn ($session) => $session->skor >= 90)->count(),
            '80-89' => $academicSessions->filter(fn ($session) => $session->skor >= 80 && $session->skor < 90)->count(),
            '70-79' => $academicSessions->filter(fn ($session) => $session->skor >= 70 && $session->skor < 80)->count(),
            '0-69' => $academicSessions->filter(fn ($session) => $session->skor < 70)->count(),
        ];

        $surveyComponents = $surveySessions
            ->groupBy('mapel_paket_id')
            ->map(function ($items) {
                $mapel = $items->first()?->mapelPaket;
                $categoryDistribution = [];

                foreach ($items as $session) {
                    $profile = $session->profil_ringkasan ?: SurveyAnalytics::sessionProfile($session);
                    $label = $profile['overall_category'] ?? 'Belum cukup data';
                    $categoryDistribution[$label] = ($categoryDistribution[$label] ?? 0) + 1;
                }

                return [
                    'mapel' => $mapel,
                    'participants' => $items->count(),
                    'average_score' => round($items->avg('skor') ?? 0, 2),
                    'category_distribution' => $categoryDistribution,
                ];
            })
            ->values();

        return [
            'exam' => $exam,
            'ranking' => $ranking,
            'distribution' => $distribution,
            'participantsCount' => $academicSessions->count(),
            'averageScore' => round((float) ($academicSessions->avg('skor') ?? 0), 2),
            'surveyComponents' => $surveyComponents,
        ];
    }
}
