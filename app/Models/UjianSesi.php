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
        'mapel_paket_id',
        'user_id',
        'nama',
        'nomor_wa',
        'session_token',
        'timer_state',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'skor',
        'profil_ringkasan',
    ];

    protected $casts = [
        'timer_state'   => 'array',
        'waktu_mulai'   => 'datetime',
        'waktu_selesai' => 'datetime',
        'skor'          => 'decimal:2',
        'profil_ringkasan' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function mapelPaket(): BelongsTo
    {
        return $this->belongsTo(MapelPaket::class);
    }

    public function jawabanSiswas(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class);
    }
}
