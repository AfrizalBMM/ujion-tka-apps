<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;

class ChatController extends Controller {
    public function index() {
        $user = Auth::user();
        $chats = Chat::where('user_id', $user->id)->orderBy('created_at')->get();
        return view('guru.chat', compact('chats'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'message' => 'required',
            'image' => 'nullable|image|max:2048',
        ]);
        $data['user_id'] = Auth::id();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('chat-images', 'public');
        }
        Chat::create($data);
        return back();
    }
}