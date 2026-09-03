<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppBlast;
use App\Models\LandingExamOrder;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MidtransService;
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

class MidtransPaymentController extends Controller
{
    public function start(Request $request, MidtransService $midtrans): JsonResponse
    {
        if (! $midtrans->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Pembayaran otomatis belum diaktifkan admin. Silakan hubungi admin.',
            ], 503);
        }

        $pendingRegistration = $request->session()->get('pending_registration');

        if (! is_array($pendingRegistration) || empty($pendingRegistration['teacher_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Session pendaftaran tidak ditemukan. Silakan ulangi pendaftaran.',
            ], 419);
        }

        $teacher = User::query()->find($pendingRegistration['teacher_id']);
        if (! $teacher) {
            $request->session()->forget('pending_registration');

            return response()->json([
                'ok' => false,
                'message' => 'Data pendaftar tidak ditemukan. Silakan ulangi pendaftaran.',
            ], 404);
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
            $snap = $midtrans->createSnapTransaction($transaction);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        $transaction->refresh();

        return response()->json([
            'ok' => true,
            'snap_token' => $snap['token'],
            'order_id' => $snap['order_id'],
            'reference_code' => $transaction->reference_code,
            'plan_name' => $transaction->plan_name,
            'amount' => 'Rp'.number_format((float) $transaction->amount, 0, ',', '.'),
            'client_key' => $midtrans->clientKey(),
            'is_production' => $midtrans->isProduction(),
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

    public function notification(Request $request, MidtransService $midtrans): JsonResponse
    {
        if (! $midtrans->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Midtrans not configured'], 503);
        }

        $payload = $request->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '') {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 422);
        }

        if (! $midtrans->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans notification rejected: invalid signature', ['order_id' => $orderId]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        if (str_starts_with($orderId, 'PUJ-')) {
            $order = $this->processPublicExamStatusPayload($payload);

            return response()->json(['success' => $order !== null]);
        }

        $transaction = $this->processStatusPayload($payload);

        return response()->json(['success' => $transaction !== null]);
    }

    public function finish(Request $request, MidtransService $midtrans): RedirectResponse|View
    {
        $orderId = trim((string) $request->query('order_id', ''));
        $transaction = $this->findTransactionByOrderId($orderId);

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

        if ($transaction->status === Transaction::STATUS_PENDING && $transaction->payment_method === Transaction::PAYMENT_METHOD_MIDTRANS) {
            $remoteStatus = $midtrans->status($orderId);

            if (is_array($remoteStatus)) {
                $transaction = $this->processStatusPayload($remoteStatus) ?? $transaction->refresh();
            }
        }

        return view('payments.midtrans-success', [
            'transaction' => $transaction,
            'token' => $transaction->status === Transaction::STATUS_SUCCESS
                ? $transaction->user?->access_token
                : null,
        ]);
    }

    public function status(Request $request, MidtransService $midtrans): JsonResponse
    {
        $orderId = trim((string) $request->query('order_id', ''));
        $transaction = $this->findTransactionByOrderId($orderId);

        if (! $transaction) {
            return response()->json(['ok' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $this->authorizeSessionAccess($transaction);

        if ($transaction->status === Transaction::STATUS_PENDING && $transaction->payment_method === Transaction::PAYMENT_METHOD_MIDTRANS) {
            $remoteStatus = $midtrans->status($orderId);

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

    private function processStatusPayload(array $payload): ?Transaction
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $transaction = $this->findTransactionByOrderId($orderId);

        if (! $transaction) {
            Log::warning('Midtrans notification for unknown order', ['order_id' => $orderId]);

            return null;
        }

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return $transaction;
        }

        $grossAmount = (float) ($payload['gross_amount'] ?? 0);
        if (abs($grossAmount - (float) $transaction->amount) > 0.01) {
            Log::critical('Midtrans notification amount mismatch', [
                'reference_code' => $transaction->reference_code,
                'order_id' => $orderId,
                'expected' => $transaction->amount,
                'received' => $payload['gross_amount'] ?? null,
            ]);

            return $transaction;
        }

        $midtransStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? '');

        if ($midtransStatus === 'settlement'
            || ($midtransStatus === 'capture' && $fraudStatus === 'accept')) {
            $this->markSuccess($transaction, $midtransStatus, $paymentType);

            return $transaction->refresh();
        }

        if (in_array($midtransStatus, ['deny', 'cancel', 'expire'], true)) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'midtrans_transaction_status' => $midtransStatus,
                'midtrans_payment_type' => $paymentType,
                'rejection_reason' => 'Pembayaran Midtrans tidak selesai (status: '.$midtransStatus.').',
                'reviewed_at' => now(),
            ]);

            return $transaction->refresh();
        }

        $transaction->update([
            'midtrans_transaction_status' => $midtransStatus,
            'midtrans_payment_type' => $paymentType,
        ]);

        return $transaction->refresh();
    }

    public function processPublicExamStatusPayload(array $payload): ?LandingExamOrder
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $order = app(MidtransService::class)->findOrder($orderId);

        if (! $order) {
            Log::warning('Midtrans notification for unknown public exam order', ['order_id' => $orderId]);

            return null;
        }

        if ($order->isPaid()) {
            return $order;
        }

        $grossAmount = (float) ($payload['gross_amount'] ?? 0);
        if (abs($grossAmount - (float) $order->amount) > 0.01) {
            Log::critical('Midtrans public exam amount mismatch', [
                'order_id' => $orderId,
                'expected' => $order->amount,
                'received' => $payload['gross_amount'] ?? null,
            ]);

            return $order;
        }

        $midtransStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? '');

        if ($midtransStatus === 'settlement'
            || ($midtransStatus === 'capture' && $fraudStatus === 'accept')) {
            $this->markPublicExamPaid($order, $midtransStatus, $paymentType);

            return $order->refresh();
        }

        if (in_array($midtransStatus, ['deny', 'cancel', 'expire'], true)) {
            $order->update([
                'status' => LandingExamOrder::STATUS_FAILED,
                'midtrans_transaction_status' => $midtransStatus,
                'midtrans_payment_type' => $paymentType,
            ]);

            return $order->refresh();
        }

        $order->update([
            'midtrans_transaction_status' => $midtransStatus,
            'midtrans_payment_type' => $paymentType,
        ]);

        return $order->refresh();
    }

    private function markPublicExamPaid(LandingExamOrder $order, string $midtransStatus, string $paymentType): void
    {
        $order->update([
            'status' => LandingExamOrder::STATUS_PAID,
            'midtrans_transaction_status' => $midtransStatus,
            'midtrans_payment_type' => $paymentType,
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

    private function markSuccess(Transaction $transaction, string $midtransStatus, string $paymentType): void
    {
        $transaction->update([
            'payment_method' => Transaction::PAYMENT_METHOD_MIDTRANS,
            'midtrans_transaction_status' => $midtransStatus,
            'midtrans_payment_type' => $paymentType,
            'paid_at' => now(),
            'rejection_reason' => null,
        ]);

        $teacher = $transaction->user;

        if (! $teacher) {
            Log::critical('Midtrans settlement without teacher account', [
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

    private function findTransactionByOrderId(string $orderId): ?Transaction
    {
        if ($orderId === '') {
            return null;
        }

        return Transaction::query()
            ->where('midtrans_order_id', $orderId)
            ->first()
            ?? Transaction::query()
                ->where('reference_code', $orderId)
                ->first();
    }

    private function authorizeSessionAccess(Transaction $transaction): void
    {
        if (Auth::check() && Auth::user()?->isSuperadmin()) {
            return;
        }

        $pendingRegistration = session('pending_registration');
        $sessionTeacherId = is_array($pendingRegistration) ? ($pendingRegistration['teacher_id'] ?? null) : null;

        abort_unless((int) $sessionTeacherId === (int) $transaction->user_id, 403);
    }
}
