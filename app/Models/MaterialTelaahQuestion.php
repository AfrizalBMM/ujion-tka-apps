<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTelaahQuestion extends Model
{
    protected $table = 'material_telaah_questions';

    protected $fillable = [
        'material_id',
        'global_question_id',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function globalQuestion(): BelongsTo
    {
        return $this->belongsTo(GlobalQuestion::class);
    }
}
