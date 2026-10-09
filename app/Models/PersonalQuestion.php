<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'jenjang',
        'kategori',
        'tipe',
        'pertanyaan',
        'opsi',
        'jawaban_benar',
        'pembahasan',
        'image_path',
        'status',
    ];

    protected $casts = [
        'opsi' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
