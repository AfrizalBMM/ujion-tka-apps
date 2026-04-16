<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PilihanJawaban extends Model
{
    protected $fillable = ['soal_id', 'kode', 'teks', 'gambar', 'is_benar'];

    protected $casts = [
        'is_benar' => 'boolean',
    ];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? Storage::url($this->gambar) : null;
    }
}
