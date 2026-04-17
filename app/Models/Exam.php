<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Exam extends Model {
    protected $guarded = [];
    protected $casts = [
        'tanggal_terbit' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $exam): void {
            if (blank($exam->token)) {
                $exam->token = self::generateUniqueToken();
            }
        });
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

    public static function generateUniqueToken(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = strtoupper(Str::random(6));

            if (! self::query()->where('token', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Gagal generate token ujian unik.');
    }
}
