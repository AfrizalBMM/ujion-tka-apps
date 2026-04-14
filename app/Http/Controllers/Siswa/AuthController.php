<?php

namespace App\Http\Controllers\Siswa;

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
        // TODO: Validasi token dari database atau sumber lain
        $token = $request->input('token');
        if ($token === 'UJION2026') { // Contoh validasi token
            session(['siswa_token' => $token]);
            return redirect()->route('siswa.identitas');
        }
        return back()->withErrors(['token' => 'Token tidak valid']);
    }
}
