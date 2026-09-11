<?php

namespace App\Http\Controllers;

use App\Models\LandingExamOrder;
use App\Services\DokuService;
use Illuminate\Http\JsonResponse;

class LandingExamPaymentController extends Controller
{
    public function start(string $orderToken, DokuService $doku): JsonResponse
    {
        if (! $doku->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Pembayaran otomatis belum diaktifkan admin. Silakan hubungi admin.',
            ], 503);
        }

        $order = LandingExamOrder::where('session_token', $orderToken)->first();

        if (! $order) {
            return response()->json(['ok' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if ($order->isPaid()) {
            return response()->json([
                'ok' => false,
                'message' => 'Pembayaran Anda sudah sukses sebelumnya.',
                'status' => 'paid',
            ], 409);
        }

        if ($order->status === LandingExamOrder::STATUS_FAILED) {
            $order->update([
                'status' => LandingExamOrder::STATUS_PENDING_PAYMENT,
                'doku_invoice_number' => null,
                'doku_transaction_status' => null,
            ]);
            $order->refresh();
        }

        try {
            $checkout = $doku->createCheckoutPaymentForOrder($order);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        $order->refresh();

        return response()->json([
            'ok' => true,
            'payment_url' => $checkout['payment_url'],
            'order_id' => $checkout['invoice_number'],
            'amount' => 'Rp'.number_format((float) $order->amount, 0, ',', '.'),
        ]);
    }

    public function status(DokuService $doku): JsonResponse
    {
        $invoiceNumber = trim((string) request()->query('order_id', ''));
        $order = $doku->findOrder($invoiceNumber);

        if (! $order) {
            return response()->json(['ok' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if ($order->status === LandingExamOrder::STATUS_PENDING_PAYMENT && $order->doku_invoice_number) {
            $remoteStatus = $doku->checkStatus($invoiceNumber);

            if (is_array($remoteStatus)) {
                $order = app(DokuPaymentController::class)
                    ->processPublicExamStatusPayload($remoteStatus) ?? $order->refresh();
            }
        }

        return response()->json([
            'ok' => true,
            'status' => $order->status,
            'amount' => 'Rp'.number_format((float) $order->amount, 0, ',', '.'),
            'start_url' => $order->isPaid() ? route('ujian-online.start', $order->session_token) : null,
        ]);
    }

    public function finish()
    {
        $invoiceNumber = trim((string) request()->query('order_id', ''));
        $doku = app(DokuService::class);
        $order = $doku->findOrder($invoiceNumber);

        if (! $order) {
            return redirect()->route('landing');
        }

        if ($order->status === LandingExamOrder::STATUS_PENDING_PAYMENT && $order->doku_invoice_number) {
            $remoteStatus = $doku->checkStatus($invoiceNumber);

            if (is_array($remoteStatus)) {
                app(DokuPaymentController::class)
                    ->processPublicExamStatusPayload($remoteStatus);
                $order = $order->refresh();
            }
        }

        return view('payments.doku-popup-close', [
            'message' => [
                'type' => 'doku-payment-finished',
                'order_id' => $invoiceNumber,
            ],
            'fallbackUrl' => route('ujian-online.pending', $order->session_token),
        ]);
    }
}
