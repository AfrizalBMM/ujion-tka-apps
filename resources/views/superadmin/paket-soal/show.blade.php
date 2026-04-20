@extends('layouts.superadmin')

@section('title', 'Detail Paket Soal')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->jenjang?->kode }} &middot; {{ $paket->tahun_ajaran }}</span>
        <h1 class="page-title">{{ $paket->nama }}</h1>
        <p class="page-description">Akses tiap mapel untuk menyusun soal, bacaan, dan struktur paket ujian siswa.</p>
        <div class="page-actions">
            <a href="{{ route('superadmin.paket-soal.edit', $paket) }}" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Edit Metadata</a>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        @foreach($paket->mapelPakets as $mapel)
            <article class="card">
                <div class="section-heading mb-5">
                    <div>
                        <h2 class="section-title">{{ $mapel->nama_label }}</h2>
                        <p class="section-description">{{ $mapel->soals->count() }}/{{ $mapel->jumlah_soal }} soal &middot; {{ $mapel->durasi_menit }} menit</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('superadmin.soal.create', [$paket, $mapel]) }}"
                           class="btn-primary px-4 py-2 text-xs">
                            <i class="fa-solid fa-pen-to-square mr-1.5"></i>Buat Manual
                        </a>
                        <a href="{{ route('superadmin.soal.bank-builder', [$paket, $mapel]) }}"
                           class="btn-secondary px-4 py-2 text-xs">
                            <i class="fa-solid fa-layer-group mr-1.5"></i>Dari Bank Soal
                        </a>
                    </div>
                </div>
                <form class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-3 dark:border-slate-800 dark:bg-slate-900/60" method="POST" action="{{ route('superadmin.mapel.update', [$paket, $mapel]) }}">
                    @csrf
                    @method('PUT')
                    <div class="input-group">
                        <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Jumlah Soal</label>
                        <input type="number" name="jumlah_soal" class="input" value="{{ old('jumlah_soal', $mapel->jumlah_soal) }}" min="1" max="200" required>
                    </div>
                    <div class="input-group">
                        <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Durasi (menit)</label>
                        <input type="number" name="durasi_menit" class="input" value="{{ old('durasi_menit', $mapel->durasi_menit) }}" min="1" max="600" required>
                    </div>
                    <div class="input-group">
                        <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Urutan</label>
                        <input type="number" name="urutan" class="input" value="{{ old('urutan', $mapel->urutan) }}" min="1" max="10" required>
                    </div>
                    <div class="md:col-span-3">
                        <button class="btn-secondary px-4 py-2 text-xs" type="submit">Simpan Konfigurasi</button>
                    </div>
                </form>
                <div class="space-y-3">
                    @forelse($mapel->soals->take(5) as $soal)
                        <div class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold">Soal {{ $soal->nomor_soal }}</div>
                                <span class="badge-info">{{ str($soal->tipe_soal)->replace('_', ' ')->headline() }}</span>
                            </div>
                            <p class="mt-2 text-sm text-textSecondary">{{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 120) }}</p>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada soal pada mapel ini.</div>
                    @endforelse
                </div>
            </article>
        @endforeach
    </section>

    {{-- ── Section Token Ujian ─────────────────────────────────────── --}}
    <section class="card">
        <div class="section-heading mb-5">
            <div>
                <h2 class="section-title flex items-center gap-2">
                    <i class="fa-solid fa-key text-primary"></i>
                    Token Akses Ujian
                </h2>
                <p class="section-description">
                    Daftar sesi ujian yang menggunakan paket ini. Bagikan token kepada guru agar bisa melakukan simulasi.
                </p>
            </div>
            <a href="{{ route('superadmin.exams.index') }}" class="btn-secondary px-4 py-2 text-xs">
                <i class="fa-solid fa-plus mr-1.5"></i>Buat Ujian Baru
            </a>
        </div>

        @if($paket->exams->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-triangle-exclamation mb-2 text-2xl text-amber-400"></i>
                <p>Belum ada ujian yang dibuat dari paket ini.</p>
                <a href="{{ route('superadmin.exams.index') }}" class="btn-primary mt-3 px-4 py-2 text-xs">Buat Ujian Pertama</a>
            </div>
        @else
            <div class="table-container">
                <table class="table-ujion min-w-[700px]">
                    <thead>
                        <tr>
                            <th>Judul Ujian</th>
                            <th>Tanggal Terbit</th>
                            <th>Max Peserta</th>
                            <th>Status</th>
                            <th class="text-center">Token Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paket->exams as $exam)
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ $exam->judul }}</div>
                                    <div class="text-xs text-textSecondary">ID #{{ $exam->id }}</div>
                                </td>
                                <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
                                <td>{{ $exam->max_peserta }} peserta</td>
                                <td>
                                    @if($exam->status === 'terbit' && $exam->is_active)
                                        <span class="badge-success">Aktif & Terbit</span>
                                    @elseif($exam->status === 'terbit')
                                        <span class="badge-warning">Terbit (Nonaktif)</span>
                                    @else
                                        <span class="badge-info">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    @if($exam->examMapelTokens->isEmpty())
                                        <span class="badge-warning text-xs">Belum ada token</span>
                                    @else
                                        <div class="space-y-1.5 flex flex-col items-center">
                                            @foreach($exam->examMapelTokens as $mt)
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-textSecondary w-16 truncate text-right">
                                                        {{ $mt->mapelPaket?->nama_label ?? 'Mapel' }}
                                                    </span>
                                                    <span id="token-sa-detail-{{ $mt->id }}"
                                                          class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-xs font-bold tracking-widest text-primary">
                                                        {{ $mt->token }}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        id="copy-sa-detail-{{ $mt->id }}"
                                                        onclick="copyMapelTokenDetail('{{ $mt->token }}', {{ $mt->id }})"
                                                        class="btn-secondary px-2 py-1 text-[10px]">
                                                        <i class="fa-solid fa-copy"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
function copyMapelTokenDetail(token, id) {
    navigator.clipboard.writeText(token).then(() => {
        const btn = document.getElementById('copy-sa-detail-' + id);
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.classList.add('text-emerald-600');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('text-emerald-600');
        }, 2000);
    });
}
</script>
@endpush
@endsection
