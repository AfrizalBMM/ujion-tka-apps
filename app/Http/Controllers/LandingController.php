<?php

namespace App\Http\Controllers;

use App\Models\GlobalQuestion;
use App\Models\Material;
use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function index(): View
    {
        $pricingPlans = config('landing.pricing', []);

        if (Schema::hasTable('pricing_plans')) {
            $hasPromoActive = Schema::hasColumn('pricing_plans', 'promo_active');

            $pricingPlans = PricingPlan::query()
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

        // Fetch counts for materials and questions
        $materialStats = Material::query()
            ->select('jenjang', 'mapel', DB::raw('count(*) as count'))
            ->groupBy('jenjang', 'mapel')
            ->get();

        $questionStats = GlobalQuestion::query()
            ->join('jenjangs', 'global_questions.jenjang_id', '=', 'jenjangs.id')
            ->select('jenjangs.kode as jenjang', 'material_mapel as mapel', DB::raw('count(*) as count'))
            ->groupBy('jenjangs.kode', 'material_mapel')
            ->get();

        $stats = [];

        foreach ($materialStats as $m) {
            $jenjang = $m->jenjang;
            $mapel = $m->mapel ?: 'Umum';
            $stats[$jenjang][$mapel]['materials'] = $m->count;
        }

        foreach ($questionStats as $q) {
            $jenjang = $q->jenjang;
            $mapel = $q->mapel ?: 'Umum';
            $stats[$jenjang][$mapel]['questions'] = $q->count;
        }

        return view('landing', [
            'pricingPlans' => $pricingPlans,
            'stats' => $stats,
        ]);
    }
}
