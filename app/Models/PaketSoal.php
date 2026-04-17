<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaketSoal extends Model
{
    protected $fillable = ['jenjang_id', 'nama', 'tahun_ajaran', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function mapelPakets(): HasMany
    {
        return $this->hasMany(MapelPaket::class)->orderBy('urutan');
    }

    public function mapels(): HasMany
    {
        return $this->mapelPakets();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isManagedByGuru(?User $user): bool
    {
        return $user?->isGuru()
            && $this->created_by === $user->id
            && $this->jenjang?->kode === $user->jenjang;
    }
}
