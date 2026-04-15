@extends('layouts.superadmin')
@section('title', 'Analisis Ujian')
@section('content')
<div class="max-w-4xl space-y-8">
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Ranking Peserta</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[420px]">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama</th>
                    <th>Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ranking as $i => $p)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td>{{ $p['score'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Distribusi Nilai</h2>
        <div class="table-container">
        <table class="table-ujion w-full min-w-[420px]">
            <thead>
                <tr>
                    <th>Rentang Nilai</th>
                    <th>Jumlah Peserta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($distribution as $range => $count)
                <tr>
                    <td>{{ $range }}</td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="flex flex-col justify-end gap-2 sm:flex-row">
        <a href="#" class="btn-primary w-full sm:w-auto">Export ke Excel</a>
        <a href="#" class="btn-secondary w-full sm:w-auto">Export ke PDF</a>
    </div>
</div>
@endsection
