<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService
    ) {}

    /**
     * POST /api/coupons/verify
     *
     * Input: code, amount, jenjang (optional)
     * Returns: valid, discounted_amount, discount_value, error
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'jenjang' => ['nullable', 'string', 'in:SD,SMP,SMA'],
            'identifier' => ['nullable', 'string', 'max:30'],
        ]);

        $result = $this->couponService->verify(
            code: $validated['code'],
            amount: (float) $validated['amount'],
            jenjang: $validated['jenjang'] ?? null,
            user: $request->user(),
            identifier: $request->input('identifier'),
        );

        return response()->json([
            'valid' => $result['valid'],
            'discounted_amount' => $result['discounted_amount'],
            'discount_value' => $result['discount_value'],
            'original_amount' => (float) $validated['amount'],
            'error' => $result['error'],
        ]);
    }
}
