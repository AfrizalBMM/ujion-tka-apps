@extends('layouts.superadmin')

@section('title', 'Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Paket Soal Per Jenjang</span>
        <h1 class="page-title">Kelola paket TKA yang terdiri dari dua mapel dan struktur soal baru.</h1>
        <p class="page-description">Paket aktif per jenjang menjadi sumber utama konten untuk ujian siswa.</p>
        <div class="page-actions">
            <a href="{{ route('superadmin.paket-soal.create') }}" class="btn-primary">Paket Baru</a>
        </div>
    </section>

    <section class="card">
        <form class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Filter Jenjang</label>
                <select name="jenjang_id" class="input">
                    <option value="">Semua jenjang</option>
                    @foreach($jenjangs as $jenjang)
                        <option value="{{ $jenjang->id }}" @selected(request('jenjang_id') == $jenjang->id)>{{ $jenjang->kode }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group">
                <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Filter Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" class="input" value="{{ request('tahun_ajaran') }}" placeholder="2025/2026">
            </div>
            <div class="flex items-end">
                <button class="btn-secondary w-full" type="submit">Terapkan</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-container">
            <table class="table-ujion min-w-[880px]">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th>Jenjang</th>
                        <th>Tahun</th>
                        <th>Mapel</th>
                        <th>Status</th>
                        <th>Dibuat Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paketSoals as $paket)
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $paket->nama }}</div>
                                <div class="text-xs text-textSecondary">ID #{{ $paket->id }}</div>
                            </td>
                            <td>{{ $paket->jenjang?->kode }}</td>
                            <td>{{ $paket->tahun_ajaran }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($paket->mapelPakets as $mapel)
                                        <span class="badge-info">{{ $mapel->nama_label }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($paket->is_active)
                                    <span class="badge-success">Aktif</span>
                                @else
                                    <span class="badge-warning">Draft</span>
                                @endif
                            </td>
                            <td>{{ $paket->createdBy?->name ?? '-' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('superadmin.paket-soal.show', $paket) }}" class="btn-secondary px-3 py-2 text-xs">Detail</a>
                                    <a href="{{ route('superadmin.paket-soal.edit', $paket) }}" class="btn-secondary px-3 py-2 text-xs">Edit</a>
                                    <form method="POST" action="{{ route('superadmin.paket-soal.toggle', $paket) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn-primary px-3 py-2 text-xs" type="submit">{{ $paket->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('superadmin.paket-soal.destroy', $paket) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger px-3 py-2 text-xs" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-textSecondary">Belum ada paket soal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
