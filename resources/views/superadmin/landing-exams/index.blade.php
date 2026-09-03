@extends('layouts.superadmin')

@section('title', 'Ujian Publik Langsung')

@section('content')
<div class="space-y-6">
    <div class="section-heading">
        <div>
            <h1 class="text-2xl font-bold">Ujian Publik Langsung Siswa</h1>
            <p class="text-sm text-textSecondary mt-1">Kelola ujian yang ditampilkan di landing page untuk siswa yang membeli langsung.</p>
        </div>
        <a href="{{ route('superadmin.landing-exams.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus mr-2"></i>
            Tambah Ujian Publik
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pendapatan</div>
            <div class="mt-2 text-2xl font-black text-emerald-600">Rp{{ number_format((float) $totalRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pesanan</div>
            <div class="mt-2 text-2xl font-black text-indigo-600">{{ $totalOrders }}</div>
        </div>
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Ujian Aktif</div>
            <div class="mt-2 text-2xl font-black text-slate-700 dark:text-slate-200">{{ $landingExams->where('is_active', true)->count() }}</div>
        </div>
    </div>

    <div class="card">
        @if ($landingExams->isEmpty())
            <div class="empty-state text-center py-12">
                <i class="fa-solid fa-store text-4xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
                <p class="text-textSecondary">Belum ada ujian publik. Klik "Tambah Ujian Publik" untuk membuat.</p>
            </div>
        @else
            <div class="table-ujion overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-700/60">
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Ujian</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Jenjang</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Mapel Aktif</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Pesanan</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-widest text-textSecondary">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60 dark:divide-slate-700/40">
                        @forelse ($landingExams as $le)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-sm">{{ $le->exam?->judul ?? '—' }}</div>
                                    <div class="text-xs text-textSecondary mt-0.5">/{{ strtolower($le->jenjang) }}/{{ $le->slug }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $le->jenjang }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $le->mapels->where('is_active', true)->count() }} / {{ $le->mapels->count() }}</td>
                                <td class="px-4 py-3 text-sm font-semibold">{{ $le->paid_orders_count ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    @if ($le->is_active)
                                        <span class="badge-success">Aktif</span>
                                    @else
                                        <span class="badge-warning">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('superadmin.landing-exams.show', $le) }}" class="btn-secondary px-3 py-1.5 text-xs">
                                            <i class="fa-solid fa-eye mr-1"></i>Detail
                                        </a>
                                        <form method="POST" action="{{ route('superadmin.landing-exams.toggle', $le) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-secondary px-3 py-1.5 text-xs">
                                                @if($le->is_active)
                                                    <i class="fa-solid fa-pause mr-1"></i>Nonaktifkan
                                                @else
                                                    <i class="fa-solid fa-play mr-1"></i>Aktifkan
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-textSecondary">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
