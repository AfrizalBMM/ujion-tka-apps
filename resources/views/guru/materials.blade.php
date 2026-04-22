@extends('layouts.guru')
@section('title', 'Materi')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Materi</h1>
    <div class="card p-4">
        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            Anda melihat materi jenjang <strong>{{ $jenjangUser ?? '-' }}</strong>, termasuk materi global yang ditetapkan untuk jenjang yang sama.
        </div>
    </div>
    <form method="GET" action="{{ route('guru.materials') }}" class="card p-4 space-y-4 sm:space-y-0 sm:flex sm:items-end sm:gap-4" data-ssd-autosubmit data-materials-filter-form>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Mata Pelajaran</label>
            <div class="ssd-wrap mt-1">
                <input type="hidden" name="mapel" value="{{ $filters['mapel'] ?? '' }}">
                <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                    <span class="ssd-label">{{ ($filters['mapel'] ?? '') ?: 'Semua Mapel' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari mapel..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option" data-value="">Semua Mapel</div>
                        @foreach($mapels as $m)
                            <div class="ssd-option{{ ($filters['mapel'] ?? '') === $m ? ' ssd-selected' : '' }}" data-value="{{ $m }}">{{ $m }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
            <div class="ssd-wrap mt-1">
                <input type="hidden" name="curriculum" value="{{ $filters['curriculum'] ?? '' }}">
                <button type="button" class="ssd-trigger input flex items-center justify-between gap-2 w-full">
                    <span class="ssd-label">{{ ($filters['curriculum'] ?? '') ?: 'Semua Kurikulum' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari kurikulum..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option" data-value="">Semua Kurikulum</div>
                        @foreach($curriculums as $c)
                            <div class="ssd-option{{ ($filters['curriculum'] ?? '') === $c ? ' ssd-selected' : '' }}" data-value="{{ $c }}">{{ $c }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Cari Materi</label>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="input mt-1 w-full" placeholder="Cari materi..." data-live-search>
        </div>
        <a href="{{ route('guru.materials') }}" class="btn-secondary h-[42px] flex items-center justify-center">Reset</a>
    </form>

    <div id="materials-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($materials as $material)
        <div class="card p-4 flex flex-col">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="text-[10px] font-bold text-primary uppercase tracking-wider mb-0.5">{{ $material->mapel }}</div>
                    <div class="font-bold">{{ $material->subelement }}</div>
                </div>
                <span class="badge-info shrink-0">Materi dari Ujion</span>
            </div>
            <div class="mt-1 text-sm text-textSecondary">
                <i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $material->unit }}
                <i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $material->sub_unit }}
            </div>
            <div class="mt-2 text-xs text-textSecondary">Kurikulum: {{ $material->curriculum }}</div>
            <div class="text-xs text-textSecondary mb-3">Jenjang: {{ $material->jenjang ?? 'Semua' }}</div>
            <a href="{{ route('guru.materials.show', $material) }}" class="btn-primary mb-2 w-full text-center">Detail Materi</a>
            @if($material->link)
                <a href="{{ $material->link }}" class="btn-secondary mb-2 w-full text-center" target="_blank" rel="noopener">Buka Link</a>
            @endif
            @if(in_array($material->id, $bookmarks))
                <form method="POST" action="{{ route('guru.materials.unbookmark', $material) }}">@csrf<button class="btn-danger w-full">Hapus Bookmark</button></form>
            @else
                <form method="POST" action="{{ route('guru.materials.bookmark', $material) }}">@csrf<button class="btn-secondary w-full">Bookmark</button></form>
            @endif
        </div>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[data-materials-filter-form]');
    var input = document.querySelector('[data-live-search][name="search"]');
    var grid = document.getElementById('materials-grid');
    if (!form || !input || !grid) return;

    var timer = null;
    var abortController = null;
    var lastValue = (input.value || '').trim();
    var isComposing = false;

    function buildUrl() {
        var action = form.getAttribute('action') || window.location.pathname;
        var url = new URL(action, window.location.origin);
        var params = new URLSearchParams(new FormData(form));
        url.search = params.toString();
        return url;
    }

    function setLoading(isLoading) {
        grid.classList.toggle('opacity-60', !!isLoading);
        grid.classList.toggle('pointer-events-none', !!isLoading);
    }

    async function fetchAndReplace(force) {
        var nextValue = (input.value || '').trim();
        if (!force && nextValue === lastValue) return;
        lastValue = nextValue;

        var url = buildUrl();

        if (abortController) abortController.abort();
        abortController = new AbortController();

        setLoading(true);
        try {
            var res = await fetch(url.toString(), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortController.signal,
            });
            if (!res.ok) throw new Error('Request failed');

            var html = await res.text();
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var nextGrid = doc.getElementById('materials-grid');
            if (nextGrid) {
                grid.innerHTML = nextGrid.innerHTML;
            }

            history.replaceState({}, '', url.pathname + (url.search ? ('?' + url.searchParams.toString()) : ''));
        } catch (e) {
            // ignore abort/errors; user can still submit manual (enter) if needed
        } finally {
            setLoading(false);
        }
    }

    function scheduleFetch() {
        if (isComposing) return;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetchAndReplace();
        }, 450);
    }

    form.addEventListener('submit', function (e) {
        if (document.activeElement === input) {
            e.preventDefault();
            fetchAndReplace(true);
        }
    });

    input.addEventListener('compositionstart', function () { isComposing = true; });
    input.addEventListener('compositionend', function () { isComposing = false; scheduleFetch(); });
    input.addEventListener('input', scheduleFetch);
});
</script>
@endsection
