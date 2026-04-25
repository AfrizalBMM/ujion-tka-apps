<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingBranding extends Model
{
    protected $fillable = [
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
