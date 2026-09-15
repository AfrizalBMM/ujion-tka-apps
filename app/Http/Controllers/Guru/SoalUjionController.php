<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SoalUjionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $jenjangId = Jenjang::where('kode', $user->jenjang)->value('id');
        $bookmarks = $user->global_question_bookmarks ?? [];

        $filters = [
            'search' => $request->query('search'),
            'mapel' => $request->query('mapel'),
            'curriculum' => $request->query('curriculum'),
        ];

        $questionsQuery = GlobalQuestion::query()
            ->where('is_active', true)
            ->where('jenjang_id', $jenjangId);

        if ($request->boolean('bookmarked')) {
            // When empty, force no results.
            $questionsQuery->whereIn('id', ! empty($bookmarks) ? $bookmarks : [-1]);
        }

        if ($filters['search']) {
            $questionsQuery->where(function ($q) use ($filters) {
                $q->where('question_text', 'like', '%'.$filters['search'].'%')
                    ->orWhere('reading_passage', 'like', '%'.$filters['search'].'%');
            });
        }
        if ($filters['mapel']) {
            $questionsQuery->where('material_mapel', $filters['mapel']);
        }
        if ($filters['curriculum']) {
            $questionsQuery->where('material_curriculum', $filters['curriculum']);
        }

        $questions = $questionsQuery->with('jenjang')->latest()->paginate(24)->withQueryString();

        $questions->getCollection()->transform(function (GlobalQuestion $question) use ($bookmarks) {
            return [
                'id' => $question->id,
                'material_curriculum' => $question->material_curriculum,
                'material_mapel' => $question->material_mapel,
                'jenjang_nama' => $question->jenjang?->nama,
                'material_subelement' => $question->material_subelement,
                'question_excerpt' => Str::limit(strip_tags((string) $question->question_text), 80),
                'is_bookmarked' => in_array($question->id, $bookmarks),
            ];
        });

        $mapels = GlobalQuestion::where('is_active', true)
            ->where('jenjang_id', $jenjangId)
            ->whereNotNull('material_mapel')
            ->distinct()->pluck('material_mapel');

        $curriculums = GlobalQuestion::where('is_active', true)
            ->where('jenjang_id', $jenjangId)
            ->whereNotNull('material_curriculum')
            ->distinct()->pluck('material_curriculum');

        $bookmarked = $request->boolean('bookmarked');
        $nextBookmarked = $bookmarked ? null : 1;
        $bookmarkUrl = route('guru.soal-ujion.index', array_filter(array_merge($request->query(), [
            'bookmarked' => $nextBookmarked,
        ]), fn ($v) => $v !== null && $v !== ''));

        return Inertia::render('Guru/SoalUjion', compact(
            'questions',
            'mapels',
            'curriculums',
            'filters',
            'bookmarked',
            'bookmarkUrl',
            'bookmarks',
        ));
    }

    public function show(GlobalQuestion $question): Response
    {
        $this->authorize('view', $question);
        $user = Auth::user();
        $isBookmarked = in_array($question->id, $user->global_question_bookmarks ?? []);
        $question->loadMissing('jenjang');

        return Inertia::render('Guru/SoalUjionShow', compact('question', 'isBookmarked'));
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
