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
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($materials as $material)
        <div class="card p-4 flex flex-col">
            <div class="flex items-start justify-between gap-3">
                <div class="font-bold">{{ $material->subelement }}</div>
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
@endsection
