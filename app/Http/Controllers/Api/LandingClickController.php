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
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }
}
