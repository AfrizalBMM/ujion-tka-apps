<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingHeroMockup extends Model
{
    protected $fillable = [
        'badge',
        'title',
        'description',
        'image_path',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.ltrim($this->image_path, '/'));
    }
}
