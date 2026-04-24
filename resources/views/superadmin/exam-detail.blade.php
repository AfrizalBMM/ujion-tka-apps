@extends('layouts.superadmin')
@section('title', 'Detail Ujian')
@section('content')
<div class="max-w-3xl space-y-6">
    <div class="card p-6 flex flex-col items-center">
        <div class="text-lg font-bold mb-2">Token Ujian</div>
        <div class="flex flex-col items-center gap-3 sm:flex-row">
            <span id="token-text" class="break-all text-center font-mono text-2xl tracking-widest bg-gray-100 px-4 py-2 rounded sm:text-3xl">{{ $exam->token }}</span>
            <button type="button" data-copy-exam-token-single class="btn-secondary w-full sm:w-auto">Copy Token</button>
        </div>
        <div id="copy-success" class="text-green-600 mt-2 hidden">Token berhasil disalin!</div>
    </div>
    <div class="card p-6">
        <div class="mb-2 font-bold">Judul Ujian:</div>
        <div class="mb-4">{{ $exam->judul }}</div>
        <div class="mb-2 font-bold">Tanggal Terbit:</div>
        <div class="mb-4">{{ $exam->tanggal_terbit->format('d M Y H:i') }}</div>
        <div class="mb-2 font-bold">Max Peserta:</div>
        <div class="mb-4">{{ $exam->max_peserta }}</div>
        <div class="mb-2 font-bold">Status:</div>
        <div class="mb-4">{{ $exam->status }}</div>
        <div class="mb-2 font-bold">Aktif:</div>
        <div class="mb-4">
            @if($exam->is_active)
                <span class="badge-success">Aktif</span>
            @else
                <span class="badge-danger">Nonaktif</span>
            @endif
        </div>
        <a href="{{ route('superadmin.exams.builder', $exam) }}" class="btn-primary">Builder Soal</a>
    </div>
</div>
@endsection
