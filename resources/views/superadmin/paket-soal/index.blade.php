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
                <div class="ssd-wrap mt-1">
                    <input type="hidden" name="jenjang_id" value="{{ request('jenjang_id') }}">
                    <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                        @php $selectedJenjang = $jenjangs->firstWhere('id', request('jenjang_id')) @endphp
                        <span class="ssd-label">{{ $selectedJenjang ? $selectedJenjang->kode : 'Semua jenjang' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                    </button>
                    <div class="ssd-panel">
                        <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                        <div class="ssd-list">
                            <div class="ssd-option{{ !request('jenjang_id') ? ' ssd-selected' : '' }}" data-value="">Semua jenjang</div>
                            @foreach($jenjangs as $jenjang)
                                <div class="ssd-option{{ request('jenjang_id') == $jenjang->id ? ' ssd-selected' : '' }}" data-value="{{ $jenjang->id }}">{{ $jenjang->kode }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
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
                            <td class="text-center">
                                <div class="relative inline-block" data-dropdown>
                                    <button type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-xl border border-border bg-white text-textSecondary hover:border-primary/40 hover:text-primary transition dark:bg-slate-800"
                                            data-dropdown-trigger
                                            title="Aksi">
                                        <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
                                    </button>

                                    <div class="absolute right-0 top-full z-50 mt-1.5 hidden w-44 rounded-2xl border border-border bg-white shadow-xl dark:bg-slate-900"
                                         data-dropdown-menu>
                                        <div class="p-1.5 space-y-0.5">

                                            <a href="{{ route('superadmin.paket-soal.show', $paket) }}"
                                               class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 transition">
                                                <i class="fa-solid fa-eye w-4 text-blue-500"></i>
                                                Detail
                                            </a>

                                            <a href="{{ route('superadmin.paket-soal.edit', $paket) }}"
                                               class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 transition">
                                                <i class="fa-solid fa-pen-to-square w-4 text-amber-500"></i>
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('superadmin.paket-soal.toggle', $paket) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 transition">
                                                    @if($paket->is_active)
                                                        <i class="fa-solid fa-toggle-off w-4 text-slate-400"></i>
                                                        Nonaktifkan
                                                    @else
                                                        <i class="fa-solid fa-toggle-on w-4 text-emerald-500"></i>
                                                        Aktifkan
                                                    @endif
                                                </button>
                                            </form>

                                            <div class="my-1 border-t border-border"></div>

                                            <form method="POST" action="{{ route('superadmin.paket-soal.destroy', $paket) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        data-confirm="Hapus paket soal '{{ $paket->nama }}'? Semua soal dan mapel di dalamnya akan dihapus permanen."
                                                        data-confirm-title="Hapus Paket Soal"
                                                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                                    <i class="fa-solid fa-trash-can w-4"></i>
                                                    Hapus
                                                </button>
                                            </form>

                                        </div>
                                    </div>
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

@push('scripts')
<script>
(function () {
    // Toggle dropdown saat klik trigger
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-dropdown-trigger]');
        const outside = !e.target.closest('[data-dropdown]');

        // Tutup semua dropdown dulu
        document.querySelectorAll('[data-dropdown-menu]').forEach(m => m.classList.add('hidden'));

        if (trigger) {
            const menu = trigger.closest('[data-dropdown]')?.querySelector('[data-dropdown-menu]');
            if (menu) menu.classList.remove('hidden');
        }
    });

    // Tutup saat tekan ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-dropdown-menu]').forEach(m => m.classList.add('hidden'));
        }
    });
})();
</script>
@endpush
