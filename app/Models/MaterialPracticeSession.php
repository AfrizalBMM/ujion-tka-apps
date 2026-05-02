<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialPracticeSession extends Model
{
    protected $fillable = [
        'material_practice_token_id',
        'nama',
        'nomor_wa',
        'session_token',
        'status',
        'waktu_mulai',
        'waktu_selesai',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MaterialPracticeToken::class, 'material_practice_token_id');
    }

    public function telaahAnswers(): HasMany
    {
        return $this->hasMany(MaterialTelaahAnswer::class, 'material_practice_session_id');
    }

    public function packageAttempts(): HasMany
    {
        return $this->hasMany(MaterialPracticePackageAttempt::class, 'material_practice_session_id');
    }
}
