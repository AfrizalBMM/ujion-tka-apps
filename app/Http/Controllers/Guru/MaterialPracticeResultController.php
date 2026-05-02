<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialPracticeSession;
use App\Models\MaterialPracticeToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MaterialPracticeResultController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $jenjangUser = $user?->jenjang;

        $tokens = MaterialPracticeToken::query()
            ->with('material')
            ->withCount('sessions')
            ->when(Schema::hasColumn('materials', 'jenjang'), function ($q) use ($jenjangUser) {
                $q->whereHas('material', fn ($mq) => $mq->where('jenjang', $jenjangUser));
            })
            ->orderByDesc('id')
            ->get();

        return view('guru.results.practice-index', compact('tokens'));
    }

    public function show(Material $material): View
    {
        $user = Auth::user();
        $this->ensureAccessibleMaterial($material, $user?->jenjang ?? null);

        $token = MaterialPracticeToken::query()
            ->where('material_id', $material->id)
            ->first();

        $sessions = collect();

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
                    'packageAttempts' => fn ($q) => $q->with('package')->orderBy('material_practice_package_id'),
                ])
                ->orderByDesc('id')
                ->get();
        }

        return view('guru.results.practice-show', compact('material', 'token', 'sessions'));
    }

    private function ensureAccessibleMaterial(Material $material, ?string $jenjangUser): void
    {
        if (! Schema::hasColumn('materials', 'jenjang')) {
            return;
        }

        abort_unless($material->jenjang === $jenjangUser, 403);
    }
}
