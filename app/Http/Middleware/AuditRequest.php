<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditRequest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('ujion.audit_enabled', true)) {
            return $response;
        }

        if ($request->isMethod('get') && $request->is('up')) {
            return $response;
        }

        try {
            AuditLog::create([
                'user_id' => $request->user()?->id,
                'method' => $request->getMethod(),
                'path' => $this->sanitizePath($request->path()),
                'route_name' => optional($request->route())->getName(),
                'ip_address' => $this->maskIp($request->ip()),
                'user_agent' => $this->sanitizeUserAgent((string) $request->userAgent()),
            ]);
        } catch (\Throwable $e) {
            // Intentionally swallow audit failures.
        }

        return $response;
    }

    private function sanitizePath(?string $path): string
    {
        $segments = collect(explode('/', trim((string) $path, '/')))
            ->filter()
            ->map(function (string $segment) {
                if (preg_match('/^\d+$/', $segment)) {
                    return '{id}';
                }

                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $segment)) {
                    return '{uuid}';
                }

                if (preg_match('/^[A-Za-z0-9_-]{16,}$/', $segment)) {
                    return '{token}';
                }

                return $segment;
            })
            ->values();

        return $segments->isEmpty() ? '/' : $segments->implode('/');
    }

    private function maskIp(?string $ip): string
    {
        if (blank($ip)) {
            return '-';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = 'x';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $visible = array_slice($parts, 0, 4);

            return implode(':', $visible).':xxxx:xxxx:xxxx:xxxx';
        }

        return '-';
    }

    private function sanitizeUserAgent(string $userAgent): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $userAgent) ?? '');

        if ($normalized === '') {
            return '-';
        }

        return sprintf(
            '%s [sha1:%s]',
            $this->resolveUserAgentFamily($normalized),
            Str::upper(substr(sha1($normalized), 0, 10))
        );
    }

    private function resolveUserAgentFamily(string $userAgent): string
    {
        $families = [
            'Edg/' => 'Edge',
            'Chrome/' => 'Chrome',
            'Firefox/' => 'Firefox',
            'Safari/' => 'Safari',
            'OPR/' => 'Opera',
            'PostmanRuntime/' => 'Postman',
            'Laravel' => 'Laravel HTTP Client',
        ];

        foreach ($families as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Other';
    }
}
