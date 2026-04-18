<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChatController extends Controller {
    public function index(): View {
        $user = Auth::user();

        $chats = Chat::with(['fromUser', 'toUser'])
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->orderBy('created_at')
            ->get();

        return view('guru.chat', compact('chats'));
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'message' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
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
            $data['image_path'] = $request->file('image')->store('chat-images', 'public');
        }

        unset($data['image']);

        Chat::create($data);

        return back();
    }
}
