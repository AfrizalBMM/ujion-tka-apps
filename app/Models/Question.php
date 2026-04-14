<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Question extends Model {
    protected $guarded = [];
    protected $casts = [
        'opsi' => 'array',
        'is_active' => 'boolean',
    ];
    public function material() { return $this->belongsTo(Material::class); }
    public function exams() {
        return $this->belongsToMany(Exam::class, 'exam_question')->withTimestamps()->withPivot('order');
    }
}