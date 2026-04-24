<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChatImageController extends Controller
{
    public function show(Chat $chat): BinaryFileResponse
    {
        // Only allow participants to view the attachment.
        $userId = auth()->id();
        if (! $userId || ($userId !== (int) $chat->from_user_id && $userId !== (int) $chat->to_user_id)) {
            abort(403);
        }

        if (! $chat->image_path) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($chat->image_path)) {
            abort(404);
        }

        $path = $disk->path($chat->image_path);
        $mime = $disk->mimeType($chat->image_path) ?? 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($chat->image_path) . '"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
