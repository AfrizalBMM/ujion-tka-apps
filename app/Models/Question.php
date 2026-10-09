<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'material_id',
        'jenjang',
        'tingkat',
        'kategori',
        'tipe',
        'pertanyaan',
        'opsi',
        'jawaban_benar',
        'pembahasan',
        'image_path',
        'status',
        'is_active',
    ];

    protected $casts = [
        'opsi' => 'array',
        'is_active' => 'boolean',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_question')->withTimestamps()->withPivot('order');
    }
}
