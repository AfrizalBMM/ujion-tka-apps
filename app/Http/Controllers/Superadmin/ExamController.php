<?php
namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\GlobalQuestion;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller {
    public function index(): View {
        $exams = Exam::with('paketSoal.jenjang')->latest()->get();
        $paketSoals = PaketSoal::with('jenjang')->latest()->get();
        return view('superadmin.exams', compact('exams', 'paketSoals'));
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'paket_soal_id' => 'required|exists:paket_soals,id',
            'judul' => 'required',
            'tanggal_terbit' => 'required|date',
            'max_peserta' => 'required|integer',
            'timer' => 'nullable|integer',
            'status' => 'required|in:draft,terbit',
        ]);
        if (blank($data['timer'])) {
            $data['timer'] = PaketSoal::with('mapelPakets')->find($data['paket_soal_id'])?->mapelPakets?->sum('durasi_menit') ?? 150;
        }
        $data['user_id'] = $request->user()->id;

        Exam::create($data);

        return back()->with('flash', ['type' => 'success', 'message' => 'Ujian berhasil dibuat.']);
    }

    public function destroy(Exam $exam): RedirectResponse {
        $this->deleteExamQuestions($exam);
        $exam->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Ujian dihapus.']);
    }

    public function toggle(Exam $exam): RedirectResponse {
        $exam->update(['is_active' => ! $exam->is_active]);

        return back();
    }

    public function builder(Exam $exam): View {
        $bankQuestions = GlobalQuestion::with('material')
            ->where('is_active', true)
            ->latest()
            ->get();
        $questions = $exam->questions()->orderBy('exam_question.order')->get();
        $materials = Material::all();

        return view('superadmin.exam-builder', compact('exam', 'questions', 'bankQuestions', 'materials'));
    }

    public function importBankQuestions(Request $request, Exam $exam): RedirectResponse {
        $data = $request->validate([
            'global_question_ids' => 'required|array|min:1',
            'global_question_ids.*' => 'integer|exists:global_questions,id',
        ]);

        $materialId = $this->resolveFallbackMaterialId();
        if (! $materialId) {
            return back()->with('flash', [
                'type' => 'warning',
                'message' => 'Minimal satu materi harus tersedia sebelum mengimpor bank soal ke ujian.',
            ]);
        }

        DB::transaction(function () use ($exam, $data, $materialId): void {
            $order = $exam->questions()->count();
            $globalQuestions = GlobalQuestion::query()
                ->whereIn('id', array_values(array_unique($data['global_question_ids'])))
                ->get()
                ->keyBy('id');

            foreach ($data['global_question_ids'] as $globalQuestionId) {
                $source = $globalQuestions->get($globalQuestionId);
                if (! $source) {
                    continue;
                }

                $question = Question::create($this->makeQuestionPayload(
                    exam: $exam,
                    source: [
                        'material_id' => $source->material_id ?: $materialId,
                        'pertanyaan' => $source->question_text,
                        'tipe' => $source->question_type === 'multiple_choice' ? 'PG' : 'Singkat',
                        'opsi' => $source->options,
                        'jawaban_benar' => $source->answer_key,
                        'pembahasan' => $source->explanation,
                        'status' => 'terbit',
                    ],
                    fallbackMaterialId: $materialId,
                ));

                $exam->questions()->attach($question->id, ['order' => ++$order]);
            }
        });

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal dari bank berhasil diimpor ke ujian.']);
    }

    public function saveBuilder(Request $request, Exam $exam): RedirectResponse {
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*.material_id' => 'nullable|integer|exists:materials,id',
            'questions.*.pertanyaan' => 'required',
            'questions.*.tipe' => 'required',
            'questions.*.opsi' => 'nullable|array',
            'questions.*.jawaban_benar' => 'nullable|string',
            'questions.*.pembahasan' => 'nullable|string',
            'questions.*.image' => 'nullable|string',
        ]);

        $materialId = $this->resolveFallbackMaterialId();
        if (! $materialId) {
            return back()->with('flash', [
                'type' => 'warning',
                'message' => 'Minimal satu materi harus tersedia sebelum menyimpan builder ujian.',
            ]);
        }

        DB::transaction(function () use ($exam, $data, $materialId): void {
            $this->deleteExamQuestions($exam);

            foreach (array_values($data['questions']) as $index => $questionData) {
                $question = Question::create($this->makeQuestionPayload(
                    exam: $exam,
                    source: $questionData,
                    fallbackMaterialId: $materialId,
                ));

                $exam->questions()->attach($question->id, ['order' => $index + 1]);
            }
        });

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal ujian berhasil disimpan.']);
    }

    public function show(Exam $exam): View {
        return view('superadmin.exam-detail', compact('exam'));
    }

    private function makeQuestionPayload(Exam $exam, array $source, int $fallbackMaterialId): array
    {
        $paketSoal = $exam->paketSoal;

        return [
            'material_id' => (int) ($source['material_id'] ?? $fallbackMaterialId),
            'jenjang' => $paketSoal?->jenjang?->kode ?? 'GENERAL',
            'tingkat' => $paketSoal?->tahun_ajaran ?? 'UMUM',
            'kategori' => 'Sedang',
            'tipe' => $source['tipe'],
            'pertanyaan' => $source['pertanyaan'],
            'opsi' => ! empty($source['opsi']) ? array_values($source['opsi']) : null,
            'jawaban_benar' => $source['jawaban_benar'] ?? null,
            'pembahasan' => $source['pembahasan'] ?? null,
            'image_path' => $source['image'] ?? null,
            'status' => $source['status'] ?? 'draft',
            'is_active' => true,
        ];
    }

    private function resolveFallbackMaterialId(): ?int
    {
        return Material::query()->orderBy('id')->value('id');
    }

    private function deleteExamQuestions(Exam $exam): void
    {
        $questionIds = $exam->questions()->pluck('questions.id');

        $exam->questions()->detach();

        if ($questionIds->isNotEmpty()) {
            Question::query()->whereIn('id', $questionIds)->delete();
        }
    }
}
