<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PilihanJawaban extends Model
{
    protected $fillable = ['soal_id', 'kode', 'teks', 'gambar', 'is_benar', 'nilai_survey', 'profil_label'];

    protected $casts = [
        'is_benar' => 'boolean',
        'nilai_survey' => 'integer',
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
