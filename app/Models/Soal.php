<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Soal extends Model
{
    protected $fillable = [
        'mapel_paket_id',
        'teks_bacaan_id',
        'nomor_soal',
        'tipe_soal',
        'jenis_instrumen',
        'indikator',
        'dimensi',
        'subdimensi',
        'kategori_profil',
        'arah_skor',
        'pertanyaan',
        'gambar',
        'bobot',
    ];

    public function mapelPaket(): BelongsTo
    {
        return $this->belongsTo(MapelPaket::class);
    }

    public function teksBacaan(): BelongsTo
    {
        return $this->belongsTo(TeksBacaan::class);
    }

    public function pilihanJawabans(): HasMany
    {
        return $this->hasMany(PilihanJawaban::class)->orderBy('kode');
    }

    public function pasanganMenjodohkans(): HasMany
    {
        return $this->hasMany(PasanganMenjodohkan::class)->orderBy('urutan');
    }

    public function jawabanSiswas(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class);
    }

    public function isPilihanGanda(): bool
    {
        return $this->tipe_soal === 'pilihan_ganda';
    }

    public function isMenjodohkan(): bool
    {
        return $this->tipe_soal === 'menjodohkan';
    }

    public function isSurvey(): bool
    {
        return $this->jenis_instrumen !== 'akademik';
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? Storage::url($this->gambar) : null;
    }
}
