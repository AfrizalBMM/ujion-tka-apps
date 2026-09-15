<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LandingClickLog;
use App\Models\Transaction;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(): InertiaResponse
    {
        $metrics = $this->buildMetrics();

        $metrics['latestAuditLogs'] = collect($metrics['latestAuditLogs'])->map(fn (AuditLog $log) => [
            'method' => $log->method,
            'path' => $log->path,
            'ip_address' => $log->ip_address,
            'created_at_human' => $log->created_at?->diffForHumans(),
        ])->all();

        return Inertia::render('Superadmin/Dashboard', $metrics);
    }

    public function exportCsv(): StreamedResponse
    {
        $metrics = $this->buildMetrics();

        $callback = function () use ($metrics): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['metric', 'value']);
            fputcsv($out, ['active_teachers', $metrics['activeTeachersCount']]);
            fputcsv($out, ['pending_registrations', $metrics['pendingRegistrationCount']]);
            fputcsv($out, ['ongoing_exams', $metrics['ongoingExamsCount']]);
            fputcsv($out, ['total_revenue', $metrics['totalRevenue']]);
            fputcsv($out, ['revenue_doku', $metrics['revenueBreakdown']['doku'] ?? 0]);
            fputcsv($out, ['revenue_manual', $metrics['revenueBreakdown']['manual'] ?? 0]);
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

    private function buildMetrics(): array
    {
        $activeTeachersCount = User::where('role', User::ROLE_GURU)->where('account_status', User::STATUS_ACTIVE)->count();

        $pendingRegistrationCount = User::query()
            ->where('role', User::ROLE_GURU)
            ->where('account_status', User::STATUS_PENDING)
            ->where('payment_status', User::PAYMENT_AWAITING)
            ->count();

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

        $totalRevenue = (int) Transaction::query()
            ->where('status', Transaction::STATUS_SUCCESS)
            ->sum('amount');

        $revenueByMethod = Transaction::query()
            ->where('status', Transaction::STATUS_SUCCESS)
            ->selectRaw('payment_method, COALESCE(SUM(amount), 0) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $revenueBreakdown = [
            'doku' => (int) ($revenueByMethod[Transaction::PAYMENT_METHOD_DOKU] ?? 0),
            'manual' => (int) ($revenueByMethod['manual_qris'] ?? 0),
        ];

        $pendingPaymentCount = Transaction::where('status', Transaction::STATUS_PENDING)
            ->count();

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
            'pendingRegistrationCount' => $pendingRegistrationCount,
            'ongoingExamsCount' => $ongoingExamsCount,
            'totalRevenue' => $totalRevenue,
            'revenueBreakdown' => $revenueBreakdown,
            'topTeacherName' => $topTeacher?->name,
            'latestAuditLogs' => $latestAuditLogs,
            'pendingPaymentCount' => $pendingPaymentCount,
        ];
    }
}
