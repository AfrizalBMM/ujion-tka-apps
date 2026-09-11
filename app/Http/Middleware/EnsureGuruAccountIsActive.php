<?php

namespace App\Http\Middleware;

use App\Models\User;
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
    ];

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

        if ($user->account_status === User::STATUS_ACTIVE) {
            return $next($request);
        }

        if ($user->account_status === User::STATUS_PENDING) {
            if ($this->routeAllowedForPending($request)) {
                return $next($request);
            }

            return redirect()
                ->route('guru.dashboard')
                ->with('flash', [
                    'type' => 'warning',
                    'title' => 'Selesaikan pembayaran dulu',
                    'message' => 'Fitur ini terbuka setelah pembayaran aktivasi berhasil. Silakan selesaikan pembayaran dari dashboard Anda.',
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
