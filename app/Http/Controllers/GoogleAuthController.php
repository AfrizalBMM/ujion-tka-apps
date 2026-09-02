<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function connectRedirect(Request $request)
    {
        $request->session()->put('google_connect', true);

        return Socialite::driver('google')->redirect();
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $user->update([
            'google_id' => null,
            'google_avatar' => null,
        ]);

        return redirect()->route('guru.profile')->with('flash', [
            'type' => 'success',
            'title' => 'Google diputuskan',
            'message' => 'Koneksi akun Google berhasil diputus. Anda tetap bisa masuk dengan WhatsApp + token.',
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('flash', [
                'type' => 'danger',
                'title' => 'Login Google dibatalkan',
                'message' => 'Proses login dengan Google tidak selesai. Silakan coba lagi.',
            ]);
        }

        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $googleId = $googleUser->getId() ? (string) $googleUser->getId() : null;

        if ($request->session()->pull('google_connect') === true && Auth::check()) {
            return $this->handleConnect($request, $googleUser, $googleId);
        }

        if ($email === '') {
            return redirect()->route('login')->with('flash', [
                'type' => 'danger',
                'title' => 'Email Google tidak tersedia',
                'message' => 'Akun Google Anda tidak memberikan email. Pastikan email Google sudah terverifikasi.',
            ]);
        }

        $user = User::query()
            ->where('role', User::ROLE_GURU)
            ->where(function ($query) use ($email, $googleId) {
                $query->where('email', $email);

                if ($googleId) {
                    $query->orWhere('google_id', $googleId);
                }
            })
            ->first();

        if ($user) {
            $this->linkGoogleAccount($user, $googleUser, $googleId);

            if ($user->account_status === User::STATUS_ACTIVE) {
                Auth::login($user, true);
                $request->session()->regenerate();

                return redirect()->intended(route('guru.dashboard'));
            }

            if ($user->account_status === User::STATUS_PENDING) {
                $this->storePendingSession($request, $user);

                return redirect()->route('register.guru.pending')->with('flash', [
                    'type' => 'info',
                    'title' => 'Akun ditemukan, masih pending',
                    'message' => 'Akun Anda terhubung dengan Google namun masih menunggu pembayaran aktivasi. Lanjutkan pembayaran di bawah ini.',
                ]);
            }

            return redirect()->route('login')->with('flash', [
                'type' => 'danger',
                'title' => 'Akun ditangguhkan',
                'message' => 'Akun Anda sedang ditangguhkan. Silakan hubungi admin.',
            ]);
        }

        $request->session()->put('google_registration', [
            'google_id' => $googleId,
            'name' => trim((string) $googleUser->getName()),
            'email' => $email,
            'avatar' => $googleUser->getAvatar() ?: null,
        ]);

        return redirect()->route('auth.google.complete');
    }

    public function showComplete(Request $request)
    {
        $google = $request->session()->get('google_registration');

        if (! is_array($google) || empty($google['email'])) {
            return redirect()->route('register.guru.form');
        }

        return view('auth.google-complete', [
            'google' => $google,
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $google = $request->session()->get('google_registration');

        if (! is_array($google) || empty($google['email'])) {
            return redirect()->route('register.guru.form');
        }

        $validated = $request->validate([
            'jenjang' => ['required', 'in:'.implode(',', config('ujion.jenjangs'))],
            'satuan_pendidikan' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:20'],
        ], [], [
            'satuan_pendidikan' => 'satuan pendidikan',
            'no_wa' => 'nomor WhatsApp',
        ]);

        $normalizedWa = PhoneNumber::toLocalFormat(PhoneNumber::normalizeIndonesian($validated['no_wa']));

        if (User::whereIn('no_wa', PhoneNumber::variants($validated['no_wa']))->exists()) {
            return back()->withErrors([
                'no_wa' => 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau masuk dengan jalur WhatsApp + token.',
            ])->withInput();
        }

        $existing = User::where('email', $google['email'])->first();
        if ($existing) {
            $request->session()->forget('google_registration');

            return redirect()->route('login')->with('flash', [
                'type' => 'info',
                'title' => 'Email sudah terdaftar',
                'message' => 'Email Google ini sudah terdaftar. Silakan masuk kembali dengan Google.',
            ]);
        }

        $user = User::create([
            'name' => $google['name'] !== '' ? $google['name'] : $google['email'],
            'email' => $google['email'],
            'password' => Hash::make(Str::password(24)),
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => $validated['jenjang'],
            'satuan_pendidikan' => $validated['satuan_pendidikan'],
            'no_wa' => $normalizedWa,
            'google_id' => $google['google_id'] ?? null,
            'google_avatar' => $google['avatar'] ?? null,
            'email_verified_at' => now(),
        ]);

        $request->session()->forget('google_registration');
        $this->storePendingSession($request, $user);

        return redirect()->route('register.guru.pending')->with('flash', [
            'type' => 'success',
            'title' => 'Akun Google berhasil dihubungkan',
            'message' => 'Lengkapi pembayaran aktivasi di bawah ini untuk mengaktifkan akun guru Anda.',
        ]);
    }

    private function handleConnect(Request $request, $googleUser, ?string $googleId): RedirectResponse
    {
        $user = Auth::user();

        if (! $googleId) {
            return redirect()->route('guru.profile')->with('flash', [
                'type' => 'danger',
                'title' => 'Google tidak dapat dihubungkan',
                'message' => 'Akun Google Anda tidak memberikan identifier yang valid. Silakan coba lagi.',
            ]);
        }

        $taken = User::query()
            ->where('google_id', $googleId)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($taken) {
            return redirect()->route('guru.profile')->with('flash', [
                'type' => 'danger',
                'title' => 'Google sudah terhubung ke akun lain',
                'message' => 'Akun Google ini sudah terhubung dengan akun guru lain. Silakan gunakan akun Google yang berbeda.',
            ]);
        }

        $googleEmail = mb_strtolower(trim((string) $googleUser->getEmail()));
        $currentEmail = mb_strtolower(trim((string) $user->email));

        $updates = [
            'google_id' => $googleId,
            'google_avatar' => $googleUser->getAvatar() ?: $user->google_avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ];

        if ($googleEmail !== '' && $googleEmail !== $currentEmail) {
            $emailTaken = User::query()
                ->where('email', $googleEmail)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailTaken) {
                return redirect()->route('guru.profile')->with('flash', [
                    'type' => 'danger',
                    'title' => 'Email Google sudah dipakai akun lain',
                    'message' => 'Email dari akun Google Anda sudah terdaftar di akun lain. Koneksi dibatalkan agar tidak terjadi konflik akun.',
                ]);
            }

            $updates['email'] = $googleEmail;
        }

        $user->update($updates);

        return redirect()->route('guru.profile')->with('flash', [
            'type' => 'success',
            'title' => 'Google terhubung',
            'message' => isset($updates['email'])
                ? 'Akun Google berhasil dihubungkan dan email diperbarui ke '.$googleEmail.'.'
                : 'Akun Google berhasil dihubungkan. Anda sekarang bisa masuk dengan Google.',
        ]);
    }

    private function linkGoogleAccount(User $user, $googleUser, ?string $googleId): void
    {
        $updates = [];

        if (! $user->google_id && $googleId) {
            $taken = User::query()
                ->where('google_id', $googleId)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $taken) {
                $updates['google_id'] = $googleId;
            }
        }

        if (! $user->google_avatar && $googleUser->getAvatar()) {
            $updates['google_avatar'] = $googleUser->getAvatar();
        }

        if (! $user->email_verified_at) {
            $updates['email_verified_at'] = now();
        }

        if ($updates !== []) {
            $user->update($updates);
        }
    }

    private function storePendingSession(Request $request, User $teacher): void
    {
        $plan = $this->resolvePlanForJenjang($teacher->jenjang);

        $request->session()->put('pending_registration', [
            'teacher_id' => $teacher->id,
            'pricing_plan_id' => $plan?->id,
            'harga' => $plan?->price,
        ]);
    }

    private function resolvePlanForJenjang(?string $jenjang): ?PricingPlan
    {
        $query = PricingPlan::query()->where('is_active', true);

        if ($jenjang && Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            $plan = (clone $query)
                ->where('jenjang', $jenjang)
                ->first();

            if ($plan) {
                return $plan;
            }
        }

        if (Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            return (clone $query)
                ->whereNull('jenjang')
                ->first();
        }

        return $query->first();
    }
}
