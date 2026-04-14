<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\Exam;

class DashboardController extends Controller {
    public function index() {
        $user = Auth::user();
        $ujianDibuat = $user->exams()->count();
        $totalPeserta = \App\Models\Participant::whereIn('exam_id', $user->exams()->pluck('id'))->count();
        $rataRataKelas = \App\Models\Participant::whereIn('exam_id', $user->exams()->pluck('id'))->whereNotNull('skor')->avg('skor') ?? 0;
        
        $logs = AuditLog::where('user_id', $user->id)->latest()->limit(10)->get();
        $pengumuman = []; // TODO: Ambil pengumuman penting
        return view('guru.dashboard', compact('ujianDibuat','totalPeserta','rataRataKelas','logs','pengumuman'));
    }
}