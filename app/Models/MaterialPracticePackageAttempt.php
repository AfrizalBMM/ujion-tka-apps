<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialPracticePackageAttempt extends Model
{
    protected $fillable = [
        'material_practice_session_id',
        'material_practice_package_id',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'total_soal',
        'benar',
        'skor',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'total_soal' => 'integer',
        'benar' => 'integer',
        'skor' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MaterialPracticeSession::class, 'material_practice_session_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(MaterialPracticePackage::class, 'material_practice_package_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MaterialPracticePackageAnswer::class, 'material_practice_package_attempt_id');
    }
}
