<?php
namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\PaketSoal;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\Material;

class ExamController extends Controller {
    public function index() {
        $exams = Exam::with('paketSoal.jenjang')->latest()->get();
        $paketSoals = PaketSoal::with('jenjang')->latest()->get();
        return view('superadmin.exams', compact('exams', 'paketSoals'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'paket_soal_id' => 'required|exists:paket_soals,id',
            'judul' => 'required',
            'tanggal_terbit' => 'required|date',
            'max_peserta' => 'required|integer',
            'timer' => 'nullable|integer',
            'status' => 'required|in:draft,terbit',
        ]);
        $data['token'] = strtoupper(substr(md5(uniqid()), 0, 6));
        if (blank($data['timer'])) {
            $data['timer'] = PaketSoal::with('mapelPakets')->find($data['paket_soal_id'])?->mapelPakets?->sum('durasi_menit') ?? 150;
        }
        Exam::create($data);
        return back()->with('flash', ['type' => 'success', 'message' => 'Ujian berhasil dibuat.']);
    }
    public function destroy(Exam $exam) {
        $exam->delete();
        return back()->with('flash', ['type' => 'success', 'message' => 'Ujian dihapus.']);
    }
    public function toggle(Exam $exam) {
        $exam->update(['is_active' => ! $exam->is_active]);
        return back();
    }
    public function builder(Exam $exam) {
        $bankQuestions = Question::whereNull('exam_id')->get();
        $questions = $exam->questions()->orderBy('exam_question.order')->get();
        $materials = Material::all();
        return view('superadmin.exam-builder', compact('exam', 'questions', 'bankQuestions', 'materials'));
    }
    public function importBankQuestions(Request $request, Exam $exam) {
        $data = $request->validate(['question_ids' => 'required|array']);
        $order = $exam->questions()->count();
        foreach ($data['question_ids'] as $qid) {
            $exam->questions()->attach($qid, ['order' => ++$order]);
        }
        return back()->with('flash', ['type' => 'success', 'message' => 'Soal dari bank berhasil diimpor ke ujian.']);
    }
    public function saveBuilder(Request $request, Exam $exam) {
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*.pertanyaan' => 'required',
            'questions.*.tipe' => 'required',
            'questions.*.opsi' => 'nullable|array',
            'questions.*.jawaban_benar' => 'nullable|string',
            'questions.*.pembahasan' => 'nullable|string',
            'questions.*.image' => 'nullable|string',
        ]);
        // Hapus semua soal lama ujian ini
        Question::where('exam_id', $exam->id)->delete();
        // Simpan soal baru
        foreach ($data['questions'] as $q) {
            $q['exam_id'] = $exam->id;
            Question::create($q);
        }
        return back()->with('flash', ['type' => 'success', 'message' => 'Soal ujian berhasil disimpan.']);
    }
    public function show(Exam $exam) {
        return view('superadmin.exam-detail', compact('exam'));
    }
}
