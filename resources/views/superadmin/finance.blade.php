@extends('layouts.superadmin')

@section('title', 'Manajemen Keuangan')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Manajemen QR & Harga</h1>
        <p class="mt-2 text-textSecondary dark:text-slate-300">Atur instruksi pembayaran dan harga yang akan dilihat calon guru saat mendaftar.</p>
    </div>

    <div class="card border border-amber-100 bg-amber-50/80">
        <div class="flex items-start gap-3 text-sm text-amber-900">
            <i class="fa-solid fa-receipt mt-0.5"></i>
            <div>
                <p class="font-semibold">Catatan operasional</p>
                <p class="mt-1">Pastikan minimal ada satu QR aktif dan satu paket harga aktif agar alur pendaftaran guru menampilkan instruksi pembayaran yang lengkap.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- QR SECTION -->
        <div class="card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="font-bold text-lg">QR Pembayaran</div>
                    <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Upload metode pembayaran yang akan muncul di halaman setelah guru selesai mengisi formulir.</div>
                </div>
            </div>

            <form class="mt-6 space-y-4" method="POST" action="{{ route('superadmin.payment-qrs.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Label</label>
                        <input class="input mt-1" name="label" placeholder="Contoh: QRIS Sekolah / BCA Transfer" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan Tampil</label>
                        <input class="input mt-1" type="number" name="sort_order" min="0" value="0">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">File Gambar QR</label>
                    <input class="input mt-1" type="file" name="image" accept="image/*" required>
                    <p class="mt-1 text-xs text-muted">Gunakan gambar yang jelas agar mudah dipindai di perangkat mobile.</p>
                </div>
                <button class="btn-primary w-full md:w-auto" type="submit">
                    <i class="fa-solid fa-plus mr-2"></i> Simpan QR Pembayaran
                </button>
            </form>

            <div class="mt-8 space-y-4">
                @if(count($paymentQrs) > 0)
                @foreach ($paymentQrs as $qr)
                    <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-4">
                                <img class="w-16 h-16 rounded-xl object-cover border border-border dark:border-slate-800" src="{{ \Illuminate\Support\Facades\Storage::url($qr->image_path) }}" alt="{{ $qr->label }}">
                                <div>
                                    <div class="font-bold">{{ $qr->label }}</div>
                                    <div class="mt-1 text-xs text-muted dark:text-slate-400">Urutan: {{ $qr->sort_order }}</div>
                                    <div class="mt-2">
                                        @if ($qr->is_active)
                                            <span class="badge-success"><i class="fa-solid fa-circle-check mr-1"></i> Aktif</span>
                                        @else
                                            <span class="badge-danger"><i class="fa-solid fa-circle-xmark mr-1"></i> Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('superadmin.payment-qrs.toggle', $qr) }}">
                                    @csrf
                                    <button class="btn-secondary p-2" type="submit" title="Ubah status QR"><i class="fa-solid fa-power-off"></i></button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.payment-qrs.destroy', $qr) }}">
                                    @csrf
                                    <button class="btn-danger p-2" type="submit" data-confirm="Hapus QR ini?" title="Hapus QR pembayaran"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>

                        <details class="mt-4">
                            <summary class="text-sm font-bold cursor-pointer text-primary">Edit detail QR</summary>
                            <form class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3" method="POST" action="{{ route('superadmin.payment-qrs.update', $qr) }}" enctype="multipart/form-data">
                                @csrf
                                <div>
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Label</label>
                                    <input class="input mt-1" name="label" value="{{ $qr->label }}" required>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
                                    <input class="input mt-1" type="number" name="sort_order" min="0" value="{{ $qr->sort_order }}">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Ganti gambar (opsional)</label>
                                    <input class="input mt-1" type="file" name="image" accept="image/*">
                                </div>
                                <div class="md:col-span-2">
                                    <button class="btn-primary w-full" type="submit">Simpan perubahan</button>
                                </div>
                            </form>
                        </details>
                    </div>
                @endforeach
                @else
                    <div class="text-sm text-muted dark:text-slate-400 py-4 text-center border-2 border-dashed rounded-xl">Belum ada QR pembayaran.</div>
                @endif
            </div>
        </div>

        <!-- PRICING SECTION -->
        <div class="card">
            <div>
                <div class="font-bold text-lg">Paket Harga</div>
                <div class="mt-1 text-sm text-textSecondary dark:text-slate-300">Tentukan nominal yang ditampilkan ke calon guru beserta informasi promo jika diperlukan.</div>
            </div>

            <form class="mt-6 space-y-4" method="POST" action="{{ route('superadmin.pricing-plans.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nama Paket</label>
                        <input class="input mt-1" name="name" placeholder="Contoh: Paket Aktivasi Guru" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subtitle</label>
                        <input class="input mt-1" name="subtitle" placeholder="Contoh: Akses 1 akun guru / operator">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Harga (Angka saja)</label>
                        <input class="input mt-1" name="price" placeholder="49000" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Harga Asli (Coret)</label>
                        <input class="input mt-1" name="original_price" placeholder="99000">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Periode</label>
                        <input class="input mt-1" name="period" value="/bulan">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Urutan</label>
                        <input class="input mt-1" type="number" name="sort_order" min="0" value="0">
                    </div>
                </div>
                <button class="btn-primary w-full md:w-auto" type="submit">
                    <i class="fa-solid fa-plus mr-2"></i> Simpan Paket Harga
                </button>
            </form>

            <div class="mt-8 space-y-4">
                @if(count($pricingPlans) > 0)
                @foreach ($pricingPlans as $plan)
                    <div class="p-4 rounded-card border border-border bg-white dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="font-bold text-lg text-primary">{{ $plan->name }}</div>
                                <div class="mt-1 text-sm font-bold">Rp {{ number_format((int)$plan->price, 0, ',', '.') }} <span class="text-muted dark:text-slate-400 font-normal">{{ $plan->period }}</span></div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if ($plan->is_active)
                                        <span class="badge-success"><i class="fa-solid fa-circle-check mr-1"></i> Aktif</span>
                                    @else
                                        <span class="badge-danger"><i class="fa-solid fa-circle-xmark mr-1"></i> Off</span>
                                    @endif

                                    @if ($plan->promo_active)
                                        <span class="badge-info"><i class="fa-solid fa-tag mr-1"></i> Promo Aktif</span>
                                    @endif
                                </div>

                                @if (!empty($plan->original_price))
                                    <div class="mt-2 text-xs text-muted dark:text-slate-400 italic">Harga normal: Rp {{ number_format((int)$plan->original_price, 0, ',', '.') }}</div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('superadmin.pricing-plans.toggle-active', $plan) }}">
                                    @csrf
                                    <button class="btn-secondary p-2" type="submit" title="Ubah status paket"><i class="fa-solid fa-eye"></i></button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.pricing-plans.toggle-promo', $plan) }}">
                                    @csrf
                                    <button class="btn-secondary p-2" type="submit" title="Ubah status promo"><i class="fa-solid fa-bolt"></i></button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.pricing-plans.destroy', $plan) }}">
                                    @csrf
                                    <button class="btn-danger p-2" type="submit" data-confirm="Hapus paket ini?" title="Hapus paket harga"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>

                        <details class="mt-4">
                            <summary class="text-sm font-bold cursor-pointer text-primary">Edit paket</summary>
                            <form class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3" method="POST" action="{{ route('superadmin.pricing-plans.update', $plan) }}">
                                @csrf
                                <div>
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Nama</label>
                                    <input class="input mt-1" name="name" value="{{ $plan->name }}" required>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Subtitle</label>
                                    <input class="input mt-1" name="subtitle" value="{{ $plan->subtitle }}">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Harga</label>
                                    <input class="input mt-1" name="price" value="{{ $plan->price }}" required>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-textSecondary dark:text-slate-300">Harga Coret</label>
                                    <input class="input mt-1" name="original_price" value="{{ $plan->original_price }}">
                                </div>
                                <div class="md:col-span-2">
                                    <button class="btn-primary w-full" type="submit">Simpan</button>
                                </div>
                            </form>
                        </details>
                    </div>
                @endforeach
                @else
                    <div class="text-sm text-muted dark:text-slate-400 py-4 text-center border-2 border-dashed rounded-xl">Belum ada paket harga.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
