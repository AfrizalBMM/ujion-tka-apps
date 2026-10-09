<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

class PricingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Bulanan',
                'subtitle' => 'Paket Berlangganan Bulanan',
                'price' => '50000',
                'original_price' => '75000',
                'period' => 'monthly',
                'promo_active' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '6 Bulan',
                'subtitle' => 'Hemat 33% — Paket Semester',
                'price' => '200000',
                'original_price' => '300000',
                'period' => 'semiannual',
                'promo_active' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Tahunan',
                'subtitle' => 'Hemat 44% — Paket Tahunan',
                'price' => '400000',
                'original_price' => '600000',
                'period' => 'yearly',
                'promo_active' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
