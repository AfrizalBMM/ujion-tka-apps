@extends('layouts.superadmin')

@section('title', 'Manajemen Guru & Operator')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Manajemen Guru & Akses</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Aktivasi manual, refresh token, dan suspend akun guru.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="table-ujion min-w-[760px]">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Status Akun</th>
                        <th>Token Akses</th>
                        <th class="text-right">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($teachers) > 0)
                    @foreach ($teachers as $teacher)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <td>
                                <div class="font-bold">{{ $teacher->name }}</div>
                                <div class="text-xs text-muted">Terdaftar: {{ $teacher->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="text-textSecondary dark:text-slate-300">{{ $teacher->email }}</td>
                            <td>
                                @if ($teacher->account_status === \App\Models\User::STATUS_ACTIVE)
                                    <span class="badge-success"><i class="fa-solid fa-circle-check mr-1"></i> Aktif</span>
                                @elseif ($teacher->account_status === \App\Models\User::STATUS_PENDING)
                                    <span class="badge-warning"><i class="fa-solid fa-clock mr-1"></i> Menunggu Aktivasi</span>
                                @else
                                    <span class="badge-danger"><i class="fa-solid fa-ban mr-1"></i> Ditangguhkan</span>
                                @endif
                            </td>
                            <td class="font-mono text-sm text-blue-600 dark:text-blue-400">
                                @if($teacher->access_token)
                                    <span>
                                        {{ str_repeat('•', max(strlen($teacher->access_token) - 4, 0)) }}{{ substr($teacher->access_token, -4) }}
                                    </span>
                                @else
                                    <span class="text-muted italic">Belum aktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($teacher->account_status !== \App\Models\User::STATUS_ACTIVE)
                                    <form method="POST" action="{{ route('superadmin.teachers.activate', $teacher) }}">
                                        @csrf
                                        <button class="btn-primary p-2" type="submit" title="Aktifkan Akun">
                                            <i class="fa-solid fa-user-check"></i>
                                        </button>
                                    </form>
                                    @endif
                                    
                                    <form method="POST" action="{{ route('superadmin.teachers.refresh-token', $teacher) }}">
                                        @csrf
                                        <button class="btn-secondary p-2" type="submit" title="Ganti Token Baru">
                                            <i class="fa-solid fa-rotate"></i>
                                        </button>
                                    </form>

                                    @if($teacher->account_status !== \App\Models\User::STATUS_SUSPEND)
                                    <form method="POST" action="{{ route('superadmin.teachers.suspend', $teacher) }}">
                                        @csrf
                                        <button class="btn-danger p-2" type="submit" data-confirm="Tangguhkan akses guru ini?" title="Suspend">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <i class="fa-solid fa-users-slash text-4xl text-slate-200 mb-3 block"></i>
                                <span class="text-muted dark:text-slate-400 italic text-lg">Belum ada user dengan role guru untuk saat ini.</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
