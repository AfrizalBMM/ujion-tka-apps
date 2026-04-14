<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Material;

class MaterialController extends Controller {
    public function index() {
        $user = Auth::user();
        $jenjang = $user->jenjang ?? null;
        $materials = Material::when($jenjang, fn($q)=>$q->where('jenjang',$jenjang))->get();
        $bookmarks = $user->bookmarks ?? [];
        return view('guru.materials', compact('materials','bookmarks'));
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
        $bookmarks = array_diff($user->bookmarks ?? [], [$material->id]);
        $user->bookmarks = $bookmarks;
        $user->save();
        return back();
    }
}