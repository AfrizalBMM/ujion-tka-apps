@extends('layouts.superadmin')
@section('title', 'Ujian')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Manajemen Ujian Per Paket Lengkap</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Pilih satu paket lengkap per jenjang, lalu sistem akan menyiapkan token untuk 4 bagian baku secara otomatis.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="card p-4">
            <form method="POST" action="{{ route('superadmin.exams.store') }}">
                @csrf
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="text-xs font-bold">Paket Soal</label>
                        <select name="paket_soal_id" class="input w-full" required>
                            <option value="">Pilih paket</option>
                            @foreach($paketSoals as $paket)
                                <option value="{{ $paket->id }}">{{ $paket->nama }} &middot; {{ $paket->jenjang?->kode }} &middot; {{ $paket->tahun_ajaran }}</option>
                            @endforeach
                        </select>
                    </div>
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
                        <label class="text-xs font-bold">Timer (menit, opsional)</label>
                        <input type="number" name="timer" class="input w-full" placeholder="Auto dari total 4 bagian">
                    </div>
                    <div>
                        <label class="text-xs font-bold">Status</label>
                        <select name="status" class="input w-full" required>
                            <option value="draft">Draft</option>
                            <option value="terbit">Terbit</option>
                        </select>
                    </div>
                </div>
                <button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Buat Ujian</button>
            </form>
        </div>

        <div class="card bg-gradient-to-r from-blue-600 to-indigo-700 border-none p-4 text-white shadow-glow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-bold text-lg">Import Ujian</div>
                    <p class="mt-1 text-xs text-blue-100">Download template Excel, isi data ujian, lalu upload kembali.</p>
                </div>
                <i class="fa-solid fa-file-arrow-up text-2xl text-white/80"></i>
            </div>
            <div class="mt-4 flex flex-col gap-3">
                <form method="POST" action="{{ route('superadmin.exams.import') }}" enctype="multipart/form-data" class="flex flex-col gap-2">
                    @csrf
                    <input class="input border-white/20 bg-white/10 text-white file:mr-3 file:rounded-lg file:border-0 file:bg-white/20 file:px-3 file:py-2 file:text-white" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                    <button class="btn-primary border-none bg-white text-blue-700 hover:bg-blue-50" type="submit">
                        <i class="fa-solid fa-upload mr-2"></i>Import File
                    </button>
                </form>
                <a href="{{ route('superadmin.exams.template') }}" class="btn-secondary border-white/30 bg-transparent text-white hover:bg-white/10">
                    <i class="fa-solid fa-file-excel mr-2"></i>Download Template Excel
                </a>
            </div>
        </div>
    </div>

    <section class="card p-4">
        @php
            $activeFilterCount = collect([
                ($search ?? '') !== '' ? $search : null,
                $status ?? null,
                $jenjangId ?? null,
            ])->filter()->count();
        @endphp
        <div class="mb-5 flex items-center gap-3">
            <div class="font-bold text-lg">Filter Ujian</div>
            @if ($activeFilterCount > 0)
                <span class="badge-info text-xs">{{ $activeFilterCount }} filter aktif</span>
                <a href="{{ route('superadmin.exams.index') }}" class="flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700">
                    <i class="fa-solid fa-xmark"></i> Reset
                </a>
            @endif
        </div>
        <form method="GET" action="{{ route('superadmin.exams.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_1fr_1fr_auto]">
            <div>
                <label class="text-xs font-bold">Cari Ujian</label>
                <input type="text" name="search" class="input mt-1 w-full" value="{{ $search ?? '' }}" placeholder="Judul ujian, paket, tahun ajaran, atau jenjang">
            </div>
            <div>
                <label class="text-xs font-bold">Jenjang</label>
                <select name="jenjang_id" class="input mt-1 w-full">
                    <option value="">Semua Jenjang</option>
                    @foreach($jenjangs as $jenjang)
                        <option value="{{ $jenjang->id }}" @selected((string) ($jenjangId ?? '') === (string) $jenjang->id)>{{ $jenjang->kode }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-bold">Status</label>
                <select name="status" class="input mt-1 w-full">
                    <option value="">Semua Status</option>
                    <option value="draft" @selected(($status ?? '') === 'draft')>Draft</option>
                    <option value="terbit" @selected(($status ?? '') === 'terbit')>Terbit</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button class="btn-primary w-full lg:w-auto" type="submit">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari
                </button>
                <a href="{{ route('superadmin.exams.index') }}" class="btn-secondary w-full text-center lg:w-auto">Reset</a>
            </div>
        </form>
    </section>

    <div class="card p-4">
        <div class="table-container">
            <table class="table-ujion w-full min-w-[760px]">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Paket</th>
                        <th>Jenjang</th>
                        <th>Tanggal Terbit</th>
                        <th>Max</th>
                        <th>Token per Bagian</th>
                        <th>Status</th>
                        <th>Aktif</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $exam)
                    <tr>
                        <td>{{ $exam->judul }}</td>
                        <td>{{ $exam->paketSoal?->nama ?? '-' }}</td>
                        <td>{{ $exam->paketSoal?->jenjang?->kode ?? '-' }}</td>
                        <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
                        <td>{{ $exam->max_peserta }}</td>
                        <td>
                            @if($exam->examMapelTokens->isEmpty())
                                <span class="badge-warning text-xs">Belum ada token</span>
                            @else
                                <div class="space-y-1.5">
                                    @foreach($exam->examMapelTokens as $mt)
                                        <div class="flex items-center gap-2">
                                            <span class="w-20 truncate text-[10px] font-bold text-textSecondary">
                                                {{ $mt->mapelPaket?->nama_label ?? 'Mapel' }}
                                            </span>
                                            <span id="token-sa-{{ $mt->id }}" class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-sm font-bold tracking-widest text-primary">
                                                {{ $mt->token }}
                                            </span>
                                            <button type="button" id="copy-sa-{{ $mt->id }}" onclick="copyMapelToken('{{ $mt->token }}', {{ $mt->id }})" class="btn-secondary px-2 py-1 text-[11px]">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>{{ $exam->status }}</td>
                        <td>
                            @if($exam->is_active)
                                <span class="badge-success">Aktif</span>
                            @else
                                <span class="badge-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('superadmin.exams.toggle', $exam) }}">@csrf<button class="btn-secondary px-2 py-1 text-xs">Toggle</button></form>
                                <form method="POST" action="{{ route('superadmin.exams.destroy', $exam) }}">@csrf<button class="btn-danger px-2 py-1 text-xs">Hapus</button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-textSecondary">Belum ada ujian yang cocok dengan filter ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyMapelToken(token, id) {
    const doCopy = () => {
        const btn = document.getElementById('copy-sa-' + id);
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.classList.add('text-emerald-600');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('text-emerald-600'); }, 2000);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(token).then(doCopy);
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = token;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            doCopy();
        } catch (err) {
            console.error('Fallback copy failed', err);
        }
        document.body.removeChild(textArea);
    }
}
</script>
@endpush
@endsection
