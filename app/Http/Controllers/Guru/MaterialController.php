<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Material;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MaterialController extends Controller {
    public function index(Request $request): View {
        $user = Auth::user();
        $jenjangUser = $user->jenjang ?? null;

        $materialsQuery = Material::query();

        if (Schema::hasColumn('materials', 'jenjang')) {
            $materialsQuery->where('jenjang', $jenjangUser);
        }

        $materials = $materialsQuery->orderBy('subelement')->orderBy('unit')->orderBy('sub_unit')->get();

        $bookmarks = $user->bookmarks ?? [];
        return view('guru.materials', compact('materials', 'bookmarks', 'jenjangUser'));
    }

    public function show(Material $material): View
    {
        $user = Auth::user();

        $this->ensureAccessibleMaterial($material, $user->jenjang ?? null);

        $globalQuestionCount = GlobalQuestion::where('material_id', $material->id)->count();
        $examSnapshotCount = Question::where('material_id', $material->id)
            ->where('jenjang', $user->jenjang)
            ->count();
        $isBookmarked = in_array($material->id, $user->bookmarks ?? []);

        return view('guru.material-show', compact('material', 'globalQuestionCount', 'examSnapshotCount', 'isBookmarked'));
    }
    public function bookmark(Material $material) {
        $user = Auth::user();
        $this->ensureAccessibleMaterial($material, $user->jenjang ?? null);
        $bookmarks = $user->bookmarks ?? [];
        if (!in_array($material->id, $bookmarks)) {
            $bookmarks[] = $material->id;
            $user->bookmarks = $bookmarks;
            $user->save();
        }
        return back();
    }
    public function unbookmark(Material $material) {
        $user = Auth::user();
        $this->ensureAccessibleMaterial($material, $user->jenjang ?? null);
        $bookmarks = array_values(array_diff($user->bookmarks ?? [], [$material->id]));
        $user->bookmarks = $bookmarks;
        $user->save();
        return back();
    }

    private function ensureAccessibleMaterial(Material $material, ?string $jenjangUser): void
    {
        if (! Schema::hasColumn('materials', 'jenjang')) {
            return;
        }

        abort_unless($material->jenjang === $jenjangUser, 403);
    }
}
