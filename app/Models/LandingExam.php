<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class LandingExam extends Model
{
    protected $fillable = [
        'exam_id',
        'jenjang',
        'slug',
        'short_description',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->jenjang, $model->exam?->judul);
            }
        });
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function mapels(): HasMany
    {
        return $this->hasMany(LandingExamMapel::class)->orderBy('sort_order');
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(LandingExamOrder::class, LandingExamMapel::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function generateUniqueSlug(string $jenjang, ?string $title): string
    {
        $base = Str::slug($title ?: 'ujian') ?: 'ujian';
        $candidate = $base;

        for ($i = 0; $i < 20; $i++) {
            if (! self::query()->where('slug', $candidate)->where('id', '!=', $this->id ?? 0)->exists()) {
                return $candidate;
            }
            $candidate = $base.'-'.Str::random(4);
        }

        return $base.'-'.time();
    }
}
