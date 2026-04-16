@php
    $flash = session('flash');
    $type = is_array($flash) ? ($flash['type'] ?? null) : null;
    $title = is_array($flash) ? ($flash['title'] ?? null) : null;
    $message = is_array($flash) ? ($flash['message'] ?? null) : null;
    $description = is_array($flash) ? ($flash['description'] ?? null) : null;
    $token = is_array($flash) ? ($flash['token'] ?? null) : null;
    $tokenLabel = is_array($flash) ? ($flash['token_label'] ?? 'Token akses baru') : 'Token akses baru';
    $copyBlock = is_array($flash) ? ($flash['copy_block'] ?? null) : null;
    $copyBlockLabel = is_array($flash) ? ($flash['copy_block_label'] ?? 'Template pesan siap kirim') : 'Template pesan siap kirim';

    if (! $message && session('status')) {
        $type = $type ?: 'success';
        $message = session('status');
    }

    $type = in_array($type, ['success', 'warning', 'danger', 'info'], true) ? $type : 'info';

    $typeClass = match ($type) {
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'danger' => 'alert-danger',
        default => 'alert-info',
    };

    $icon = match ($type) {
        'success' => 'fa-solid fa-circle-check',
        'warning' => 'fa-solid fa-triangle-exclamation',
        'danger' => 'fa-solid fa-circle-xmark',
        default => 'fa-solid fa-circle-info',
    };
@endphp

@if ($errors->any())
    <div class="alert alert-danger mb-6" role="alert">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <i class="{{ $icon }} mt-0.5"></i>
                <div>
                    <div class="font-bold">Terjadi kesalahan</div>
                    <div class="mt-1 text-sm">{{ $errors->first() }}</div>
                </div>
            </div>
            <button type="button" class="btn-secondary px-3" data-flash-close aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
@endif

@if ($message)
    <div class="alert {{ $typeClass }} mb-6" role="alert">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <i class="{{ $icon }} mt-0.5"></i>
                <div>
                    @if ($title)
                        <div class="font-bold">{{ $title }}</div>
                    @endif
                    <div class="text-sm font-medium {{ $title ? 'mt-1' : '' }}">{{ $message }}</div>
                    @if ($description)
                        <div class="mt-1 text-sm opacity-90">{{ $description }}</div>
                    @endif
                    @if ($token)
                        <div class="mt-3 text-xs font-semibold uppercase tracking-wide opacity-75">{{ $tokenLabel }}</div>
                        <div class="mt-2 rounded-xl border border-current/20 bg-white/60 px-3 py-2 font-mono text-sm tracking-widest text-slate-800">
                            {{ $token }}
                        </div>
                    @endif
                    @if ($copyBlock)
                        <div class="mt-4 text-xs font-semibold uppercase tracking-wide opacity-75">{{ $copyBlockLabel }}</div>
                        <pre class="mt-2 whitespace-pre-wrap rounded-xl border border-current/20 bg-white/60 px-3 py-3 text-sm text-slate-800">{{ $copyBlock }}</pre>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-secondary px-3" data-flash-close aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
@endif
