@extends('layouts.superadmin')
@section('title', 'Live Chat')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Live Chat Guru/Operator</h1>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="md:col-span-1">
            <div class="card p-4 mb-4">
                <div class="mb-3 text-sm font-bold">Percakapan</div>
                <div class="space-y-2">
                    @forelse($users as $user)
                        <a href="{{ route('superadmin.chat.index', ['user' => $user->id]) }}"
                           class="block rounded-xl border p-3 transition {{ optional($selectedUser)->id === $user->id ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-blue-300' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                                @if($user->unread_messages_count)
                                    <span class="rounded-full bg-red-500 px-2 py-1 text-[10px] font-bold text-white">{{ $user->unread_messages_count }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="text-sm text-gray-500">Belum ada guru yang bisa dipilih.</div>
                    @endforelse
                </div>
            </div>
            <div class="card p-4">
                <form method="POST" action="{{ route('superadmin.chat.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label class="block text-xs font-bold mb-1">Kirim ke Guru</label>
                        <select name="to_user_id" class="input w-full" required>
                            <option value="">Pilih Guru</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(optional($selectedUser)->id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="message" class="input w-full" rows="2" placeholder="Tulis pesan..."></textarea>
                    </div>
                    <div class="mb-2">
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <button class="btn-primary w-full" type="submit">Kirim</button>
                </form>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="card h-[420px] overflow-y-auto p-4 sm:h-96">
                @if($selectedUser)
                    @forelse($chats as $chat)
                        <div class="mb-4 flex {{ $chat->from_user_id == auth()->id() ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] sm:max-w-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs">{{ $chat->fromUser->name }}</span>
                                    @if($chat->is_read)
                                        <span class="text-green-500 text-xs"><i class="fa-solid fa-check-double"></i> Dibaca</span>
                                    @else
                                        <span class="text-gray-400 text-xs"><i class="fa-solid fa-check"></i> Terkirim</span>
                                    @endif
                                </div>
                                @if($chat->message)
                                    <div class="bg-blue-100 rounded p-2 mt-1">{{ $chat->message }}</div>
                                @endif
                                @if($chat->image_path)
                                    <img src="{{ Storage::url($chat->image_path) }}" class="mt-2 rounded max-w-full h-32 object-cover">
                                @endif
                                <div class="text-xs text-gray-400 mt-1">{{ $chat->created_at->format('d M H:i') }}</div>
                                <form method="POST" action="{{ route('superadmin.chat.destroy', $chat) }}" class="inline">
                                    @csrf
                                    <button class="text-red-500 text-xs" type="submit">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">Belum ada pesan untuk percakapan ini.</div>
                    @endforelse
                @else
                    <div class="text-sm text-gray-500">Pilih guru untuk melihat percakapan.</div>
                @endif
            </div>
            @if($chatPaginator)
                <div class="mt-4">
                    {{ $chatPaginator->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
