# Panduan Implementasi Step-by-Step WhatsApp Gateway v4 di Ujion TKA

Dokumen ini merangkum langkah-langkah teknis secara berurutan untuk mengimplementasikan integrasi antara **Laravel (Ujion TKA)** dengan **Node.js WhatsApp Gateway v4** berdasarkan dokumen PRD `konek.md` dan `resume.md`.

## Tahap 1: Persiapan dan Konfigurasi Dasar

### 1. Konfigurasi Environment Laravel
Tambahkan kredensial koneksi WA Gateway ke dalam file `.env` Laravel:
```env
WA_GATEWAY_URL="http://127.0.0.1:3000"
WA_SENDER_ID="tka-admin"
QRIS_ADMIN_WHATSAPP="081234567890" # Nomor tujuan notifikasi superadmin
```

### 2. Pembuatan Service `WhatsAppService`
Buat service class terpusat untuk menangani *request HTTP* ke API Node.js Gateway.
- **Path File:** `app/Services/WhatsAppService.php`
- **Fungsi Utama:** `sendMessage($number, $message)` untuk teks, dan `sendMedia($number, $url, $caption)` untuk lampiran.
- **Fitur Tambahan:** Menangkap *exception/error* dan mengembalikan status balasan dengan seragam.

### 3. Persiapan Database Log Riwayat Pesan
Buat *migration* untuk tabel `whatsapp_logs` agar histori pesan terkirim/gagal dapat dipantau di sisi backend:
```bash
php artisan make:migration create_whatsapp_logs_table
```
Kolom tabel meliputi: `phone`, `message`, `status`, dan `response_data`.

---

## Tahap 2: Manajemen Queue (Pesan Masal & Blast)

Sistem wajib menggunakan antrean (*Queue*) di Laravel agar proses pengiriman pesan massal atau otomatis tidak menyebabkan server aplikasi (*web request*) lambat atau *timeout*.

### 1. Buat Job Pengiriman WA
```bash
php artisan make:job SendWhatsAppBlast
```
- **Path File:** `app/Jobs/SendWhatsAppBlast.php`
- **Konfigurasi:** 
  - Tambahkan *delay* atau `sleep(rand(2, 5))` acak antara 2-5 detik untuk menghindari *banned* WhatsApp.
  - Atur batas *retry* maksimal (`public $tries = 3;`).
  - Atur jeda antar percobaan gagal (`public $backoff = [60, 180, 300];`).

### 2. Menjalankan Worker
Pastikan *queue worker* berjalan di *environment production* dengan memprioritaskan antrean OTP di atas antrean massal:
```bash
php artisan queue:work --queue=high,low
```

---

## Tahap 3: Integrasi Alur Kerja Web (Controllers)

### 1. Alur Registrasi & Pembayaran Guru
Di dalam controller registrasi (misal: `FinanceController`):
- **Upload Bukti:** Saat guru mengunggah bukti bayar QRIS, panggil `WhatsAppService` untuk menembak notifikasi langsung ke WA Admin. Kirim juga pesan konfirmasi tanda terima ke nomor guru.
- **Aktivasi:** Saat Admin menekan tombol aktifkan akun, kirim pesan berisi sambutan dan **Access Token** ke WA guru.

### 2. Alur Pengumuman Jadwal Ujian (Blast)
Di controller manajemen ujian (`ExamController`):
- Saat Superadmin menerbitkan jadwal ujian baru, lakukan perulangan *query* ke seluruh guru aktif.
- *Dispatch* job `SendWhatsAppBlast` dengan *payload* rincian ujian.
- Gunakan antrean berprioritas rendah (`dispatch()->onQueue('low')`) agar OTP tetap lancar.

### 3. Alur Pengiriman Hasil Latihan Materi
Di controller latihan (`MaterialPracticeController`):
- Alih-alih mengirim file PDF yang berat via WA, panggil `sendMessage` untuk mengirim *link/URL tautan* ke file PDF unduhan.

---

## Tahap 4: Implementasi Webhook & Auto-Reply (Chatbot)

### 1. Endpoint Webhook Laravel
Buat *route* API untuk mendengarkan/menangkap pesan WA yang masuk (diteruskan oleh Node.js):
- **Routing:** `Route::post('/wa-webhook', [WebhookController::class, 'handle']);` di `routes/api.php`

### 2. Logika Chatbot di `WebhookController`
Sistem akan membaca teks yang dikirim pengguna. Terapkan logika respons dinamis:
- `"CEK HASIL"`: Cari data skor pengguna di database (berdasarkan nomor HP) dan balas teks skor.
- `"LUPA TOKEN"`: Cek ketersediaan akun, lalu kirim ulang Access Token login.
- `"MENU" / "HALO"`: Balas dengan daftar perintah chatbot yang tersedia.

### 3. Keamanan Webhook
- Lakukan validasi *IP Whitelisting* (tolak permintaan kecuali dari IP lokal Node.js) atau sertakan API Key pada *Header Request* yang divalidasi Middleware.

---

## Tahap 5: Integrasi UI Dashboard Superadmin

### 1. Halaman Scan QR Code Live
Buat *view* Blade khusus (`resources/views/superadmin/wa-koneksi.blade.php`) agar Admin bisa melakukan *scan* akun WA langsung dari web (tanpa perlu membuka VPS).
- Masukkan *library* klien Socket.IO.
- Lakukan koneksi Websocket dengan server Node.js dan kirim perintah `create-session`.
- Tangkap tanggapan berupa *base64 image URL* untuk dirender di tag `<img>`.

### 2. Monitoring Kesehatan Server (Ping)
Tambahkan fungsi dalam *Task Scheduling* (`Console/Kernel.php` atau `routes/console.php`) untuk me-ngecek (ping) *endpoint* `/` pada WA Gateway setiap 10 menit. Apabila gagal, kirim peringatan terpusat ke tim teknis/developer.
