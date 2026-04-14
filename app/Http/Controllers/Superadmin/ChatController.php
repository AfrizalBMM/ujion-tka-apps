<?php
namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller {
    public function index() {
        $chats = Chat::with(['fromUser', 'toUser'])->orderBy('created_at')->get();
        $users = User::where('role', User::ROLE_GURU)->get();
        return view('superadmin.chat', compact('chats', 'users'));
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