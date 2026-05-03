<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialPracticeSession;
use App\Models\MaterialPracticeToken;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MaterialPracticeResultController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('guru.results.index', ['tab' => 'materi']);
    }

    public function show(Material $material): View
    {
        $user = Auth::user();
        $this->ensureAccessibleMaterial($material, $user?->jenjang ?? null);

        $token = MaterialPracticeToken::query()
            ->where('material_id', $material->id)
            ->first();

        $sessions = collect();
        $stats = [
            'total' => 0,
            'completed' => 0,
            'avg' => 0,
            'max' => 0,
            'min' => 0,
        ];
        $packageStats = collect();
        $questionStats = collect();
        $telaahStats = collect();
        $rankings = collect();

        if ($token) {
            $sessions = MaterialPracticeSession::query()
                ->where('material_practice_token_id', $token->id)
                ->withCount([
                    'packageAttempts as packages_done_count' => fn ($q) => $q->where('status', 'selesai'),
                ])
                ->withAvg([
                    'packageAttempts as skor_avg' => fn ($q) => $q->where('status', 'selesai'),
                ], 'skor')
                ->with([
                    'telaahAnswers.globalQuestion',
                    'packageAttempts' => fn ($q) => $q
                        ->with(['package.questions', 'answers.globalQuestion'])
                        ->orderBy('material_practice_package_id'),
                ])
                ->orderByDesc('id')
                ->get();

            $completedAttempts = $sessions->flatMap->packageAttempts->where('status', 'selesai');
            $sessionAverages = $sessions->mapWithKeys(function ($session) {
                $doneAttempts = $session->packageAttempts->where('status', 'selesai');

                return [
                    $session->id => $doneAttempts->isNotEmpty()
                        ? round((float) $doneAttempts->avg('skor'), 2)
                        : null,
                ];
            });

            $rankings = $sessions
                ->map(function ($session) use ($sessionAverages) {
                    $session->avg_score = $sessionAverages[$session->id];
                    $session->packages_done = $session->packageAttempts->where('status', 'selesai')->count();
                    $session->telaah_correct = $session->telaahAnswers->where('is_correct', true)->count();

                    return $session;
                })
                ->sortByDesc(fn ($session) => $session->avg_score ?? -1)
                ->values();

            $scores = $sessionAverages->filter(fn ($score) => $score !== null)->values();
            $stats = [
                'total' => $sessions->count(),
                'completed' => $rankings->where('packages_done', '>=', 3)->count(),
                'avg' => round((float) ($completedAttempts->avg('skor') ?? 0), 2),
                'max' => round((float) ($scores->max() ?? 0), 2),
                'min' => round((float) ($scores->min() ?? 0), 2),
            ];

            $packageStats = $token->packages()
                ->with('questions')
                ->orderBy('paket_no')
                ->get()
                ->map(function ($package) use ($completedAttempts) {
                    $attempts = $completedAttempts->where('material_practice_package_id', $package->id);

                    return [
                        'paket_no' => $package->paket_no,
                        'total_soal' => $package->questions->count(),
                        'attempts' => $attempts->count(),
                        'avg' => round((float) ($attempts->avg('skor') ?? 0), 2),
                        'max' => round((float) ($attempts->max('skor') ?? 0), 2),
                    ];
                });

            $answers = $completedAttempts->flatMap->answers;
            $questionStats = $answers
                ->groupBy('global_question_id')
                ->map(function (Collection $answers, $questionId) {
                    $question = $answers->first()?->globalQuestion;
                    $total = $answers->count();
                    $correct = $answers->where('is_correct', true)->count();

                    return [
                        'id' => (int) $questionId,
                        'question' => $question,
                        'total' => $total,
                        'correct' => $correct,
                        'percent' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                    ];
                })
                ->sortBy('id')
                ->values();

            $telaahStats = $sessions
                ->flatMap->telaahAnswers
                ->groupBy('global_question_id')
                ->map(function (Collection $answers, $questionId) {
                    $question = $answers->first()?->globalQuestion;
                    $total = $answers->count();
                    $correct = $answers->where('is_correct', true)->count();

                    return [
                        'id' => (int) $questionId,
                        'question' => $question,
                        'total' => $total,
                        'correct' => $correct,
                        'percent' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                    ];
                })
                ->sortBy('id')
                ->values();
        }

        return view('guru.results.practice-show', compact(
            'material',
            'token',
            'sessions',
            'stats',
            'packageStats',
            'questionStats',
            'telaahStats',
            'rankings'
        ));
    }

    public function student(Material $material, MaterialPracticeSession $session): View
    {
        $user = Auth::user();
        $this->ensureAccessibleMaterial($material, $user?->jenjang ?? null);

        $token = MaterialPracticeToken::query()
            ->where('material_id', $material->id)
            ->firstOrFail();

        abort_unless((int) $session->material_practice_token_id === (int) $token->id, 404);

        $session->load([
            'token.material',
            'telaahAnswers.globalQuestion',
            'packageAttempts' => fn ($q) => $q
                ->with(['package.questions', 'answers.globalQuestion'])
                ->orderBy('material_practice_package_id'),
        ]);

        $attempts = $session->packageAttempts;
        $answersByAttempt = $attempts->mapWithKeys(fn ($attempt) => [
            $attempt->id => $attempt->answers->keyBy('global_question_id'),
        ]);
        $avgScore = $attempts->where('status', 'selesai')->avg('skor');

        return view('guru.results.practice-student', compact(
            'material',
            'token',
            'session',
            'attempts',
            'answersByAttempt',
            'avgScore'
        ));
    }

    private function ensureAccessibleMaterial(Material $material, ?string $jenjangUser): void
    {
        if (! Schema::hasColumn('materials', 'jenjang')) {
            return;
        }

        abort_unless($material->jenjang === $jenjangUser, 403);
    }
}
