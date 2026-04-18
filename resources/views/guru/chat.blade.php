@extends('layouts.guru')
@section('title', 'Live Chat')
@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Live Chat dengan Superadmin</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Gunakan chat ini untuk koordinasi aktivasi akun, token akses, dan kebutuhan operasional lainnya.</p>
    </div>

    <div class="card p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 shadow-card">
            <img src="https://ui-avatars.com/api/?name=Superadmin&background=22C1C3&color=fff" class="w-10 h-10 rounded-full border border-white shadow" alt="avatar">
            <div class="flex-1 min-w-0">
                <div class="font-bold text-base text-slate-900 dark:text-slate-100">Superadmin</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Percakapan dengan superadmin</div>
            </div>
            <div class="flex items-center gap-2 ml-2">
                <!-- Info Button -->
                <button type="button" class="icon-button btn-info btn-xs" title="Detail Akun" onclick="document.getElementById('modal-detail-akun').classList.remove('hidden')">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
        </div>

        <div class="h-[460px] overflow-y-auto pr-1" id="chat-box">
            <ul class="space-y-4">
                @forelse($chats as $chat)
                    @php
                        $isOwn = $chat->from_user_id == auth()->id();
                    @endphp
                    <li class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[88%] sm:max-w-xl">
                            <div class="mb-1 flex items-center gap-2 {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $chat->fromUser->name ?? ($isOwn ? 'Anda' : 'Superadmin') }}
                                </span>
                                <span class="text-[11px] text-slate-400">{{ $chat->created_at->format('d M H:i') }}</span>
                            </div>

                            <div class="rounded-2xl px-4 py-3 shadow-sm {{ $isOwn ? 'bg-blue-100 text-slate-800 dark:bg-blue-500/20 dark:text-slate-100' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100' }}">
                                @if($chat->message)
                                    <div class="whitespace-pre-wrap text-sm leading-6">{{ $chat->message }}</div>
                                @endif

                                @if($chat->image_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($chat->image_path) }}" target="_blank" class="mt-3 block">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($chat->image_path) }}"
                                            alt="Lampiran chat"
                                            class="max-h-56 rounded-2xl border border-white/60 object-cover shadow-sm dark:border-slate-700"
                                        >
                                    </a>
                                @endif
                            </div>
                        </div>
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

        <form method="POST" action="{{ route('guru.chat.store') }}" enctype="multipart/form-data" class="mt-5 border-t border-slate-200/80 pt-4 dark:border-slate-800">
            @csrf
            <div class="flex gap-2 items-end">
                <textarea name="message" class="input flex-1 min-h-10" rows="1" placeholder="Tulis pesan..."></textarea>
                <label class="flex items-center cursor-pointer mb-0">
                    <input type="file" name="image" accept="image/*" class="hidden">
                    <span class="icon-button" title="Lampirkan Media"><i class="fa-solid fa-photo-film"></i></span>
                </label>
                <button class="btn-primary px-5" type="submit"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </form>
    </div>


<!-- Modal Info/Tutorial Chat -->
<div id="modal-detail-akun" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-6 w-full max-w-md relative">
        <button class="absolute top-2 right-2 text-gray-400 hover:text-red-500" onclick="document.getElementById('modal-detail-akun').classList.add('hidden')">
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
<script>
    setTimeout(()=>{
        var box = document.getElementById('chat-box');
        box.scrollTop = box.scrollHeight;
    }, 100);
</script>
@endsection
