<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TrialService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruAccountIsActive
{
    private const PENDING_ALLOWED_ROUTES = [
        'guru.dashboard',
        'guru.guide',
    ];

    private const PENDING_ALLOWED_PREFIXES = [
        'guru.profile',
        'guru.chat',
        'guru.trial.profile',
    ];

    public function __construct(
        private readonly TrialService $trialService
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuperadmin()) {
            return $next($request);
        }

        if (! $user || $user->role !== User::ROLE_GURU) {
            abort(403);
        }

        // Akun berlangganan aktif → akses penuh
        if ($user->account_status === User::STATUS_ACTIVE) {
            return $next($request);
        }

        // Guru dalam masa trial gratis → akses penuh (belum perlu bayar)
        $this->trialService->checkExpiry($user);
        $user = $user->fresh();

        if ($user->isTrialActive()) {
            return $next($request);
        }

        // Trial sudah habis → serahkan ke middleware trial.active (redirect ke pricing)
        if ($user->isTrialExpired()) {
            return $next($request);
        }

        if ($user->account_status === User::STATUS_PENDING) {
            if ($this->routeAllowedForPending($request)) {
                return $next($request);
            }

            // Belum pernah lengkapi profil → arahkan ke form trial
            if ($user->trial_status === User::TRIAL_NONE) {
                return redirect()
                    ->route('guru.trial.profile.show')
                    ->with('flash', [
                        'type' => 'info',
                        'title' => 'Lengkapi profil dulu',
                        'message' => 'Selesaikan data profil untuk mengaktifkan trial gratis Anda.',
                    ]);
            }

            return redirect()
                ->route('guru.dashboard')
                ->with('flash', [
                    'type' => 'warning',
                    'title' => 'Masa trial berakhir',
                    'message' => 'Trial Anda sudah berakhir. Pilih paket berlangganan untuk membuka semua fitur.',
                ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('flash', [
            'type' => 'warning',
            'title' => 'Akses guru belum tersedia',
            'message' => 'Akun Anda sedang ditangguhkan. Silakan hubungi admin.',
        ]);
    }

    private function routeAllowedForPending(Request $request): bool
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === '' || $routeName === 'guru.dashboard') {
            return true;
        }

        if (in_array($routeName, self::PENDING_ALLOWED_ROUTES, true)) {
            return true;
        }

        foreach (self::PENDING_ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
