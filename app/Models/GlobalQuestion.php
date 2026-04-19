<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalQuestion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'jenjang_id' => 'integer',
        'material_id' => 'integer',
        'created_by' => 'integer',
        'reading_passage' => 'string',
    ];

    public function jenjang()
    {
        return $this->belongsTo(Jenjang::class, 'jenjang_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
