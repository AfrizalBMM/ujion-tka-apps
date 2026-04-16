<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapelPaket extends Model
{
    protected $fillable = ['paket_soal_id', 'nama_mapel', 'jumlah_soal', 'durasi_menit', 'urutan'];

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
            'matematika' => 'Matematika',
            'bahasa_indonesia' => 'Bahasa Indonesia',
            default => str($this->nama_mapel)->headline()->toString(),
        };
    }
}
