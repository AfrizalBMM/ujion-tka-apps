<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_NOMINAL = 'nominal';

    public const TYPE_FREE = 'free';

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_transaction',
        'max_usage_total',
        'max_usage_per_user',
        'jenjang_target',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_transaction' => 'decimal:2',
        'max_usage_total' => 'integer',
        'max_usage_per_user' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isPercentage(): bool
    {
        return $this->type === self::TYPE_PERCENTAGE;
    }

    public function isNominal(): bool
    {
        return $this->type === self::TYPE_NOMINAL;
    }

    public function isFree(): bool
    {
        return $this->type === self::TYPE_FREE;
    }
}
