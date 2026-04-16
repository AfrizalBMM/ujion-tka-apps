@extends('layouts.guru')
@section('title', 'Bank Soal Pribadi')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Bank Soal Pribadi</h1>
    <div class="card p-4 mb-4">
        <form method="POST" action="{{ route('guru.personal-questions.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="text-xs font-bold">Jenjang</label>
                    <input name="jenjang" class="input w-full" required>
                </div>
                <div>
                    <label class="text-xs font-bold">Kategori</label>
                    <input name="kategori" class="input w-full" required>
                </div>
                <div>
                    <label class="text-xs font-bold">Tipe Soal</label>
                    <select name="tipe" class="input w-full" required>
                        <option value="PG">Pilihan Ganda</option>
                        <option value="Checklist">Checklist</option>
                        <option value="Singkat">Jawaban Singkat</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold">Pertanyaan</label>
                    <textarea name="pertanyaan" class="input w-full" required></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold">Opsi (jika PG/Checklist, pisahkan dengan koma)</label>
                    <input name="options_raw" class="input w-full" placeholder="A, B, C, D">
                </div>
                <div>
                    <label class="text-xs font-bold">Jawaban Benar</label>
                    <input name="jawaban_benar" class="input w-full">
                </div>
                <div>
                    <label class="text-xs font-bold">Pembahasan</label>
                    <textarea name="pembahasan" class="input w-full"></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold">Gambar (opsional)</label>
                    <input type="file" name="image" accept="image/*" class="input w-full">
                </div>
                <div>
                    <label class="text-xs font-bold">Status</label>
                    <select name="status" class="input w-full" required>
                        <option value="draft">Draft</option>
                        <option value="terbit">Terbit</option>
                    </select>
                </div>
            </div>
            <button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Tambah Soal</button>
        </form>
    </div>
    <div class="card p-4">
        <a href="{{ route('guru.personal-questions.builder') }}" class="btn-primary mb-4 w-full sm:w-auto">Builder Soal Fullscreen</a>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[620px]">
            <thead>
                <tr>
                    <th>Jenjang</th>
                    <th>Kategori</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($questions as $question)
                <tr>
                    <td>{{ $question->jenjang }}</td>
                    <td>{{ $question->kategori }}</td>
                    <td>{{ $question->tipe }}</td>
                    <td>{{ $question->status }}</td>
                    <td>
                        <form method="POST" action="{{ route('guru.personal-questions.destroy', $question) }}">@csrf<button class="btn-danger">Hapus</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
