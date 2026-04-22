<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function getAssessmentLabelAttribute(): string
    {
        return config('ujion.assessment_types.' . $this->assessment_type . '.label', strtoupper((string) $this->assessment_type));
    }
}
