<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\AppSetting;
use App\Services\QrisService;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentController extends Controller
{
    public function show(string $referenceCode, QrisService $qrisService): View
    {
        $transaction = Transaction::query()
            ->with(['user', 'tarifJenjang'])
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        $this->authorizeSessionAccess($transaction);

        $amount = (int) round((float) $transaction->amount);
        $payload = $qrisService->generateFixedAmountPayload($amount);
        $formattedAmount = Number::currency($amount, 'IDR', 'id');
        $adminNumber = PhoneNumber::normalizeIndonesian(
            (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))
        );

        $message = rawurlencode(sprintf(
            'Halo Admin Ujion, saya sudah bayar Paket %s senilai %s. Kode referensi: %s. Bukti bayar akan saya upload di halaman aktivasi.',
            $transaction->plan_name,
            $formattedAmount,
            $transaction->reference_code,
        ));

        $waUrl = $adminNumber !== ''
            ? "https://wa.me/{$adminNumber}?text={$message}"
            : null;

        return view('payments.show', [
            'transaction' => $transaction,
            'formattedAmount' => $formattedAmount,
            'qrCodeSvg' => QrCode::format('svg')->size(320)->margin(1)->generate($payload),
            'waUrl' => $waUrl,
        ]);
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
