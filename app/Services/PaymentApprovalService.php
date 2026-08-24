<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\TokenGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentApprovalService
{
    public function approve(User $teacher, ?Transaction $transaction = null): ?string
    {
        return DB::transaction(function () use ($teacher, $transaction): ?string {
            $teacher = User::whereKey($teacher->id)->lockForUpdate()->firstOrFail();

            $transaction = $transaction
                ? Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail()
                : Transaction::where('user_id', $teacher->id)
                    ->where('status', Transaction::STATUS_PENDING)
                    ->lockForUpdate()
                    ->first();

            if ($transaction && $transaction->status === Transaction::STATUS_SUCCESS) {
                return null;
            }

            if (! $transaction
                && $teacher->payment_status === User::PAYMENT_APPROVED
                && $teacher->account_status === User::STATUS_ACTIVE) {
                return null;
            }

            $token = TokenGenerator::uniqueTeacherToken();

            if ($transaction) {
                $transaction->update([
                    'status' => Transaction::STATUS_SUCCESS,
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'rejection_reason' => null,
                ]);

                $teacher->update([
                    'role' => User::ROLE_GURU,
                    'account_status' => User::STATUS_ACTIVE,
                    'payment_status' => User::PAYMENT_APPROVED,
                    'payment_verified_at' => now(),
                    'payment_reviewed_by' => Auth::id(),
                    'payment_rejection_reason' => null,
                    'payment_proof_path' => $transaction->payment_proof_path,
                    'payment_submitted_at' => $transaction->payment_submitted_at,
                    'access_token' => $token,
                ]);
            } else {
                $teacher->update([
                    'role' => User::ROLE_GURU,
                    'account_status' => User::STATUS_ACTIVE,
                    'payment_status' => User::PAYMENT_APPROVED,
                    'payment_verified_at' => now(),
                    'payment_reviewed_by' => Auth::id(),
                    'payment_rejection_reason' => null,
                    'access_token' => $token,
                ]);
            }

            return $token;
        });
    }

    public function reject(User $teacher, string $reason, ?Transaction $transaction = null): bool
    {
        return DB::transaction(function () use ($teacher, $transaction, $reason): bool {
            $teacher = User::whereKey($teacher->id)->lockForUpdate()->firstOrFail();

            $transaction = $transaction
                ? Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail()
                : Transaction::where('user_id', $teacher->id)
                    ->where('status', Transaction::STATUS_PENDING)
                    ->lockForUpdate()
                    ->first();

            if ($transaction && $transaction->status === Transaction::STATUS_SUCCESS) {
                return false;
            }

            if ($transaction) {
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'rejection_reason' => $reason,
                ]);
            }

            $teacher->update([
                'payment_status' => User::PAYMENT_REJECTED,
                'payment_verified_at' => now(),
                'payment_reviewed_by' => Auth::id(),
                'payment_rejection_reason' => $reason,
                'account_status' => User::STATUS_PENDING,
            ]);

            return true;
        });
    }
}
