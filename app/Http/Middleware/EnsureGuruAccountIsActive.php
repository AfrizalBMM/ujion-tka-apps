<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruAccountIsActive
{
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

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $user->account_status === User::STATUS_PENDING
            ? 'Akun Anda masih menunggu verifikasi pembayaran. Silakan tunggu token akses dari admin.'
            : 'Akun Anda sedang ditangguhkan. Silakan hubungi admin.';

        return redirect()->route('login')->with('flash', [
            'type' => 'warning',
            'title' => 'Akses guru belum tersedia',
            'message' => $message,
        ]);
    }
}
