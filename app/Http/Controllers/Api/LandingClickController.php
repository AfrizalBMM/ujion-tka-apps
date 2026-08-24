<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingClickLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingClickController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:64'],
            'href' => ['nullable', 'string', 'max:2048'],
            'path' => ['nullable', 'string', 'max:1024'],
            'referrer' => ['nullable', 'string', 'max:2048'],
        ]);

        LandingClickLog::create([
            'user_id' => $request->user()?->id,
            'event' => $validated['event'],
            'href' => $validated['href'] ?? null,
            'path' => $validated['path'] ?? $request->path(),
            'referrer' => $validated['referrer'] ?? $request->headers->get('referer'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $this->maskIp($request->ip()),
        ]);

        return response()->json(['ok' => true]);
    }

    private function maskIp(?string $ip): ?string
    {
        if (blank($ip)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = 'x';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);

            return implode(':', array_slice($parts, 0, 4)).':xxxx:xxxx:xxxx:xxxx';
        }

        return null;
    }
}
