<?php
namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller {
    public function index() {
        $questions = Question::with('material')->get();
        $materials = Material::all();
        return view('superadmin.questions', compact('questions', 'materials'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'jenjang' => 'required',
            'tingkat' => 'required',
            'kategori' => 'required',
            'tipe' => 'required',
            'pertanyaan' => 'required',
            'opsi' => 'nullable|array',
            'jawaban_benar' => 'nullable|string',
            'pembahasan' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,terbit',
        ]);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('question-images', 'public');
        }
        $data['opsi'] = $request->opsi ? json_encode($request->opsi) : null;
        Question::create($data);
        return back()->with('flash', ['type' => 'success', 'message' => 'Soal berhasil ditambahkan.']);
    }
    public function destroy(Question $question) {
        if ($question->image_path) Storage::disk('public')->delete($question->image_path);
        $question->delete();
        return back()->with('flash', ['type' => 'success', 'message' => 'Soal dihapus.']);
    }
    public function toggle(Question $question) {
        $question->update(['is_active' => ! $question->is_active]);
        return back();
    }
}