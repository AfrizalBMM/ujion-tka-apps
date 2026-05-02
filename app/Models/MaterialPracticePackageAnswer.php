<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPracticePackageAnswer extends Model
{
    protected $fillable = [
        'material_practice_package_attempt_id',
        'global_question_id',
        'jawaban',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(MaterialPracticePackageAttempt::class, 'material_practice_package_attempt_id');
    }

    public function globalQuestion(): BelongsTo
    {
        return $this->belongsTo(GlobalQuestion::class);
    }
}
