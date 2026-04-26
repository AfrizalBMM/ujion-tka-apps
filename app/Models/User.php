<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SISWA = 'siswa';
    public const ROLE_GURU = 'guru';
    public const ROLE_SUPERADMIN = 'superadmin';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPEND = 'suspend';

    public const PAYMENT_AWAITING = 'awaiting_payment';
    public const PAYMENT_SUBMITTED = 'submitted';
    public const PAYMENT_APPROVED = 'approved';
    public const PAYMENT_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'account_status',
        'payment_status',
        'payment_proof_path',
        'payment_submitted_at',
        'payment_verified_at',
        'payment_reviewed_by',
        'payment_rejection_reason',
        'access_token',
        'jenjang',
        'satuan_pendidikan',
        'no_wa',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'access_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'access_token' => 'hashed',
            'bookmarks' => 'array',
            'global_question_bookmarks' => 'array',
            'payment_submitted_at' => 'datetime',
            'payment_verified_at' => 'datetime',
        ];
    }

    public function paymentReviewer()
    {
        return $this->belongsTo(self::class, 'payment_reviewed_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isGuru(): bool
    {
        return $this->role === self::ROLE_GURU;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        $background = $this->isSuperadmin() ? '4F6EF7' : '22C1C3';

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?: 'User') . "&background={$background}&color=fff";
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'user_id');
    }

    public function createdPaketSoals()
    {
        return $this->hasMany(PaketSoal::class, 'created_by');
    }

    public function personalQuestions()
    {
        return $this->hasMany(PersonalQuestion::class);
    }

    public function sentChats()
    {
        return $this->hasMany(Chat::class, 'from_user_id');
    }

    public function receivedChats()
    {
        return $this->hasMany(Chat::class, 'to_user_id');
    }
}
