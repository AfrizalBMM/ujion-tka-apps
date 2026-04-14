@extends('layouts.guru')
@section('title', 'Materi')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Materi</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($materials as $material)
        <div class="card p-4 flex flex-col">
            <div class="font-bold">{{ $material->subelement }} - {{ $material->unit }}</div>
            <div class="text-xs text-gray-500 mb-2">Jenjang: {{ $material->jenjang }}</div>
            <div class="mb-2">{{ $material->kurikulum }}</div>
            <a href="{{ $material->link }}" class="btn-primary btn-xs mb-2" target="_blank">Akses Materi</a>
            @if(in_array($material->id, $bookmarks))
                <form method="POST" action="{{ route('guru.materials.unbookmark', $material) }}">@csrf<button class="btn-danger btn-xs w-full">Hapus Bookmark</button></form>
            @else
                <form method="POST" action="{{ route('guru.materials.bookmark', $material) }}">@csrf<button class="btn-secondary btn-xs w-full">Bookmark</button></form>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection