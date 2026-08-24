<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentProofController extends Controller
{
    public function show(Request $request): BinaryFileResponse
    {
        $path = trim((string) $request->route('path'));

        abort_if($path === '' || ! str_starts_with($path, 'payment-proofs/'), 404);

        if (str_contains($path, '..') || str_contains($path, '\\') || ! preg_match('/\A[A-Za-z0-9._\/-]+\z/', $path)) {
            abort(404);
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
