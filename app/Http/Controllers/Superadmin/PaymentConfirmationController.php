<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentConfirmationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $statusFilter = (string) $request->string('status');

        $transactionsQuery = Transaction::query()
            ->with(['user', 'tarifJenjang']);

        if ($statusFilter !== '' && in_array($statusFilter, [Transaction::STATUS_PENDING, Transaction::STATUS_SUCCESS, Transaction::STATUS_FAILED], true)) {
            $transactionsQuery->where('status', $statusFilter);
        }

        if ($search !== '') {
            $transactionsQuery->where(function ($query) use ($search) {
                $query->where('reference_code', 'like', "%{$search}%")
                    ->orWhere('plan_name', 'like', "%{$search}%")
                    ->orWhere('midtrans_order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('no_wa', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $transactionsQuery
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        $summary = [
            'pending' => Transaction::query()->where('status', Transaction::STATUS_PENDING)->count(),
            'success' => Transaction::query()->where('status', Transaction::STATUS_SUCCESS)->count(),
            'failed' => Transaction::query()->where('status', Transaction::STATUS_FAILED)->count(),
        ];

        return view('superadmin.payment-confirmations', compact('transactions', 'summary', 'search', 'statusFilter'));
    }
}
