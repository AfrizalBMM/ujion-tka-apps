<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppBlast;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WaMessageTemplateService;
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

    public function approve(Transaction $transaction, \App\Services\PaymentApprovalService $paymentService, WaMessageTemplateService $templates): RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_PENDING || blank($transaction->payment_proof_path)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Bukti pembayaran belum tersedia',
                'message' => 'Transaksi ini belum siap untuk diverifikasi atau statusnya sudah berubah.',
            ]);
        }

        try {
            $teacher = $transaction->user;
        } catch (\Exception $e) {
            return $this->missingTeacherResponse();
        }

        if (! $teacher) {
            return $this->missingTeacherResponse();
        }

        $token = $paymentService->approve($teacher, $transaction);

        $waBody = $templates->render('event_payment_approved', [
            'name' => $teacher->name,
            'token' => $token,
        ]);

        if (! blank($teacher->no_wa)) {
            SendWhatsAppBlast::dispatch($teacher->no_wa, $waBody)->onQueue('high');
        }

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pembayaran berhasil disetujui',
            'message' => "Transaksi {$transaction->reference_code} telah ditandai sukses dan akun guru sudah aktif.",
            'description' => 'Gunakan template di bawah ini untuk mengirim konfirmasi pembayaran dan token akses ke guru.',
            'token' => $token,
            'token_label' => 'Token akses',
            'copy_block' => $waBody,
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    public function reject(Request $request, Transaction $transaction, \App\Services\PaymentApprovalService $paymentService, WaMessageTemplateService $templates): RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Transaksi tidak bisa ditolak',
                'message' => 'Status transaksi ini sudah berubah dan tidak lagi menunggu review.',
            ]);
        }

        $teacher = $transaction->user;
        if (! $teacher) {
            return $this->missingTeacherResponse();
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $paymentService->reject($teacher, $validated['rejection_reason'], $transaction);

        $waBody = $templates->render('event_payment_rejected', [
            'name' => $teacher->name,
            'reason' => $validated['rejection_reason'],
        ]);

        if (! blank($teacher->no_wa)) {
            SendWhatsAppBlast::dispatch($teacher->no_wa, $waBody)->onQueue('high');
        }

        return back()->with('flash', [
            'type' => 'warning',
            'title' => 'Pembayaran ditandai perlu perbaikan',
            'message' => "Transaksi {$transaction->reference_code} ditolak dan guru diminta mengunggah ulang bukti pembayaran.",
            'copy_block' => $waBody,
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    private function missingTeacherResponse(): RedirectResponse
    {
        return back()->with('flash', [
            'type' => 'warning',
            'title' => 'Akun guru tidak ditemukan',
            'message' => 'Akun guru untuk transaksi ini sudah tidak tersedia. Periksa data transaksi sebelum melanjutkan review.',
        ]);
    }

}
