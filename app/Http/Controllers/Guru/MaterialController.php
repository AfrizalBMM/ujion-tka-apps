<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
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
        $filter = $request->query('jenjang');

        // Guru biasanya hanya melihat global + jenjangnya.
        // Filter yang disediakan:
        // - (null) = default (global + jenjang user)
        // - GLOBAL = hanya global
        $selectedJenjang = $filter === 'GLOBAL' ? 'GLOBAL' : null;

        $materialsQuery = Material::query();

        // Backward-compatible: jika kolom `jenjang` belum dimigrasikan, jangan paksa filter.
        if (Schema::hasColumn('materials', 'jenjang')) {
            if ($selectedJenjang === 'GLOBAL') {
                $materialsQuery->whereNull('jenjang');
            } elseif ($jenjangUser) {
                $materialsQuery->where(function ($inner) use ($jenjangUser) {
                    $inner->whereNull('jenjang')->orWhere('jenjang', $jenjangUser);
                });
            }
        }

        $materials = $materialsQuery->orderBy('subelement')->orderBy('unit')->orderBy('sub_unit')->get();

        $bookmarks = $user->bookmarks ?? [];
        return view('guru.materials', compact('materials', 'bookmarks', 'selectedJenjang', 'jenjangUser'));
    }

    public function show(Material $material): View
    {
        $user = Auth::user();

        if (Schema::hasColumn('materials', 'jenjang') && $material->jenjang) {
            abort_unless($material->jenjang === ($user->jenjang ?? null), 403);
        }

        $questionCount = Question::where('material_id', $material->id)->count();
        $isBookmarked = in_array($material->id, $user->bookmarks ?? []);

        return view('guru.material-show', compact('material', 'questionCount', 'isBookmarked'));
    }
    public function bookmark(Material $material) {
        $user = Auth::user();
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
        $bookmarks = array_values(array_diff($user->bookmarks ?? [], [$material->id]));
        $user->bookmarks = $bookmarks;
        $user->save();
        return back();
    }
}
