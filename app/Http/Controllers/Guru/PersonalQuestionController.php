<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PersonalQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PersonalQuestionController extends Controller {
    public function index(Request $request): View {
        $user = Auth::user();
        $questions = PersonalQuestion::where('user_id', $user->id)
            ->where('jenjang', $user->jenjang)
            ->when($request->kategori, fn($q)=>$q->where('kategori',$request->kategori))
            ->get();

        return view('guru.personal-questions', compact('questions', 'user'));
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

    public function builder(): View {
        $user = Auth::user();
        $questions = PersonalQuestion::where('user_id', $user->id)
            ->where('jenjang', $user->jenjang)
            ->get();

        return view('guru.personal-question-builder', compact('questions', 'user'));
    }

    public function saveBuilder(Request $request): RedirectResponse {
        $user = Auth::user();
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*.pertanyaan' => 'required|string',
            'questions.*.tipe' => 'required|in:PG,Checklist,Singkat',
            'questions.*.opsi' => 'nullable|array',
            'questions.*.jawaban_benar' => 'nullable|string',
            'questions.*.pembahasan' => 'nullable|string',
            'questions.*.image' => 'nullable|string',
            'questions.*.jenjang' => 'nullable|string',
            'questions.*.kategori' => 'required|string|max:255',
            'questions.*.status' => 'required|in:draft,terbit',
        ]);

        DB::transaction(function () use ($user, $data): void {
            $existingQuery = PersonalQuestion::query()->where('user_id', $user->id);
            if (filled($user->jenjang)) {
                $existingQuery->where('jenjang', $user->jenjang);
            }

            $existingQuestions = $existingQuery->get();

            foreach ($existingQuestions as $existingQuestion) {
                if ($existingQuestion->image_path) {
                    Storage::disk('public')->delete($existingQuestion->image_path);
                }
            }

            $existingQuery->delete();

            foreach ($data['questions'] as $q) {
                if (array_key_exists('image', $q)) {
                    $q['image_path'] = $q['image'];
                    unset($q['image']);
                }

                $q['user_id'] = $user->id;
                $q['jenjang'] = $user->jenjang ?: ($q['jenjang'] ?? null);
                PersonalQuestion::create($q);
            }
        });

        return back()->with('flash', ['type'=>'success','message'=>'Soal berhasil disimpan.']);
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
}
