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

    $filters = [
      'search'      => $request->query('search'),
      'mapel'       => $request->query('mapel'),
      'curriculum'  => $request->query('curriculum'),
    ];

    $questionsQuery = GlobalQuestion::query()
      ->where('is_active', true)
      ->where('jenjang_id', $jenjangId);

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

    return view('guru.soal-ujion', compact('questions', 'mapels', 'curriculums'));
  }

  public function show(GlobalQuestion $question): View
  {
    $this->authorize('view', $question);
    return view('guru.soal-ujion-show', compact('question'));
  }
}
