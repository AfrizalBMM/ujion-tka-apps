<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JawabanSiswa extends Model
{
    protected $fillable = [
        'ujian_sesi_id',
        'soal_id',
        'tipe_soal',
        'jawaban_pg',
        'jawaban_menjodohkan',
        'is_ragu',
    ];

    protected $casts = [
        'jawaban_menjodohkan' => 'array',
        'is_ragu' => 'boolean',
    ];

    public function ujianSesi(): BelongsTo
    {
        return $this->belongsTo(UjianSesi::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
