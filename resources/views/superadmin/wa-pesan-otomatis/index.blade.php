@extends('layouts.superadmin')

@section('title', 'Pesan Otomatis')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Pesan Otomatis</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Kelola copywriting untuk balasan bot, notifikasi pembayaran, dan pesan event. Placeholder yang didukung: <span class="font-mono">{name}</span>, <span class="font-mono">{token}</span>, <span class="font-mono">{reason}</span>, <span class="font-mono">{exam_title}</span>, <span class="font-mono">{exam_date}</span>, <span class="font-mono">{login_url}</span>.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.wa-templates.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus mr-2"></i>
                Tambah Template
            </a>
        </div>
    </div>

    <div class="card">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Daftar</div>
        <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Template pesan</div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-muted">
                        <th class="py-2 pr-4">Key</th>
                        <th class="py-2 pr-4">Judul</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr class="border-t border-slate-200/60 dark:border-slate-700/60">
                            <td class="py-3 pr-4 font-mono text-xs">{{ $template->key }}</td>
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $template->title }}</div>
                                @if (!blank($template->description))
                                    <div class="mt-1 text-xs text-textSecondary dark:text-slate-300">{{ $template->description }}</div>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                @if ($template->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-500/10 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('superadmin.wa-templates.edit', $template) }}" class="btn-secondary">
                                        <i class="fa-solid fa-pen mr-2"></i>
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ route('superadmin.wa-templates.toggle', $template) }}">
                                        @csrf
                                        <button type="submit" class="btn-secondary">
                                            <i class="fa-solid fa-toggle-{{ $template->is_active ? 'on' : 'off' }} mr-2"></i>
                                            {{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.wa-templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?')">
                                        @csrf
                                        <button type="submit" class="btn-danger">
                                            <i class="fa-solid fa-trash mr-2"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-slate-200/60 dark:border-slate-700/60">
                            <td colspan="4" class="py-6 text-center text-textSecondary dark:text-slate-300">
                                Belum ada template. Klik "Tambah Template" untuk mulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
