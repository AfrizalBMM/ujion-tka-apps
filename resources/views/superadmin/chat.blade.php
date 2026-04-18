@extends('layouts.superadmin')
@section('title', 'Live Chat')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Live Chat Guru/Operator</h1>
    </div>
    <div class="grid gap-6 md:grid-cols-3">

        <div class="md:col-span-1">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-card p-4 flex flex-col gap-4 h-full">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-users text-primary"></i>
                    <span class="font-bold text-base">Daftar Guru</span>
                    <div class="flex-1"></div>
                    @if($users->count())
                    <form method="POST" action="{{ route('superadmin.chat.destroyAllGuru') }}" onsubmit="return false;" id="delete-all-chats-guru-form">
                        @csrf
                        <button type="submit" class="icon-button btn-danger btn-xs" data-confirm data-confirm-title="Hapus Semua Pesan Semua Guru?" data-confirm="Semua pesan dan gambar dari seluruh guru akan dihapus permanen. Lanjutkan?" title="Hapus semua pesan guru">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
                <div class="flex-1 overflow-y-auto pr-1">
                    @forelse($users as $user)
                        <a href="{{ route('superadmin.chat.index', ['user' => $user->id]) }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2 mb-1 transition {{ optional($selectedUser)->id === $user->id ? 'bg-blue-50 border border-blue-400 dark:bg-blue-500/10 dark:border-blue-400/40' : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=22C1C3&color=fff" class="w-9 h-9 rounded-full border border-white shadow" alt="avatar">
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</div>
                            </div>
                            @if($user->unread_messages_count)
                                <span class="ml-2 rounded-full bg-red-500 px-2 py-1 text-[10px] font-bold text-white">{{ $user->unread_messages_count }}</span>
                            @endif
                        </a>
                    @empty
                        <div class="text-sm text-gray-500">Belum ada guru yang bisa dipilih.</div>
                    @endforelse
                </div>
            </div>
            <!-- Form kirim pesan lama di sidebar dihapus, hanya ada di bawah area percakapan -->
        </div>
        <div class="md:col-span-2 flex flex-col h-full">
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 shadow-card mb-2">
                @if($selectedUser)
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($selectedUser->name) }}&background=22C1C3&color=fff" class="w-10 h-10 rounded-full border border-white shadow" alt="avatar">
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-base text-slate-900 dark:text-slate-100">{{ $selectedUser->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ $selectedUser->email }}</div>
                    </div>
                    <div class="flex items-center gap-2 ml-2">
                        <!-- Info Button -->
                        <button type="button" class="icon-button btn-info btn-xs" title="Detail Akun" onclick="document.getElementById('modal-detail-akun').classList.remove('hidden')">
                            <i class="fa-solid fa-circle-info"></i>
                        </button>
                        <!-- Delete All Button -->
                        @if($selectedUser && $chats->count())
                        <form method="POST" action="{{ route('superadmin.chat.destroyAll', ['user' => $selectedUser->id]) }}" onsubmit="return false;" id="delete-all-chats-form">
                            @csrf
                            <button type="submit" class="icon-button btn-danger btn-xs" data-confirm data-confirm-title="Hapus Semua Pesan?" data-confirm="Semua pesan dan gambar pada percakapan ini akan dihapus permanen. Lanjutkan?" title="Hapus semua pesan">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                @else
                    <span class="text-sm text-gray-500">Pilih guru untuk mulai chat.</span>
                @endif
            </div>
            <div class="flex-1 overflow-y-auto p-2 md:p-4 rounded-2xl shadow-inner" style="background: white; background-image: linear-gradient(rgba(120,120,120,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(120,120,120,0.06) 1px, transparent 1px); background-size: 32px 32px;">
                @if($selectedUser)
                    @forelse($chats as $chat)
                        <div class="mb-3 flex {{ $chat->from_user_id == auth()->id() ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[88%] sm:max-w-xl">
                                <div class="rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm {{ $chat->from_user_id == auth()->id() ? 'bg-blue-100 text-slate-800 dark:bg-blue-500/20 dark:text-slate-100' : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100' }}">
                                    @if($chat->message)
                                        <div class="whitespace-pre-wrap">{{ $chat->message }}</div>
                                    @endif
                                    @if($chat->image_path)
                                        <a href="{{ Storage::url($chat->image_path) }}" target="_blank" class="mt-3 block">
                                            <img src="{{ Storage::url($chat->image_path) }}" class="max-h-56 rounded-2xl border border-slate-200 max-w-full object-cover dark:border-slate-700">
                                        </a>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-xs text-gray-400 dark:text-slate-500 {{ $chat->from_user_id == auth()->id() ? 'justify-end' : 'justify-start' }}">
                                    <span>{{ $chat->created_at->format('d M H:i') }}</span>
                                    @if($chat->from_user_id == auth()->id())
                                        @if($chat->is_read)
                                            <span class="text-green-500"><i class="fa-solid fa-check-double"></i></span>
                                        @else
                                            <span><i class="fa-solid fa-check"></i></span>
                                        @endif
                                    @endif
                                    <!-- tombol hapus pesan individu dihapus, hanya tombol hapus semua di header -->
                                </div>
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
            <!-- Form kirim pesan dalam card -->
            <div class="mt-4">
                <div class="card p-4">
                    <form method="POST" action="{{ route('superadmin.chat.store') }}" enctype="multipart/form-data" class="flex gap-2 items-end">
                        @csrf
                        <!-- Dropdown guru dihapus, karena sudah dipilih di area percakapan -->
                        <input type="hidden" name="to_user_id" value="{{ $selectedUser ? $selectedUser->id : '' }}">
                        <label class="flex items-center cursor-pointer mb-0">
                            <input type="file" name="image" accept="image/*" class="hidden">
                            <span class="icon-button" title="Lampirkan Media"><i class="fa-solid fa-photo-film"></i></span>
                        </label>
                        <textarea name="message" class="input flex-1 min-h-10" rows="1" placeholder="Tulis pesan..."></textarea>
                        <button class="btn-primary px-5" type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Detail Akun -->
@if($selectedUser)
<div id="modal-detail-akun" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-6 w-full max-w-md relative">
        <button class="absolute top-2 right-2 text-gray-400 hover:text-red-500" onclick="document.getElementById('modal-detail-akun').classList.add('hidden')">
            <i class="fa-solid fa-xmark fa-lg"></i>
        </button>
        <div class="flex flex-col items-center gap-3">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($selectedUser->name) }}&background=22C1C3&color=fff" class="w-16 h-16 rounded-full border border-white shadow" alt="avatar">
            <div class="font-bold text-lg text-slate-900 dark:text-slate-100">{{ $selectedUser->name }}</div>
            <div class="text-sm text-gray-500 dark:text-slate-400">{{ $selectedUser->email }}</div>
            <div class="mt-2 w-full space-y-2">
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">Role</span>
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $selectedUser->role ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">Status Akun</span>
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $selectedUser->account_status ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">Access Token</span>
                    <span class="font-mono text-xs text-slate-700 dark:text-slate-200">{{ $selectedUser->access_token ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">Jenjang</span>
                    <span class="text-slate-900 dark:text-slate-100">{{ $selectedUser->jenjang ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">Tingkat</span>
                    <span class="text-slate-900 dark:text-slate-100">{{ $selectedUser->tingkat ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">Satuan Pendidikan</span>
                    <span class="text-slate-900 dark:text-slate-100">{{ $selectedUser->satuan_pendidikan ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b">
                    <span class="text-gray-500">No. WA</span>
                    <span class="text-slate-900 dark:text-slate-100">{{ $selectedUser->no_wa ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
