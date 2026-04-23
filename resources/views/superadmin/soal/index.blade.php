@extends('layouts.superadmin')

@section('title', 'Kelola Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->nama }}</span>
        <h1 class="page-title">{{ $mapel->nama_label }}</h1>
        <p class="page-description">
            {{ $mapel->soals->count() }}/{{ $mapel->jumlah_soal }} butir terisi, durasi {{ $mapel->durasi_menit }} menit.
            {{ $mapel->isSurvey() ? 'Komponen ini hanya memakai teks bacaan, teks soal, dan pilihan ganda profil.' : '' }}
        </p>
        <div class="page-actions">
            <a href="{{ route('superadmin.soal.create', [$paket, $mapel, 'tipe_soal' => 'pilihan_ganda']) }}" class="btn-primary">Tambah PG</a>
            @unless($mapel->isSurvey())
                <a href="{{ route('superadmin.soal.create', [$paket, $mapel, 'tipe_soal' => 'menjodohkan']) }}" class="btn-secondary">Tambah Menjodohkan</a>
            @endunless
            <a href="{{ route('superadmin.teks-bacaan.index', [$paket, $mapel]) }}" class="btn-secondary">Teks Bacaan</a>
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
                            <td>
                                <div>{{ \Illuminate\Support\Str::limit($soal->indikator, 100) }}</div>
                                @if($mapel->isSurvey() && $soal->dimensi)
                                    <div class="mt-1 text-xs text-textSecondary">{{ $soal->dimensi }}{{ $soal->subdimensi ? ' · ' . $soal->subdimensi : '' }}</div>
                                @endif
                            </td>
                            <td>{{ $soal->teksBacaan?->judul ?? '-' }}</td>
                            <td>{{ $soal->isPilihanGanda() ? $soal->pilihanJawabans->count().' pilihan' : $soal->pasanganMenjodohkans->count().' pasangan' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('superadmin.soal.edit', [$paket, $mapel, $soal]) }}" class="btn-secondary px-3 py-2 text-xs">Edit</a>
                                    <form method="POST" action="{{ route('superadmin.soal.destroy', [$paket, $mapel, $soal]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
                                    </form>
                                </div>
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
