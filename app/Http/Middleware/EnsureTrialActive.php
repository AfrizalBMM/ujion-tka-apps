<?php

namespace App\Http\Middleware;

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

        // Only check trial for guru users
        if ($user->isGuru()) {
            // If user has approved payment (active subscription), bypass trial check
            if ($user->payment_status === User::PAYMENT_APPROVED) {
                return $next($request);
            }

            $this->trialService->checkExpiry($user);

            // If trial is active, allow access
            if ($user->fresh()->isTrialActive()) {
                return $next($request);
            }

            // If trial expired or none, redirect to pricing page
            return redirect()->route('pricing')
                ->with('warning', 'Masa trial Anda telah berakhir. Silakan pilih paket berlangganan untuk melanjutkan.');
        }

        return $next($request);
    }
}
