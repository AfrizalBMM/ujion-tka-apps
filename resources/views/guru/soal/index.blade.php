@extends('layouts.guru')

@section('title', 'Kelola Soal')

@section('content')
@php($canManage = $paket->isManagedByGuru(auth()->user()))
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->nama }}</span>
        <h1 class="page-title">{{ $mapel->nama_label }}</h1>
        <p class="page-description">
            {{ $canManage
                ? 'Kelola bank soal mapel ini dengan tipe pilihan ganda atau menjodohkan.'
                : 'Soal pada paket milik superadmin hanya dapat dilihat dari akun guru.' }}
        </p>
        <div class="page-actions">
            @if($canManage)
                <a href="{{ route('guru.soal.create', [$paket, $mapel, 'tipe_soal' => 'pilihan_ganda']) }}" class="btn-primary">Tambah PG</a>
                <a href="{{ route('guru.soal.create', [$paket, $mapel, 'tipe_soal' => 'menjodohkan']) }}" class="btn-secondary">Tambah Menjodohkan</a>
                <a href="{{ route('guru.teks-bacaan.index', [$paket, $mapel]) }}" class="btn-secondary">Teks Bacaan</a>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="table-container">
            <table class="table-ujion min-w-[920px]">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tipe</th>
                        <th>Indikator</th>
                        <th>Teks Bacaan</th>
                        <th>Isi Jawaban</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mapel->soals as $soal)
                        <tr>
                            <td>{{ $soal->nomor_soal }}</td>
                            <td>{{ str($soal->tipe_soal)->replace('_', ' ')->headline() }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($soal->indikator, 100) }}</td>
                            <td>{{ $soal->teksBacaan?->judul ?? '-' }}</td>
                            <td>{{ $soal->isPilihanGanda() ? $soal->pilihanJawabans->count().' pilihan' : $soal->pasanganMenjodohkans->count().' pasangan' }}</td>
                            <td>
                                @if($canManage)
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('guru.soal.edit', [$paket, $mapel, $soal]) }}" class="btn-secondary px-3 py-2 text-xs">Edit</a>
                                        <form method="POST" action="{{ route('guru.soal.destroy', [$paket, $mapel, $soal]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-textSecondary">Read only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-textSecondary">Belum ada soal pada mapel ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
