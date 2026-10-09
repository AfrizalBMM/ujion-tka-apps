<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TrialService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrialActive
{
    public function __construct(
        private readonly TrialService $trialService
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Superadmin bypasses trial check
        if ($user->isSuperadmin()) {
            return $next($request);
        }

        if (! $user->isGuru()) {
            return $next($request);
        }

        // Langganan aktif (akun lama / pembayaran sukses) → akses penuh
        if ($user->account_status === User::STATUS_ACTIVE) {
            return $next($request);
        }

        // Sinkronkan status trial (active → expired saat lewat tanggal)
        $this->trialService->checkExpiry($user);
        $user = $user->fresh();

        // Trial aktif → akses penuh
        if ($user->isTrialActive()) {
            return $next($request);
        }

        // Trial sudah berakhir → wajib pilih paket berlangganan
        if ($user->isTrialExpired()) {
            return redirect()->route('pricing')
                ->with('warning', 'Masa trial Anda telah berakhir. Silakan pilih paket berlangganan untuk melanjutkan.');
        }

        // Belum pernah mengaktifkan trial (profil belum lengkap) → lengkapi profil dulu
        return redirect()->route('guru.trial.profile.show')->with('flash', [
            'type' => 'info',
            'title' => 'Lengkapi profil untuk mulai trial',
            'message' => 'Isi data profil Anda untuk mengaktifkan trial gratis.',
        ]);
    }
}
