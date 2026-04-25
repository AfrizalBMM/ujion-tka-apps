@extends('layouts.guru')
@section('title', 'Live Chat')
@section('content')
@php
    $chatPartnerName = $superadmin?->name ?? 'Superadmin';
    $chatPartnerAvatarUrl = $superadmin?->avatar_url
        ?? 'https://ui-avatars.com/api/?name=Superadmin&background=4F6EF7&color=fff';
@endphp
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Live Chat dengan Admin Ujion</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Gunakan chat ini untuk koordinasi aktivasi akun, token akses, dan kebutuhan operasional lainnya.</p>
    </div>

    <div class="card p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 shadow-card">
            <img src="{{ $chatPartnerAvatarUrl }}" class="w-10 h-10 rounded-full border border-white object-cover shadow" alt="Avatar {{ $chatPartnerName }}">
            <div class="flex-1 min-w-0">
                <div class="font-bold text-base text-slate-900 dark:text-slate-100">{{ $chatPartnerName }}</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Percakapan dengan Admin Ujion</div>
            </div>
            <div class="flex items-center gap-2 ml-2">
                <!-- Info Button -->
                <button type="button" class="icon-button btn-info btn-xs" title="Detail Akun" data-open-chat-info>
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
        </div>

        <div class="h-[460px] overflow-y-auto p-2 sm:p-4 rounded-2xl shadow-inner" id="chat-box" style="background: white; background-image: linear-gradient(rgba(120,120,120,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(120,120,120,0.06) 1px, transparent 1px); background-size: 32px 32px;">
            <ul class="space-y-4">
                @forelse($chats as $chat)
                    @php
                        $isOwn = (int) $chat->from_user_id === (int) auth()->id();
                        $sender = $chat->fromUser;
                        $senderName = $sender?->name ?? ($isOwn ? (auth()->user()?->name ?? 'Anda') : $chatPartnerName);
                        $senderAvatarUrl = $sender?->avatar_url
                            ?? ($isOwn
                                ? (auth()->user()?->avatar_url ?? 'https://ui-avatars.com/api/?name=Guru&background=22C1C3&color=fff')
                                : $chatPartnerAvatarUrl);
                    @endphp
                    <li class="flex items-end gap-2 {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                        @unless($isOwn)
                            <img src="{{ $senderAvatarUrl }}" class="h-8 w-8 shrink-0 rounded-full border border-white object-cover shadow" alt="Avatar {{ $senderName }}">
                        @endunless
                        <div class="max-w-[88%] sm:max-w-xl">
                            <div class="mb-1 flex items-center gap-2 {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                                <span class="text-[11px] text-slate-400">{{ $chat->created_at->format('d M H:i') }}</span>
                            </div>

                            <div class="rounded-2xl px-4 py-3 shadow-sm {{ $isOwn ? 'bg-blue-100 text-slate-800 dark:bg-blue-500/20 dark:text-slate-100' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100' }}">
                                @if($chat->message)
                                    <div class="whitespace-pre-wrap text-sm leading-6">{{ $chat->message }}</div>
                                @endif

                                 @if($chat->image_path)
                                     <a href="{{ route('guru.chat.image', $chat) }}" target="_blank" class="mt-3 block">
                                         <img
                                             src="{{ route('guru.chat.image', $chat) }}"
                                             alt="Lampiran chat"
                                             class="max-h-56 rounded-2xl border border-white/60 object-cover shadow-sm dark:border-slate-700"
                                         >
                                     </a>
                                 @endif
                            </div>
                        </div>
                        @if($isOwn)
                            <img src="{{ $senderAvatarUrl }}" class="h-8 w-8 shrink-0 rounded-full border border-white object-cover shadow" alt="Avatar {{ $senderName }}">
                        @endif
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/80 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40">
                        <i class="fa-solid fa-comments text-3xl text-slate-300 dark:text-slate-600"></i>
                        <div class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Belum ada percakapan</div>
                        <div class="mt-1 text-sm text-textSecondary dark:text-slate-400">Mulai kirim pesan atau gambar ke superadmin dari form di bawah.</div>
                    </li>
                @endforelse
            </ul>
        </div>

        <form method="POST" action="{{ route('guru.chat.store') }}" enctype="multipart/form-data" class="mt-5 border-t border-slate-200/80 pt-4 dark:border-slate-800" id="chat-send-form">
            @csrf
            <div class="space-y-2">
                <div id="chat-image-preview" class="hidden">
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-900/40">
                        <img id="chat-image-preview-img" alt="Preview gambar" class="h-16 w-16 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <div id="chat-image-preview-meta" class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200"></div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">Preview lampiran, akan terkirim saat Anda klik kirim.</div>
                        </div>
                        <button type="button" class="icon-button btn-danger btn-xs" id="chat-image-preview-clear" title="Hapus gambar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="flex gap-2 items-end">
                    <textarea name="message" class="input flex-1 min-h-10" rows="1" placeholder="Tulis pesan..."></textarea>
                    <label class="flex items-center cursor-pointer mb-0">
                        <input type="file" name="image" accept="image/*" class="hidden" id="chat-image-input">
                        <span class="icon-button" title="Lampirkan Media (maks 2 MB)"><i class="fa-solid fa-photo-film"></i></span>
                    </label>
                    <button class="btn-primary px-5" type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400">Lampiran gambar maksimal 2 MB.</div>
            </div>
        </form>
    </div>


<!-- Modal Info/Tutorial Chat -->
<div id="modal-detail-akun" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-6 w-full max-w-md relative">
        <button class="absolute top-2 right-2 text-gray-400 hover:text-red-500" data-close-chat-info>
            <i class="fa-solid fa-xmark fa-lg"></i>
        </button>
        <div class="flex flex-col items-center gap-3">
            <div class="text-2xl text-blue-500"><i class="fa-solid fa-circle-info"></i></div>
            <div class="font-bold text-lg text-slate-900 dark:text-slate-100">Tutorial & Fitur Chat</div>
            <div class="mt-2 w-full space-y-3 text-sm text-slate-700 dark:text-slate-200">
                <ul class="list-disc pl-5 space-y-1">
                    <li>Kirim pesan teks ke superadmin untuk konsultasi, aktivasi akun, atau kebutuhan operasional lainnya.</li>
                    <li>Anda dapat melampirkan gambar (misal: bukti pembayaran, dokumen, dsb) pada chat.</li>
                    <li>Semua riwayat chat akan tersimpan dan dapat dilihat kembali di halaman ini.</li>
                    <li>Superadmin dapat membalas pesan Anda secara langsung di chat ini.</li>
                    <li>Gunakan bahasa yang sopan dan jelas agar komunikasi efektif.</li>
                </ul>
                <div class="mt-2 text-xs text-gray-500 dark:text-slate-400">
                    Jika ada kendala teknis, silakan hubungi superadmin melalui chat ini atau kontak resmi yang tersedia.
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
