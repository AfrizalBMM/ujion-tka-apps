<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialPracticePackage;
use App\Models\MaterialPracticeToken;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

    private function ensureAccessibleMaterial(Material $material, ?string $jenjangUser): void
    {
        if (! Schema::hasColumn('materials', 'jenjang')) {
            return;
        }

        abort_unless($material->jenjang === $jenjangUser, 403);
    }
}
