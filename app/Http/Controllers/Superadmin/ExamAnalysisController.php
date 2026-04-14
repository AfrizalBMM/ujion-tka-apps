<?php
namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamAnalysisController extends Controller {
    public function show(Exam $exam) {
        // Dummy data, replace with real calculation
        $participants = [
            ['name' => 'Siswa A', 'score' => 85],
            ['name' => 'Siswa B', 'score' => 70],
            ['name' => 'Siswa C', 'score' => 95],
        ];
        $ranking = collect($participants)->sortByDesc('score')->values();
        $distribution = [
            '90-100' => 1,
            '80-89' => 1,
            '70-79' => 1,
            '0-69' => 0,
        ];
        return view('superadmin.exam-analysis', compact('exam', 'ranking', 'distribution'));
    }
}