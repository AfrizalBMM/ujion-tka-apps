@php
    $isEdit = isset($paket);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jenjang</label>
            <div class="ssd-wrap mt-1">
                <input type="hidden" name="jenjang_id" value="{{ old('jenjang_id', $paket->jenjang_id ?? null) }}" required>
                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                    @php $selectedJenjang = $jenjangs->firstWhere('id', old('jenjang_id', $paket->jenjang_id ?? null)) @endphp
                    <span class="ssd-label">{{ $selectedJenjang ? ($selectedJenjang->kode . ' - ' . $selectedJenjang->nama) : 'Pilih jenjang' }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                </button>
                <div class="ssd-panel">
                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jenjang..."></div>
                    <div class="ssd-list">
                        <div class="ssd-option{{ !old('jenjang_id', $paket->jenjang_id ?? null) ? ' ssd-selected' : '' }}" data-value="">Pilih jenjang</div>
                        @foreach($jenjangs as $jenjang)
                            <div class="ssd-option{{ old('jenjang_id', $paket->jenjang_id ?? null) == $jenjang->id ? ' ssd-selected' : '' }}" data-value="{{ $jenjang->id }}">{{ $jenjang->kode }} - {{ $jenjang->nama }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            @error('jenjang_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div class="input-group">
            <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Tahun Ajaran</label>
            <input type="text" name="tahun_ajaran" class="input" value="{{ old('tahun_ajaran', $paket->tahun_ajaran ?? '') }}" placeholder="2025/2026" required>
            @error('tahun_ajaran') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="input-group">
        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Nama Paket Soal</label>
        <input type="text" name="nama" class="input" value="{{ old('nama', $paket->nama ?? '') }}" placeholder="Paket TKA SMP Gelombang 1" required>
        @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>

    <label class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900/70">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" @checked(old('is_active', $paket->is_active ?? false))>
        <span>Jadikan paket aktif untuk jenjang ini</span>
    </label>

    <div class="flex flex-wrap gap-3">
        <button class="btn-primary" type="submit">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ $submitLabel }}
        </button>
        <a href="{{ $cancelUrl }}" class="btn-secondary">Batal</a>
    </div>
</form>
