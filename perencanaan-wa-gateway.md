# Perencanaan Revisi Integrasi WhatsApp Gateway v4 (BE & FE)

Berdasarkan kesesuaian dengan struktur kode *project* Anda saat ini, ini adalah rancangan pasti (*fix plan*) dan spesifikasi *hook* yang akan kita implementasikan.

## 1. Konfigurasi Environment (`.env`)
Kita akan menambahkan konfigurasi untuk lingkungan lokal maupun *production*. URL *hosting* akan di-komen terlebih dahulu.
```env
# WA_GATEWAY_URL="https://wassap.reditech.id" # Untuk production nanti
WA_GATEWAY_URL="http://127.0.0.1:3000"        # Untuk lokal
WA_SENDER_ID="tka-admin"
# Note: QRIS_ADMIN_WHATSAPP mungkin menggunakan setting aplikasi di DB, tapi kita juga bisa sediakan fallback.
```

## 2. Pembuatan Komponen Backend Core
1. **Service API (`app/Services/WhatsAppService.php`):** *Class* untuk melakukan koneksi REST API (`sendMessage`, `sendMedia`) ke Node.js server.
2. **Database Migration (`create_whatsapp_logs_table`):** Tabel pemantau histori sukses/gagal pengiriman.
3. **Job Queue (`app/Jobs/SendWhatsAppBlast.php`):** Penanganan antrean pesan massal agar proses *controller* tidak *timeout*. Memiliki penanganan *retry* dan *delay*.
4. **Webhook Controller (`app/Http/Controllers/Api/WebhookController.php`):** Untuk merespons *chat* balasan otomatis dari sistem seperti "CEK HASIL" atau "LUPA TOKEN".

---

## 3. Titik Hook Implementasi pada Controller (Diselaraskan dengan Codebase)

### A. Alur Registrasi & Upload Bukti Bayar
- **Target File:** `app/Http/Controllers/RegisterGuruController.php` (Fungsi `uploadPaymentProof` dan `paymentData`).
- **Implementasi:** 
  - Saat ini kode me-*redirect* guru ke `wa.me/{admin_number}` untuk *chat* admin secara manual (pada baris `redirect()->away(...)`).
  - **Revisi:** Sistem akan memanggil `$whatsappService->sendMessage()` secara *background* untuk menembak pesan ke WA Superadmin berisi *"Halo Admin Ujion, Ada pembayaran pendaftar baru..."*.
  - Guru akan langsung diarahkan (redirect) kembali ke halaman sukses/login tanpa perlu *chat* WA admin manual.

### B. Alur Verifikasi Pembayaran (Approve / Reject)
- **Target File:** `app/Http/Controllers/Superadmin/PaymentConfirmationController.php` (Fungsi `approve` dan `reject`).
- **Implementasi:**
  - Saat ini Superadmin harus menyalin (*copy-paste*) hasil `GuruNotificationTemplates` untuk *chat* guru manual.
  - **Revisi:** Saat fungsi `approve()` dijalankan dan *Token Akses* di-generate, sistem otomatis mengirim pesan WA ke `$teacher->no_wa` berisi:
    > *"Halo [Nama Guru], Pembayaran aktivasi Ujion Anda telah berhasil disetujui. Berikut adalah Token Akses Anda: [Token]."*
  - Hal yang sama berlaku untuk `reject()`, sistem mengirim pesan WA langsung ke guru berisi alasan penolakan agar mereka melakukan *upload* ulang.

### C. Alur Publish Ujian / Tryout Baru
- **Target File:** `app/Http/Controllers/Superadmin/ExamController.php` (Fungsi `store`).
- **Implementasi:**
  - Saat ini, pembuatan ujian hanya men-*generate* token mapel (di `ExamMapelToken`).
  - **Revisi:** Tambahkan *logic* untuk mengambil seluruh data Guru dengan status akun Aktif (`User::where('account_status', 'aktif')`), lalu melempar *Queue* `SendWhatsAppBlast` ke masing-masing nomor WA.
  - Pesan blast: *"📢 Jadwal Ujian/Tryout Baru: [Judul Ujian] telah diterbitkan dan akan dimulai pada [Tanggal]. Silakan persiapkan siswa Anda."*

### D. Alur Cek Hasil / Lupa Token via Bot (Webhook)
- **Target File:** `routes/api.php` dan `WebhookController.php`.
- **Implementasi:** 
  - Jika guru membalas pesan "LUPA TOKEN", *webhook* mencocokkan nomor WA pengirim dengan kolom `no_wa` di tabel `users`. Jika cocok dan statusnya `aktif`, bot otomatis membalas dengan mengirimkan `access_token` terbaru.
  - (Skenario PDF Link untuk latihan siswa dapat ditambahkan jika siswa nantinya memiliki integrasi pengisian form nomor WA).

### E. Fitur Pengumuman Massal (Blast) oleh Superadmin
- **Target File:** Controller baru (misal: `app/Http/Controllers/Superadmin/WaBlastController.php`).
- **Implementasi:**
  - Mengambil data *form input* berupa teks pesan pengumuman dan target penerima (semua guru, atau guru jenjang tertentu).
  - Melakukan *looping* dan men-*dispatch* antrean menggunakan job `SendWhatsAppBlast`.
  - Pesan yang dikirim dapat disesuaikan sepenuhnya oleh Superadmin via textarea di halaman web.

---

## 4. Perencanaan Frontend (FE)

### A. Menu: "Koneksi WhatsApp" di Dashboard Superadmin
- **Lokasi Menu:** Sidebar Superadmin (di bawah Manajemen Sistem atau Settings).
- **Fungsi:** Halaman khusus merender UI yang melakukan koneksi *Socket.IO* ke URL Gateway (`http://127.0.0.1:3000`).
- **Tampilan:** Menampilkan pesan *"Menunggu Koneksi"*, me-*render* QR Code saat belum *login*, dan indikator *"Terhubung"* setelah admin memindai *barcode* dengan HP-nya.
- **File Target:** Pembuatan `resources/views/superadmin/wa-koneksi.blade.php`.

### B. Menu: "Blast Pengumuman" di Dashboard Superadmin
- **Lokasi Menu:** Sidebar Superadmin (bisa diletakkan berdekatan dengan Koneksi WhatsApp).
- **Fungsi:** Form UI bagi Superadmin untuk mengetik pesan pengumuman (*Broadcast*) ke para Guru secara kustom.
- **Tampilan:** Form input *textarea* untuk isi pesan, dan opsi checkbox/dropdown untuk filter target penerima (Semua Guru Aktif, Berdasarkan Jenjang, dll).
- **File Target:** Pembuatan `resources/views/superadmin/wa-blast.blade.php`.

*(Untuk notifikasi bot Telegram saat server mati/down, akan dibuatkan placeholder logika-nya yang bisa Anda teruskan nanti).*
