@extends('layouts.guru')
@section('title', 'Live Chat')
@section('content')
<div class="max-w-3xl space-y-6">
    <h1 class="text-2xl font-bold mb-4">Live Chat dengan Superadmin</h1>
    <div class="card mb-4 h-[420px] overflow-y-auto p-4 sm:h-96" id="chat-box">
        <ul class="space-y-2">
            @foreach($chats as $chat)
                <li class="flex {{ $chat->user_id == auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-lg p-2 sm:max-w-xs {{ $chat->user_id == auth()->id() ? 'bg-blue-100' : 'bg-gray-100' }}">
                        <div class="text-sm">{{ $chat->message }}</div>
                        @if($chat->image_path)
                            <img src="{{ asset('storage/'.$chat->image_path) }}" class="max-h-32 mt-2 rounded">
                        @endif
                        <div class="text-xs text-gray-400 mt-1">{{ $chat->created_at->format('H:i d M') }}</div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
    <form method="POST" action="{{ route('guru.chat.store') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row">
        @csrf
        <input name="message" class="input flex-1" placeholder="Ketik pesan..." required>
        <input type="file" name="image" class="input w-full sm:w-32">
        <button class="btn-primary w-full sm:w-auto" type="submit">Kirim</button>
    </form>
</div>
<script>
    setTimeout(()=>{
        var box = document.getElementById('chat-box');
        box.scrollTop = box.scrollHeight;
    }, 100);
</script>
@endsection
