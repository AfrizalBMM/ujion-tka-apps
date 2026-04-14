<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function index(): View
    {
        $pricingPlans = config('landing.pricing', []);

        if (Schema::hasTable('pricing_plans')) {
            $hasPromoActive = Schema::hasColumn('pricing_plans', 'promo_active');

            $pricingPlans = \App\Models\PricingPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(3)
                ->get()
                ->map(fn ($plan) => [
                    'name' => $plan->name,
                    'subtitle' => $plan->subtitle,
                    'price' => $plan->price,
                    'original_price' => ($hasPromoActive && ! $plan->promo_active) ? null : $plan->original_price,
                    'period' => $plan->period,
                ])
                ->all();
        }

        return view('landing', [
            'pricingPlans' => $pricingPlans,
        ]);
    }
}
