<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeksBacaan extends Model
{
    protected $fillable = ['mapel_paket_id', 'judul', 'konten'];

    public function mapelPaket(): BelongsTo
    {
        return $this->belongsTo(MapelPaket::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class);
    }
}
