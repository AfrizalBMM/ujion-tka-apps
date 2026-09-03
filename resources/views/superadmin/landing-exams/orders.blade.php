@extends('layouts.superadmin')

@section('title', 'Pesanan — ' . ($landingExam->exam?->judul ?? 'Ujian Publik'))

@section('content')
<div class="space-y-6">
    <div class="section-heading">
        <div>
            <h1 class="text-2xl font-bold">Pesanan: {{ $landingExam->exam?->judul ?? '—' }}</h1>
            <p class="text-sm text-textSecondary mt-1">Riwayat pendaftaran & pembayaran siswa untuk ujian publik ini.</p>
        </div>
        <a href="{{ route('superadmin.landing-exams.show', $landingExam) }}" class="btn-secondary">Kembali</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Pendapatan</div>
            <div class="mt-2 text-xl font-black text-emerald-600">Rp{{ number_format((float) $revenue, 0, ',', '.') }}</div>
        </div>
        <div class="metric-card">
            <div class="text-xs font-bold uppercase tracking-widest text-textSecondary">Total Pesanan</div>
            <div class="mt-2 text-xl font-black text-indigo-600">{{ $orders->total() }}</div>
        </div>
    </div>

    <div class="card">
        @if($orders->isEmpty())
            <div class="empty-state text-center py-12">
                <i class="fa-solid fa-receipt text-4xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
                <p class="text-textSecondary">Belum ada pesanan untuk ujian ini.</p>
            </div>
        @else
            <div class="table-ujion overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-700/60">
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">No WA</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Mapel</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Nominal</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-textSecondary">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60 dark:divide-slate-700/40">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 text-sm font-medium">{{ $order->nama }}</td>
                                <td class="px-4 py-3 text-sm">{{ $order->nomor_wa }}</td>
                                <td class="px-4 py-3 text-sm">{{ $order->landingExamMapel?->mapelPaket?->nama_label ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold">Rp{{ number_format((float) $order->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    @switch($order->status)
                                        @case(\App\Models\LandingExamOrder::STATUS_PENDING_PAYMENT)
                                            <span class="badge-warning">Pending</span>
                                            @break
                                        @case(\App\Models\LandingExamOrder::STATUS_PAID)
                                            <span class="badge-success">Dibayar</span>
                                            @break
                                        @case(\App\Models\LandingExamOrder::STATUS_EXAM_STARTED)
                                            <span class="badge-success">Ujian Dimulai</span>
                                            @break
                                        @case(\App\Models\LandingExamOrder::STATUS_EXAM_COMPLETED)
                                            <span class="badge-success">Selesai</span>
                                            @break
                                        @case(\App\Models\LandingExamOrder::STATUS_FAILED)
                                            <span class="badge-danger">Gagal</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-4 py-3 text-xs text-textSecondary">{{ $order->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
