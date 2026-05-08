# Step Implementasi WhatsApp Gateway (Ujion TKA)

Dokumen ini merangkum langkah implementasi WhatsApp Gateway di project Laravel ini, berdasarkan PRD di `perencanaan-wa-gateway.md` dan referensi teknis di `konek.md` / `implementasi-wa-gateway.md`.

> Catatan: per 8 Mei 2026, yang sudah diimplementasikan di repo adalah **menu + halaman Superadmin** (Koneksi WhatsApp & Blast Pengumuman) beserta service+job minimal untuk eksekusi blast via queue.

---

## A. Persiapan

### 1) Konfigurasi environment (.env)

Tambahkan (atau sesuaikan) variabel berikut:

```env
# WA_GATEWAY_URL="https://wassap.reditech.id" # Untuk production nanti
WA_GATEWAY_URL="http://127.0.0.1:3000"        # Untuk lokal
WA_SENDER_ID="tka-admin"

# opsional (fallback notifikasi admin pembayaran)
QRIS_ADMIN_WHATSAPP="081234567890"

# opsional (alert healthcheck via Telegram)
TELEGRAM_BOT_TOKEN=""
TELEGRAM_CHAT_ID=""

# opsional (mengamankan endpoint webhook)
WA_WEBHOOK_KEY=""
```

### 2) Pastikan Node.js WA Gateway berjalan

- Jalankan service WA Gateway di port 3000 (atau sesuaikan `WA_GATEWAY_URL`).
- Minimal fitur yang harus tersedia dari WA Gateway (sesuai integrasi di repo ini):
    - Socket.IO client script tersedia di: `WA_GATEWAY_URL/socket.io/socket.io.js`
    - Socket event:
        - emit: `create-session` payload `{ id: WA_SENDER_ID }`
        - listen: `qr` (berisi base64 image) dan `ready`
    - REST API:
        - POST `/send-message`
        - POST `/send-media`

Checklist:

- [x] WA Gateway bisa diakses dari server Laravel (cek `socket.io.js`)
- [x] Scan QR berhasil dan status “Terhubung” muncul di menu Koneksi WhatsApp

Cara mengaktifkan WA Gateway (lokal / server):

1. Siapkan Node.js (disarankan LTS).
2. Jalankan project WA Gateway di folder:

- `C:\xampp\htdocs\WA_Gateway_v4`
  Perintah (PowerShell):

```bash
cd C:\xampp\htdocs\WA_Gateway_v4
npm install
node server.js
```

3. Pastikan port dan URL sesuai:
    - Jika gateway jalan di mesin yang sama: `WA_GATEWAY_URL="http://127.0.0.1:3000"`
    - Jika gateway jalan di mesin berbeda: gunakan IP/domain server gateway (contoh: `http://192.168.1.10:3000`)
4. Verifikasi cepat dari sisi server Laravel:

- Buka `WA_GATEWAY_URL/` via browser → harus mengembalikan JSON “Server is Running...”.
- Buka `WA_GATEWAY_URL/socket.io/socket.io.js` via browser.
- Atau gunakan PowerShell:
    - `curl http://127.0.0.1:3000/socket.io/socket.io.js -UseBasicParsing | Select-Object -First 1`

5. Masuk ke menu Superadmin → Koneksi WhatsApp, lalu scan QR dari HP admin.

Jika QR tidak muncul / Socket.IO gagal connect:

- Pastikan origin web Anda diizinkan oleh CORS Socket.IO gateway.
    - Jika Laravel jalan via XAMPP Apache (umumnya `http://localhost/...`), origin-nya `http://localhost`.
    - Jika Laravel jalan via `php artisan serve` port 8000, origin-nya `http://localhost:8000`.

Menjalankan WA Gateway 24/7:

- Opsi A (PM2, disarankan untuk server):
    ```bash
    cd C:\xampp\htdocs\WA_Gateway_v4
    npm install
    npm install -g pm2
    pm2 start server.js --name "wa-gateway"
    pm2 save
    ```
- Opsi B (Windows Task Scheduler):
    - Create Task… → Trigger: At startup / At log on
    - Action: Start a program
    - Program/script: `node`
    - Add arguments: `server.js`
    - Start in: `C:\xampp\htdocs\WA_Gateway_v4`

Opsional: mengaktifkan Webhook (agar chatbot Laravel menerima pesan masuk)

