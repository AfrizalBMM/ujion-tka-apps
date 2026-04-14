<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Schema;

class GlobalQuestionController extends Controller
{
    public function index()
    {
        $globalQuestions = GlobalQuestion::with('material')->latest()->get();
        $materials = \App\Models\Material::all();
        return view('superadmin.questions', compact('globalQuestions', 'materials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_id' => ['nullable', 'integer'],
            'question_type' => ['required', 'string', 'max:40'],
            'question_text' => ['required', 'string'],
            'options_raw' => ['nullable', 'string'],
            'answer_key' => ['nullable', 'string', 'max:40'],
            'explanation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['material_id']) && Schema::hasTable('materials')) {
            $request->validate([
                'material_id' => ['exists:materials,id'],
            ]);
        } else {
            $validated['material_id'] = null;
        }

        $options = null;
        $raw = trim((string) ($validated['options_raw'] ?? ''));
        if ($raw !== '') {
            $options = collect(preg_split('/\r\n|\r|\n/', $raw))
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();
        }

        GlobalQuestion::create([
            'material_id' => $validated['material_id'],
            'question_type' => $validated['question_type'],
            'question_text' => $validated['question_text'],
            'options' => $options,
            'answer_key' => $validated['answer_key'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil ditambahkan.']);
    }

    public function destroy(GlobalQuestion $globalQuestion): RedirectResponse
    {
        $globalQuestion->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal global berhasil dihapus.']);
    }

    public function template(): StreamedResponse
    {
        $headers = ['question_type', 'question_text', 'options', 'answer_key', 'explanation'];

        $callback = function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, ['multiple_choice', 'Contoh pertanyaan?', 'A|B|C|D', 'A', 'Pembahasan singkat...']);
            fclose($out);
        };

        return response()->streamDownload($callback, 'template-soal-global.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $path = $validated['file']->getRealPath();
        if (! $path) {
            return back()->with('flash', ['type' => 'warning', 'message' => 'File import tidak valid.']);
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return back()->with('flash', ['type' => 'warning', 'message' => 'File import tidak bisa dibaca.']);
        }

        $header = fgetcsv($handle);
        $created = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header ?: [], $row);
            if (! is_array($data)) {
                continue;
            }

            $questionText = trim((string) ($data['question_text'] ?? ''));
            if ($questionText === '') {
                continue;
            }

            $options = null;
            $optionsRaw = trim((string) ($data['options'] ?? ''));
            if ($optionsRaw !== '') {
                $options = collect(explode('|', $optionsRaw))
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => $v !== '')
                    ->values()
                    ->all();
            }

            GlobalQuestion::create([
                'material_id' => null,
                'question_type' => trim((string) ($data['question_type'] ?? 'multiple_choice')) ?: 'multiple_choice',
                'question_text' => $questionText,
                'options' => $options,
                'answer_key' => trim((string) ($data['answer_key'] ?? '')) ?: null,
                'explanation' => trim((string) ($data['explanation'] ?? '')) ?: null,
                'is_active' => true,
                'created_by' => $request->user()?->id,
            ]);

            $created++;
        }

        fclose($handle);

        return back()->with('flash', ['type' => 'success', 'message' => "Import selesai. Berhasil: {$created} soal."]);
    }
}
