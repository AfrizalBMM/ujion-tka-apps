<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * GET /api/chat/threads
     *
     * Returns all chat threads for the authenticated user.
     * - Guru: thread with superadmin
     * - Superadmin: threads with all guru
     */
    public function threads(): JsonResponse
    {
        $user = Auth::user();

        if ($user->isSuperadmin()) {
            $threads = User::query()
                ->where('role', User::ROLE_GURU)
                ->withCount([
                    'sentChats as unread_count' => function ($query) use ($user) {
                        $query->where('to_user_id', $user->id)
                            ->where('is_read', false);
                    },
                ])
                ->withMax('sentChats as last_message_at', 'created_at')
                ->get()
                ->sortByDesc('last_message_at')
                ->values()
                ->map(fn (User $u) => [
                    'thread_id' => 'guru-'.$u->id,
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'avatar_url' => $u->avatar_url,
                    'jenjang' => $u->jenjang,
                    'unread_count' => $u->unread_count ?? 0,
                    'last_message_at' => $u->last_message_at,
                ]);
        } elseif ($user->isGuru()) {
            $superadmin = User::query()
                ->where('role', User::ROLE_SUPERADMIN)
                ->first();

            $unreadCount = Chat::query()
                ->where('from_user_id', $superadmin?->id)
                ->where('to_user_id', $user->id)
                ->where('is_read', false)
                ->count();

            $lastMessage = Chat::query()
                ->where(function ($q) use ($user, $superadmin) {
                    $q->where('from_user_id', $user->id)
                        ->where('to_user_id', $superadmin?->id);
                })
                ->orWhere(function ($q) use ($user, $superadmin) {
                    $q->where('from_user_id', $superadmin?->id)
                        ->where('to_user_id', $user->id);
                })
                ->latest()
                ->first();

            $threads = collect([
                [
                    'thread_id' => 'superadmin',
                    'user_id' => $superadmin?->id,
                    'name' => $superadmin?->name ?? 'Superadmin',
                    'avatar_url' => $superadmin?->avatar_url ?? '',
                    'jenjang' => null,
                    'unread_count' => $unreadCount,
                    'last_message_at' => $lastMessage?->created_at,
                ],
            ]);
        } else {
            $threads = collect();
        }

        return response()->json(['threads' => $threads]);
    }

    /**
     * POST /api/chat/send
     *
     * Input: to_user_id, message (optional), image (optional)
     */
    public function send(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $recipient = User::findOrFail($validated['to_user_id']);

        // Enforce guru-admin only: guru can only send to superadmin, superadmin can only send to guru
        if ($user->isGuru() && ! $recipient->isSuperadmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat hanya tersedia antara guru dan admin.',
            ], 403);
        }

        if ($user->isSuperadmin() && ! $recipient->isGuru()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat hanya tersedia dengan akun guru.',
            ], 403);
        }

        if (! $user->isGuru() && ! $user->isSuperadmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses chat tidak diizinkan untuk peran ini.',
            ], 403);
        }

        if (! $request->filled('message') && ! $request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Isi pesan atau unggah gambar.',
            ], 422);
        }

        $chat = Chat::create([
            'from_user_id' => $user->id,
            'to_user_id' => $recipient->id,
            'conversation_type' => Chat::CONVERSATION_GURU_ADMIN,
            'message' => $validated['message'],
            'image_path' => $request->hasFile('image')
                ? $request->file('image')->store('chat-images', 'local')
                : null,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $chat->id,
                'from_user_id' => $chat->from_user_id,
                'to_user_id' => $chat->to_user_id,
                'message' => $chat->message,
                'image_url' => $chat->image_path,
                'is_read' => $chat->is_read,
                'created_at' => $chat->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * GET /api/chat/messages/{thread}
     *
     * thread = user_id (for superadmin viewing guru) or 'superadmin' (for guru)
     */
    public function messages(Request $request, string $thread): JsonResponse
    {
        $user = Auth::user();

        $partnerId = null;
        if ($thread === 'superadmin') {
            $partnerId = User::where('role', User::ROLE_SUPERADMIN)->value('id');
        } else {
            $partnerId = (int) $thread;
        }

        if (! $partnerId) {
            return response()->json(['messages' => []]);
        }

        $partner = User::find($partnerId);
        if (! $partner) {
            return response()->json(['messages' => []]);
        }

        // Enforce guru-admin only
        if ($user->isGuru() && ! $partner->isSuperadmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat hanya tersedia antara guru dan admin.',
            ], 403);
        }

        if ($user->isSuperadmin() && ! $partner->isGuru()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat hanya tersedia dengan akun guru.',
            ], 403);
        }

        $messages = Chat::query()
            ->with(['fromUser', 'toUser'])
            ->where(function ($q) use ($user, $partner) {
                $q->where('from_user_id', $user->id)
                    ->where('to_user_id', $partner->id);
            })
            ->orWhere(function ($q) use ($user, $partner) {
                $q->where('from_user_id', $partner->id)
                    ->where('to_user_id', $user->id);
            })
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn (Chat $chat) => [
                'id' => $chat->id,
                'is_own' => (int) $chat->from_user_id === (int) $user->id,
                'message' => $chat->message,
                'image_url' => $chat->image_path,
                'is_read' => $chat->is_read,
                'created_at' => $chat->created_at?->format('d M H:i'),
                'sender_name' => $chat->fromUser?->name,
                'sender_avatar_url' => $chat->fromUser?->avatar_url,
            ]);

        // Mark as read
        Chat::query()
            ->where('from_user_id', $partner->id)
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'thread' => $thread,
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'avatar_url' => $partner->avatar_url,
            ],
            'messages' => $messages,
        ]);
    }
}