- WA Gateway v4 ini menyimpan URL webhook per device di file: `C:\xampp\htdocs\WA_Gateway_v4\session\webhook-<WA_SENDER_ID>.json`
- Cara paling mudah:
    1. Jalankan gateway + scan QR sampai ready
    2. Buat/ubah file (contoh untuk `WA_SENDER_ID=tka-admin`):
        - `C:\xampp\htdocs\WA_Gateway_v4\session\webhook-tka-admin.json`
        - Isi:
            ```json
            { "url": "<APP_BASE_URL>/api/wa-webhook", "key": "" }
            ```
        - Contoh APP_BASE_URL:
            - Jika pakai `php artisan serve --port=8000`: `http://127.0.0.1:8000`
            - Jika pakai XAMPP Apache: `http://localhost/ujion-tka-apps/public`
    3. Restart WA Gateway supaya URL webhook terbaca konsisten

Catatan keamanan:

- Jika Anda mengaktifkan `WA_WEBHOOK_KEY`, isi `key` di file webhook agar WA Gateway mengirim header `X-WA-WEBHOOK-KEY`.

Opsional (disarankan untuk production): aktifkan keamanan `WA_WEBHOOK_KEY`

1. Set di `.env` Laravel:

- `WA_WEBHOOK_KEY="<SECRET>"`

2. Set di WA Gateway untuk device terkait (contoh `tka-admin`):

- `C:\xampp\htdocs\WA_Gateway_v4\session\webhook-tka-admin.json`
- Isi `key` dengan secret yang sama:
    ```json
    { "url": "http://127.0.0.1:8000/api/wa-webhook", "key": "<SECRET>" }
    ```

---

## B. Implementasi Menu (Superadmin)

### 1) Tambah route Superadmin

Tambahkan route untuk:

- Halaman koneksi/scan QR
- Halaman blast + POST kirim blast

Checklist:

- [x] GET `/superadmin/wa-koneksi`
- [x] GET `/superadmin/wa-blast`
- [x] POST `/superadmin/wa-blast`

### 2) Tambah menu di sidebar Superadmin

Letakkan menu di section “Sistem” atau “Settings”:

- [x] Menu: **Koneksi WhatsApp**
- [x] Menu: **Blast Pengumuman**

### 3) Halaman “Koneksi WhatsApp” (Scan QR)

Spesifikasi minimum UI:

- [x] Teks status: “Menunggu Koneksi / Menghubungkan...”
- [x] Render QR code saat event `qr` diterima
- [x] Indikator “Terhubung” saat event `ready` diterima

Spesifikasi integrasi Socket.IO (mengacu konek.md):

- Hubungkan ke `WA_GATEWAY_URL`
- Emit event: `create-session` payload `{ id: WA_SENDER_ID }`
- Listen event:
    - `qr` → `data.src` adalah image base64
    - `ready` → menandakan WhatsApp aktif

### 4) Halaman “Blast Pengumuman”

Spesifikasi minimum UI:

- [x] Textarea isi pesan
- [x] Target penerima:
    - Semua guru aktif
    - Guru aktif per jenjang
- [x] Submit akan men-_dispatch_ job queue per penerima

---

## C. Backend Core (Wajib untuk integrasi penuh)

### 1) Service WhatsApp (REST API)

Buat service terpusat untuk:

- [x] sendMessage(number, message) → POST `/send-message`
- [x] sendMedia(number, url, caption) → POST `/send-media`

Catatan:

- Normalisasi nomor: hilangkan karakter non-digit, `08xxxx` menjadi `628xxxx`.
- Gunakan timeout untuk request.

### 2) Job Queue pengiriman

Buat job `SendWhatsAppBlast`:

- [x] `tries = 3`
- [x] `backoff = [60, 180, 300]`
- [x] dipakai untuk blast massal dan notifikasi otomatis

Rekomendasi:

- Hindari `sleep()` di dalam job (memblokir worker).
- Beri `delay()` saat dispatch (acak 2-7 detik) untuk mengurangi risiko pembatasan WhatsApp.

### 3) Queue worker

Jalankan worker (lokal/production):

```bash
php artisan queue:work --queue=high,low
```

---

## D. Hook Integrasi ke Flow Aplikasi (sesuai PRD)

### 1) Registrasi & upload bukti bayar

Target:

- Register Guru Controller (fungsi upload/payment) yang sebelumnya redirect ke `wa.me`.

Checklist:

