<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppBlast;
use App\Models\Coupon;
use App\Models\LandingExamOrder;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CouponService;
use App\Services\DokuService;
use App\Services\PaymentApprovalService;
use App\Services\WaMessageTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuPaymentController extends Controller
{
    public function start(Request $request, DokuService $doku): JsonResponse
    {
        if (! $doku->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Pembayaran otomatis belum diaktifkan admin. Silakan hubungi admin.',
            ], 503);
        }

        $teacher = $request->user();

        if (! $teacher || $teacher->role !== User::ROLE_GURU) {
            return response()->json([
                'ok' => false,
                'message' => 'Sesi tidak valid. Silakan daftar atau masuk kembali.',
            ], 401);
        }

        if ($teacher->account_status === User::STATUS_SUSPEND) {
            return response()->json([
                'ok' => false,
                'message' => 'Akun Anda ditangguhkan. Silakan hubungi admin.',
            ], 403);
        }

        $existingSuccess = $teacher->transactions()
            ->where('status', Transaction::STATUS_SUCCESS)
            ->exists();

        if ($existingSuccess || $teacher->account_status === User::STATUS_ACTIVE) {
            return response()->json([
                'ok' => false,
                'message' => 'Pembayaran Anda sudah sukses sebelumnya.',
                'status' => 'success',
            ], 409);
        }

        $plan = PricingPlan::resolveForJenjang($teacher->jenjang);

        if (! $plan) {
            return response()->json([
                'ok' => false,
                'message' => 'Tarif jenjang belum tersedia. Hubungi admin untuk melanjutkan pembayaran.',
            ], 422);
        }

        $transaction = $this->createPendingTransactionFor($teacher, $plan);

        try {
            $checkout = $doku->createCheckoutPayment($transaction);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        $transaction->refresh();

        return response()->json([
            'ok' => true,
            'payment_url' => $checkout['payment_url'],
            'order_id' => $checkout['invoice_number'],
            'reference_code' => $transaction->reference_code,
            'plan_name' => $transaction->plan_name,
            'amount' => 'Rp'.number_format((float) $transaction->amount, 0, ',', '.'),
        ]);
    }

    public function createPendingTransactionFor(User $teacher, PricingPlan $plan): Transaction
    {
        $planAmount = $this->sanitizeAmount($plan->price);

        return DB::transaction(function () use ($teacher, $plan, $planAmount): Transaction {
            $transaction = $teacher->transactions()
                ->where('status', Transaction::STATUS_PENDING)
                ->where('amount', $planAmount)
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($transaction) {
                return $transaction;
            }

            $teacher->transactions()
                ->where('status', Transaction::STATUS_PENDING)
                ->where('amount', '!=', $planAmount)
                ->update([
                    'status' => Transaction::STATUS_FAILED,
                    'rejection_reason' => 'Dibatalkan otomatis karena tarif berubah.',
                    'reviewed_at' => now(),
                ]);

            return $teacher->transactions()->create([
                'pricing_plan_id' => $plan->id,
                'reference_code' => $this->generateReferenceCode(),
                'plan_name' => $plan->name,
                'amount' => $planAmount,
                'status' => Transaction::STATUS_PENDING,
            ]);
        });
    }

    private function sanitizeAmount(string|int|float|null $amount): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $amount) ?? '0';

        return $normalized !== '' ? $normalized : '0';
    }

    private function generateReferenceCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'UJN-'.now()->format('ymd').'-'.strtoupper(Str::random(8));

            if (! Transaction::query()->where('reference_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Gagal generate reference code.');
    }

    public function notification(Request $request, DokuService $doku): JsonResponse
    {
        if (! $doku->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Doku not configured'], 503);
        }

        if (! $doku->verifyNotificationSignature($request)) {
            Log::warning('Doku notification rejected: invalid signature', [
                'invoice' => $request->input('order.invoice_number'),
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $invoiceNumber = $this->extractInvoiceNumber($payload);

        if ($invoiceNumber === '') {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 422);
        }

        if (str_starts_with($invoiceNumber, 'PUJ-')) {
            $order = $this->processPublicExamStatusPayload($payload);

            return response()->json(['success' => $order !== null]);
        }

        if (str_starts_with($invoiceNumber, 'PMB-')) {
            $transaction = $this->processPembahasanStatusPayload($payload);

            return response()->json(['success' => $transaction !== null]);
        }

        $transaction = $this->processStatusPayload($payload);

        return response()->json(['success' => $transaction !== null]);
    }

    public function finish(Request $request, DokuService $doku): RedirectResponse|View
    {
        $invoiceNumber = trim((string) $request->query('order_id', ''));
        $transaction = $this->findTransactionByInvoiceNumber($invoiceNumber);

        if (! $transaction) {
            return redirect()
                ->route('landing')
                ->with('flash', [
                    'type' => 'warning',
                    'title' => 'Transaksi tidak ditemukan',
                    'message' => 'Kami tidak menemukan transaksi untuk pembayaran ini.',
                ]);
        }

        $this->authorizeSessionAccess($transaction);

        if ($transaction->status === Transaction::STATUS_PENDING && $transaction->payment_method === Transaction::PAYMENT_METHOD_DOKU) {
            $remoteStatus = $doku->checkStatus($invoiceNumber);

            if (is_array($remoteStatus)) {
                if ($transaction->type === Transaction::TYPE_PEMBAHASAN) {
                    $transaction = $this->processPembahasanStatusPayload($remoteStatus) ?? $transaction->refresh();
                } else {
                    $transaction = $this->processStatusPayload($remoteStatus) ?? $transaction->refresh();
                }
            }
        }

        // For pembahasan transactions, redirect to the exam result page
        if ($transaction->type === Transaction::TYPE_PEMBAHASAN) {
            $sesi = $transaction->ujianSesi;
            $order = $sesi?->landingExamOrder;
            if ($order && $transaction->status === Transaction::STATUS_SUCCESS) {
                return redirect()
                    ->route('ujian-online.result', $order->session_token)
                    ->with('flash', [
                        'type' => 'success',
                        'title' => 'Pembayaran berhasil',
                        'message' => 'Pembahasan telah terbuka. Silakan lihat hasil ujian Anda.',
                    ]);
            }
        }

        return view('payments.doku-success', [
            'transaction' => $transaction,
            'token' => $transaction->status === Transaction::STATUS_SUCCESS
                ? $transaction->user?->access_token
                : null,
        ]);
    }

    public function status(Request $request, DokuService $doku): JsonResponse
    {
        $invoiceNumber = trim((string) $request->query('order_id', ''));
        $transaction = $this->findTransactionByInvoiceNumber($invoiceNumber);

        if (! $transaction) {
            return response()->json(['ok' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $this->authorizeSessionAccess($transaction);

        if ($transaction->status === Transaction::STATUS_PENDING && $transaction->payment_method === Transaction::PAYMENT_METHOD_DOKU) {
            $remoteStatus = $doku->checkStatus($invoiceNumber);

            if (is_array($remoteStatus)) {
                $transaction = $this->processStatusPayload($remoteStatus) ?? $transaction->refresh();
            }
        }

        return response()->json([
            'ok' => true,
            'reference_code' => $transaction->reference_code,
            'status' => $transaction->status,
            'plan_name' => $transaction->plan_name,
            'amount' => 'Rp'.number_format((float) $transaction->amount, 0, ',', '.'),
            'token' => $transaction->status === Transaction::STATUS_SUCCESS
                ? $transaction->user?->access_token
                : null,
            'login_url' => route('login'),
        ]);
    }

    public function cancel(Request $request): View|RedirectResponse
    {
        if ($request->user()?->role === User::ROLE_GURU) {
            return view('payments.doku-popup-close', [
                'message' => [
                    'type' => 'doku-payment-cancelled',
                ],
                'fallbackUrl' => route('guru.dashboard'),
            ]);
        }

        return redirect()->route('login');
    }

    private function processStatusPayload(array $payload): ?Transaction
    {
        $invoiceNumber = $this->extractInvoiceNumber($payload);
        $transaction = $this->findTransactionByInvoiceNumber($invoiceNumber);

        if (! $transaction) {
            Log::warning('Doku notification for unknown order', ['invoice_number' => $invoiceNumber]);

            return null;
        }

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return $transaction;
        }

        $grossAmount = (float) $this->extractAmount($payload);
        if (abs($grossAmount - (float) $transaction->amount) > 0.01) {
            Log::critical('Doku notification amount mismatch', [
                'reference_code' => $transaction->reference_code,
                'invoice_number' => $invoiceNumber,
                'expected' => $transaction->amount,
                'received' => $this->extractAmount($payload),
            ]);

            return $transaction;
        }

        $dokuStatus = $this->extractStatus($payload);
        $paymentChannel = $this->extractPaymentChannel($payload);

        if ($dokuStatus === 'SUCCESS') {
            $this->markSuccess($transaction, $dokuStatus, $paymentChannel);

            return $transaction->refresh();
        }

        if (in_array($dokuStatus, ['FAILED', 'EXPIRED', 'CANCELLED'], true)) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'doku_transaction_status' => $dokuStatus,
                'doku_payment_channel' => $paymentChannel,
                'rejection_reason' => 'Pembayaran tidak selesai (status: '.strtolower($dokuStatus).').',
                'reviewed_at' => now(),
            ]);

            return $transaction->refresh();
        }

        $transaction->update([
            'doku_transaction_status' => $dokuStatus,
            'doku_payment_channel' => $paymentChannel,
        ]);

        return $transaction->refresh();
    }

    public function processPembahasanStatusPayload(array $payload): ?Transaction
    {
        $invoiceNumber = $this->extractInvoiceNumber($payload);
        $transaction = $this->findTransactionByInvoiceNumber($invoiceNumber);

        if (! $transaction) {
            Log::warning('Doku notification for unknown pembahasan transaction', ['invoice_number' => $invoiceNumber]);

            return null;
        }

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return $transaction;
        }

        $grossAmount = (float) $this->extractAmount($payload);
        if (abs($grossAmount - (float) $transaction->amount) > 0.01) {
            Log::critical('Doku pembahasan amount mismatch', [
                'reference_code' => $transaction->reference_code,
                'invoice_number' => $invoiceNumber,
                'expected' => $transaction->amount,
                'received' => $this->extractAmount($payload),
            ]);

            return $transaction;
        }

        $dokuStatus = $this->extractStatus($payload);
        $paymentChannel = $this->extractPaymentChannel($payload);

        if ($dokuStatus === 'SUCCESS') {
            $this->markPembahasanSuccess($transaction, $dokuStatus, $paymentChannel);

            return $transaction->refresh();
        }

        if (in_array($dokuStatus, ['FAILED', 'EXPIRED', 'CANCELLED'], true)) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'doku_transaction_status' => $dokuStatus,
                'doku_payment_channel' => $paymentChannel,
                'rejection_reason' => 'Pembayaran pembahasan tidak selesai (status: '.strtolower($dokuStatus).').',
                'reviewed_at' => now(),
            ]);

            return $transaction->refresh();
        }

        $transaction->update([
            'doku_transaction_status' => $dokuStatus,
            'doku_payment_channel' => $paymentChannel,
        ]);

        return $transaction->refresh();
    }

    private function markPembahasanSuccess(Transaction $transaction, string $dokuStatus, string $paymentChannel): void
    {
        $transaction->update([
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => Transaction::PAYMENT_METHOD_DOKU,
            'doku_transaction_status' => $dokuStatus,
            'doku_payment_channel' => $paymentChannel,
            'paid_at' => now(),
            'rejection_reason' => null,
        ]);

        // Unlock pembahasan for the associated exam session
        $sesi = $transaction->ujianSesi;
        if ($sesi) {
            $sesi->update(['pembahasan_unlocked_at' => now()]);

            // Record coupon usage if coupon was applied
            if ($transaction->coupon_id && $transaction->discount_value > 0) {
                $coupon = Coupon::find($transaction->coupon_id);
                if ($coupon) {
                    app(CouponService::class)->recordUsage(
                        coupon: $coupon,
                        originalAmount: (float) $transaction->original_amount,
                        discountValue: (float) $transaction->discount_value,
                        finalAmount: (float) $transaction->amount,
                        user: $transaction->user,
                        identifier: $sesi->nomor_wa,
                        referenceCode: $transaction->reference_code,
                    );
                }
            }

            // Send WA notification if we have a phone number
            if (! blank($sesi->nomor_wa)) {
                $waBody = app(WaMessageTemplateService::class)->render('event_pembahasan_unlocked', [
                    'name' => $sesi->nama,
                    'exam_title' => $sesi->exam?->judul ?? 'Ujian',
                ]);

                SendWhatsAppBlast::dispatch($sesi->nomor_wa, $waBody)->onQueue('high');
            }
        } else {
            Log::critical('Pembahasan payment success but no exam session linked', [
                'reference_code' => $transaction->reference_code,
            ]);
        }
    }

    public function processPublicExamStatusPayload(array $payload): ?LandingExamOrder
    {
        $invoiceNumber = $this->extractInvoiceNumber($payload);
        $order = app(DokuService::class)->findOrder($invoiceNumber);

        if (! $order) {
            Log::warning('Doku notification for unknown public exam order', ['invoice_number' => $invoiceNumber]);

            return null;
        }

        if ($order->isPaid()) {
            return $order;
        }

        $grossAmount = (float) $this->extractAmount($payload);
        if (abs($grossAmount - (float) $order->amount) > 0.01) {
            Log::critical('Doku public exam amount mismatch', [
                'invoice_number' => $invoiceNumber,
                'expected' => $order->amount,
                'received' => $this->extractAmount($payload),
            ]);

            return $order;
        }

        $dokuStatus = $this->extractStatus($payload);
        $paymentChannel = $this->extractPaymentChannel($payload);

        if ($dokuStatus === 'SUCCESS') {
            $this->markPublicExamPaid($order, $dokuStatus, $paymentChannel);

            return $order->refresh();
        }

        if (in_array($dokuStatus, ['FAILED', 'EXPIRED', 'CANCELLED'], true)) {
            $order->update([
                'status' => LandingExamOrder::STATUS_FAILED,
                'doku_transaction_status' => $dokuStatus,
                'doku_payment_channel' => $paymentChannel,
            ]);

            return $order->refresh();
        }

        $order->update([
            'doku_transaction_status' => $dokuStatus,
            'doku_payment_channel' => $paymentChannel,
        ]);

        return $order->refresh();
    }

    private function markPublicExamPaid(LandingExamOrder $order, string $dokuStatus, string $paymentChannel): void
    {
        $order->update([
            'status' => LandingExamOrder::STATUS_PAID,
            'doku_transaction_status' => $dokuStatus,
            'doku_payment_channel' => $paymentChannel,
            'paid_at' => now(),
        ]);

        $mapel = $order->landingExamMapel;
        $landingExam = $mapel?->landingExam;
        $exam = $landingExam?->exam;
        $mapelPaket = $mapel?->mapelPaket;

        if (! $exam || ! $mapelPaket) {
            Log::critical('Public exam paid but exam/mapel missing', ['order_id' => $order->id]);

            return;
        }

        if (! blank($order->nomor_wa)) {
            $waBody = app(WaMessageTemplateService::class)->render('event_public_exam_paid', [
                'name' => $order->nama,
                'exam_title' => $exam->judul,
                'mapel_label' => $mapelPaket->nama_label,
                'exam_url' => route('ujian-online.start', $order->session_token),
            ]);

            SendWhatsAppBlast::dispatch($order->nomor_wa, $waBody)->onQueue('high');
        }
    }

    private function markSuccess(Transaction $transaction, string $dokuStatus, string $paymentChannel): void
    {
        $transaction->update([
            'payment_method' => Transaction::PAYMENT_METHOD_DOKU,
            'doku_transaction_status' => $dokuStatus,
            'doku_payment_channel' => $paymentChannel,
            'paid_at' => now(),
            'rejection_reason' => null,
        ]);

        $teacher = $transaction->user;

        if (! $teacher) {
            Log::critical('Doku settlement without teacher account', [
                'reference_code' => $transaction->reference_code,
            ]);

            return;
        }

        $token = app(PaymentApprovalService::class)->approve($teacher, $transaction->refresh());

        if ($token !== null && ! blank($teacher->no_wa)) {
            $waBody = app(WaMessageTemplateService::class)->render('event_payment_approved', [
                'name' => $teacher->name,
                'token' => $token,
            ]);

            SendWhatsAppBlast::dispatch($teacher->no_wa, $waBody)->onQueue('high');
        }
    }

    private function findTransactionByInvoiceNumber(string $invoiceNumber): ?Transaction
    {
        if ($invoiceNumber === '') {
            return null;
        }

        return Transaction::query()
            ->where('doku_invoice_number', $invoiceNumber)
            ->first()
            ?? Transaction::query()
                ->where('reference_code', $invoiceNumber)
                ->first();
    }

    private function authorizeSessionAccess(Transaction $transaction): void
    {
        $user = Auth::user();

        if ($user && $user->isSuperadmin()) {
            return;
        }

        // For pembahasan transactions by guest users (no user_id), allow access
        if ($transaction->type === Transaction::TYPE_PEMBAHASAN && ! $transaction->user_id) {
            return;
        }

        abort_unless($user && (int) $user->id === (int) $transaction->user_id, 403);
    }

    private function extractInvoiceNumber(array $payload): string
    {
        return trim((string) ($payload['order']['invoice_number']
            ?? $payload['order']['invoiceNumber']
            ?? $payload['order_id']
            ?? ''));
    }

    private function extractAmount(array $payload): string
    {
        return (string) ($payload['order']['amount']
            ?? $payload['gross_amount']
            ?? 0);
    }

    private function extractStatus(array $payload): string
    {
        return strtoupper(trim((string) ($payload['transaction']['status']
            ?? $payload['transaction_status']
            ?? '')));
    }

    private function extractPaymentChannel(array $payload): string
    {
        return trim((string) ($payload['payment']['channel']
            ?? $payload['payment']['method']
            ?? $payload['payment_type']
            ?? ''));
    }
}
