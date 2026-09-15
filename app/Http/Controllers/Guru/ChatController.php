<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $superadmin = User::query()
            ->where('role', User::ROLE_SUPERADMIN)
            ->first();

        $chatPartnerName = $superadmin?->name ?? 'Superadmin';
        $chatPartnerAvatarUrl = $superadmin?->avatar_url
            ?? 'https://ui-avatars.com/api/?name=Superadmin&background=4F6EF7&color=fff';

        $chats = Chat::with(['fromUser', 'toUser'])
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->latest()
            ->limit(200)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(function (Chat $chat) use ($user, $chatPartnerName, $chatPartnerAvatarUrl) {
                $isOwn = (int) $chat->from_user_id === (int) $user->id;
                $sender = $chat->fromUser;

                return [
                    'id' => $chat->id,
                    'is_own' => $isOwn,
                    'message' => $chat->message,
                    'image_url' => $chat->image_path ? route('guru.chat.image', $chat) : null,
                    'created_at' => $chat->created_at?->format('d M H:i'),
                    'sender_name' => $sender?->name ?? ($isOwn ? ($user->name ?? 'Anda') : $chatPartnerName),
                    'sender_avatar_url' => $sender?->avatar_url
                        ?? ($isOwn
                            ? ($user->avatar_url ?? 'https://ui-avatars.com/api/?name=Guru&background=22C1C3&color=fff')
                            : $chatPartnerAvatarUrl),
                ];
            });

        return Inertia::render('Guru/Chat', [
            'chats' => $chats,
            'chatPartnerName' => $chatPartnerName,
            'chatPartnerAvatarUrl' => $chatPartnerAvatarUrl,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => 'nullable|string|max:2000',
            'image' => 'nullable|image|max:2048',
        ], [
            'image.image' => 'File lampiran harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        if (! $request->filled('message') && ! $request->hasFile('image')) {
            return back()->withErrors(['message' => 'Isi pesan atau unggah gambar terlebih dahulu.']);
        }

        $superadminId = User::where('role', User::ROLE_SUPERADMIN)->value('id');
        if (! $superadminId) {
            return back()->withErrors(['message' => 'Superadmin belum tersedia untuk menerima chat.']);
        }

        $data['from_user_id'] = Auth::id();
        $data['to_user_id'] = $superadminId;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('chat-images', 'local');
        }

        unset($data['image']);

        Chat::create($data);

        return back();
    }
}
