@extends('layouts.superadmin')

@section('title', 'Blast Pengumuman')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Blast Pengumuman</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Kirim pengumuman WhatsApp ke guru aktif. Pengiriman dilakukan lewat antrean (queue) agar tidak memberatkan request.
            </p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="card lg:col-span-3">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Pesan</div>
            <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Tulis pengumuman</div>

            <form method="POST" action="{{ route('superadmin.wa-blast.send') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Target penerima</label>
                    <select id="wa-target" name="target" class="input w-full">
                        <option value="guru_all_active" {{ old('target', 'guru_all_active') === 'guru_all_active' ? 'selected' : '' }}>Semua guru aktif</option>
                        <option value="guru_jenjang" {{ old('target') === 'guru_jenjang' ? 'selected' : '' }}>Guru aktif per jenjang</option>
                        <option value="guru_school" {{ old('target') === 'guru_school' ? 'selected' : '' }}>Guru aktif per sekolah</option>
                        <option value="siswa_all" {{ old('target') === 'siswa_all' ? 'selected' : '' }}>Semua siswa (peserta ujian)</option>
                        <option value="siswa_paket" {{ old('target') === 'siswa_paket' ? 'selected' : '' }}>Siswa per paket soal</option>
                    </select>
                </div>

                <div id="wa-jenjang-wrap" class="{{ old('target') === 'guru_jenjang' ? '' : 'hidden' }}">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Jenjang</label>
                    <select name="jenjang" class="input w-full">
                        <option value="">Pilih jenjang</option>
                        @foreach ($jenjangOptions as $jenjang)
                            <option value="{{ $jenjang }}" {{ old('jenjang') === $jenjang ? 'selected' : '' }}>{{ $jenjang }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="wa-school-wrap" class="{{ old('target') === 'guru_school' ? '' : 'hidden' }}">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Satuan pendidikan (nama sekolah)</label>
                    <input name="school" class="input w-full" list="school-options" value="{{ old('school') }}" placeholder="Contoh: SMP Negeri 1">
                    <datalist id="school-options">
                        @foreach (($schoolOptions ?? []) as $school)
                            <option value="{{ $school }}"></option>
                        @endforeach
                    </datalist>
                    <div class="mt-2 text-xs text-textSecondary dark:text-slate-300">Pencarian memakai <span class="font-mono">LIKE</span>. Isi sebagian nama sekolah juga boleh.</div>
                </div>

                <div id="wa-paket-wrap" class="{{ old('target') === 'siswa_paket' ? '' : 'hidden' }}">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Paket soal</label>
                    <select name="paket_soal_id" class="input w-full">
                        <option value="">Pilih paket soal</option>
                        @foreach (($paketSoalOptions ?? []) as $paket)
                            <option value="{{ $paket->id }}" {{ (string) old('paket_soal_id') === (string) $paket->id ? 'selected' : '' }}>
                                {{ $paket->nama }}{{ $paket->jenjang ? ' (' . $paket->jenjang->kode . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Jadwal pengiriman</label>
                    <input
                        type="datetime-local"
                        name="scheduled_at"
                        class="input w-full"
                        value="{{ old('scheduled_at') }}"
                        placeholder="Pilih tanggal dan waktu"
                    >
                    <div class="mt-2 text-xs text-textSecondary dark:text-slate-300">
                        Kosongkan untuk mengirim segera. Waktu ditentukan dalam zona waktu lokal.
                    </div>
                    @error('scheduled_at')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Isi pesan</label>
                    <textarea name="message" class="input w-full min-h-40" placeholder="Contoh: 📢 Pengumuman jadwal ujian...">{{ old('message') }}</textarea>
                    @error('message')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-paper-plane mr-2"></i>
                        Jadwalkan Blast
                    </button>
                </div>
            </form>
        </div>

        <div class="card lg:col-span-2">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Catatan</div>
            <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Operasional</div>

            <ul class="mt-4 space-y-2 text-sm text-textSecondary dark:text-slate-300">
                <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Pastikan WA Gateway (Node.js) sedang berjalan.</span></li>
                <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Jalankan queue worker: <span class="font-mono">php artisan queue:work --queue=high,low</span></span></li>
                <li class="flex gap-2"><i class="fa-solid fa-circle-check mt-1 text-[10px] text-muted"></i><span>Sistem memberi delay acak per pesan untuk mengurangi risiko pembatasan WhatsApp.</span></li>
            </ul>
        </div>
    </div>

    <div class="mt-6 card">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-muted">Monitoring Blast</div>
                <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Aktivitas Pesan WhatsApp</div>
            </div>
            <div class="text-sm text-textSecondary dark:text-slate-300">Menampilkan 10 catatan terbaru dari log pengiriman WA.</div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="text-xs uppercase tracking-wide text-muted">Terkirim</div>
                <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $blastStats['success'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="text-xs uppercase tracking-wide text-muted">Gagal</div>
                <div class="mt-2 text-3xl font-bold text-rose-600">{{ $blastStats['failed'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-900 dark:bg-slate-900">
                <div class="text-xs uppercase tracking-wide text-muted">Lainnya</div>
                <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $blastStats['unknown'] ?? 0 }}</div>
            </div>
        </div>

        <div class="mt-4 text-xs text-textSecondary dark:text-slate-400">
            <strong>Debug:</strong> blastStats = {{ json_encode($blastStats ?? []) }}, blastLogs count = {{ count($blastLogs ?? []) }}
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full border-collapse text-left text-sm">
                <thead>
                    <tr>
                        <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Waktu</th>
                        <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Nomor</th>
                        <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Status</th>
                        <th class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900 dark:border-slate-700 dark:text-slate-100">Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blastLogs as $log)
                        <tr class="hover:bg-slate-100 dark:hover:bg-slate-800">
                            <td class="border-b border-slate-200 px-4 py-3 text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="border-b border-slate-200 px-4 py-3 text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $log->phone }}</td>
                            <td class="border-b border-slate-200 px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : ($log->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="border-b border-slate-200 px-4 py-3 text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ str($log->message)->limit(80) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border-b border-slate-200 px-4 py-6 text-center text-sm text-textSecondary dark:border-slate-700">Belum ada log blast WhatsApp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@push('scripts')
<script>
(() => {
    const target = document.getElementById('wa-target');
    const jenjangWrap = document.getElementById('wa-jenjang-wrap');
    const schoolWrap = document.getElementById('wa-school-wrap');
    const paketWrap = document.getElementById('wa-paket-wrap');

    if (!target) return;

    target.addEventListener('change', () => {
        if (jenjangWrap) jenjangWrap.classList.toggle('hidden', target.value !== 'guru_jenjang');
        if (schoolWrap) schoolWrap.classList.toggle('hidden', target.value !== 'guru_school');
        if (paketWrap) paketWrap.classList.toggle('hidden', target.value !== 'siswa_paket');
    });
})();
</script>
@endpush
@endsection
