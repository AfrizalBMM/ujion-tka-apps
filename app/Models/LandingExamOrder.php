<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LandingExamOrder extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXAM_STARTED = 'exam_started';

    public const STATUS_EXAM_COMPLETED = 'exam_completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'landing_exam_mapel_id',
        'nama',
        'nomor_wa',
        'session_token',
        'status',
        'amount',
        'midtrans_order_id',
        'midtrans_transaction_status',
        'midtrans_payment_type',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->session_token)) {
                $model->session_token = Str::random(80);
            }
        });
    }

    public function landingExamMapel(): BelongsTo
    {
        return $this->belongsTo(LandingExamMapel::class);
    }

    public function ujianSesi(): HasOne
    {
        return $this->hasOne(UjianSesi::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID
            || $this->status === self::STATUS_EXAM_STARTED
            || $this->status === self::STATUS_EXAM_COMPLETED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_EXAM_COMPLETED;
    }
}
