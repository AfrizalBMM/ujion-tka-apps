@extends('layouts.guru')

@section('title', 'Kelola Soal')

@section('content')
@php
    $canManage = $paket->isManagedByGuru(auth()->user());
@endphp
<div class="space-y-6">
  <section class="page-hero">
    <span class="page-kicker">{{ $paket->nama }}</span>
    <h1 class="page-title">{{ $mapel->nama_label }}</h1>
    <p class="page-description">
      {{ $canManage
                ? ($mapel->isSurvey() ? 'Kelola butir survey siswa dengan teks bacaan, teks soal, dan pilihan ganda profil.' : 'Kelola bank soal mapel ini dengan tipe pilihan ganda atau menjodohkan.')
                : 'Soal pada paket milik superadmin hanya dapat dilihat dari akun guru.' }}
    </p>
    <div class="page-actions">
      @if($canManage)
      <a href="{{ route('guru.soal.create', [$paket, $mapel, 'tipe_soal' => 'pilihan_ganda']) }}"
        class="btn-primary">Tambah PG</a>
      @unless($mapel->isSurvey())
      <a href="{{ route('guru.soal.create', [$paket, $mapel, 'tipe_soal' => 'menjodohkan']) }}"
        class="btn-secondary">Tambah Menjodohkan</a>
      @endunless
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
            <td>
              <div>{{ \Illuminate\Support\Str::limit($soal->indikator, 100) }}</div>
              @if($mapel->isSurvey() && $soal->dimensi)
              <div class="mt-1 text-xs text-textSecondary">
                {{ $soal->dimensi }}{{ $soal->subdimensi ? ' · ' . $soal->subdimensi : '' }}</div>
              @endif
            </td>
            <td>{{ $soal->teksBacaan?->judul ?? '-' }}</td>
            <td>
              {{ $soal->isPilihanGanda() ? $soal->pilihanJawabans->count().' pilihan' : $soal->pasanganMenjodohkans->count().' pasangan' }}
            </td>
            <td>
              @if($canManage)
              <div class="flex flex-wrap gap-2">
                <a href="{{ route('guru.soal.edit', [$paket, $mapel, $soal]) }}"
                  class="btn-secondary px-3 py-2 text-xs">Edit</a>
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
  @if($canManage && !$mapel->isSurvey())
  <section class="card mt-6">
    <h2 class="font-bold text-lg mb-2 flex items-center gap-2">
      <i class="fa-solid fa-layer-group"></i>
      Ambil Soal dari Ujion
    </h2>
    <form method="POST" action="{{ route('guru.soal.import-ujion', [$paket, $mapel]) }}">
      @csrf
      <div class="overflow-x-auto">
        <table class="table-ujion min-w-[800px]">
          <thead>
            <tr>
              <th></th>
              <th>Pertanyaan</th>
              <th>Mapel</th>
              <th>Kurikulum</th>
              <th>Jenjang</th>
            </tr>
          </thead>
          <tbody>
            @php
            $jenjangId = $paket->jenjang_id;
            $bankSoals = \App\Models\GlobalQuestion::where('is_active', true)
            ->where('jenjang_id', $jenjangId)
            ->where('material_mapel', $mapel->nama_mapel)
            ->latest()->take(30)->get();
            $existingIds = $mapel->soals->pluck('global_question_id')->filter()->all();
            @endphp
            @forelse($bankSoals as $gq)
            <tr>
              <td>
                <input type="checkbox" name="global_question_ids[]" value="{{ $gq->id }}" @if(in_array($gq->id,
                $existingIds)) disabled checked @endif>
              </td>
              <td>{!! Str::limit(strip_tags($gq->question_text), 80) !!}</td>
              <td>{{ $gq->material_mapel }}</td>
              <td>{{ $gq->material_curriculum }}</td>
              <td>{{ $gq->jenjang?->nama ?? '-' }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center text-textSecondary">Tidak ada soal Ujion untuk mapel ini.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <button class="btn-primary mt-4" type="submit">Import Soal Terpilih</button>
    </form>
  </section>
  @endif
</div>
@endsection
