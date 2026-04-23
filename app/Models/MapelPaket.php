<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapelPaket extends Model
{
    public const NAMA_MATEMATIKA = 'matematika';
    public const NAMA_BAHASA_INDONESIA = 'bahasa_indonesia';
    public const NAMA_SURVEY_KARAKTER = 'survey_karakter';
    public const NAMA_SURVEY_LINGKUNGAN = 'survey_lingkungan_belajar';

    protected $fillable = [
        'paket_soal_id',
        'nama_mapel',
        'kategori_komponen',
        'mode_penilaian',
        'kode_komponen',
        'is_wajib',
        'petunjuk_khusus',
        'jumlah_soal',
        'durasi_menit',
        'urutan',
    ];

    protected $casts = [
        'is_wajib' => 'boolean',
    ];

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('nomor_soal');
    }

    public function teksBacaans(): HasMany
    {
        return $this->hasMany(TeksBacaan::class);
    }

    public function getNamaLabelAttribute(): string
    {
        return match ($this->nama_mapel) {
            self::NAMA_MATEMATIKA => 'Matematika',
            self::NAMA_BAHASA_INDONESIA => 'Bahasa Indonesia',
            self::NAMA_SURVEY_KARAKTER => 'Survey Karakter',
            self::NAMA_SURVEY_LINGKUNGAN => 'Survey Lingkungan Belajar',
            default => str($this->nama_mapel)->headline()->toString(),
        };
    }

    public function isSurvey(): bool
    {
        return $this->kategori_komponen === 'survey';
    }

    public function isAkademik(): bool
    {
        return ! $this->isSurvey();
    }

    public function usesProfiling(): bool
    {
        return $this->mode_penilaian === 'profiling';
    }

    public function usesScore(): bool
    {
        return $this->mode_penilaian === 'score';
    }
}
