<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\MaterialPracticeToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialPracticeAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('siswa.practice.login');
    }

    public function validateToken(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'min:6', 'max:10'],
        ]);

        $tokenRaw = strtoupper(trim((string) $request->input('token')));

        $practiceToken = MaterialPracticeToken::query()
            ->with('material')
            ->withCount('packages')
            ->where('token', $tokenRaw)
            ->where('is_active', true)
            ->first();

        if (! $practiceToken || $practiceToken->packages_count < 1) {
            return back()->withErrors(['token' => 'Token latihan tidak valid atau belum disiapkan.']);
        }

        session([
            'siswa_practice_token' => $tokenRaw,
            'siswa_practice_token_id' => $practiceToken->id,
        ]);

        return redirect()->route('siswa.practice.identitas');
    }
}
