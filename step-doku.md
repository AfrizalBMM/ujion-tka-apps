# Implementasi Payment Gateway Doku — Ujion TKA

Dokumen ringkasan + panduan implementasi **Doku Checkout** pada project Ujion TKA. Menggantikan Midtrans Snap dan alur QRIS GoPay manual (keduanya sudah dihapus total).

## Perubahan dari Midtrans

| Aspek | Midtrans (lama) | Doku (baru) |
|---|---|---|
| Interaksi user | Popup Snap di halaman (snap.js) | Redirect penuh ke hosted checkout page Doku |
| Order identifier | `order_id` | `invoice_number` |
| Cek status | `GET /v2/{order_id}/status` (basic auth server key) | `GET /orders/v1/status/{invoice_number}` (signature HMAC) |
| Webhook signature | sha512 payload fields | HMAC-SHA256 dari komponen header + Digest body |
| Kredensial | Server Key + Client Key, mode sandbox/production | Client-Id (`BRN-...`) + Secret-Key (`SK-...`), production saja |
| Kolom DB | `midtrans_*` | `doku_*` (rename via migration `2026_09_11_000001_rename_midtrans_columns_to_doku.php`) |

## Arsitektur

### Flow guru (aktivasi akun)

```
Register guru (manual/Google) → auto-login (status pending) → dashboard guru (menu locked)
  → klik menu locked / tombol "Bayar Sekarang" (JS core/doku-checkout.js)
      → POST /payments/doku/start (AJAX, auth-based)
      → window.open ke response.payment.url (checkout Doku di tab baru)
  → halaman utama polling GET /payments/doku/status?order_id=... tiap 3 detik
  → setelah bayar, Doku redirect popup ke callback_url:
      GET /payments/doku/finish?order_id={invoice}
  → popup kirim postMessage 'doku-payment-finished' ke opener + window.close()
  → halaman utama redirect ke payments/doku/finish → tampil token akses, akun aktif, menu terbuka
  → jika user cancel: callback_url_cancel → GET /payments/doku/cancel → popup-close view
```

Guru pending diizinkan middleware `guru.active` hanya untuk `guru.dashboard`, `guru.profile*`, `guru.chat*`, `guru.guide`. Route lain → redirect dashboard + flash. Halaman `pending-aktivasi` & flow resume dihapus — guru yang kehilangan session cukup daftar ulang dengan email/WA sama (deteksi akun pending → auto-login).

### Flow ujian publik (landing ujian-online)

```
Daftar ujian publik → halaman pending order
  → klik "Bayar Sekarang" → POST /ujian-online/pay/{orderToken}
  → window.open ke checkout Doku (halaman pending tetap terbuka + polling)
  → setelah bayar popup ke GET /ujian-online/pay/finish → popup-close view (postMessage + close)
  → halaman pending update otomatis jadi tombol "Mulai Ujian"
```

### Webhook (produksi)

```
POST /api/payments/doku/notification
Headers: Client-Id, Request-Id, Request-Timestamp, Signature
Body: order.invoice_number, order.amount, transaction.status, payment.channel
```

Signature diverifikasi server-side (HMAC-SHA256 komponen `Client-Id/Request-Id/Request-Timestamp/Request-Target/Digest`), timestamp maksimal 5 menit (anti-replay), nominal dicocokkan dengan transaksi.

Status mapping: `SUCCESS` → sukses (aktifkan akun/kirim token WA), `FAILED`/`EXPIRED`/`CANCELLED` → gagal, selain itu pending.

## File Terlibat

