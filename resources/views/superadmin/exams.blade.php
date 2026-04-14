@extends('layouts.superadmin')
@section('title', 'Ujian')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Manajemen Ujian</h1>
    <div class="card p-4 mb-4">
        <form method="POST" action="{{ route('superadmin.exams.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs font-bold">Judul</label>
                    <input name="judul" class="input w-full" required>
                </div>
                <div>
                    <label class="text-xs font-bold">Tanggal Terbit</label>
                    <input type="datetime-local" name="tanggal_terbit" class="input w-full" required>
                </div>
                <div>
                    <label class="text-xs font-bold">Max Peserta</label>
                    <input type="number" name="max_peserta" class="input w-full" value="50" required>
                </div>
                <div>
                    <label class="text-xs font-bold">Timer (menit)</label>
                    <input type="number" name="timer" class="input w-full">
                </div>
                <div>
                    <label class="text-xs font-bold">Status</label>
                    <select name="status" class="input w-full" required>
                        <option value="draft">Draft</option>
                        <option value="terbit">Terbit</option>
                    </select>
                </div>
            </div>
            <button class="btn-primary mt-3" type="submit">Buat Ujian</button>
        </form>
    </div>
    <div class="card p-4">
        <table class="table-ujion w-full">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Tanggal Terbit</th>
                    <th>Max Peserta</th>
                    <th>Token</th>
                    <th>Status</th>
                    <th>Aktif</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($exams as $exam)
                <tr>
                    <td>{{ $exam->judul }}</td>
                    <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
                    <td>{{ $exam->max_peserta }}</td>
                    <td>{{ $exam->token }}
                        <button onclick="navigator.clipboard.writeText('{{ $exam->token }}')" class="btn-secondary btn-xs ml-2">Copy</button>
                        <a href="{{ route('superadmin.exams.show', $exam) }}" class="btn-primary btn-xs ml-2">Detail</a>
                    </td>
                    <td>{{ $exam->status }}</td>
                    <td>
                        @if($exam->is_active)
                            <span class="badge-success">Aktif</span>
                        @else
                            <span class="badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td class="flex gap-2">
                        <form method="POST" action="{{ route('superadmin.exams.toggle', $exam) }}">@csrf<button class="btn-secondary btn-xs">Toggle</button></form>
                        <form method="POST" action="{{ route('superadmin.exams.destroy', $exam) }}">@csrf<button class="btn-danger btn-xs">Hapus</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection