<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model {
    protected $guarded = [];
    protected $casts = [
        'tanggal_terbit' => 'datetime',
        'is_active'      => 'boolean',
    ];

    public function getAssessmentLabelAttribute(): string
    {
        $assessmentType = $this->assessment_type ?: ($this->paketSoal?->assessment_type ?: 'paket_lengkap');

        return config('ujion.assessment_types.' . $assessmentType . '.label', strtoupper((string) $assessmentType));
    }

    public function isSurvey(): bool
    {
        return in_array($this->assessment_type, ['survey_karakter', 'sulingjar'], true);
    }

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

    public function examMapelTokens()
    {
        return $this->hasMany(ExamMapelToken::class);
    }

    public function mapels()
    {
        return $this->paketSoal->mapels();
    }
}
