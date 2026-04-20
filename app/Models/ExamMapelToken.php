<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExamMapelToken extends Model
{
    protected $fillable = ['exam_id', 'mapel_paket_id', 'token'];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->token)) {
                $model->token = self::generateUniqueToken();
            }
        });
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function mapelPaket(): BelongsTo
    {
        return $this->belongsTo(MapelPaket::class);
    }

    public static function generateUniqueToken(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = strtoupper(Str::random(8));
            if (! self::query()->where('token', $candidate)->exists()) {
                return $candidate;
            }
        }
        abort(500, 'Gagal generate token mapel unik.');
    }
}
