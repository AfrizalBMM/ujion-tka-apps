<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalQuestion extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'jenjang_id' => 'integer',
        'material_id' => 'integer',
        'created_by' => 'integer',
        'reading_passage' => 'string',
    ];

    public function getAssessmentLabelAttribute(): string
    {
        return config('ujion.assessment_types.' . $this->assessment_type . '.label', strtoupper((string) $this->assessment_type));
    }

    public function jenjang()
    {
        return $this->belongsTo(Jenjang::class, 'jenjang_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
