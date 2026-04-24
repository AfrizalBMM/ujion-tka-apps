<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SoalUjionController extends Controller
{
  public function index(Request $request): View
  {
    $user = Auth::user();
    $jenjangId = Jenjang::where('kode', $user->jenjang)->value('id');
    $bookmarks = $user->global_question_bookmarks ?? [];

    $filters = [
      'search'      => $request->query('search'),
      'mapel'       => $request->query('mapel'),
      'curriculum'  => $request->query('curriculum'),
    ];

    $questionsQuery = GlobalQuestion::query()
      ->where('is_active', true)
      ->where('jenjang_id', $jenjangId);

    if ($request->boolean('bookmarked')) {
      // When empty, force no results.
      $questionsQuery->whereIn('id', !empty($bookmarks) ? $bookmarks : [-1]);
    }

    if ($filters['search']) {
      $questionsQuery->where(function ($q) use ($filters) {
        $q->where('question_text', 'like', '%' . $filters['search'] . '%')
          ->orWhere('reading_passage', 'like', '%' . $filters['search'] . '%');
      });
    }
    if ($filters['mapel']) {
      $questionsQuery->where('material_mapel', $filters['mapel']);
    }
    if ($filters['curriculum']) {
      $questionsQuery->where('material_curriculum', $filters['curriculum']);
    }

    $questions = $questionsQuery->with('jenjang')->latest()->get();

    $mapels = GlobalQuestion::where('is_active', true)
      ->where('jenjang_id', $jenjangId)
      ->whereNotNull('material_mapel')
      ->distinct()->pluck('material_mapel');
    $curriculums = GlobalQuestion::where('is_active', true)
      ->where('jenjang_id', $jenjangId)
      ->whereNotNull('material_curriculum')
      ->distinct()->pluck('material_curriculum');

    return view('guru.soal-ujion', compact('questions', 'mapels', 'curriculums', 'bookmarks'));
  }

  public function show(GlobalQuestion $question): View
  {
    $this->authorize('view', $question);
    $user = Auth::user();
    $isBookmarked = in_array($question->id, $user->global_question_bookmarks ?? []);
    return view('guru.soal-ujion-show', compact('question', 'isBookmarked'));
  }

  public function bookmark(GlobalQuestion $question)
  {
    $this->authorize('view', $question);

    $user = Auth::user();
    $bookmarks = $user->global_question_bookmarks ?? [];
    if (! in_array($question->id, $bookmarks)) {
      $bookmarks[] = $question->id;
      $user->global_question_bookmarks = array_values($bookmarks);
      $user->save();
    }

    return back();
  }

  public function unbookmark(GlobalQuestion $question)
  {
    $this->authorize('view', $question);

    $user = Auth::user();
    $bookmarks = array_values(array_diff($user->global_question_bookmarks ?? [], [$question->id]));
    $user->global_question_bookmarks = $bookmarks;
    $user->save();

    return back();
  }
}
