<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PersonalQuestion;

class PersonalQuestionController extends Controller {
    public function index(Request $request) {
        $user = Auth::user();
        $questions = PersonalQuestion::where('user_id', $user->id)
            ->when($request->jenjang, fn($q)=>$q->where('jenjang',$request->jenjang))
            ->when($request->kategori, fn($q)=>$q->where('kategori',$request->kategori))
            ->get();
        return view('guru.personal-questions', compact('questions'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'jenjang' => 'required',
            'kategori' => 'required',
            'tipe' => 'required',
            'pertanyaan' => 'required',
            'opsi' => 'nullable|array',
            'jawaban_benar' => 'nullable|string',
            'pembahasan' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,terbit',
        ]);
        $data['user_id'] = Auth::id();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('personal-question-images', 'public');
        }
        $data['opsi'] = $request->opsi ? json_encode($request->opsi) : null;
        PersonalQuestion::create($data);
        return back()->with('flash', ['type'=>'success','message'=>'Soal berhasil ditambahkan.']);
    }
    public function destroy(PersonalQuestion $question) {
        $question->delete();
        return back()->with('flash', ['type'=>'success','message'=>'Soal dihapus.']);
    }
    public function builder() {
        $user = Auth::user();
        $questions = PersonalQuestion::where('user_id', $user->id)->get();
        return view('guru.personal-question-builder', compact('questions'));
    }
    public function saveBuilder(Request $request) {
        $user = Auth::user();
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*.pertanyaan' => 'required',
            'questions.*.tipe' => 'required',
            'questions.*.opsi' => 'nullable|array',
            'questions.*.jawaban_benar' => 'nullable|string',
            'questions.*.pembahasan' => 'nullable|string',
            'questions.*.image' => 'nullable|string',
            'questions.*.jenjang' => 'required',
            'questions.*.kategori' => 'required',
            'questions.*.status' => 'required|in:draft,terbit',
        ]);
        // Hapus semua soal lama user ini
        PersonalQuestion::where('user_id', $user->id)->delete();
        // Simpan soal baru
        foreach ($data['questions'] as $q) {
            $q['user_id'] = $user->id;
            PersonalQuestion::create($q);
        }
        return back()->with('flash', ['type'=>'success','message'=>'Soal berhasil disimpan.']);
    }
}