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
            <select name="jenjang_id" class="input" required>
                <option value="">Pilih jenjang</option>
                @foreach($jenjangs as $jenjang)
                    <option value="{{ $jenjang->id }}" @selected(old('jenjang_id', $paket->jenjang_id ?? null) == $jenjang->id)>
                        {{ $jenjang->kode }} - {{ $jenjang->nama }}
                    </option>
                @endforeach
            </select>
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
