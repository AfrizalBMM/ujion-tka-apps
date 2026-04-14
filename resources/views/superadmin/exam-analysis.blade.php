@extends('layouts.superadmin')
@section('title', 'Analisis Ujian')
@section('content')
<div class="max-w-3xl mx-auto mt-8 space-y-8">
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Ranking Peserta</h2>
        <table class="table-ujion w-full">
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
    <div class="card p-6">
        <h2 class="font-bold text-xl mb-4">Distribusi Nilai</h2>
        <table class="table-ujion w-full">
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
    <div class="flex justify-end">
        <a href="#" class="btn-primary">Export ke Excel</a>
        <a href="#" class="btn-secondary ml-2">Export ke PDF</a>
    </div>
</div>
@endsection