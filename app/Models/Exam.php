<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model {
    protected $guarded = [];
    protected $casts = [
        'tanggal_terbit' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function questions() {
        return $this->belongsToMany(Question::class, 'exam_question')->withTimestamps()->withPivot('order');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paketSoal()
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function participants() {
        return $this->hasMany(Participant::class);
    }

    public function ujianSesis()
    {
        return $this->hasMany(UjianSesi::class);
    }
}
