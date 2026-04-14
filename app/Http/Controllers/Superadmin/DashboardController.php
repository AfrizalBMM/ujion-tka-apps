<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GlobalQuestion;
use App\Models\Material;
use App\Models\PaymentQr;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeTeachersCount = User::where('role', User::ROLE_GURU)->where('account_status', User::STATUS_ACTIVE)->count();
        
        $dailyActivity = [
            'labels' => [],
            'values' => [],
        ];

        if (Schema::hasTable('audit_logs')) {
            $activityRows = AuditLog::query()
                ->where('created_at', '>=', now()->subDays(14))
                ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
                ->groupBy('d')
                ->orderBy('d')
                ->get();

            $dailyActivity['labels'] = $activityRows->pluck('d')->map(fn ($d) => (string) $d)->all();
            $dailyActivity['values'] = $activityRows->pluck('total')->map(fn ($t) => (int) $t)->all();
        }

        $latestAuditLogs = AuditLog::latest()->limit(5)->get();

        return view('superadmin.dashboard', [
            'dailyActivity' => $dailyActivity,
            'activeTeachersCount' => $activeTeachersCount,
            'ongoingExamsCount' => 0, // Placeholder
            'totalRevenue' => '0', // Placeholder
            'topTeacherName' => User::where('role', User::ROLE_GURU)->first()?->name,
            'latestAuditLogs' => $latestAuditLogs,
        ]);
    }
}
