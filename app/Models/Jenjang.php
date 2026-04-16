<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jenjang extends Model
{
    protected $fillable = ['kode', 'nama', 'urutan'];

    public function paketSoals(): HasMany
    {
        return $this->hasMany(PaketSoal::class)->orderByDesc('tahun_ajaran');
    }
}
