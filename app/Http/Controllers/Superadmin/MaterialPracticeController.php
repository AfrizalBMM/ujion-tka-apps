<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\Material;
use App\Models\MaterialPracticeToken;
use App\Models\MaterialTelaahQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class MaterialPracticeController extends Controller
{
    public function show(Material $material): View
    {
        $token = MaterialPracticeToken::query()
            ->with(['packages'])
            ->where('material_id', $material->id)
            ->first();

        $telaah = MaterialTelaahQuestion::query()
            ->where('material_id', $material->id)
            ->with('globalQuestion')
            ->orderBy('urutan')
            ->get();

        $bankQuestions = GlobalQuestion::query()
            ->forMaterial($material)
            ->where('question_type', 'multiple_choice')
            ->where('is_active', true)
            ->latest('id')
            ->limit(200)
            ->get();

        $bankQuestionsById = $bankQuestions->keyBy('id');

        $bankQuestionCount = GlobalQuestion::query()
            ->forMaterial($material)
            ->where('question_type', 'multiple_choice')
            ->where('is_active', true)
            ->count();

        return view('superadmin.material-practice.show', compact(
            'material',
            'token',
            'telaah',
            'bankQuestions',
            'bankQuestionsById',
            'bankQuestionCount'
        ));
    }

    public function saveTelaah(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'size:2'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:global_questions,id'],
        ]);

        $ids = array_values($validated['question_ids']);

        $validCount = GlobalQuestion::query()
            ->whereIn('id', $ids)
            ->forMaterial($material)
            ->where('question_type', 'multiple_choice')
            ->where('is_active', true)
            ->count();

        if ($validCount !== 2) {
            return back()->with('flash', [
                'type' => 'warning',
                'message' => 'Soal telaah harus berasal dari bank soal PG aktif pada materi yang sama.',
            ]);
        }

        DB::transaction(function () use ($material, $ids): void {
            MaterialTelaahQuestion::query()->where('material_id', $material->id)->delete();

            foreach ($ids as $index => $globalQuestionId) {
                MaterialTelaahQuestion::create([
                    'material_id' => $material->id,
                    'global_question_id' => $globalQuestionId,
                    'urutan' => $index + 1,
                ]);
            }
        });

        return back()->with('flash', ['type' => 'success', 'message' => 'Telaah soal berhasil disimpan.']);
    }

    public function upsertToken(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah_soal_per_paket' => ['required', 'in:10,15'],
        ]);

        $jumlah = (int) $validated['jumlah_soal_per_paket'];

        try {
            DB::transaction(function () use ($request, $material, $jumlah): void {
                $token = MaterialPracticeToken::query()->where('material_id', $material->id)->first();

                if (! $token) {
                    $token = MaterialPracticeToken::create([
                        'material_id' => $material->id,
                        'jumlah_soal_per_paket' => $jumlah,
                        'is_active' => true,
                        'created_by' => $request->user()?->id,
                    ]);
                } else {
                    $token->update([
                        'jumlah_soal_per_paket' => $jumlah,
                        'is_active' => true,
                    ]);
                }

                $token->regeneratePackages();
            });
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Token & paket latihan berhasil disiapkan.']);
    }

    public function regeneratePackages(Material $material): RedirectResponse
    {
        $token = MaterialPracticeToken::query()->where('material_id', $material->id)->first();

        if (! $token) {
            return back()->with('flash', ['type' => 'warning', 'message' => 'Token latihan belum dibuat untuk materi ini.']);
        }

        try {
            $token->regeneratePackages();
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Paket latihan berhasil diacak ulang.']);
    }
}
