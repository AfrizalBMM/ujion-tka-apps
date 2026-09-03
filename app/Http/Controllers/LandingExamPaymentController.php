<?php

namespace App\Http\Controllers;

use App\Models\LandingExamOrder;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LandingExamPaymentController extends Controller
{
    public function start(string $orderToken, MidtransService $midtrans): JsonResponse
    {
        if (! $midtrans->isEnabled()) {
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
                'midtrans_order_id' => null,
                'midtrans_transaction_status' => null,
            ]);
            $order->refresh();
        }

        try {
            $snap = $midtrans->createSnapTransactionForOrder($order);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        $order->refresh();

        return response()->json([
            'ok' => true,
            'snap_token' => $snap['token'],
            'order_id' => $snap['order_id'],
            'amount' => 'Rp'.number_format((float) $order->amount, 0, ',', '.'),
            'client_key' => $midtrans->clientKey(),
            'is_production' => $midtrans->isProduction(),
        ]);
    }

    public function status(MidtransService $midtrans): JsonResponse
    {
        $orderId = trim((string) request()->query('order_id', ''));
        $order = $midtrans->findOrder($orderId);

        if (! $order) {
            return response()->json(['ok' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if ($order->status === LandingExamOrder::STATUS_PENDING_PAYMENT && $order->midtrans_order_id) {
            $remoteStatus = $midtrans->status($orderId);

            if (is_array($remoteStatus)) {
                $order = app(MidtransPaymentController::class)
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

    public function finish(): RedirectResponse
    {
        $orderId = trim((string) request()->query('order_id', ''));
        $midtrans = app(MidtransService::class);
        $order = $midtrans->findOrder($orderId);

        if (! $order) {
            return redirect()->route('landing');
        }

        if ($order->status === LandingExamOrder::STATUS_PENDING_PAYMENT && $order->midtrans_order_id) {
            $remoteStatus = $midtrans->status($orderId);

            if (is_array($remoteStatus)) {
                app(MidtransPaymentController::class)
                    ->processPublicExamStatusPayload($remoteStatus);
                $order = $order->refresh();
            }
        }

        return redirect()->route('ujian-online.pending', $order->session_token);
    }
}
