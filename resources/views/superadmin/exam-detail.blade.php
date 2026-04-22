@extends('layouts.superadmin')

@section('title', 'Detail Ujian')

@section('content')
<div class="max-w-4xl space-y-6">
    <section class="page-hero">
        <span class="page-kicker">Ujian Dari Paket Lengkap</span>
        <h1 class="page-title">{{ $exam->judul }}</h1>
        <p class="page-description">Detail ujian, paket yang digunakan, serta token per bagian untuk Bahasa Indonesia, Matematika, Survey Karakter, dan Sulingjar.</p>
    </section>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="card p-6">
            <div class="mb-2 font-bold text-lg">Token per Bagian Paket</div>
            <p class="mb-4 text-sm text-textSecondary">Setiap siswa masuk ke bagian ujian menggunakan token yang sesuai dengan bagian paket.</p>
            @if($exam->examMapelTokens->isEmpty())
                <div class="empty-state">Belum ada token bagian untuk ujian ini.</div>
            @else
                <div class="space-y-3">
                    @foreach($exam->examMapelTokens as $token)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
                            <div>
                                <div class="text-sm font-bold text-slate-900">{{ $token->mapelPaket?->nama_label ?? 'Bagian' }}</div>
                                <div class="mt-1 text-xs text-textSecondary">{{ $token->mapelPaket?->isSurvey() ? 'Bagian survey' : 'Bagian akademik' }}</div>
                                <div class="mt-1 font-mono text-lg tracking-widest text-primary">{{ $token->token }}</div>
                            </div>
                            <button type="button" class="btn-secondary" onclick="copyExamToken('{{ $token->token }}', this)">Copy</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card p-6">
            <div class="mb-2 font-bold">Paket Lengkap</div>
            <div class="mb-4">{{ $exam->paketSoal?->nama ?? '-' }}</div>
            <div class="mb-2 font-bold">Jenjang</div>
            <div class="mb-4">{{ $exam->paketSoal?->jenjang?->kode ?? '-' }}</div>
            <div class="mb-2 font-bold">Tanggal Terbit</div>
            <div class="mb-4">{{ $exam->tanggal_terbit->format('d M Y H:i') }}</div>
            <div class="mb-2 font-bold">Max Peserta</div>
            <div class="mb-4">{{ $exam->max_peserta }}</div>
            <div class="mb-2 font-bold">Total Durasi Paket</div>
            <div class="mb-4">{{ $exam->timer }} menit</div>
            <div class="mb-2 font-bold">Status</div>
            <div class="mb-4">{{ $exam->status }}</div>
            <div class="mb-2 font-bold">Aktif</div>
            <div class="mb-4">
                @if($exam->is_active)
                    <span class="badge-success">Aktif</span>
                @else
                    <span class="badge-danger">Nonaktif</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('superadmin.exams.analysis', $exam) }}" class="btn-secondary">Lihat Analisis</a>
                <a href="{{ route('superadmin.exams.builder', $exam) }}" class="btn-primary">Builder Soal Snapshot</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyExamToken(token, button) {
    navigator.clipboard.writeText(token).then(() => {
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => {
            button.textContent = original;
        }, 1200);
    });
}
</script>
@endpush
@endsection
