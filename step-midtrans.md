# Implementasi Payment Gateway Midtrans — Ujion TKA

Dokumen ringkasan + tutorial implementasi Midtrans (Snap Popup) pada project Ujion TKA. Ditulis setelah proses implementasi selesai — berisi arsitektur akhir, file yang terlibat, dan langkah penggunaan.

---

## 1. Ringkasan

### Sebelum
- Guru bayar via **QRIS GoPay manual**: superadmin menempel QRIS master payload, guru scan, upload bukti, admin review/approve manual.
- Alur panjang: daftar → halaman QR → transfer → upload bukti → tunggu admin → token via WA.

### Sesudah
- Guru bayar via **Midtrans Snap Popup** (GoPay, QRIS, Virtual Account, e-wallet, kartu kredit, dll).
- **Satu halaman**: klik "Bayar Sekarang" di halaman aktivasi → popup Snap muncul → bayar → akun aktif otomatis → token akses tampil + dikirim via WhatsApp.
- Alur manual QRIS GoPay **dihapus total** (payload QRIS, upload bukti, review manual).
- Midtrans **opsional lewat checkbox** di menu Keuangan superadmin. Jika nonaktif, halaman pembayaran menampilkan tombol hubungi admin via WhatsApp.

### Alur Baru (detail)

```
Guru daftar → akun pending → halaman /register/guru/pending
  → klik "Bayar Sekarang"
  → POST /payments/midtrans/start (AJAX)
      → buat/ambil transaksi pending (reference UJN-ymmdd-XXXXXXXX)
      → request Snap token ke Midtrans (order_id = reference_code)
  → popup Snap Midtrans muncul di halaman (snap.js window.snap.pay)
  → guru pilih metode & bayar
  → halaman polling GET /payments/midtrans/status?order_id=... (tiap 3 detik)
      → server cek status langsung ke API Midtrans (GET /v2/{order}/status)
      → settlement/capture → transaksi success + akun aktif + token dibuat
  → panel sukses: token akses + tombol copy + tombol "Masuk Sekarang"
  → token juga dikirim via WhatsApp (template event_payment_approved)

Jalur paralel (fallback polling):
  Webhook POST /api/payments/midtrans/notification
      → verifikasi signature sha512(order_id + status_code + gross_amount + server_key)
      → validasi nominal cocok dengan transaksi
      → settlement → aktivasi otomatis (idempotent)
  Finish URL GET /payments/midtrans/finish (callback dari Midtrans)
```

---

## 2. Arsitektur & File Terlibat

### Backend

| File | Peran |
|---|---|
| `app/Services/MidtransService.php` | Snap API client: `createSnapTransaction()` (buat order + token, retry saat order_id bentrok 409), `verifySignature()` sha512, `status()` cek status ke API Midtrans, `isEnabled()`, `isProduction()`, `baseUrl()` (sandbox/production) |
| `app/Http/Controllers/MidtransPaymentController.php` | `start()` (JSON: snap_token, client_key, order_id, nominal), `notification()` (webhook), `finish()` (callback URL + fallback cek status), `status()` (endpoint polling), `createPendingTransactionFor()` (buat/batalkan transaksi stale saat tarif berubah), `processStatusPayload()` (state machine status Midtrans → status transaksi) |
| `app/Models/PricingPlan.php` | `resolveForJenjang()` — pemilihan tarif per jenjang (fallback ke plan global) |
| `app/Models/Transaction.php` | Konstanta `PAYMENT_METHOD_MIDTRANS` / `PAYMENT_METHOD_MANUAL_QRIS` |
| `app/Services/PaymentApprovalService.php` | `approve()` — dipakai otomatisasi aktivasi (transaksi success + akun aktif + token) |
| `app/Http/Controllers/Superadmin/FinanceController.php` | Setting Midtrans (enabled, environment, server key, client key) di `app_settings` |
| `app/Http/Controllers/Superadmin/PaymentConfirmationController.php` | Riwayat transaksi: semua metode, filter status, search (termasuk order_id Midtrans) |

### Frontend

| File | Peran |
|---|---|
| `resources/views/pending-aktivasi.blade.php` | Halaman pembayaran utama: tombol Bayar Sekarang → load snap.js dinamis → popup Snap → panel loading/polling/sukses (token + copy)/gagal (coba lagi) |
| `resources/views/payments/midtrans-success.blade.php` | Halaman fallback finish URL dari Midtrans |
| `resources/views/superadmin/finance.blade.php` | Form setting Midtrans: checkbox aktifkan, mode (SSD dropdown), Client Key, Server Key. URL webhook tampil otomatis |

### Database

Migration `2026_09_03_000001_add_midtrans_columns_to_transactions_table.php` — kolom baru di `transactions`:

| Kolom | Isi |
|---|---|
| `payment_method` | `midtrans` / `manual_qris` (default `manual_qris` untuk data lama) |
| `midtrans_order_id` | order_id di Midtrans (= reference_code, atau `-R####` jika retry) |
| `midtrans_transaction_status` | status terakhir dari Midtrans (settlement, pending, expire, ...) |
| `midtrans_payment_type` | channel pembayaran (gopay, qris, bank_transfer, ...) |
| `paid_at` | waktu pembayaran sukses |

Setting Midtrans disimpan di tabel `app_settings` (key: `midtrans_enabled`, `midtrans_environment`, `midtrans_server_key`, `midtrans_client_key`) — **bukan** di `.env`, agar bisa diubah dari UI superadmin.

### Routes

