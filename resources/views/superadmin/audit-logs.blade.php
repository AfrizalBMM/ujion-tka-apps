@extends('layouts.superadmin')

@section('title', 'Audit Logs')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Audit Logs</h1>
            <p class="mt-2 text-textSecondary dark:text-slate-300">Rekam aktivitas superadmin & guru. Log otomatis dihapus setelah 30 hari.</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('superadmin.audit-logs.cleanup') }}" onsubmit="return false;" data-cleanup-form>
                @csrf
                <input type="hidden" name="mode" value="older_than_30d">
                <button type="button" class="btn-secondary whitespace-nowrap" data-cleanup-open data-cleanup-mode="older_than_30d">
                    <i class="fa-solid fa-broom mr-2"></i>
                    Hapus Log >30 Hari
                </button>
            </form>
        </div>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div class="grid grid-cols-2 divide-slate-200 dark:divide-slate-800 lg:grid-cols-4 lg:divide-x">
            <div class="flex items-center gap-3 p-3 lg:p-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                    <i class="fa-solid fa-database text-sm text-slate-500 dark:text-slate-300"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-muted">Total Log</div>
                    <div class="text-lg font-bold leading-tight text-slate-900 dark:text-slate-100">{{ number_format($summary['total']) }}</div>
                    <div class="truncate text-[10px] text-muted">30 hari terakhir</div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 lg:p-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10">
                    <i class="fa-solid fa-calendar-day text-sm text-blue-600"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-muted">Hari Ini</div>
                    <div class="text-lg font-bold leading-tight text-blue-700">{{ number_format($summary['today']) }}</div>
                    <div class="truncate text-[10px] text-muted">Sejak tengah malam</div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 lg:p-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-500/10">
                    <i class="fa-solid fa-user-pen text-sm text-amber-600"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-muted">User Paling Aktif</div>
                    <div class="truncate text-sm font-bold leading-tight text-slate-900 dark:text-slate-100">{{ $summary['top_user']['name'] ?? '-' }}</div>
                    <div class="truncate text-[10px] text-muted">{{ $summary['top_user'] ? number_format($summary['top_user']['total']).' aksi' : 'Belum ada data' }}</div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 lg:p-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-500/10">
                    <i class="fa-solid fa-bolt text-sm text-purple-600"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-muted">Aksi Terbanyak</div>
                    <div class="truncate text-sm font-bold leading-tight text-slate-900 dark:text-slate-100" title="{{ $summary['top_action']['name'] ?? '' }}">{{ $summary['top_action']['name'] ?? '-' }}</div>
                    <div class="truncate text-[10px] text-muted">{{ $summary['top_action'] ? number_format($summary['top_action']['total']).'x dipanggil' : 'Belum ada data' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.audit-logs.index') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <input type="text" name="q" value="{{ $filters['q'] }}" class="input w-full py-1.5 text-sm sm:w-auto sm:flex-1 sm:min-w-[180px]" placeholder="Cari path, route, IP...">
            <div class="ssd-wrap" style="width: 130px;">
                <input type="hidden" name="method" value="{{ $filters['method'] }}">
                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                    <span class="ssd-label truncate">{{ $filters['method'] ?: 'Semua method' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari method..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option{{ $filters['method'] === '' ? ' ssd-selected' : '' }}" data-value="">Semua method</div>
                        @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                            <div class="ssd-option{{ $filters['method'] === $method ? ' ssd-selected' : '' }}" data-value="{{ $method }}">{{ $method }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="ssd-wrap" style="max-width: 170px;">
                <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                    <span class="ssd-label truncate">{{ $filters['user_id'] ? ($users->firstWhere('id', (int) $filters['user_id'])?->name ?? 'User '.$filters['user_id']) : 'Semua user' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari user..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option{{ (int) $filters['user_id'] === 0 ? ' ssd-selected' : '' }}" data-value="">Semua user</div>
                        @foreach ($users as $user)
                            <div class="ssd-option{{ (int) $filters['user_id'] === $user->id ? ' ssd-selected' : '' }}" data-value="{{ $user->id }}">{{ $user->name }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="input w-full py-1.5 text-sm sm:w-auto" title="Dari tanggal">
            <span class="hidden text-xs text-muted sm:inline">–</span>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="input w-full py-1.5 text-sm sm:w-auto" title="Sampai tanggal">
            <div class="flex gap-2">
                <button type="submit" class="btn-primary px-3 py-1.5 text-sm">Filter</button>
                <a href="{{ route('superadmin.audit-logs.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Reset</a>
            </div>
        </form>

        <div class="table-container">
            <table class="table-ujion min-w-[860px]">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Method</th>
                        <th>Path</th>
                        <th>IP</th>
                        <th>Route</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @if($auditLogs->isNotEmpty())
                        @foreach ($auditLogs as $log)
                            <tr>
                                <td class="text-xs text-muted dark:text-slate-400">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                                <td>
                                    @if ($log->method === 'GET')
                                        <span class="badge-info">GET</span>
                                    @elseif (in_array($log->method, ['POST', 'PUT', 'PATCH']))
                                        <span class="badge-warning">{{ $log->method }}</span>
                                    @else
                                        <span class="badge-danger">{{ $log->method }}</span>
                                    @endif
                                </td>
                                <td class="font-mono text-xs text-textSecondary dark:text-slate-300">{{ $log->path ?: '/' }}</td>
                                <td class="text-xs text-textSecondary dark:text-slate-300">{{ $log->ip_address ?: '-' }}</td>
                                <td class="text-xs text-muted dark:text-slate-400">
                                    <div>{{ $log->route_name ?: '-' }}</div>
                                    <div class="mt-1 text-[11px]">{{ $log->user_agent ?: '-' }}</div>
                                </td>
                                <td class="text-xs text-muted dark:text-slate-400">
                                    @if ($log->user)
                                        <div class="font-semibold text-slate-700 dark:text-slate-200">{{ $log->user->name }}</div>
                                        <div class="mt-0.5">{{ ucfirst($log->user->role) }} · ID {{ $log->user_id }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted dark:text-slate-400">Tidak ada log yang cocok dengan filter.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($auditLogs->isNotEmpty() && method_exists($auditLogs, 'links'))
            <div class="mt-4">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>

<div id="cleanup-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase tracking-wide text-muted">Bersihkan Audit Log</div>
                <div id="cleanup-modal-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">Hapus log lebih tua dari 30 hari</div>
            </div>
            <button type="button" class="btn-secondary" data-cleanup-close>Tutup</button>
        </div>

        <p id="cleanup-modal-description" class="mt-3 text-sm text-textSecondary dark:text-slate-300">Tindakan ini tidak bisa dibatalkan.</p>

        <form method="POST" action="{{ route('superadmin.audit-logs.cleanup') }}" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="mode" value="older_than_30d" id="cleanup-mode-input">

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3 dark:border-rose-500/20 dark:bg-rose-500/10">
                <input type="checkbox" id="cleanup-confirm" class="mt-0.5 h-4 w-4 rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                <span class="text-sm font-semibold text-rose-900 dark:text-rose-100">Saya mengerti bahwa log yang dihapus tidak bisa dikembalikan.</span>
            </label>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn-secondary" data-cleanup-close>Batal</button>
                <button type="submit" class="btn-danger" id="cleanup-submit" disabled>
                    <i class="fa-solid fa-trash mr-2"></i>
                    Hapus Log
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const cleanupModal = document.getElementById('cleanup-modal');
    const cleanupTitle = document.getElementById('cleanup-modal-title');
    const cleanupDescription = document.getElementById('cleanup-modal-description');
    const cleanupModeInput = document.getElementById('cleanup-mode-input');
    const cleanupConfirm = document.getElementById('cleanup-confirm');
    const cleanupSubmit = document.getElementById('cleanup-submit');

    document.querySelectorAll('[data-cleanup-open]').forEach((button) => {
        button.addEventListener('click', () => {
            cleanupModeInput.value = button.dataset.cleanupMode || 'older_than_30d';
            const isAll = cleanupModeInput.value === 'all';

            cleanupTitle.textContent = isAll ? 'Hapus SEMUA audit log' : 'Hapus log lebih tua dari 30 hari';
            cleanupDescription.textContent = isAll
                ? 'Seluruh rekam audit log akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.'
                : 'Log yang lebih tua dari 30 hari akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.';

            cleanupConfirm.checked = false;
            cleanupSubmit.disabled = true;
            cleanupModal?.classList.remove('hidden');
            cleanupModal?.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-cleanup-close]').forEach((button) => {
        button.addEventListener('click', () => {
            cleanupModal?.classList.add('hidden');
            cleanupModal?.classList.remove('flex');
        });
    });

    cleanupModal?.addEventListener('click', (event) => {
        if (event.target === cleanupModal) {
            cleanupModal.classList.add('hidden');
            cleanupModal.classList.remove('flex');
        }
    });

    cleanupConfirm?.addEventListener('change', () => {
        cleanupSubmit.disabled = ! cleanupConfirm.checked;
    });
</script>
@endsection
