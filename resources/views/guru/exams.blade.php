@extends('layouts.guru')
@section('title', 'Simulasi Ujian & Survey')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Simulasi Paket Ujian</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">
            Gunakan halaman ini untuk mencoba alur siswa per bagian paket, mengecek kesiapan paket lengkap, dan melihat pembahasan
            sebagai bahan evaluasi sebelum dipakai untuk pembinaan siswa.
        </p>
    </div>

    <section class="card p-4">
        @php
            $activeFilterCount = collect([
                ($search ?? '') !== '' ? $search : null,
            ])->filter()->count();
        @endphp
        <div class="mb-5 flex items-center gap-3">
            <div class="font-bold text-lg">Filter Simulasi</div>
            @if ($activeFilterCount > 0)
                <span class="badge-info text-xs">{{ $activeFilterCount }} filter aktif</span>
                <a href="{{ route('guru.exams') }}" class="flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700">
                    <i class="fa-solid fa-xmark"></i> Reset
                </a>
            @endif
        </div>
        <form method="GET" action="{{ route('guru.exams') }}" class="grid gap-4 md:grid-cols-[minmax(0,2fr)_auto]">
            <div>
                <label class="text-xs font-bold uppercase tracking-wide text-muted">Cari Ujian / Paket</label>
                <input type="text" name="search" class="input mt-1 w-full" value="{{ $search ?? '' }}" placeholder="Judul ujian atau nama paket">
            </div>
            <div class="flex items-end gap-3">
                <button class="btn-primary w-full md:w-auto" type="submit">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari
                </button>
                <a href="{{ route('guru.exams') }}" class="btn-secondary w-full text-center md:w-auto">Reset</a>
            </div>
        </form>
    </section>

    <form method="POST" action="{{ route('guru.exams.join') }}" class="card mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label class="text-xs font-bold uppercase tracking-wide text-muted">Token simulasi</label>
            <input name="token" class="input w-full" placeholder="Masukkan token ujian untuk mencoba alur siswa" required>
        </div>
        <button class="btn-primary w-full sm:w-auto" type="submit">
            <i class="fa-solid fa-play"></i>
            Mulai Simulasi
        </button>
    </form>

    <div class="card mb-4 p-4">
        <div class="mb-2">
            <h2 class="font-semibold">Ujian yang Bisa Dicoba</h2>
            <p class="mt-1 text-sm text-textSecondary dark:text-slate-400">Daftar ujian aktif yang bisa guru buka untuk melihat pengalaman siswa secara langsung per bagian paket.</p>
        </div>
        <div class="table-container">
            <table class="table-ujion w-full min-w-[640px]">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center">Token per Bagian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($available as $exam)
                    <tr>
                        <td>{{ $exam->judul }}</td>
                        <td>{{ $exam->paketSoal?->nama ?? '-' }}</td>
                        <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
                        <td>{{ $exam->status }}</td>
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
                                            <span id="token-guru-{{ $mt->id }}" class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-sm font-bold tracking-widest text-primary">
                                                {{ $mt->token }}
                                            </span>
                                            <button type="button" id="copy-guru-{{ $mt->id }}" onclick="guruCopyMapelToken('{{ $mt->token }}', {{ $mt->id }})" title="Salin token" class="btn-secondary px-2 py-1 text-[11px] transition-all">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-textSecondary">Belum ada ujian aktif yang sesuai jenjang atau filter Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4 p-4">
        <div class="mb-2">
            <h2 class="font-semibold">Riwayat Simulasi</h2>
            <p class="mt-1 text-sm text-textSecondary dark:text-slate-400">Simulasi yang sudah pernah Anda kerjakan beserta hasil dan pembahasannya.</p>
        </div>
        <div class="table-container">
            <table class="table-ujion w-full min-w-[620px]">
                <thead>
                    <tr>
                        <th>Judul Simulasi</th>
                        <th>Paket</th>
                        <th>Skor</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $h)
                    <tr>
                        <td>{{ $h['judul'] }}</td>
                        <td>{{ $h['paket'] }}</td>
                        <td>{{ $h['skor'] }}</td>
                        <td>{{ $h['status'] }}</td>
                        <td><a href="{{ route('guru.exams.result', $h['exam_id']) }}" class="btn-secondary">Lihat Hasil</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-gray-400">Belum ada riwayat simulasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function guruCopyMapelToken(token, id) {
    const doCopy = () => {
        const btn = document.getElementById('copy-guru-' + id);
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.classList.add('text-emerald-600');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('text-emerald-600');
        }, 2000);
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
