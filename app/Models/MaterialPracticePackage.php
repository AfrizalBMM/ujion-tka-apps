<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialPracticePackage extends Model
{
    protected $fillable = [
        'material_practice_token_id',
        'paket_no',
    ];

    protected $casts = [
        'paket_no' => 'integer',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MaterialPracticeToken::class, 'material_practice_token_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(GlobalQuestion::class, 'material_practice_package_questions', 'material_practice_package_id', 'global_question_id')
            ->withTimestamps()
            ->withPivot('urutan')
            ->orderByPivot('urutan');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(MaterialPracticePackageAttempt::class, 'material_practice_package_id');
    }
}
