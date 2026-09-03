<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PricingPlan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'promo_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function resolveForJenjang(?string $jenjang): ?self
    {
        if (! Schema::hasTable('pricing_plans')) {
            return null;
        }

        $query = static::query()->where('is_active', true);

        if ($jenjang && Schema::hasColumn('pricing_plans', 'jenjang')) {
            $plan = (clone $query)
                ->where('jenjang', $jenjang)
                ->first();

            if ($plan) {
                return $plan;
            }
        }

        if (Schema::hasColumn('pricing_plans', 'jenjang')) {
            return (clone $query)
                ->whereNull('jenjang')
                ->first();
        }

        return $query->first();
    }
}
