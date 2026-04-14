@php
    $flash = session('flash');
    $type = is_array($flash) ? ($flash['type'] ?? null) : null;
    $message = is_array($flash) ? ($flash['message'] ?? null) : null;

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
                <div class="text-sm font-medium">{{ $message }}</div>
            </div>
            <button type="button" class="btn-secondary px-3" data-flash-close aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
@endif
