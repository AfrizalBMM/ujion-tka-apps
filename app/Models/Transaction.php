<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const PAYMENT_METHOD_DOKU = 'doku';

    protected $fillable = [
        'user_id',
        'pricing_plan_id',
        'reference_code',
        'plan_name',
        'amount',
        'status',
        'payment_method',
        'doku_invoice_number',
        'doku_transaction_status',
        'doku_payment_channel',
        'paid_at',
        'payment_proof_path',
        'payment_submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function tarifJenjang()
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
