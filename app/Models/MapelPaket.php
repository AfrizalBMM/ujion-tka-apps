<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapelPaket extends Model
{
    protected $fillable = ['paket_soal_id', 'nama_mapel', 'jumlah_soal', 'durasi_menit', 'urutan'];

    public function getAssessmentTypeAttribute(): string
    {
        return config('ujion.mapel_assessment_types.' . $this->nama_mapel, 'tka');
    }

    public function isSurvey(): bool
    {
        return in_array($this->assessment_type, ['survey_karakter', 'sulingjar'], true);
    }

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
        return config('ujion.mapel_labels.' . $this->nama_mapel)
            ?? str($this->nama_mapel)->replace('_', ' ')->headline()->toString();
    }
}
