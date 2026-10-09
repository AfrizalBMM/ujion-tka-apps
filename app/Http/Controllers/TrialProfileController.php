<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TrialService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrialProfileController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user && $user->role === User::ROLE_GURU, 403);

        // Sudah lengkap & trial aktif → langsung ke dashboard
        if ($user->isTrialActive()) {
            return redirect()->route('guru.dashboard');
        }

        $jenjangs = config('ujion.jenjangs', ['SD', 'SMP', 'SMA']);
        $trialDays = app(TrialService::class)->getDefaultDays();

        return Inertia::render('Auth/TrialProfileComplete', [
            'jenjangOptions' => $jenjangs,
            'trialDays' => $trialDays,
            'defaults' => [
                'name' => $user->name,
                'jenjang' => $user->jenjang,
                'satuan_pendidikan' => $user->satuan_pendidikan,
                'no_wa' => $user->no_wa,
            ],
        ]);
    }

    public function complete(Request $request, TrialService $trialService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->role === User::ROLE_GURU, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(config('ujion.jenjangs', ['SD', 'SMP', 'SMA']))],
            'satuan_pendidikan' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $normalizedWa = PhoneNumber::toLocalFormat(PhoneNumber::normalizeIndonesian($validated['no_wa']));

        // Cegah nomor WA dipakai akun guru lain
        $waTaken = User::query()
            ->where('id', '!=', $user->id)
            ->whereIn('no_wa', PhoneNumber::variants($validated['no_wa']))
            ->exists();

        if ($waTaken) {
            return back()->withErrors([
                'no_wa' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
            ])->withInput();
        }

        $user->fill([
            'name' => $validated['name'],
            'jenjang' => $validated['jenjang'],
            'satuan_pendidikan' => $validated['satuan_pendidikan'],
            'no_wa' => $normalizedWa,
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        // Aktifkan trial sesuai durasi yang diatur admin
        $trialService->startTrial($user);
        $days = $trialService->getDefaultDays();

        return redirect()
            ->route('guru.dashboard')
            ->with('flash', [
                'type' => 'success',
                'title' => 'Trial aktif!',
                'message' => "Akses penuh platform selama {$days} hari. Nikmati semua fitur Ujion TKA.",
            ]);
    }
}
