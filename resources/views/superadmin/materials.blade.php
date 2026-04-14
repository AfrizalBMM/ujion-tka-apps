@extends('layouts.superadmin')

@section('title', 'Master Data Materi')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Data Kurikulum & Materi</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Kelola hierarki kurikulum, subelemen, unit, dan sub unit materi global.</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- FORM ADD -->
        <div class="lg:col-span-1">
            <div class="card sticky top-6">
                <div class="font-bold text-lg mb-4">Tambah Materi Baru</div>
                <form class="space-y-4" method="POST" action="{{ route('superadmin.materials.store') }}">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Kurikulum</label>
                        <select class="input mt-1" name="curriculum" required>
                            <option value="Merdeka">Kurikulum Merdeka</option>
                            <option value="K-13">K-13 (Masa Transisi)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subelemen</label>
                        <input class="input mt-1" name="subelement" required placeholder="E.g: Akidah Akhlak">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Unit / Bab</label>
                        <input class="input mt-1" name="unit" required placeholder="E.g: Rukun Iman">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Sub Unit / Sub Bab</label>
                        <input class="input mt-1" name="sub_unit" required placeholder="E.g: Mengenal Malaikat">
                    </div>
                    <button class="btn-primary w-full" type="submit">
                        <i class="fa-solid fa-plus mr-2"></i> Tambah Materi
                    </button>
                    <p class="text-[10px] text-muted italic">Materi yang ditambahkan akan tersedia sebagai referensi saat pembuatan bank soal oleh semua guru.</p>
                </form>
            </div>
        </div>

        <!-- LIST DATA -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="font-bold text-lg mb-4">Daftar Materi Terdaftar</div>
                
                <div class="grid grid-cols-1 gap-3">
                    @if(count($materials) > 0)
                    @foreach ($materials as $m)
                        <div class="flex items-start justify-between gap-4 p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800 hover:shadow-sm transition-shadow">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="badge-info text-[10px]">{{ $m->curriculum }}</span>
                                    <div class="text-xs text-muted">ID: #{{ $m->id }}</div>
                                </div>
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $m->subelement }}</div>
                                <div class="mt-1 text-sm text-textSecondary dark:text-slate-400">
                                    <i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $m->unit }} 
                                    <i class="fa-solid fa-chevron-right text-[10px] mx-1"></i> {{ $m->sub_unit }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('superadmin.materials.destroy', $m) }}">
                                @csrf
                                <button class="btn-danger p-2" type="submit" data-confirm="Hapus materi ini? Data soal yang terikat akan kehilangan referensi materi. Lanjutkan?" title="Hapus">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                    @else
                        <div class="text-center py-20 border-2 border-dashed rounded-xl">
                            <i class="fa-solid fa-book-open text-5xl text-slate-100 mb-4 block"></i>
                            <span class="text-muted italic">Belum ada kurikulum/materi yang diinput.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection