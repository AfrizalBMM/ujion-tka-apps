@extends('layouts.superadmin')

@section('title', 'Audit Logs')

@section('content')
<div class="space-y-4">
    <div>
        <h1 class="text-2xl">Audit Logs</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Daftar aktivitas terbaru yang tercatat.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="table-ujion">
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
                    @if($auditLogs->isNotEmpty())
                        @foreach ($auditLogs as $log)
                            <tr>
                                <td class="text-xs text-muted dark:text-slate-400">{{ $log->created_at }}</td>
                                <td class="font-bold">{{ $log->method }}</td>
                                <td class="text-textSecondary dark:text-slate-300">{{ $log->path }}</td>
                                <td class="text-textSecondary dark:text-slate-300">{{ $log->ip_address }}</td>
                                <td class="text-xs text-muted dark:text-slate-400">{{ $log->route_name }}</td>
                                <td class="text-xs text-muted dark:text-slate-400">{{ $log->user_id ?: '-' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-muted dark:text-slate-400">Belum ada data.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
