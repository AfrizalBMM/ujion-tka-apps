@extends('layouts.guru')
@section('title', 'Dashboard Guru')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Dashboard Guru</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-4">
            <div class="text-xs text-gray-500">Ujian Dibuat</div>
            <div class="text-3xl font-bold">{{ $ujianDibuat }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-500">Rata-rata Skor Kelas</div>
            <div class="text-3xl font-bold">{{ number_format($rataRataKelas, 2) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-gray-500">Total Peserta</div>
            <div class="text-3xl font-bold">{{ $totalPeserta }}</div>
        </div>
    </div>
    <div class="card p-4">
        <h2 class="font-semibold mb-2">Aktivitas Terbaru</h2>
        <ul class="divide-y">
            @if(count($logs) > 0)
                @foreach ($logs as $log)
                    <li class="py-2 text-sm text-gray-700">{{ $log->created_at }} - {{ $log->route_name }} ({{ $log->ip_address }})</li>
                @endforeach
            @else
                <li class="py-2 text-gray-400">Belum ada aktivitas.</li>
            @endif
        </ul>
    </div>
    <div class="card p-4">
        <h2 class="font-semibold mb-2">Pengumuman Penting</h2>
        <ul>
            @if(count($pengumuman) > 0)
                @foreach ($pengumuman as $info)
                    <li class="py-2 text-sm text-blue-700">{{ $info }}</li>
                @endforeach
            @else
                <li class="py-2 text-gray-400">Tidak ada pengumuman.</li>
            @endif
        </ul>
    </div>
</div>
@endsection