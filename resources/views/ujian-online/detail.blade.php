@extends('layouts.public')

@section('title', $landingExam->exam?->judul ?? 'Ujian Online')
@section('description', $landingExam->short_description ?? 'Detail ujian online Ujion TKA.')

@section('content')
<div class="flex items-center gap-2 text-sm text-textSecondary">
    <a href="{{ route('ujian-online.index') }}" class="hover:text-indigo-600">Ujian Online</a>
    <i class="fa-solid fa-chevron-right text-xs"></i>
    <a href="{{ route('ujian-online.jenjang', strtolower($landingExam->jenjang)) }}" class="hover:text-indigo-600">{{ $landingExam->jenjang }}</a>
</div>

<div class="mt-4">
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $landingExam->jenjang }}</span>
    </div>
    <h1 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">{{ $landingExam->exam?->judul ?? '—' }}</h1>

    @if($landingExam->description)
        <div class="mt-4 max-w-3xl text-textSecondary leading-relaxed">{!! nl2br(e($landingExam->description)) !!}</div>
    @endif

    @if($landingExam->short_description && !$landingExam->description)
        <p class="mt-4 max-w-3xl text-textSecondary">{{ $landingExam->short_description }}</p>
    @endif
</div>

<div class="mt-8">
    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Pilih Mapel</h2>
    <p class="mt-1 text-sm text-textSecondary">Bayar per mapel. Setelah pembayaran, Anda langsung dapat link ujian.</p>

    <div class="mt-6 space-y-6">
        @foreach($landingExam->mapels as $mapel)
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white dark:border-slate-700/60 dark:bg-slate-900">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $mapel->mapelPaket?->nama_label ?? '—' }}</h3>
                            <div class="mt-1 flex flex-wrap items-center gap-4 text-sm text-textSecondary">
                                <span><i class="fa-solid fa-list-ol mr-1"></i>{{ $mapel->mapelPaket?->soals?->count() ?? $mapel->mapelPaket?->jumlah_soal ?? 0 }} soal</span>
                                <span><i class="fa-solid fa-clock mr-1"></i>{{ $mapel->mapelPaket?->durasi_menit ?? 0 }} menit</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            @if($mapel->original_price && (float) $mapel->original_price > (float) $mapel->price)
                                <div class="text-sm text-textSecondary line-through">Rp{{ number_format((float) $mapel->original_price, 0, ',', '.') }}</div>
                            @endif
                            <div class="text-2xl font-black text-indigo-600">Rp{{ number_format((float) $mapel->price, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('ujian-online.register', [strtolower($landingExam->jenjang), $landingExam]) }}" class="mt-5 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end" data-register-form>
                        @csrf
                        <input type="hidden" name="mapel_id" value="{{ $mapel->id }}">
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Nama Lengkap</label>
                            <input type="text" name="nama" class="input" required placeholder="Nama lengkap Anda" value="{{ old('nama') }}">
                        </div>
                        <div class="input-group">
                            <label class="text-xs font-bold uppercase tracking-widest text-textSecondary">Nomor WhatsApp</label>
                            <input type="tel" name="nomor_wa" class="input" required placeholder="08xxxxxxxxxx" value="{{ old('nomor_wa') }}">
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-bolt mr-1"></i>
                            Daftar & Bayar
                        </button>
                    </form>

                    @if($errors->any() && old('mapel_id') == $mapel->id)
                        <div class="mt-3 rounded-xl border border-rose-100 bg-rose-50 px-4 py-2 text-sm text-rose-700">
                            @error('nama'){{ $message }}@enderror
                            @error('nomor_wa'){{ $message }}@enderror
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-8">
    <a href="{{ route('ujian-online.jenjang', strtolower($landingExam->jenjang)) }}" class="text-sm text-textSecondary hover:text-indigo-600">
        <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke daftar
    </a>
</div>
@endsection
