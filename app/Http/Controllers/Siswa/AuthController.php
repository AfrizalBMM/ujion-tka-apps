<?php

namespace App\Http\Controllers\Siswa;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('siswa.login');
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $exam = Exam::with('paketSoal.mapelPakets')
            ->where('token', strtoupper($request->input('token')))
            ->where('is_active', true)
            ->first();

        if (! $exam || ! $exam->paketSoal || $exam->paketSoal->mapelPakets->isEmpty()) {
            return back()->withErrors(['token' => 'Token tidak valid atau paket soal belum siap.']);
        }

        session(['siswa_token' => $exam->token]);

        return redirect()->route('siswa.identitas');
    }
}
