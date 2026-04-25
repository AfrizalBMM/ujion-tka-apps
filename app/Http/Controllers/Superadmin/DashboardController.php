<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LandingClickLog;
use App\Models\PricingPlan;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('superadmin.dashboard', $this->buildMetrics());
    }

    public function exportCsv(): StreamedResponse
    {
        $metrics = $this->buildMetrics();

        $callback = function () use ($metrics): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['metric', 'value']);
            fputcsv($out, ['active_teachers', $metrics['activeTeachersCount']]);
            fputcsv($out, ['ongoing_exams', $metrics['ongoingExamsCount']]);
            fputcsv($out, ['total_revenue', $metrics['totalRevenue']]);
            fputcsv($out, ['top_teacher', $metrics['topTeacherName'] ?? '-']);
            fputcsv($out, []);
            fputcsv($out, ['date', 'activity_count']);

            foreach (($metrics['dailyActivity']['labels'] ?? []) as $index => $label) {
                fputcsv($out, [$label, $metrics['dailyActivity']['values'][$index] ?? 0]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, 'dashboard-superadmin.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(): View
    {
        return view('superadmin.exports.dashboard-print', $this->buildMetrics());
    }

    private function normalizeCurrency(?string $value): int
    {
        if (! $value) {
            return 0;
        }

        return (int) preg_replace('/\D+/', '', $value);
    }

    private function buildMetrics(): array
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

        $landingTraffic = [
            'labels' => [],
            'views' => [],
            'clicks' => [],
        ];

        if (Schema::hasTable('landing_click_logs')) {
            $labels = collect(range(13, 0))
                ->map(fn (int $offset) => now()->subDays($offset)->toDateString());

            $from = now()->subDays(14);

            $viewsRows = LandingClickLog::query()
                ->where('created_at', '>=', $from)
                ->where('event', 'landing_view')
                ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
                ->groupBy('d')
                ->orderBy('d')
                ->get()
                ->pluck('total', 'd');

            $clickRows = LandingClickLog::query()
                ->where('created_at', '>=', $from)
                ->where('event', '!=', 'landing_view')
                ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
                ->groupBy('d')
                ->orderBy('d')
                ->get()
                ->pluck('total', 'd');

            $landingTraffic['labels'] = $labels->values()->all();
            $landingTraffic['views'] = $labels->map(fn (string $d) => (int) ($viewsRows[$d] ?? 0))->values()->all();
            $landingTraffic['clicks'] = $labels->map(fn (string $d) => (int) ($clickRows[$d] ?? 0))->values()->all();
        }

        $latestAuditLogs = AuditLog::latest()->limit(5)->get();
        $ongoingExamsCount = UjianSesi::query()
            ->where('status', 'mengerjakan')
            ->count();

        $activePlans = PricingPlan::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('jenjang');
        $fallbackPlan = PricingPlan::query()
            ->where('is_active', true)
            ->first();

        $activeTeachersByJenjang = User::query()
            ->where('role', User::ROLE_GURU)
            ->where('account_status', User::STATUS_ACTIVE)
            ->selectRaw('jenjang, COUNT(*) as total')
            ->groupBy('jenjang')
            ->pluck('total', 'jenjang');

        $totalRevenue = 0;
        foreach ($activeTeachersByJenjang as $jenjang => $count) {
            $totalRevenue += (int) $count * $this->normalizeCurrency(($activePlans->get($jenjang) ?? $fallbackPlan)?->price);
        }

        $topTeacher = User::query()
            ->where('role', User::ROLE_GURU)
            ->withCount(['personalQuestions as contributed_questions_count' => function ($query) {
                $query->where('status', 'terbit');
            }])
            ->orderByDesc('contributed_questions_count')
            ->orderBy('name')
            ->first();

        return [
            'dailyActivity' => $dailyActivity,
            'landingTraffic' => $landingTraffic,
            'activeTeachersCount' => $activeTeachersCount,
            'ongoingExamsCount' => $ongoingExamsCount,
            'totalRevenue' => $totalRevenue,
            'topTeacherName' => $topTeacher?->name,
            'latestAuditLogs' => $latestAuditLogs,
        ];
    }
}
