<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTelaahAnswer extends Model
{
    protected $table = 'material_telaah_answers';

    protected $fillable = [
        'material_practice_session_id',
        'global_question_id',
        'jawaban',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MaterialPracticeSession::class, 'material_practice_session_id');
    }

    public function globalQuestion(): BelongsTo
    {
        return $this->belongsTo(GlobalQuestion::class);
    }
}
