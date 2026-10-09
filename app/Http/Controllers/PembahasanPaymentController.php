<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\LandingExamMapel;
use App\Models\Transaction;
use App\Models\UjianSesi;
use App\Services\CouponService;
use App\Services\DokuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PembahasanPaymentController extends Controller
{
    public function __construct(
        private readonly DokuService $dokuService,
        private readonly CouponService $couponService
    ) {}

    /**
     * POST /payments/pembahasan/start
     *
     * Input: exam_session_id, coupon_code (optional)
     * Creates a transaction of type=pembahasan and returns a Doku payment URL.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:ujian_sesis,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = Auth::user();
        $sesi = UjianSesi::with(['exam', 'mapelPaket.landingExamMapel'])->findOrFail($validated['exam_session_id']);

        // Verify ownership: either user_id matches or nomor_wa matches user's no_wa
        $isOwner = false;
        if ($user) {
            $isOwner = (int) $sesi->user_id === (int) $user->id;
        }
        if (! $isOwner && $user && $user->no_wa && $sesi->nomor_wa) {
            $isOwner = $sesi->nomor_wa === $user->no_wa;
        }
        // Also allow via landing_exam_order session_token match if guest
        if (! $isOwner && ! $user && $sesi->session_token) {
            $isOwner = hash_equals($sesi->session_token, (string) $request->input('session_token', ''));
        }

        if (! $isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke sesi ujian ini.',
            ], 403);
        }

        // Already unlocked?
        if ($sesi->pembahasanUnlocked()) {
            return response()->json([
                'success' => true,
                'already_unlocked' => true,
                'message' => 'Pembahasan sudah terbuka untuk sesi ujian ini.',
            ]);
        }

        // Determine price from the landing_exam_mapel or a default
        $originalAmount = $this->getPembahasanPrice($sesi);

        if ($originalAmount <= 0) {
            // Free — unlock immediately
            $sesi->update(['pembahasan_unlocked_at' => now()]);

            return response()->json([
                'success' => true,
                'already_unlocked' => true,
                'message' => 'Pembahasan tersedia gratis untuk sesi ini.',
            ]);
        }

        // Apply coupon if provided
        $couponCode = $validated['coupon_code'] ?? null;
        $discountValue = 0;
        $couponId = null;
        $couponResult = null;
        $finalAmount = $originalAmount;

        if ($couponCode) {
            $couponResult = $this->couponService->verify(
                code: $couponCode,
                amount: $originalAmount,
                jenjang: $user?->jenjang,
                user: $user,
                identifier: $sesi->nomor_wa,
            );

            if ($couponResult['valid']) {
                $discountValue = $couponResult['discount_value'];
                $finalAmount = $couponResult['discounted_amount'];
                $couponId = $couponResult['coupon']->id;
                $couponCode = $couponResult['coupon']->code;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $couponResult['error'] ?? 'Kupon tidak valid.',
                    'coupon_error' => true,
                ], 422);
            }
        }

        // If discount makes it free, unlock immediately and record usage
        if ($finalAmount <= 0) {
            if ($couponId && $couponResult) {
                $this->couponService->recordUsage(
                    coupon: $couponResult['coupon'],
                    originalAmount: $originalAmount,
                    discountValue: $discountValue,
                    finalAmount: 0,
                    user: $user,
                    identifier: $sesi->nomor_wa,
                );
            }

            $sesi->update(['pembahasan_unlocked_at' => now()]);

            return response()->json([
                'success' => true,
                'already_unlocked' => true,
                'message' => 'Pembahasan terbuka dengan kupon gratis.',
            ]);
        }

        // Create transaction
        $referenceCode = 'PMB-'.strtoupper(Str::random(12));

        $transaction = Transaction::create([
            'user_id' => $user?->id,
            'ujian_sesi_id' => $sesi->id,
            'type' => Transaction::TYPE_PEMBAHASAN,
            'reference_code' => $referenceCode,
            'plan_name' => 'Pembahasan - '.$sesi->nama,
            'amount' => $finalAmount,
            'original_amount' => $originalAmount,
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => Transaction::PAYMENT_METHOD_DOKU,
            'coupon_id' => $couponId,
            'coupon_code' => $couponCode,
            'discount_value' => $discountValue,
        ]);

        // Create Doku payment via existing service method (takes Transaction)
        try {
            $dokuResult = $this->dokuService->createCheckoutPayment($transaction);
        } catch (\RuntimeException $e) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'doku_transaction_status' => 'creation_failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'payment_url' => $dokuResult['payment_url'] ?? null,
            'invoice_number' => $dokuResult['invoice_number'] ?? null,
            'amount' => $finalAmount,
            'original_amount' => $originalAmount,
            'discount_value' => $discountValue,
            'reference_code' => $referenceCode,
        ]);
    }

    /**
     * Determine the pembahasan price for a given exam session.
     */
    private function getPembahasanPrice(UjianSesi $sesi): float
    {
        // Try to get price from the associated landing exam mapel
        $mapelPaket = $sesi->mapelPaket;
        if ($mapelPaket) {
            $landingMapel = LandingExamMapel::query()
                ->where('mapel_paket_id', $mapelPaket->id)
                ->where('is_active', true)
                ->whereHas('landingExam', function ($q) {
                    $q->where('is_active', true);
                })
                ->first();

            if ($landingMapel && $landingMapel->price) {
                return (float) $landingMapel->price;
            }
        }

        // Fallback default pembahasan price from AppSetting
        $defaultPrice = AppSetting::getValue('pembahasan_price', '10000');

        return (float) $defaultPrice;
    }

    /**
     * POST /payments/pembahasan/unlock
     * Manual unlock endpoint (for admin override or coupon-free unlock).
     */
    public function forceUnlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:ujian_sesis,id'],
        ]);

        $user = $request->user();
        if ($user && ! $user->isSuperadmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $sesi = UjianSesi::findOrFail($validated['exam_session_id']);
        $sesi->update(['pembahasan_unlocked_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Pembahasan di-unlock secara manual.',
        ]);
    }

    /**
     * GET /payments/pembahasan/status/{examSession}
     * Check if pembahasan is unlocked for a session.
     */
    public function status(Request $request, UjianSesi $examSession): JsonResponse
    {
        return response()->json([
            'exam_session_id' => $examSession->id,
            'pembahasan_unlocked' => $examSession->pembahasanUnlocked(),
            'unlocked_at' => $examSession->pembahasan_unlocked_at?->toISOString(),
        ]);
    }
}