```php
// routes/web.php
Route::post('/payments/midtrans/start',   [..., 'start'])->name('payments.midtrans.start');
Route::get('/payments/midtrans/finish',   [..., 'finish'])->name('payments.midtrans.finish');
Route::get('/payments/midtrans/status',   [..., 'status'])->name('payments.midtrans.status');

// routes/api.php (tanpa CSRF, throttle 120/menit)
Route::post('/payments/midtrans/notification', [..., 'notification'])
    ->name('api.payments.midtrans.notification');
```

### Keamanan

- **Signature verification**: webhook ditolak (401) jika `signature_key` tidak cocok dengan sha512(order_id + status_code + gross_amount + server_key)
- **Amount validation**: notifikasi dengan nominal tidak cocok dicatat sebagai `Log::critical` dan diabaikan
- **Idempotent**: notifikasi/webhook ganda tidak mengaktivasi ulang atau mengirim WA ganda
- **Session guard**: endpoint start/status/finish hanya bisa diakses jika session `pending_registration` milik guru yang bersangkutan (atau superadmin)
- **Fraud check**: status `capture` hanya dianggap sukses jika `fraud_status = accept`
- Webhook menolak semua request (503) jika Midtrans belum dikonfigurasi

### Test

`tests/Feature/MidtransPaymentTest.php` — 12 test: webhook settlement/expire/invalid-signature/amount-mismatch/disabled/idempotent, start (sukses, buat transaksi baru, tanpa session, sudah bayar), finish polling, status polling.

---

## 3. Tutorial Penggunaan

### A. Setup Superadmin (wajib sebelum guru bisa bayar)

1. Login superadmin → menu **Keuangan**
2. Di card **Pengaturan Pembayaran**:
   - Pastikan **Nomor WhatsApp Admin** terisi (untuk tombol hubungi admin & notifikasi)
   - Centang **Aktifkan pembayaran Midtrans**
   - Pilih **Mode**: `Sandbox (Uji Coba)` untuk uji coba / `Production` untuk live
   - Isi **Client Key** dan **Server Key**
3. Klik **Simpan Pengaturan**

> Keys diambil dari **Dashboard Midtrans** → Settings → Access Keys. Sandbox dan Production punya key berbeda — ganti keduanya saat pindah mode.

### B. Konfigurasi Dashboard Midtrans

1. Buka https://dashboard.sandbox.midtrans.com (ataa dashboard.midtrans.com untuk production)
2. **Settings → Configuration**:
   - **Payment Notification URL**: `{domain}/api/payments/midtrans/notification`
     - Contoh lokal dengan tunnel: `https://abc123.ngrok.io/api/payments/midtrans/notification`
     - URL lengkap juga tampil otomatis di menu Keuangan
   - **Finish Redirect URL**: bisa dikosongkan (finish URL sudah dikirim per-transaksi via callback `finish` di payload Snap)
3. Simpan.

> **Catatan localhost**: webhook tidak wajib untuk aktivasi — halaman pembayaran juga mengecek status langsung ke API Midtrans via polling. Jadi sandbox testing jalan sempurna tanpa public URL. Webhook penting untuk produksi agar sukses tercatat walau guru menutup browser tepat setelah bayar.

### C. Uji Coba (Sandbox)

1. Daftar akun guru baru → sampai halaman aktivasi
2. Klik **Bayar Sekarang** → popup Snap sandbox muncul
3. Pilih metode (contoh: **QRIS** atau **GoPay**) — untuk sandbox gunakan simulator VA/QR yang tersedia di dashboard (Transactions → pilih transaksi → tombol **Approve** untuk mensimulasikan pembayaran sukses, atau **Expire** untuk mensimulasikan gagal)
4. Setelah approve, halaman otomatis berubah ke panel sukses dengan token akses
5. Cek menu **Riwayat Transaksi** superadmin — transaksi tercatat `Sukses`, metode `Midtrans`, channel terlihat
6. Guru login pakai nomor WA + token akses

### D. Pindah ke Production

1. Pastikan review bisnis Midtrans **sudah disetujui** (mendapat akses key production)
2. Menu **Keuangan** → ganti Mode ke **Production** → ganti Server Key & Client Key production → Simpan
3. Daftarkan webhook URL production di dashboard production Midtrans
4. Selesai — tidak ada perubahan kode sama sekali

### E. Skenario Gagal / Pending

| Skenario | Yang terjadi |
|---|---|
| Guru menutup popup tanpa bayar | Halaman tetap polling sebentar; transaksi tetap `pending`; guru bisa klik "Bayar Sekarang" lagi |
| Pembayaran gagal/deny/cancel | Panel merah + tombol **Coba Bayar Lagi** (order baru otomatis) |
| Transaksi expire di Midtrans | Webhook menandai transaksi `failed` dengan alasan |
| Guru reload halaman aktivasi | Tombol bayar tetap tersedia; transaksi pending lama dipakai ulang (order_id sama) |
| Tarif berubah sebelum bayar | Transaksi pending lama otomatis dibatalkan, transaksi baru dibuat dengan nominal baru |

---

## 4. Catatan Teknis

- **Snap.js dimuat dinamis** sesuai mode (sandbox/production) dengan `data-client-key` dari DB — tidak ada key di source code
- **order_id retry**: jika order_id sudah pernah dipakai (409), sistem generate `{ref}-R####` otomatis
- **Jangan hapus** `payments/midtrans/finish` & `status` — tetap dipakai sebagai fallback dan halaman kembali dari popup di beberapa channel (misal VA yang buka tab baru)
- Transaksi manual lama (sebelum Midtrans) tetap terlihat di Riwayat Transaksi dengan badge **Manual**
- Dashboard superadmin card **Total Pendapatan** otomatis breakdown Midtrans vs Manual
