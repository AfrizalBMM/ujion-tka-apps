<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;

class ExamController extends Controller {
    public function index() {
        $user = Auth::user();
        $available = Exam::where('status','terbit')->get();
        $joined = $user->exams ?? [];
        $history = []; // TODO: histori ujian
        return view('guru.exams', compact('available','joined','history'));
    }
    public function join(Request $request) {
        $request->validate(['token'=>'required']);
        $exam = Exam::where('token',$request->token)->firstOrFail();
        $user = Auth::user();
        // TODO: attach user to exam participants
        return back()->with('flash', ['type'=>'success','message'=>'Berhasil join ujian.']);
    }
    public function result(Exam $exam) {
        $user = Auth::user();
        $result = null; // TODO: ambil hasil ujian user
        $pembahasan = []; // TODO: pembahasan soal
        return view('guru.exam-result', compact('exam','result','pembahasan'));
    }
}