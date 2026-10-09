<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(): Response
    {
        $plans = PricingPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (PricingPlan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'subtitle' => $plan->subtitle,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'original_price' => $plan->original_price,
                    'period' => $plan->period,
                    'promo_active' => $plan->promo_active,
                    'jenjang' => $plan->jenjang,
                ];
            });

        $trialDefaultDays = (int) AppSetting::getValue('trial_default_days', '7');

        return Inertia::render('Pricing/Index', [
            'plans' => $plans,
            'trialDays' => $trialDefaultDays,
        ]);
    }
}
