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
        $bookmarks = $user->bookmarks ?? [];

        $hasJenjang = Schema::hasColumn('materials', 'jenjang');
        $hasMapel = Schema::hasColumn('materials', 'mapel');
        $hasLink = Schema::hasColumn('materials', 'link');

        $filters = [
            'mapel'      => $request->query('mapel'),
            'curriculum' => $request->query('curriculum'),
            'search'     => $request->query('search'),
        ];

        $materialsQuery = Material::query();

        if ($hasJenjang) {
            $materialsQuery->where('jenjang', $jenjangUser);
        }

        if ($request->boolean('bookmarked')) {
            // When empty, force no results.
            $materialsQuery->whereIn('id', !empty($bookmarks) ? $bookmarks : [-1]);
        }

        $materialsQuery
            ->when($filters['mapel'], fn($q) => $q->where('mapel', $filters['mapel']))
            ->when($filters['curriculum'], fn($q) => $q->where('curriculum', $filters['curriculum']))
            ->when($filters['search'], function ($q) use ($filters, $hasMapel, $hasLink) {
                $term = trim((string) $filters['search']);
                if ($term === '') {
                    return;
                }

                $q->where(function ($qq) use ($term, $hasMapel, $hasLink) {
                    $like = '%' . $term . '%';

                    if ($hasMapel) {
                        $qq->orWhere('mapel', 'like', $like);
                    }
                    $qq->orWhere('curriculum', 'like', $like)
                        ->orWhere('subelement', 'like', $like)
                        ->orWhere('unit', 'like', $like)
                        ->orWhere('sub_unit', 'like', $like);

                    if ($hasLink) {
                        $qq->orWhere('link', 'like', $like);
                    }
                });
            });

        $materials = $materialsQuery->orderBy('mapel')->orderBy('subelement')->orderBy('unit')->orderBy('sub_unit')->get();

        $mapels = Material::query()
            ->when($hasJenjang, fn($q) => $q->where('jenjang', $jenjangUser))
            ->distinct()
            ->pluck('mapel')
            ->filter()
            ->values();

        $curriculums = Material::query()
            ->when($hasJenjang, fn($q) => $q->where('jenjang', $jenjangUser))
            ->distinct()
            ->pluck('curriculum')
            ->filter()
            ->values();

        return view('guru.materials', compact('materials', 'bookmarks', 'jenjangUser', 'filters', 'mapels', 'curriculums'));
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
