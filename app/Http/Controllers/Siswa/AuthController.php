<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ExamMapelToken;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('siswa.login');
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string|min:6|max:10',
        ]);

        $token = strtoupper(trim($request->input('token')));

        $examMapelToken = ExamMapelToken::with([
            'exam.paketSoal.mapelPakets',
            'mapelPaket',
        ])
            ->where('token', $token)
            ->whereHas('exam', fn ($q) => $q->where('is_active', true)->where('status', 'terbit'))
            ->first();

        if (! $examMapelToken) {
            return back()->withErrors(['token' => 'Token tidak valid, ujian tidak aktif, atau belum diterbitkan.']);
        }

        $exam = $examMapelToken->exam;
        $mapel = $examMapelToken->mapelPaket;

        if (! $exam->paketSoal || ! $mapel) {
            return back()->withErrors(['token' => 'Paket soal atau mapel tidak ditemukan.']);
        }

        session()->forget([
            'participant_token',
            'siswa_mapel_token',
            'siswa_exam_id',
            'siswa_mapel_id',
            'siswa_practice_token',
            'siswa_practice_token_id',
            'material_practice_session_token',
        ]);
        $request->session()->regenerate();

        session([
            'siswa_mapel_token' => $token,
            'siswa_exam_id' => $exam->id,
            'siswa_mapel_id' => $mapel->id,
        ]);

        return redirect()->route('siswa.identitas');
    }
}
