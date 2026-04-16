<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UjianSesi extends Model
{
    protected $fillable = [
        'exam_id',
        'paket_soal_id',
        'nama',
        'nomor_wa',
        'session_token',
        'timer_state',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'skor',
    ];

    protected $casts = [
        'timer_state' => 'array',
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'skor' => 'decimal:2',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function jawabanSiswas(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class);
    }
}
