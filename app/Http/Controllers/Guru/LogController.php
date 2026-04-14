<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class LogController extends Controller {
    public function index() {
        $user = Auth::user();
        $logs = AuditLog::where('user_id', $user->id)->orderByDesc('id')->limit(100)->get();
        return view('guru.logs', compact('logs'));
    }
}