@extends('layouts.superadmin')

@section('title', 'Manajemen Guru & Operator')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Manajemen Guru & Akses</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Aktivasi akun, bagikan token akses, dan tangani akun guru yang perlu ditinjau ulang.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu review</div>
            <div class="mt-2 text-2xl font-bold text-blue-700">{{ $paymentSummary[\App\Models\User::PAYMENT_SUBMITTED] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Guru yang sudah upload bukti pembayaran.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Perlu kirim ulang</div>
            <div class="mt-2 text-2xl font-bold text-rose-700">{{ $paymentSummary[\App\Models\User::PAYMENT_REJECTED] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Masih menunggu bukti yang diperbaiki.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Belum bayar</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">{{ $paymentSummary[\App\Models\User::PAYMENT_AWAITING] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Sudah daftar tetapi belum upload bukti.</div>
        </div>
        <div class="card">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">Sudah disetujui</div>
            <div class="mt-2 text-2xl font-bold text-green-700">{{ $paymentSummary[\App\Models\User::PAYMENT_APPROVED] ?? 0 }}</div>
            <div class="mt-1 text-sm text-muted">Pembayaran selesai dan akun siap dipakai.</div>
        </div>
    </div>

    <div class="card border border-blue-100 bg-blue-50/80">
        <div class="flex items-start gap-3 text-sm text-blue-900">
            <i class="fa-solid fa-circle-info mt-0.5"></i>
            <div>
                <p class="font-semibold">Alur kerja yang disarankan untuk admin</p>
                <p class="mt-1">Aktifkan akun setelah pembayaran terverifikasi, lalu kirim token akses terbaru melalui kanal yang aman seperti WhatsApp resmi admin.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('superadmin.teachers.index') }}" class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,2fr)_1fr_1fr_auto]">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Cari guru</label>
                <input type="text" name="q" value="{{ $search }}" class="input w-full" placeholder="Nama, email, WhatsApp, atau sekolah">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status pembayaran</label>
                <select name="payment_status" class="input w-full">
                    <option value="">Semua status pembayaran</option>
                    <option value="{{ \App\Models\User::PAYMENT_SUBMITTED }}" @selected($paymentStatus === \App\Models\User::PAYMENT_SUBMITTED)>Menunggu review</option>
                    <option value="{{ \App\Models\User::PAYMENT_REJECTED }}" @selected($paymentStatus === \App\Models\User::PAYMENT_REJECTED)>Ditolak</option>
                    <option value="{{ \App\Models\User::PAYMENT_AWAITING }}" @selected($paymentStatus === \App\Models\User::PAYMENT_AWAITING)>Belum upload bukti</option>
                    <option value="{{ \App\Models\User::PAYMENT_APPROVED }}" @selected($paymentStatus === \App\Models\User::PAYMENT_APPROVED)>Disetujui</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status akun</label>
                <select name="account_status" class="input w-full">
                    <option value="">Semua status akun</option>
                    <option value="{{ \App\Models\User::STATUS_PENDING }}" @selected($accountStatus === \App\Models\User::STATUS_PENDING)>Pending</option>
                    <option value="{{ \App\Models\User::STATUS_ACTIVE }}" @selected($accountStatus === \App\Models\User::STATUS_ACTIVE)>Aktif</option>
                    <option value="{{ \App\Models\User::STATUS_SUSPEND }}" @selected($accountStatus === \App\Models\User::STATUS_SUSPEND)>Ditangguhkan</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary w-full lg:w-auto">Terapkan</button>
                <a href="{{ route('superadmin.teachers.index') }}" class="btn-secondary w-full text-center lg:w-auto">Reset</a>
            </div>
        </form>

        <div class="table-container">
            <table class="table-ujion min-w-[1080px]">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Status Akun</th>
                        <th>Ringkasan Token</th>
                        <th>Bukti Bayar</th>
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
                                <div class="mt-2 text-xs text-muted">{{ $teacher->satuan_pendidikan ?: '-' }}</div>
                            </td>
                            <td class="text-textSecondary dark:text-slate-300">
                                <div>{{ $teacher->email }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $teacher->no_wa ?: '-' }}</div>
                            </td>
                            <td>
                                @if ($teacher->account_status === \App\Models\User::STATUS_ACTIVE)
                                    <span class="badge-success"><i class="fa-solid fa-circle-check mr-1"></i> Aktif</span>
                                @elseif ($teacher->account_status === \App\Models\User::STATUS_PENDING)
                                    <span class="badge-warning"><i class="fa-solid fa-clock mr-1"></i> Menunggu Aktivasi</span>
                                @else
                                    <span class="badge-danger"><i class="fa-solid fa-ban mr-1"></i> Ditangguhkan</span>
                                @endif
                                <div class="mt-2 text-xs text-muted">
                                    @if ($teacher->account_status === \App\Models\User::STATUS_ACTIVE)
                                        Siap menerima token dan masuk ke dashboard.
                                    @elseif ($teacher->account_status === \App\Models\User::STATUS_PENDING)
                                        Menunggu verifikasi admin sebelum token dibagikan.
                                    @else
                                        Perlu aktivasi ulang atau token baru sebelum bisa login.
                                    @endif
                                </div>
                                <div class="mt-3">
                                    @if ($teacher->payment_status === \App\Models\User::PAYMENT_APPROVED)
                                        <span class="badge-success">Pembayaran Disetujui</span>
                                    @elseif ($teacher->payment_status === \App\Models\User::PAYMENT_SUBMITTED)
                                        <span class="badge-info">Menunggu Review Pembayaran</span>
                                    @elseif ($teacher->payment_status === \App\Models\User::PAYMENT_REJECTED)
                                        <span class="badge-danger">Pembayaran Ditolak</span>
                                    @else
                                        <span class="badge-warning">Belum Upload Bukti</span>
                                    @endif
                                </div>
                                @if (! blank($teacher->payment_rejection_reason))
                                    <div class="mt-2 text-xs text-rose-600">Catatan: {{ $teacher->payment_rejection_reason }}</div>
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
                            <td>
                                @if (! blank($teacher->payment_proof_path))
                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            class="btn-secondary text-xs"
                                            data-payment-proof-open
                                            data-payment-proof-src="{{ \Illuminate\Support\Facades\Storage::url($teacher->payment_proof_path) }}"
                                            data-payment-proof-name="{{ $teacher->name }}"
                                        >
                                            Preview Bukti
                                        </button>
                                        <div>
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($teacher->payment_proof_path) }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">Buka file asli</a>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs italic text-muted">Belum ada bukti</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($teacher->payment_status === \App\Models\User::PAYMENT_SUBMITTED)
                                    <form method="POST" action="{{ route('superadmin.teachers.approve-payment', $teacher) }}">
                                        @csrf
                                        <button class="btn-primary p-2" type="submit" title="Setujui pembayaran dan aktifkan akun">
                                            <i class="fa-solid fa-badge-check"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.teachers.reject-payment', $teacher) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="text" name="payment_rejection_reason" class="input hidden lg:block w-48" placeholder="Alasan penolakan" required>
                                        <button class="btn-danger p-2" type="submit" title="Tolak pembayaran">
                                            <i class="fa-solid fa-reply"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($teacher->account_status !== \App\Models\User::STATUS_ACTIVE && $teacher->payment_status !== \App\Models\User::PAYMENT_SUBMITTED)
                                    <form method="POST" action="{{ route('superadmin.teachers.activate', $teacher) }}">
                                        @csrf
                                        <button class="btn-primary p-2" type="submit" title="Aktifkan akun secara manual dan tampilkan token">
                                            <i class="fa-solid fa-user-check"></i>
                                        </button>
                                    </form>
                                    @endif
                                    
                                    <form method="POST" action="{{ route('superadmin.teachers.refresh-token', $teacher) }}">
                                        @csrf
                                        <button class="btn-secondary p-2" type="submit" title="Buat token akses baru">
                                            <i class="fa-solid fa-rotate"></i>
                                        </button>
                                    </form>

                                    @if($teacher->account_status !== \App\Models\User::STATUS_SUSPEND)
                                    <form method="POST" action="{{ route('superadmin.teachers.suspend', $teacher) }}">
                                        @csrf
                                        <button class="btn-danger p-2" type="submit" data-confirm="Tangguhkan akses guru ini?" title="Tangguhkan akses">
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
                            <td colspan="6" class="text-center py-12">
                                <i class="fa-solid fa-users-slash text-4xl text-slate-200 mb-3 block"></i>
                                <span class="text-muted dark:text-slate-400 italic text-lg">Belum ada user dengan role guru untuk saat ini.</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold">Template Pesan Siap Pakai</h2>
                <p class="mt-1 text-sm text-textSecondary dark:text-slate-300">Gunakan template ini saat proses verifikasi dan aktivasi masih dilakukan manual.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($notificationTemplates as $template)
                <div class="rounded-card border border-border bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold">{{ $template['title'] }}</div>
                        <div class="flex items-center gap-2">
                            <span class="badge-info">{{ $template['audience'] }}</span>
                            <button
                                type="button"
                                class="btn-secondary text-xs"
                                data-copy-template
                                data-copy-text="{{ $template['body'] }}"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                    <pre class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $template['body'] }}</pre>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div id="payment-proof-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-3xl rounded-2xl bg-white p-4 shadow-2xl dark:bg-slate-900">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-muted">Preview bukti pembayaran</div>
                <div id="payment-proof-title" class="text-lg font-bold text-slate-900 dark:text-slate-100">Guru</div>
            </div>
            <button type="button" class="btn-secondary" data-payment-proof-close>Tutup</button>
        </div>
        <div class="overflow-hidden rounded-xl border border-border bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
            <img id="payment-proof-image" src="" alt="Bukti pembayaran" class="max-h-[70vh] w-full object-contain">
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-copy-template]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy-text') || '';
            const original = button.textContent;

            try {
                await navigator.clipboard.writeText(text);
                button.textContent = 'Tersalin';
                setTimeout(() => {
                    button.textContent = original;
                }, 1200);
            } catch (error) {
                button.textContent = 'Gagal';
                setTimeout(() => {
                    button.textContent = original;
                }, 1200);
            }
        });
    });

    const proofModal = document.getElementById('payment-proof-modal');
    const proofImage = document.getElementById('payment-proof-image');
    const proofTitle = document.getElementById('payment-proof-title');

    document.querySelectorAll('[data-payment-proof-open]').forEach((button) => {
        button.addEventListener('click', () => {
            proofImage.src = button.getAttribute('data-payment-proof-src') || '';
            proofTitle.textContent = button.getAttribute('data-payment-proof-name') || 'Guru';
            proofModal.classList.remove('hidden');
            proofModal.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-payment-proof-close]').forEach((button) => {
        button.addEventListener('click', () => {
            proofModal.classList.add('hidden');
            proofModal.classList.remove('flex');
            proofImage.src = '';
        });
    });

    proofModal?.addEventListener('click', (event) => {
        if (event.target === proofModal) {
            proofModal.classList.add('hidden');
            proofModal.classList.remove('flex');
            proofImage.src = '';
        }
    });
</script>
@endsection
