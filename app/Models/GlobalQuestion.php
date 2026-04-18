<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalQuestion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'material_id' => 'integer',
        'created_by' => 'integer',
    ];
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
