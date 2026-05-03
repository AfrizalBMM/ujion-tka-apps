<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialPracticePackage;
use App\Models\MaterialPracticePackageAttempt;
use App\Models\MaterialPracticeSession;
use App\Models\MaterialPracticeToken;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MaterialPracticePdfController extends Controller
{
    public function downloadPackage(Material $material, int $paketNo): Response
    {
        $this->ensureAccessibleMaterial($material, Auth::user()?->jenjang ?? null);

        $token = MaterialPracticeToken::query()
            ->where('material_id', $material->id)
            ->firstOrFail();

        $package = MaterialPracticePackage::query()
            ->where('material_practice_token_id', $token->id)
            ->where('paket_no', $paketNo)
            ->with('questions')
            ->firstOrFail();

        $pdf = Pdf::loadView('guru.material-practice.package-pdf', [
            'material' => $material,
            'token' => $token,
            'package' => $package,
        ])->setPaper('a4');

        $filename = sprintf('latihan-materi-%s-paket-%d.pdf', $material->id, $package->paket_no);

        return $pdf->download($filename);
    }

    public function downloadStudentPackage(
        Material $material,
        MaterialPracticeSession $session,
        MaterialPracticePackageAttempt $attempt
    ): Response {
        $this->ensureAccessibleMaterial($material, Auth::user()?->jenjang ?? null);

        $token = MaterialPracticeToken::query()
            ->where('material_id', $material->id)
            ->firstOrFail();

        abort_unless((int) $session->material_practice_token_id === (int) $token->id, 404);
        abort_unless((int) $attempt->material_practice_session_id === (int) $session->id, 404);

        $attempt->load([
            'package.questions',
            'answers.globalQuestion',
        ]);

        abort_unless((int) ($attempt->package?->material_practice_token_id ?? 0) === (int) $token->id, 404);

        $answersByQuestionId = $attempt->answers->keyBy('global_question_id');

        $pdf = Pdf::loadView('guru.material-practice.package-result-pdf', [
            'material' => $material,
            'token' => $token,
            'session' => $session,
            'attempt' => $attempt,
            'package' => $attempt->package,
            'answersByQuestionId' => $answersByQuestionId,
        ])->setPaper('a4');

        $studentName = Str::slug($session->nama) ?: 'siswa';
        $filename = sprintf(
            'hasil-latihan-materi-%s-%s-paket-%d.pdf',
            $material->id,
            $studentName,
            $attempt->package?->paket_no ?? 0
        );

        return $pdf->download($filename);
    }

    private function ensureAccessibleMaterial(Material $material, ?string $jenjangUser): void
    {
        if (! Schema::hasColumn('materials', 'jenjang')) {
            return;
        }

        abort_unless($material->jenjang === $jenjangUser, 403);
    }
}
