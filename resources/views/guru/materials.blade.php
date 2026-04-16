@extends('layouts.guru')
@section('title', 'Materi')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Materi</h1>
    <div class="card p-4">
        <form method="GET" action="{{ route('guru.materials') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="input-group flex-1">
                <label class="text-xs font-bold uppercase tracking-[0.22em] text-textSecondary">Filter</label>
                <select name="jenjang" class="input">
                    <option value="" @selected(empty($selectedJenjang))>Global + Jenjang Saya ({{ $jenjangUser ?? '-' }})</option>
                    <option value="GLOBAL" @selected($selectedJenjang === 'GLOBAL')>Hanya Global</option>
                </select>
            </div>
            <button class="btn-secondary w-full sm:w-auto" type="submit">Terapkan</button>
        </form>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($materials as $material)
        <div class="card p-4 flex flex-col">
            <div class="font-bold">{{ $material->subelement }}</div>
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
@endsection
