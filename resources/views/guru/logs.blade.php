@extends('layouts.guru')
@section('title', 'Log Aktivitas')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold mb-4">Log Aktivitas Pribadi</h1>
    <div class="card p-4">
        <table class="table-ujion w-full">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>IP</th>
                    <th>Device</th>
                    <th>Route</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td>{{ $log->user_agent ?? '-' }}</td>
                    <td>{{ $log->route_name }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection