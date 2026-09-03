<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function index(Request $request): View
    {
        $auditLogs = collect();
        $summary = [
            'total' => 0,
            'today' => 0,
            'top_user' => null,
            'top_action' => null,
        ];
        $users = collect();

        if (Schema::hasTable('audit_logs')) {
            $query = AuditLog::query()->with('user');

            $search = trim((string) $request->string('q'));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('path', 'like', "%{$search}%")
                        ->orWhere('route_name', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            }

            $method = strtoupper(trim((string) $request->string('method')));
            if (in_array($method, self::METHODS, true)) {
                $query->where('method', $method);
            }

            $userId = $request->integer('user_id');
            if ($userId > 0) {
                $query->where('user_id', $userId);
            }

            $from = trim((string) $request->string('from'));
            if ($from !== '' && strtotime($from) !== false) {
                $query->where('created_at', '>=', $from.' 00:00:00');
            }

            $to = trim((string) $request->string('to'));
            if ($to !== '' && strtotime($to) !== false) {
                $query->where('created_at', '<=', $to.' 23:59:59');
            }

            $auditLogs = $query->orderByDesc('id')->paginate(50)->withQueryString();

            $summary['total'] = AuditLog::count();
            $summary['today'] = AuditLog::where('created_at', '>=', now()->startOfDay())->count();

            $topUserRow = AuditLog::query()
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->first();
            $summary['top_user'] = $topUserRow
                ? ['name' => User::find($topUserRow->user_id)?->name ?? 'ID '.$topUserRow->user_id, 'total' => (int) $topUserRow->total]
                : null;

            $topActionRow = AuditLog::query()
                ->whereNotNull('route_name')
                ->where('route_name', '!=', '')
                ->selectRaw('route_name, COUNT(*) as total')
                ->groupBy('route_name')
                ->orderByDesc('total')
                ->first();
            $summary['top_action'] = $topActionRow
                ? ['name' => $topActionRow->route_name, 'total' => (int) $topActionRow->total]
                : null;

            $users = User::query()
                ->whereHas('auditLogs')
                ->orderBy('name')
                ->get(['id', 'name', 'role']);
        }

        return view('superadmin.audit-logs', [
            'auditLogs' => $auditLogs,
            'summary' => $summary,
            'users' => $users,
            'filters' => [
                'q' => trim((string) $request->string('q')),
                'method' => strtoupper(trim((string) $request->string('method'))),
                'user_id' => $request->integer('user_id'),
                'from' => trim((string) $request->string('from')),
                'to' => trim((string) $request->string('to')),
            ],
        ]);
    }

    public function cleanup(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('audit_logs')) {
            return back();
        }

        $validated = $request->validate([
            'mode' => ['required', 'in:older_than_30d,all'],
        ]);

        if ($validated['mode'] === 'all') {
            $deleted = AuditLog::query()->delete();
        } else {
            $deleted = AuditLog::query()->where('created_at', '<', now()->subDays(30))->delete();
        }

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Audit log dibersihkan',
            'message' => number_format((int) $deleted).' entri log berhasil dihapus.',
        ]);
    }
}
