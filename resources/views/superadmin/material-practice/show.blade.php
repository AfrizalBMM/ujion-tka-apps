@extends('layouts.superadmin')

@section('title', 'Latihan Materi')

@section('content')
<div class="space-y-6">
    <section class="page-hero">
        <span class="page-kicker">{{ $material->jenjang ?: '-' }} · {{ $material->mapel ?: '-' }} · {{ $material->curriculum }}</span>
        <h1 class="page-title">Latihan Materi</h1>
        <p class="page-description">
            Konfigurasi 2 soal telaah (PG) dan token latihan (3 paket acak) untuk materi: <span class="font-semibold">{{ $material->subelement }} → {{ $material->unit }} → {{ $material->sub_unit }}</span>.
        </p>
        <div class="page-actions">
            <a href="{{ route('superadmin.materials.index', array_filter(['jenjang' => request('jenjang')])) }}" class="btn-secondary">Kembali</a>
        </div>
    </section>

    <section class="card">
        <div class="section-heading mb-5">
            <div>
                <h2 class="section-title">Token Latihan</h2>
                <p class="section-description">Satu token per materi (berisi telaah + paket 1-3). Paket latihan akan menjadi snapshot dan dipakai semua siswa.</p>
            </div>
        </div>

        @if(($bankQuestionCount ?? 0) === 0)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-white p-4 text-sm text-textSecondary dark:bg-slate-900">
                <div>
                    <span class="badge-warning">Bank soal PG aktif: 0</span>
                    <span class="ml-2">Token/paket latihan tidak bisa digenerate sebelum bank soal untuk materi ini tersedia.</span>
                </div>
                <a href="{{ route('superadmin.global-questions.index') }}" class="btn-secondary">Buka Bank Soal</a>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-border bg-white p-4 dark:bg-slate-900">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Token</div>
                <div class="mt-2 flex items-center gap-3">
                    @if($token)
                        <code class="rounded bg-indigo-50 px-3 py-2 text-lg font-black text-indigo-700">{{ $token->token }}</code>
                        <span class="badge-success">Aktif</span>
                    @else
                        <span class="badge-warning">Belum dibuat</span>
                    @endif
                </div>
                <div class="mt-3 text-xs text-textSecondary">Jumlah soal per paket: <span class="font-semibold">{{ $token?->jumlah_soal_per_paket ?? '-' }}</span></div>
            </div>

            <div class="rounded-2xl border border-border bg-white p-4 dark:bg-slate-900">
                <form method="POST" action="{{ route('superadmin.materials.practice.token', $material) }}" class="space-y-3">
                    @csrf
                    <div class="input-group">
                        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Jumlah Soal per Paket</label>
                        <select name="jumlah_soal_per_paket" class="input" required>
                            @foreach([10,15] as $n)
                                <option value="{{ $n }}" {{ (int)old('jumlah_soal_per_paket', $token?->jumlah_soal_per_paket ?? 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-textSecondary">Aksi ini akan (re)generate paket 1-3 dari bank soal PG aktif pada materi ini.</p>
                    </div>
                    <button class="btn-primary" type="submit">{{ $token ? 'Update & Regenerate Paket' : 'Generate Token & Paket' }}</button>
                </form>

                @if($token)
                    <form method="POST" action="{{ route('superadmin.materials.practice.packages.regenerate', $material) }}" class="mt-3">
                        @csrf
                        <button class="btn-secondary" type="submit" data-confirm="Acak ulang paket 1-3? Paket siswa akan berubah." data-confirm-title="Acak Ulang Paket">Acak Ulang Paket</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-heading mb-5">
            <div>
                <h2 class="section-title">Telaah Soal (2 Butir)</h2>
                <p class="section-description">Telaah dipakai untuk pegangan guru mengajar, dan akan tampil di halaman siswa saat token dimasukkan.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-info">Bank PG aktif: {{ $bankQuestionCount }}</span>
                @if($bankQuestionCount > 200)
                    <span class="badge-warning">Menampilkan 200 terbaru</span>
                @endif
            </div>
        </div>

        @if($bankQuestionCount === 0)
            <div class="rounded-2xl border border-border bg-white p-4 text-sm text-textSecondary dark:bg-slate-900">
                Belum ada bank soal PG aktif untuk materi ini, sehingga dropdown telaah akan kosong.
                <div class="mt-3">
                    <a class="btn-secondary" href="{{ route('superadmin.global-questions.index') }}">Buka Bank Soal</a>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('superadmin.materials.practice.telaah', $material) }}" class="grid gap-4 lg:grid-cols-2">
                @csrf
                @for($i=0; $i<2; $i++)
                    @php
                        $selectedId = old('question_ids.' . $i, $telaah[$i]->global_question_id ?? null);
                    @endphp
                    <div class="input-group">
                        <label class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Soal Telaah {{ $i+1 }}</label>
                        <select name="question_ids[]" class="input" required>
                            <option value="">Pilih soal...</option>
                            @foreach($bankQuestions as $q)
                                <option value="{{ $q->id }}" {{ (int)$selectedId === (int)$q->id ? 'selected' : '' }}>
                                    #{{ $q->id }} — {{ \Illuminate\Support\Str::limit(strip_tags($q->question_text), 90) }}
                                </option>
                            @endforeach
                        </select>
                        @php
                            $selected = $selectedId ? ($bankQuestionsById[$selectedId] ?? null) : null;
                        @endphp
                        <p class="mt-1 text-[11px] text-textSecondary">Opsional bacaan: {{ $selected ? ($selected->reading_passage ? 'Ya' : 'Tidak') : '-' }}</p>
                    </div>
                @endfor
                <div class="lg:col-span-2">
                    <button class="btn-primary" type="submit">Simpan Telaah</button>
                </div>
            </form>
        @endif

        @if($telaah->count() === 2)
            <div class="mt-6 rounded-2xl border border-border bg-slate-50/70 p-4 dark:bg-slate-900/60">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-textSecondary">Preview</div>
                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                    @foreach($telaah as $row)
                        <div class="rounded-2xl border border-border bg-white p-4 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold">Telaah {{ $row->urutan }}</div>
                                <span class="badge-info">ID #{{ $row->global_question_id }}</span>
                            </div>
                            @if($row->globalQuestion?->reading_passage)
                                <div class="mt-3 text-xs font-semibold text-textSecondary">Bacaan</div>
                                <div class="mt-1 text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($row->globalQuestion->reading_passage, 260) }}</div>
                            @endif
                            <div class="mt-3 text-xs font-semibold text-textSecondary">Pertanyaan</div>
                            <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit(strip_tags($row->globalQuestion?->question_text ?? ''), 260) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
