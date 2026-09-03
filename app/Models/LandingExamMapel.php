<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingExamMapel extends Model
{
    protected $fillable = [
        'landing_exam_id',
        'mapel_paket_id',
        'price',
        'original_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function landingExam(): BelongsTo
    {
        return $this->belongsTo(LandingExam::class);
    }

    public function mapelPaket(): BelongsTo
    {
        return $this->belongsTo(MapelPaket::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(LandingExamOrder::class);
    }
}
