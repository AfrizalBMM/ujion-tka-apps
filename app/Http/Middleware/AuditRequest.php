<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AuditRequest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Schema::hasTable('audit_logs')) {
            return $response;
        }

        if ($request->isMethod('get') && $request->is('up')) {
            return $response;
        }

        try {
            AuditLog::create([
                'user_id' => $request->user()?->id,
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'route_name' => optional($request->route())->getName(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Intentionally swallow audit failures.
        }

        return $response;
    }
}
