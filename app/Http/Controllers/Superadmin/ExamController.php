<?php
namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamMapelToken;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\Question;
use App\Support\SpreadsheetTable;
use App\Support\SpreadsheetTemplateExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller {
    public function index(Request $request): View {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $jenjangId = $request->query('jenjang_id');

        $exams = Exam::with(['paketSoal.jenjang', 'examMapelTokens.mapelPaket'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('judul', 'like', '%' . $search . '%')
                        ->orWhereHas('paketSoal', fn ($paketQuery) => $paketQuery
                            ->where('nama', 'like', '%' . $search . '%')
                            ->orWhere('tahun_ajaran', 'like', '%' . $search . '%')
                            ->orWhereHas('jenjang', fn ($jenjangQuery) => $jenjangQuery
                                ->where('kode', 'like', '%' . $search . '%')
                                ->orWhere('nama', 'like', '%' . $search . '%')));
                });
            })
            ->when(in_array($status, ['draft', 'terbit'], true), fn ($query) => $query->where('status', $status))
            ->when($jenjangId, fn ($query) => $query->whereHas('paketSoal', fn ($paketQuery) => $paketQuery->where('jenjang_id', $jenjangId)))
            ->latest()
            ->get();
        $paketSoals = PaketSoal::with('jenjang')->latest()->get();
        $jenjangs = Jenjang::orderBy('urutan')->get();

        return view('superadmin.exams', compact('exams', 'paketSoals', 'search', 'status', 'jenjangId', 'jenjangs'));
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

        $paket = PaketSoal::with('mapelPakets')->findOrFail($data['paket_soal_id']);

        if (blank($data['timer'])) {
            $data['timer'] = $paket->mapelPakets->sum('durasi_menit') ?? 150;
        }
        $data['user_id'] = $request->user()->id;
        $data['assessment_type'] = $paket->assessment_type;

        $exam = Exam::create($data);

        // Auto-generate token per mapel
        foreach ($paket->mapelPakets as $mapel) {
            ExamMapelToken::create([
                'exam_id'        => $exam->id,
                'mapel_paket_id' => $mapel->id,
            ]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Ujian berhasil dibuat. Token per mapel sudah digenerate.']);
    }

    public function template(): StreamedResponse
    {
        return SpreadsheetTemplateExporter::download('template-ujian.xls', [
            'paket_soal_id',
            'judul',
            'tanggal_terbit',
            'max_peserta',
            'timer',
            'status',
            'is_active',
        ], [
            ['1', 'Simulasi TKA SD Gelombang 1', '2026-05-10 08:00', '120', '120', 'draft', '1'],
            ['2', 'Tryout TKA SMP Final', '2026-05-12 13:30', '200', '', 'terbit', '1'],
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        try {
            $rows = SpreadsheetTable::rowsFromUpload($validated['file']);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['type' => 'warning', 'message' => $exception->getMessage()]);
        }

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $paketSoalId = $this->resolvePaketSoalId($row['paket_soal_id'] ?? null);
            $judul = trim((string) ($row['judul'] ?? ''));
            $tanggalTerbit = $this->parseTanggalTerbit($row['tanggal_terbit'] ?? null);
            $status = $this->normalizeStatus($row['status'] ?? null);

            if (! $paketSoalId || $judul === '' || ! $tanggalTerbit || ! $status) {
                $skipped++;
                continue;
            }

            $timer = $this->normalizeNullableInteger($row['timer'] ?? null);
            if ($timer === null) {
                $timer = PaketSoal::with('mapelPakets')->find($paketSoalId)?->mapelPakets?->sum('durasi_menit') ?? 150;
            }

            $exam = Exam::create([
                'user_id'       => $request->user()->id,
                'paket_soal_id' => $paketSoalId,
                'assessment_type' => PaketSoal::query()->whereKey($paketSoalId)->value('assessment_type') ?? 'tka',
                'judul'         => $judul,
                'tanggal_terbit'=> $tanggalTerbit,
                'max_peserta'   => $this->normalizeNullableInteger($row['max_peserta'] ?? null) ?? 50,
                'timer'         => $timer,
                'status'        => $status,
                'is_active'     => $this->toBoolean($row['is_active'] ?? true),
            ]);

            // Auto-generate token per mapel
            $paketObj = PaketSoal::with('mapelPakets')->find($paketSoalId);
            foreach (($paketObj?->mapelPakets ?? []) as $mapel) {
                ExamMapelToken::create([
                    'exam_id'        => $exam->id,
                    'mapel_paket_id' => $mapel->id,
                ]);
            }

            $created++;
        }

        return back()->with('flash', [
            'type' => $created > 0 ? 'success' : 'warning',
            'message' => "Import ujian selesai. Berhasil: {$created}, dilewati: {$skipped}.",
        ]);
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
            ->when(($exam->assessment_type ?? 'tka') !== 'paket_lengkap', fn ($query) => $query->where('assessment_type', $exam->assessment_type ?? 'tka'))
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
        $exam->load(['paketSoal', 'examMapelTokens.mapelPaket']);
        return view('superadmin.exam-detail', compact('exam'));
    }

    private function makeQuestionPayload(Exam $exam, array $source, int $fallbackMaterialId): array
    {
        $paketSoal = $exam->paketSoal;

        return [
            'material_id' => (int) ($source['material_id'] ?? $fallbackMaterialId),
            'jenjang' => $paketSoal?->jenjang?->kode ?? 'GENERAL',
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

    private function resolvePaketSoalId(mixed $value): ?int
    {
        $paketSoalId = (int) trim((string) $value);

        if ($paketSoalId <= 0) {
            return null;
        }

        return PaketSoal::query()->whereKey($paketSoalId)->exists() ? $paketSoalId : null;
    }

    private function parseTanggalTerbit(mixed $value): ?Carbon
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeStatus(mixed $value): ?string
    {
        $normalized = SpreadsheetTable::normalizeHeader((string) $value);

        return match ($normalized) {
            '', 'draft' => 'draft',
            'terbit', 'published', 'publish' => 'terbit',
            default => null,
        };
    }

    private function normalizeNullableInteger(mixed $value): ?int
    {
        $raw = trim((string) $value);

        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return max(0, (int) $raw);
    }

    private function toBoolean(mixed $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return ! in_array($normalized, ['0', 'false', 'tidak', 'nonaktif'], true);
    }
}