| File | Tanggung jawab |
|---|---|
| `app/Services/DokuService.php` | Klien API Doku: signature/digest, `createCheckoutPayment()`, `createCheckoutPaymentForOrder()`, `checkStatus()`, `verifyNotificationSignature()` |
| `app/Http/Controllers/DokuPaymentController.php` | `start()` (auth-based), `notification()` (webhook), `finish()`, `status()` (polling), `cancel()` (popup-close) |
| `app/Http/Controllers/LandingExamPaymentController.php` | Payment ujian publik |
| `app/Http/Middleware/EnsureGuruAccountIsActive.php` | Guru pending hanya boleh dashboard/profile/chat/guide; route lain redirect dashboard |
| `resources/js/core/doku-checkout.js` | Window.open checkout, polling status, listener postMessage (config via `data-doku-config` di body layout guru) |
| `app/Models/Transaction.php` | Constant `PAYMENT_METHOD_DOKU = 'doku'` |
| `database/migrations/2026_09_11_000001_rename_midtrans_columns_to_doku.php` | Rename kolom `midtrans_*` → `doku_*`, migrasi `payment_method`, pindah setting `qris_admin_whatsapp` → `admin_whatsapp`, hapus setting `midtrans_*` |
| `resources/views/layouts/guru.blade.php` | Menu locked (sidebar + bottom nav) untuk guru pending, inject `data-doku-config` |
| `resources/views/guru/dashboard.blade.php` | Banner pembayaran (idle/loading/polling/failed) untuk guru pending |
| `resources/views/payments/doku-success.blade.php` | Halaman finish: tampil token (main window) / auto-close (popup) |
| `resources/views/payments/doku-popup-close.blade.php` | Halaman penutup popup (cancel guru & finish ujian publik): postMessage + window.close + fallback link |
| `resources/views/ujian-online/pending.blade.php` | UI bayar ujian publik (new window + polling) |
| `tests/Feature/DokuPaymentTest.php` | Test: webhook success/expire/invalid-signature/amount-mismatch/disabled/idempotent, start (auth-based), finish polling, status polling, cancel |

## Routes

```php
Route::post('/payments/doku/start',   [..., 'start'])->name('payments.doku.start');
Route::get('/payments/doku/finish',   [..., 'finish'])->name('payments.doku.finish');
Route::get('/payments/doku/status',   [..., 'status'])->name('payments.doku.status');
Route::get('/payments/doku/cancel',   [..., 'cancel'])->name('payments.doku.cancel');

// routes/api.php
Route::post('/payments/doku/notification', [..., 'notification'])
    ->name('api.payments.doku.notification');
```

## Cara Pakai

### A. Konfigurasi Aplikasi

1. Login superadmin → menu **Keuangan**
2. Centang **Aktifkan pembayaran**
3. Isi **Client-Id** (`BRN-...`) dan **Secret-Key** (`SK-...`) dari dashboard Doku
4. Simpan

Setting disimpan di tabel `app_settings` (key: `doku_enabled`, `doku_client_id`, `doku_secret_key`) — **bukan** di `.env`.

### B. Konfigurasi Dashboard Doku

Saat production, daftarkan **Notification URL** di dashboard Doku (Settings):

```
https://{domain-anda}/api/payments/doku/notification
```

> **Catatan localhost**: webhook tidak wajib — halaman finish/pending mengecek status langsung ke API Doku via polling `GET /orders/v1/status/{invoice}`. Webhook penting di produksi agar sukses tercatat walau user menutup browser tepat setelah bayar. Redirect `callback_url`/`callback_url_cancel` tetap jalan di lokal karena dieksekusi browser user.

### C. Testing Lokal

```bash
php artisan test --filter=DokuPaymentTest
```

## Troubleshooting

| Gejala | Penyebab umum |
|---|---|
| "Doku belum dikonfigurasi" | Client-Id/Secret-Key kosong atau checkbox belum aktif |
| Webhook 401 | Signature tidak cocok — pastikan Secret-Key sama, Notification URL path persis `/api/payments/doku/notification` |
| Webhook 503 | Checkbox Doku nonaktif |
| Start 502 | API Doku menolak (cek log `Laravel` "Doku checkout create failed") |
| Transaksi tidak update walau user sudah bayar | Webhook belum terdaftar di dashboard Doku — halaman finish/pending akan tetap meng-update via polling |
