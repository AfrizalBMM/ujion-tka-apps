<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $auditLogs = [];
        if (Schema::hasTable('audit_logs')) {
            $auditLogs = AuditLog::query()
                ->orderByDesc('id')
                ->limit(200)
                ->get();
        }

        return view('superadmin.audit-logs', [
            'auditLogs' => $auditLogs,
        ]);
    }
}
