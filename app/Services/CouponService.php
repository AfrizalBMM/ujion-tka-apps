<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;

class CouponService
{
    /**
     * Validate a coupon code against the given amount and jenjang.
     *
     * @param  string  $code  Coupon code
     * @param  float  $amount  Original transaction amount
     * @param  string|null  $jenjang  Target jenjang (SD/SMP/SMA)
     * @param  User|null  $user  Authenticated user (for per-user usage limit)
     * @param  string|null  $identifier  nomor_wa or email for guest users
     * @return array{valid: bool, coupon: Coupon|null, discounted_amount: float, discount_value: float, error: string|null}
     */
    public function verify(string $code, float $amount, ?string $jenjang = null, ?User $user = null, ?string $identifier = null): array
    {
        $coupon = Coupon::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
            return $this->invalidResponse('Kupon tidak ditemukan atau tidak aktif.');
        }

        // Check start date
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->invalidResponse('Kupon belum berlaku.', $coupon);
        }

        // Check end date
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $this->invalidResponse('Kupon sudah kedaluwarsa.', $coupon);
        }

        // Check minimum transaction
        if ($coupon->min_transaction > 0 && $amount < (float) $coupon->min_transaction) {
            return $this->invalidResponse(
                'Minimal transaksi Rp '.number_format((float) $coupon->min_transaction, 0, ',', '.'),
                $coupon
            );
        }

        // Check jenjang target
        if ($coupon->jenjang_target && $jenjang && $coupon->jenjang_target !== $jenjang) {
            return $this->invalidResponse('Kupon tidak berlaku untuk jenjang ini.', $coupon);
        }

        // Check total usage limit
        if ($coupon->max_usage_total > 0) {
            $totalUsage = $coupon->usages()->count();
            if ($totalUsage >= $coupon->max_usage_total) {
                return $this->invalidResponse('Kuota kupon sudah habis.', $coupon);
            }
        }

        // Check per-user usage limit
        if ($coupon->max_usage_per_user > 0) {
            $userUsage = $this->countUserUsage($coupon, $user, $identifier);
            if ($userUsage >= $coupon->max_usage_per_user) {
                return $this->invalidResponse('Batas penggunaan kupon per pengguna sudah tercapai.', $coupon);
            }
        }

        // Calculate discount
        $discountValue = $this->calculateDiscount($coupon, $amount);
        $discountedAmount = max(0, $amount - $discountValue);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discounted_amount' => $discountedAmount,
            'discount_value' => $discountValue,
            'error' => null,
        ];
    }

    /**
     * Calculate discount value for a coupon.
     */
    public function calculateDiscount(Coupon $coupon, float $amount): float
    {
        if ($coupon->isFree()) {
            return $amount;
        }

        if ($coupon->isPercentage()) {
            $percentage = min(100, max(0, (float) $coupon->value));

            return round($amount * ($percentage / 100), 2);
        }

        if ($coupon->isNominal()) {
            return min($amount, (float) $coupon->value);
        }

        return 0.0;
    }

    /**
     * Record a coupon usage.
     *
     * @param  string|null  $referenceCode  Transaction/order reference
     */
    public function recordUsage(
        Coupon $coupon,
        float $originalAmount,
        float $discountValue,
        float $finalAmount,
        ?User $user = null,
        ?string $identifier = null,
        ?string $referenceCode = null
    ): CouponUsage {
        return CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user?->id,
            'identifier' => $identifier,
            'reference_code' => $referenceCode,
            'original_amount' => $originalAmount,
            'discount_value' => $discountValue,
            'final_amount' => $finalAmount,
        ]);
    }

    /**
     * Get the remaining usage count for a coupon.
     */
    public function remainingUsage(Coupon $coupon): ?int
    {
        if ($coupon->max_usage_total <= 0) {
            return null; // unlimited
        }

        return max(0, $coupon->max_usage_total - $coupon->usages()->count());
    }

    private function countUserUsage(Coupon $coupon, ?User $user, ?string $identifier): int
    {
        $query = $coupon->usages();

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($identifier) {
            $query->where('identifier', $identifier);
        } else {
            return 0;
        }

        return $query->count();
    }

    /**
     * @return array{valid: bool, coupon: Coupon|null, discounted_amount: float, discount_value: float, error: string|null}
     */
    private function invalidResponse(string $error, ?Coupon $coupon = null): array
    {
        return [
            'valid' => false,
            'coupon' => $coupon,
            'discounted_amount' => 0.0,
            'discount_value' => 0.0,
            'error' => $error,
        ];
    }
}
