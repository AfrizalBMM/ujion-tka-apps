@extends('layouts.guru')

@section('title', 'Detail Paket Soal')

@section('content')
@php($canManage = $paket->isManagedByGuru(auth()->user()))
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $paket->jenjang?->kode }} &middot; {{ $paket->tahun_ajaran }}</span>
        <h1 class="page-title">{{ $paket->nama }}</h1>
        <p class="page-description">
            {{ $canManage
                ? 'Anda dapat meninjau dan mengelola komponen akademik maupun survey sesuai jenjang Anda.'
                : 'Paket ini dibuat oleh superadmin, sehingga di akun guru hanya dapat dilihat sebagai referensi.' }}
        </p>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        @foreach($paket->mapelPakets as $mapel)
        <article class="card">
            <div class="section-heading mb-4">
                <div>
                    <h2 class="section-title">{{ $mapel->nama_label }}</h2>
                    <p class="section-description">{{ $mapel->soals->count() }}/{{ $mapel->jumlah_soal }} butir &middot;
                        {{ $mapel->durasi_menit }} menit &middot; {{ $mapel->isSurvey() ? 'Survey Profiling' : 'Akademik' }}
                    </p>
                </div>
                <a href="{{ route('guru.soal.index', [$paket, $mapel]) }}"
                    class="btn-primary px-4 py-2 text-xs">{{ $canManage ? 'Kelola' : 'Lihat Soal' }}</a>
            </div>
            @if($canManage)
            <form
                class="grid gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-3 dark:border-slate-800 dark:bg-slate-900/60"
                method="POST" action="{{ route('guru.mapel.update', [$paket, $mapel]) }}">
                @csrf
                @method('PUT')
                <div class="input-group">
                    <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Jumlah Soal</label>
                    <input type="number" name="jumlah_soal" class="input" value="{{ old('jumlah_soal', $mapel->jumlah_soal) }}"
                        min="1" max="200" required>
                </div>
                <div class="input-group">
                    <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Durasi (menit)</label>
                    <input type="number" name="durasi_menit" class="input" value="{{ old('durasi_menit', $mapel->durasi_menit) }}"
                        min="1" max="600" required>
                </div>
                <div class="input-group">
                    <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-textSecondary">Urutan</label>
                    <input type="number" name="urutan" class="input" value="{{ old('urutan', $mapel->urutan) }}" min="1" max="10"
                        required>
                </div>
                <div class="md:col-span-3">
                    <button class="btn-secondary px-4 py-2 text-xs" type="submit">Simpan Konfigurasi</button>
                </div>
            </form>
            @else
            <div class="rounded-[24px] border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">
                Konfigurasi mapel ini dikelola oleh superadmin dan tidak bisa diubah dari akun guru.
            </div>
            @endif
            <div class="space-y-3">
                @forelse($mapel->soals->take(5) as $soal)
                <div
                    class="rounded-2xl border border-slate-200/70 bg-slate-50/85 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold">Soal {{ $soal->nomor_soal }}</div>
                        <span class="badge-info">{{ str($soal->tipe_soal)->replace('_', ' ')->headline() }}</span>
                    </div>
                    <p class="mt-2 text-sm text-textSecondary">
                        {{ \Illuminate\Support\Str::limit(strip_tags($soal->pertanyaan), 120) }}
                    </p>
                    @if($mapel->isSurvey() && $soal->dimensi)
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $soal->dimensi }}{{ $soal->subdimensi ? ' · ' . $soal->subdimensi : '' }}</p>
                    @endif
                </div>
                @empty
                <div class="empty-state">Belum ada soal pada mapel ini.</div>
                @endforelse
            </div>
        </article>
        @endforeach
    </section>

    {{-- ── Token Ujian Aktif ────────────────────────────────────────── --}}
    <section class="card">
        <div class="section-heading mb-4">
            <div>
                <h2 class="section-title flex items-center gap-2">
                    <i class="fa-solid fa-key text-primary"></i>
                    Token Ujian Aktif
                </h2>
                <p class="section-description">
                    Ujian yang sedang aktif dari paket ini. Salin token lalu masukkan di halaman
                    <a href="{{ route('guru.exams') }}" class="font-semibold text-primary underline underline-offset-2">Simulasi
                        Ujian</a>
                    untuk memulai simulasi.
                </p>
            </div>
        </div>

        @if($paket->exams->isEmpty())
        <div
            class="flex items-center gap-3 rounded-2xl border border-amber-100 bg-amber-50/80 p-4 dark:border-amber-800/40 dark:bg-amber-900/20">
            <i class="fa-solid fa-circle-info text-xl text-amber-500"></i>
            <div>
                <p class="text-sm font-medium text-amber-900 dark:text-amber-300">Belum ada ujian aktif dari paket ini.</p>
                <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                    Admin Ujion belum membuat atau mengaktifkan ujian dari paket soal ini. Hubungi admin untuk mendapatkan token.
                </p>
            </div>
        </div>
        @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($paket->exams as $exam)
            <div
                class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/60">
                <div>
                    <div class="font-semibold text-sm">{{ $exam->judul }}</div>
                    <div class="mt-0.5 text-xs text-textSecondary">
                        {{ $exam->tanggal_terbit->format('d M Y') }} &middot; maks {{ $exam->max_peserta }} peserta
                    </div>
                </div>

                <div class="space-y-2">
                    @if($exam->examMapelTokens->isEmpty())
                    <span class="badge-warning text-xs">Belum ada token</span>
                    @else
                    @foreach($exam->examMapelTokens as $mt)
                    <div
                        class="flex items-center gap-2 rounded-xl border border-slate-200/50 bg-white/50 p-2 dark:border-slate-800/50 dark:bg-slate-950/30">
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-textSecondary truncate">
                                {{ $mt->mapelPaket?->nama_label ?? 'Mapel' }}
                            </div>
                            <div id="token-guru-paket-{{ $mt->id }}" class="font-mono text-sm font-bold tracking-widest text-primary">
                                {{ $mt->token }}
                            </div>
                        </div>
                        <button type="button" id="copy-guru-paket-{{ $mt->id }}"
                            onclick="guruPaketCopyMapelToken('{{ $mt->token }}', {{ $mt->id }})" title="Salin token"
                            class="btn-secondary h-8 w-8 flex items-center justify-center rounded-lg px-0 transition-all text-xs">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    @endforeach
                    @endif
                </div>

                <a href="{{ route('guru.exams') }}" class="btn-primary w-full py-2 text-center text-xs">
                    <i class="fa-solid fa-play mr-1.5"></i>Simulasi Ujian
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
    function guruPaketCopyMapelToken(token, id) {
        const doCopy = () => {
            const btn = document.getElementById('copy-guru-paket-' + id);
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
            btn.classList.add('text-emerald-600');
            setTimeout(() => {
                btn.innerHTML = original;
                btn.classList.remove('text-emerald-600');
            }, 2000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(token).then(doCopy);
        } else {
            const textArea = document.createElement("textarea");
            textArea.value = token;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                doCopy();
            } catch (err) {
                console.error('Fallback copy failed', err);
            }
            document.body.removeChild(textArea);
        }
    }
</script>
@endpush
@endsection
