<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
