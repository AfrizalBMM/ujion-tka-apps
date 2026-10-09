<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TrialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrialProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        // Already completed profile and on trial → redirect to dashboard
        if ($user->isTrialActive()) {
            return redirect()->route('guru.dashboard');
        }

        $jenjangs = config('ujion.jenjangs', ['SD', 'SMP', 'SMA']);
        $trialDays = app(TrialService::class)->getDefaultDays();

        return Inertia::render('Auth/TrialProfileComplete', [
            'jenjangOptions' => $jenjangs,
            'trialDays' => $trialDays,
        ]);
    }

    public function complete(Request $request, TrialService $trialService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->role === User::ROLE_GURU, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'jenjang' => 'required|in:SD,SMP,SMA',
            'satuan_pendidikan' => 'required|string|max:255',
            'no_wa' => 'required|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Update profile
        $user->fill([
            'name' => $validated['name'],
            'jenjang' => $validated['jenjang'],
            'satuan_pendidikan' => $validated['satuan_pendidikan'],
            'no_wa' => $validated['no_wa'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('local')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'local');
        }

        $user->save();

        // Activate trial
        $trialService->startTrial($user);

        return redirect()
            ->route('guru.dashboard')
            ->with('flash', [
                'banner' => 'Trial aktif! Akses penuh platform selama ' . $trialService->getDefaultDays() . ' hari.',
                'bannerStyle' => 'success',
            ]);
    }
}
