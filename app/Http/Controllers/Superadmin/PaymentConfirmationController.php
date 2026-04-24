<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Support\GuruNotificationTemplates;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentConfirmationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));

        $transactionsQuery = Transaction::query()
            ->with(['user', 'tarifJenjang'])
            ->where('status', Transaction::STATUS_PENDING)
            ->whereNotNull('payment_submitted_at');

        if ($search !== '') {
            $transactionsQuery->where(function ($query) use ($search) {
                $query->where('reference_code', 'like', "%{$search}%")
                    ->orWhere('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('no_wa', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $transactionsQuery
            ->orderByDesc('payment_submitted_at')
            ->get();

        $summary = [
            'pending' => Transaction::query()->where('status', Transaction::STATUS_PENDING)->whereNotNull('payment_submitted_at')->count(),
            'success' => Transaction::query()->where('status', Transaction::STATUS_SUCCESS)->count(),
            'failed' => Transaction::query()->where('status', Transaction::STATUS_FAILED)->count(),
        ];

        return view('superadmin.payment-confirmations', compact('transactions', 'summary', 'search'));
    }

    public function approve(Transaction $transaction): RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_PENDING || blank($transaction->payment_proof_path)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Bukti pembayaran belum tersedia',
                'message' => 'Transaksi ini belum siap untuk diverifikasi atau statusnya sudah berubah.',
            ]);
        }

        $teacher = $transaction->user;
        $token = $teacher->access_token ?: $this->generateUniqueToken();

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

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pembayaran berhasil disetujui',
            'message' => "Transaksi {$transaction->reference_code} telah ditandai sukses dan akun guru sudah aktif.",
            'description' => 'Gunakan template di bawah ini untuk mengirim konfirmasi pembayaran dan token akses ke guru.',
            'token' => $token,
            'token_label' => 'Token akses',
            'copy_block' => GuruNotificationTemplates::paymentApproved($teacher->name, $token),
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    public function reject(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Transaksi tidak bisa ditolak',
                'message' => 'Status transaksi ini sudah berubah dan tidak lagi menunggu review.',
            ]);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $transaction->update([
            'status' => Transaction::STATUS_FAILED,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $transaction->user->update([
            'payment_status' => User::PAYMENT_REJECTED,
            'payment_verified_at' => now(),
            'payment_reviewed_by' => Auth::id(),
            'payment_rejection_reason' => $validated['rejection_reason'],
            'account_status' => User::STATUS_PENDING,
        ]);

        return back()->with('flash', [
            'type' => 'warning',
            'title' => 'Pembayaran ditandai perlu perbaikan',
            'message' => "Transaksi {$transaction->reference_code} ditolak dan guru diminta mengunggah ulang bukti pembayaran.",
            'copy_block' => GuruNotificationTemplates::paymentRejected(
                $transaction->user->name,
                $validated['rejection_reason']
            ),
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    private function generateUniqueToken(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $candidate = strtoupper(Str::random(10));
            if (! User::query()->where('access_token', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Gagal generate token unik.');
    }
}
