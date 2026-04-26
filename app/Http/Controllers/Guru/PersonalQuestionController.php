<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PersonalQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class PersonalQuestionController extends Controller {
    private const BUILDER_IMAGE_DIRECTORY = 'personal-question-images/';

    public function index(Request $request): View {
        $user = Auth::user();
        $baseQuery = PersonalQuestion::query()
            ->where('user_id', $user->id)
            ->where('jenjang', $user->jenjang);

        $questions = (clone $baseQuery)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->query('q'));
                $like = '%' . $term . '%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('pertanyaan', 'like', $like)
                        ->orWhere('kategori', 'like', $like)
                        ->orWhere('jawaban_benar', 'like', $like)
                        ->orWhere('pembahasan', 'like', $like);
                });
            })
            ->when($request->filled('kategori'), fn($q) => $q->where('kategori', $request->query('kategori')))
            ->when($request->filled('tipe'), fn($q) => $q->where('tipe', $request->query('tipe')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = (clone $baseQuery)
            ->select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->filter()
            ->values();

        return view('guru.personal-questions', compact('questions', 'user', 'categories'));
    }

    public function store(Request $request): RedirectResponse {
        $user = Auth::user();
        $data = $request->validate([
            'jenjang' => 'nullable|string',
            'kategori' => 'required|string|max:255',
            'tipe' => 'required|in:PG,Checklist,Singkat',
            'pertanyaan' => 'required|string',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string|max:255',
            'options_raw' => 'nullable|string',
            'jawaban_benar' => 'nullable|string|max:255',
            'pembahasan' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,terbit',
        ]);
        $data['user_id'] = $user->id;
        $data['jenjang'] = $user->jenjang ?: $request->string('jenjang')->toString();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('personal-question-images', 'public');
        }
        $data['opsi'] = $this->normalizeOptions($request->input('options'), $request->input('options_raw'));
        $data['jawaban_benar'] = $this->normalizeCorrectAnswer($data['tipe'], $request->input('jawaban_benar'), $data['opsi']);
        unset($data['options']);
        unset($data['options_raw']);
        PersonalQuestion::create($data);

        return back()->with('flash', ['type'=>'success','message'=>'Soal berhasil ditambahkan.']);
    }

    public function destroy(PersonalQuestion $question): RedirectResponse {
        $question = $this->ownedQuestion($question);

        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        $question->delete();

        return back()->with('flash', ['type'=>'success','message'=>'Soal dihapus.']);
    }

    public function update(Request $request, PersonalQuestion $question): RedirectResponse
    {
        $question = $this->ownedQuestion($question);
        $user = Auth::user();

        $data = $request->validate([
            'kategori' => 'required|string|max:255',
            'tipe' => 'required|in:PG,Checklist,Singkat',
            'pertanyaan' => 'required|string',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string|max:255',
            'options_raw' => 'nullable|string',
            'jawaban_benar' => 'nullable|string|max:255',
            'pembahasan' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean',
            'status' => 'required|in:draft,terbit',
        ], [
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'image.image' => 'File harus berupa gambar.',
        ]);

        if ($request->boolean('remove_image') && $question->image_path) {
            Storage::disk('public')->delete($question->image_path);
            $question->image_path = null;
        }

        if ($request->hasFile('image')) {
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
            }
            $question->image_path = $request->file('image')->store('personal-question-images', 'public');
        }

        $question->fill([
            'jenjang' => $user->jenjang,
            'kategori' => $data['kategori'],
            'tipe' => $data['tipe'],
            'pertanyaan' => $data['pertanyaan'],
            'opsi' => $data['tipe'] === 'Singkat' ? null : $this->normalizeOptions($request->input('options'), $request->input('options_raw')),
            'jawaban_benar' => $this->normalizeCorrectAnswer(
                $data['tipe'],
                $request->input('jawaban_benar'),
                $data['tipe'] === 'Singkat' ? null : $this->normalizeOptions($request->input('options'), $request->input('options_raw'))
            ),
            'pembahasan' => $data['pembahasan'] ?? null,
            'status' => $data['status'],
        ]);

        $question->save();

        return back()->with('flash', ['type' => 'success', 'message' => 'Soal berhasil diperbarui.']);
    }

    public function builder(): View {
        $user = Auth::user();
        $questions = PersonalQuestion::where('user_id', $user->id)
            ->where('jenjang', $user->jenjang)
            ->get();

        return view('guru.personal-question-builder', compact('questions', 'user'));
    }

    public function saveBuilder(Request $request): JsonResponse|RedirectResponse {
        $user = Auth::user();
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'nullable|integer',
            'questions.*.pertanyaan' => 'required|string',
            'questions.*.tipe' => 'required|in:PG,Checklist,Singkat',
            'questions.*.opsi' => 'nullable|array',
            'questions.*.jawaban_benar' => 'nullable|string',
            'questions.*.pembahasan' => 'nullable|string',
            'questions.*.image' => 'nullable|string', // legacy field name from old builder
            'questions.*.image_path' => 'nullable|string',
            'questions.*.jenjang' => 'nullable|string',
            'questions.*.kategori' => 'required|string|max:255',
            'questions.*.status' => 'required|in:draft,terbit',
        ]);

        $data['questions'] = collect($data['questions'])
            ->map(function (array $q, int $index): array {
                $q['id'] = isset($q['id']) ? (int) $q['id'] : null;
                $q['image_path'] = $this->sanitizeBuilderImagePath(
                    $q['image_path'] ?? ($q['image'] ?? null),
                    $index
                );
                unset($q['image']);

                return $q;
            })
            ->all();

        $incomingQuestionIds = collect($data['questions'])
            ->pluck('id')
            ->filter()
            ->values();

        if ($incomingQuestionIds->count() !== $incomingQuestionIds->unique()->count()) {
            throw ValidationException::withMessages([
                'questions' => 'Payload builder mengandung ID soal duplikat.',
            ]);
        }

        DB::transaction(function () use ($user, $data): void {
            $existingQuery = PersonalQuestion::query()->where('user_id', $user->id);
            if (filled($user->jenjang)) {
                $existingQuery->where('jenjang', $user->jenjang);
            }

            $existingQuestions = $existingQuery->get()->keyBy('id');

            $requestedIds = collect($data['questions'])
                ->pluck('id')
                ->filter()
                ->values();

            $unknownIds = $requestedIds->diff($existingQuestions->keys());
            if ($unknownIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'questions' => 'Beberapa soal builder tidak ditemukan atau bukan milik Anda.',
                ]);
            }

            $incomingImagePaths = collect($data['questions'])
                ->pluck('image_path')
                ->filter()
                ->values()
                ->all();

            $imagesToDelete = $existingQuestions
                ->pluck('image_path')
                ->filter()
                ->unique()
                ->reject(fn (string $path) => in_array($path, $incomingImagePaths, true))
                ->values()
                ->all();

            $processedIds = [];

            foreach ($data['questions'] as $q) {
                $questionId = $q['id'] ?? null;
                $q['user_id'] = $user->id;
                $q['jenjang'] = $user->jenjang ?: ($q['jenjang'] ?? null);

                $q['opsi'] = $this->normalizeOptions($q['opsi'] ?? null, null);
                $q['jawaban_benar'] = $this->normalizeCorrectAnswer((string) $q['tipe'], $q['jawaban_benar'] ?? null, $q['opsi']);
                if ($q['tipe'] === 'Singkat') {
                    $q['opsi'] = null;
                }

                unset($q['id']);

                if ($questionId) {
                    $question = $existingQuestions->get($questionId);
                    $question?->fill($q);
                    $question?->save();
                    $processedIds[] = $questionId;
                    continue;
                }

                $created = PersonalQuestion::create($q);
                $processedIds[] = $created->id;
            }

            $existingQuestions
                ->except($processedIds)
                ->each
                ->delete();

            foreach ($imagesToDelete as $path) {
                Storage::disk('public')->delete($path);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Soal berhasil disimpan.',
            ]);
        }

        return back()->with('flash', ['type'=>'success','message'=>'Soal berhasil disimpan.']);
    }

    public function uploadBuilderImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ], [
            'image.image' => 'File harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $path = $request->file('image')->store('personal-question-images', 'public');

        return response()->json([
            'path' => $path,
            'url' => route('guru.personal-questions.builder.image', ['path' => $path]),
        ]);
    }

    public function builderImage(Request $request): BinaryFileResponse
    {
        $path = trim((string) $request->query('path'));

        abort_if($path === '' || ! str_starts_with($path, 'personal-question-images/'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function ownedQuestion(PersonalQuestion $question): PersonalQuestion
    {
        abort_unless($question->user_id === Auth::id(), 404);

        return $question;
    }

    private function normalizeOptions(?array $options = null, ?string $raw = null): ?array
    {
        $normalized = collect($options ?? [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        if ($normalized !== []) {
            return $normalized;
        }

        if (blank($raw)) {
            return null;
        }

        return collect(preg_split('/[\r\n,]+/', $raw))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeCorrectAnswer(string $type, mixed $answer, ?array $options): ?string
    {
        $rawAnswer = trim((string) $answer);

        if ($rawAnswer === '') {
            return null;
        }

        if (! in_array($type, ['PG', 'Checklist'], true) || empty($options)) {
            return $rawAnswer;
        }

        $labels = range('A', 'Z');
        $labelIndex = array_search(strtoupper($rawAnswer), $labels, true);

        if ($labelIndex !== false && array_key_exists($labelIndex, $options)) {
            return $options[$labelIndex];
        }

        return $rawAnswer;
    }

    private function sanitizeBuilderImagePath(mixed $candidate, int $index): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $path = trim($candidate);
        if ($path === '') {
            return null;
        }

        $field = "questions.$index.image_path";

        if (str_contains($path, '://') || str_starts_with($path, '/') || str_contains($path, '\\')) {
            throw ValidationException::withMessages([
                $field => 'Path gambar builder tidak valid.',
            ]);
        }

        if (! str_starts_with($path, self::BUILDER_IMAGE_DIRECTORY)) {
            throw ValidationException::withMessages([
                $field => 'Gambar builder harus berasal dari upload aplikasi.',
            ]);
        }

        if (! preg_match('/\A[A-Za-z0-9._\/-]+\z/', $path)) {
            throw ValidationException::withMessages([
                $field => 'Path gambar builder mengandung karakter yang tidak diizinkan.',
            ]);
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw ValidationException::withMessages([
                    $field => 'Path gambar builder tidak valid.',
                ]);
            }
        }

        if (! Storage::disk('public')->exists($path)) {
            throw ValidationException::withMessages([
                $field => 'Gambar builder tidak ditemukan. Upload ulang gambarnya.',
            ]);
        }

        return $path;
    }
}
