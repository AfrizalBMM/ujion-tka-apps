<?php
namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller {
    public function index(Request $request): View {
        $users = User::query()
            ->where('role', User::ROLE_GURU)
            ->withCount([
                'sentChats as unread_messages_count' => function ($query) {
                    $query->where('to_user_id', auth()->id())
                        ->where('is_read', false);
                },
            ])
            ->withMax('sentChats', 'created_at')
            ->withMax('receivedChats', 'created_at')
            ->get()
            ->sortByDesc(function (User $user) {
                return max(
                    strtotime((string) $user->sent_chats_max_created_at),
                    strtotime((string) $user->received_chats_max_created_at)
                );
            })
            ->values();

        $selectedUser = $users->firstWhere('id', (int) $request->query('user')) ?? $users->first();
        $chatPaginator = null;
        $chats = collect();

        if ($selectedUser) {
            $chatPaginator = Chat::query()
                ->with(['fromUser', 'toUser'])
                ->where(function ($query) use ($selectedUser) {
                    $query->where('from_user_id', auth()->id())
                        ->where('to_user_id', $selectedUser->id);
                })
                ->orWhere(function ($query) use ($selectedUser) {
                    $query->where('from_user_id', $selectedUser->id)
                        ->where('to_user_id', auth()->id());
                })
                ->latest()
                ->paginate(25)
                ->withQueryString();

            $chatPaginator->setCollection($chatPaginator->getCollection()->sortBy('created_at')->values());
            $chats = $chatPaginator->getCollection();

            Chat::query()
                ->where('from_user_id', $selectedUser->id)
                ->where('to_user_id', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return view('superadmin.chat', compact('chats', 'users', 'selectedUser', 'chatPaginator'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);
        $data['from_user_id'] = auth()->id();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('chat-images', 'public');
        }
        Chat::create($data);
        return back()->with('flash', ['type' => 'success', 'message' => 'Pesan terkirim.']);
    }
    public function destroy(Chat $chat) {
        if ($chat->image_path) Storage::disk('public')->delete($chat->image_path);
        $chat->delete();
        return back()->with('flash', ['type' => 'success', 'message' => 'Pesan dihapus.']);
    }
    public function markRead(Chat $chat) {
        $chat->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