- [x] Ganti redirect manual `wa.me` → otomatis kirim pesan ke admin via `sendMessage()` (background/job)
- [x] Redirect guru kembali ke halaman sukses/login

### 2) Verifikasi pembayaran (Approve/Reject) oleh Superadmin

Target:

- `Superadmin/PaymentConfirmationController` (approve/reject)

Checklist:

- [x] approve: kirim token akses ke nomor guru (`no_wa`)
- [x] reject: kirim alasan penolakan ke nomor guru

### 3) Publish ujian baru

Target:

- `Superadmin/ExamController@store`

Checklist:

- [x] Ambil seluruh guru aktif
- [x] Dispatch `SendWhatsAppBlast` (queue low)

### 4) Webhook chatbot (LUPA TOKEN, CEK HASIL, MENU)

Target:

- `routes/api.php` + `WebhookController`

Checklist:

- [x] POST `/api/wa-webhook`
- [x] Validasi keamanan (API key / whitelist IP)
- [x] Mapping nomor pengirim → user.no_wa

Aktivasi & test end-to-end (WA → Gateway → Laravel → auto-reply):

- [x] WA Gateway menerima `webhook` URL otomatis saat create-session (dari halaman Koneksi WhatsApp)
- [x] File webhook device terisi (contoh: `WA_Gateway_v4/session/webhook-tka-admin.json`)
- [x] Test: kirim chat ke nomor admin dengan kata `MENU` → harus auto-reply daftar perintah
- [x] Test: kirim chat `LUPA TOKEN` dari nomor guru terdaftar → harus balas token baru

---

## E. Logging & Monitoring (opsional, tapi disarankan)

### 1) Tabel whatsapp_logs

Checklist:

- [x] Migration `whatsapp_logs` untuk histori sukses/gagal

### 2) Ping healthcheck WA Gateway

Checklist:

- [x] Scheduler ping `WA_GATEWAY_URL` tiap 10 menit
- [x] Jika down, kirim alert (email/telegram)

Catatan operasional:

- Healthcheck dijalankan oleh Laravel Scheduler, jadi harus ada proses yang memicu `php artisan schedule:run` setiap menit.
- Alert Telegram hanya aktif jika `.env` berisi `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID`.

---

## F. Operasional (wajib agar otomatis berjalan)

### 1) Aktifkan Laravel Scheduler di Windows (Task Scheduler)

Tujuan: supaya schedule (termasuk healthcheck WA Gateway) benar-benar dieksekusi.

Langkah:

1. Buka **Task Scheduler** → **Create Task...**
2. Tab **General**:

- Name: `Laravel Scheduler (UjionTKA)`
- Centang: **Run whether user is logged on or not**
- Centang: **Run with highest privileges**

3. Tab **Triggers** → **New...**:

- Begin the task: **On a schedule**
- Settings: **Daily** (bebas), Start: sekarang
- Advanced settings:
    - Centang **Repeat task every:** `1 minute`
    - For a duration of: `Indefinitely`

4. Tab **Actions** → **New...**:

- Action: **Start a program**
- Program/script: `C:\xampp\php\php.exe`
- Add arguments: `artisan schedule:run`
- Start in: `C:\xampp\htdocs\ujion-tka-apps`

5. Save, lalu klik kanan task → **Run** (uji manual)

Verifikasi:

- `php artisan schedule:list`
- `php artisan schedule:run -v`

Alternatif (paling cepat untuk lokal, tanpa Task Scheduler):

- Jalankan di terminal terpisah: `php artisan schedule:work`

Checklist:

- [ ] Task Scheduler aktif (repeat tiap 1 menit)
- [ ] `wa-gateway-healthcheck` muncul di `schedule:list`

### 2) Jalankan Queue Worker (agar blast & notifikasi otomatis terkirim)

Minimal (lokal):

```bash
php artisan queue:work --queue=high,low
```

Checklist:

- [ ] Queue worker berjalan (tidak berhenti)

### 3) Urutan aktivasi yang disarankan

1. Nyalakan WA Gateway (Node)
2. Pastikan Scheduler jalan (Task Scheduler)
3. Jalankan Queue worker
4. Scan QR via menu Koneksi WhatsApp

---

## Implementasi yang sudah masuk repo

- Menu & halaman Superadmin:
    - Koneksi WhatsApp (scan QR)
    - Blast Pengumuman (dispatch queue)
- Service + Job minimal:
    - `WhatsAppService`
    - `SendWhatsAppBlast`
