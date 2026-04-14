<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentQr;
use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    public function index(): View
    {
        $pricingPlans = [];
        if (Schema::hasTable('pricing_plans')) {
            $pricingPlans = PricingPlan::query()
                ->orderBy('sort_order')
                ->get();
        }

        $paymentQrs = [];
        if (Schema::hasTable('payment_qrs')) {
            $paymentQrs = PaymentQr::query()
                ->orderBy('sort_order')
                ->get();
        }

        return view('superadmin.finance', compact('pricingPlans', 'paymentQrs'));
    }
}
