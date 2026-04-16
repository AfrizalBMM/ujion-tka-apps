<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasanganMenjodohkan extends Model
{
    protected $fillable = ['soal_id', 'teks_kiri', 'teks_kanan', 'urutan'];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
