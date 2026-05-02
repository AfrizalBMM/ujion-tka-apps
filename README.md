# Ujion TKA

Platform ujian terintegrasi berbasis Laravel untuk `superadmin`, `guru/operator`, dan `siswa`.

Project ini saat ini berpusat pada:

- registrasi dan aktivasi guru
- pengelolaan QR pembayaran dan nominal aktivasi per jenjang
- materi dan bank soal
- paket soal TKA
- manajemen ujian
- simulasi guru dan pengerjaan siswa
- live chat guru dengan superadmin

## Stack

- Laravel 12
- PHP 8.2+
- MySQL / MariaDB
- Tailwind CSS
- Vite

## Environment & Secrets

- Jangan pernah commit file `.env` atau varian-nya ke repository.
- Gunakan `.env.example` sebagai template, lalu buat `.env` di mesin masing-masing.
- Untuk production: set `APP_ENV=production` dan `APP_DEBUG=false`.
- Jika ada indikasi `.env` / `APP_KEY` pernah bocor/ter-commit, lakukan rotasi key di server:
    - Jalankan `php artisan key:generate --force`
    - Lalu `php artisan config:clear` dan `php artisan cache:clear`
    - Catatan: rotasi `APP_KEY` akan meng-invalidasi session dan data terenkripsi lama.

## Pintu Masuk Aplikasi

- Landing: `/`
- Register guru: `/register/guru`
- Login guru: `/login`
- Login superadmin: `/ngadimin/login`
- Login siswa: `/siswa/login`
- Login siswa (latihan materi): `/siswa/latihan/login`

## Ringkasan Role

### Superadmin

Superadmin mengelola operasional sistem:

- dashboard
- keuangan & QR
- aktivasi guru
- live chat
- master materi
- bank soal global
- paket soal TKA
- manajemen ujian
- audit log
- panduan

### Guru / Operator

Guru menggunakan sistem untuk:

- registrasi dan aktivasi akun
- melihat materi sesuai jenjang
- bookmark materi
- melihat bank soal global Ujion
- bookmark soal global
- membuat dan mengelola bank soal pribadi
- mengatur paket soal yang boleh diakses
- simulasi ujian
- live chat dengan superadmin
- mengelola profil

### Siswa

Siswa menggunakan token ujian untuk:

- masuk ke sesi ujian
- mengisi identitas
- membaca petunjuk
- mengerjakan ujian per mapel
- menyimpan jawaban
- menyelesaikan ujian dan melihat status akhir

Siswa juga bisa menggunakan token **latihan materi** untuk:

- masuk ke halaman latihan berbasis materi
- mengerjakan **telaah soal** (feedback benar/salah + pembahasan, boleh retry)
- mengerjakan **paket latihan 1–3** (sekali submit per paket)

## Flow Aktif Utama

### 1. Registrasi Guru

1. Calon guru daftar di `/register/guru`
2. Sistem membuat akun pending
3. Guru masuk ke halaman `pending aktivasi`
4. Guru melihat QR dan nominal berdasarkan `jenjang`
5. Guru upload bukti pembayaran
6. Sistem membuka WhatsApp admin dengan template data guru
7. Superadmin memverifikasi lalu mengaktifkan akun

### 2. Operasional Guru

1. Guru login memakai `nama + access token`
2. Guru masuk ke dashboard
3. Guru mengakses materi, soal Ujion, bank soal pribadi, paket soal, simulasi, atau chat

### 3. Operasional Superadmin

1. Superadmin login memakai `email + password`
2. Superadmin menyiapkan QR dan nominal aktivasi per jenjang
3. Superadmin mengelola guru, materi, bank soal global, paket soal, dan ujian
4. Superadmin berkomunikasi dengan guru melalui live chat

### 4. Flow Ujian

1. Superadmin membuat paket soal
2. Paket diisi mapel, teks bacaan, dan soal
3. Superadmin membuat ujian dari paket
4. Token ujian dibagikan
5. Guru bisa memakai token untuk simulasi
6. Siswa login dengan token
7. Sistem membuat sesi ujian dan menyimpan jawaban
8. Sistem menghitung skor saat ujian selesai

### 5. Flow Latihan Materi (Baru)

1. Superadmin memilih materi lalu menyiapkan:

- 2 soal **telaah** (PG dari `global_questions` yang aktif)
- token latihan + snapshot paket 1–3 (acak dari bank soal global per materi), jumlah soal per paket: 10/15

2. Guru membagikan token latihan ke siswa
3. Siswa membuka `/siswa/latihan/login`, input token
4. Siswa isi identitas (nama wajib, WA opsional)
5. Di dashboard latihan:

- bagian **telaah**: siswa jawab → sistem langsung tampil benar/salah + pembahasan, dan boleh ganti jawaban (retry)
- bagian **paket latihan**: siswa kerjakan paket 1–3 dan submit (sekali submit per paket)

6. Guru melihat analisis hasil latihan di menu hasil (area guru)
7. Guru dapat download PDF per paket (paket 1–3)

## Modul Utama

### Superadmin

- Dashboard operasional
- Keuangan & QR per jenjang
- Konfirmasi dan aktivasi guru
- Live chat guru/operator
- Daftar guru dan token akses
- Master materi
- Bank soal global
- Paket soal TKA
- Manajemen ujian
- Audit log

Tambahan:

- Latihan materi (token per materi + telaah + snapshot paket latihan)

### Guru

- Dashboard
- Profil dan password
- Materi + bookmark
- Bank soal global Ujion + bookmark
- Bank soal pribadi + builder fullscreen
- Paket soal TKA
- Teks bacaan
- Simulasi ujian
- Live chat
- Panduan

Tambahan:

- Download PDF paket latihan materi
- Analisis hasil latihan materi

### Siswa

- Login token
- Identitas peserta
- Petunjuk ujian
- Pengerjaan ujian
- Selesai ujian

Tambahan:

- Login token latihan
- Dashboard latihan materi (telaah + paket 1–3)

## Arsitektur Data Singkat

### Jalur utama ujian

- `paket_soals`
- `mapel_pakets`
- `teks_bacaans`
- `soals`
- `pilihan_jawabans`
- `pasangan_menjodohkans`
- `ujian_sesis`
- `jawaban_siswas`

### Jalur pendukung

- `global_questions` untuk bank soal global resmi
- `personal_questions` untuk bank soal pribadi guru
- `questions` dan `exam_question` masih dipakai untuk sebagian builder/admin compatibility
- `participants` dan `participant_answers` masih ada untuk modul legacy tertentu

### Jalur latihan materi (Baru)

- `material_practice_tokens` (1 token per materi)
- `material_telaah_questions` (2 soal telaah per materi)
- `material_practice_packages` (paket 1–3 per token)
- `material_practice_package_questions` (snapshot soal per paket)
- `material_practice_sessions` (identitas siswa per token)
- `material_telaah_answers` (jawaban telaah; retry = update)
- `material_practice_package_attempts` (1 attempt per siswa per paket; submit sekali)
- `material_practice_package_answers` (jawaban paket)

Catatan desain:

- Paket latihan adalah **snapshot per token** (bukan per siswa) agar konsisten dan cocok dengan PDF.

## Struktur Folder Penting

```text
app/
  Http/
    Controllers/
      Guru/
      Siswa/
      Superadmin/
    Middleware/
  Models/
database/
  migrations/
  seeders/
resources/
  js/
    core/
    utils/
    pages/
  views/
    guru/
    superadmin/
    ujian/
routes/
  web.php
  guru.php
tests/
  Feature/
```

## Frontend Notes

Frontend saat ini sedang dirapikan agar script tidak menumpuk di Blade.

Pola yang dipakai:

- script global di `resources/js/core`
- helper reusable di `resources/js/utils`
- script halaman di `resources/js/pages`

Contoh yang sudah dipisah:

- layout controls
- KaTeX auto-render
- live filter halaman
- builder soal pribadi
- chat guru/superadmin
- beberapa halaman ujian dan paket soal

## Setup Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

## Catatan PDF

Fitur PDF paket latihan memakai `barryvdh/laravel-dompdf`.

- Endpoint PDF hanya tersedia untuk role `guru`
- View template PDF ada di `resources/views/guru/material-practice/package-pdf.blade.php`

## Route Penting (Latihan Materi)

Superadmin:

- `GET /superadmin/materials/{material}/practice` (konfigurasi telaah + token)

Siswa:

- `GET /siswa/latihan/login` → `POST /siswa/latihan/login` (validasi token)
- `GET /siswa/latihan/identitas` → `POST /siswa/latihan/mulai`
- `GET /siswa/latihan` (dashboard)
- `GET/POST /siswa/latihan/paket/{paketNo}`

Guru:

- `GET /guru/materials/{material}/latihan/paket/{paketNo}/pdf`
- `GET /guru/results/latihan-materi`

## Deployment

Saat men-deploy ke server production (shared hosting atau VPS), pastikan langkah-langkah berikut dilakukan:

1.  **Storage Link (Kritis)**: Jalankan `php artisan storage:link`. Tanpa ini, bukti pembayaran guru dan gambar soal tidak akan muncul (404). Jika di shared hosting tidak ada akses SSH, gunakan route sementara atau symlink manual via file manager.
2.  **Optimasi Konfigurasi**:
    ```bash
    composer install --optimize-autoloader --no-dev
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```
3.  **Database**: Jalankan `php artisan migrate --force` untuk memperbarui skema.
4.  **Aset**: Jalankan `npm run build` untuk memproses file CSS/JS.
5.  **Environment**: Pastikan `APP_ENV=production` dan `APP_DEBUG=false` di file `.env` server.

## Konfigurasi QRIS

Fitur pembayaran QRIS guru membutuhkan konfigurasi berikut di `.env`:

```env
GOPAY_MASTER_PAYLOAD=
QRIS_ADMIN_WHATSAPP=
```

Catatan:

- `GOPAY_MASTER_PAYLOAD` wajib diisi. Ini bukan API key GoPay, tetapi raw string QRIS statis dari merchant GoPay yang dipakai sistem untuk inject nominal dinamis.
- `QRIS_ADMIN_WHATSAPP` opsional sebagai fallback nomor admin. Nilai ini juga bisa dioverride dari menu Superadmin > Keuangan & QR.
- Jika Anda mengubah env di server lokal/production, jalankan `php artisan config:clear` agar konfigurasi terbaru terbaca.

## Testing

```bash
php artisan test
```

## Dokumen Pendukung

- `allmenu.md` untuk peta menu dan halaman aktif
- `guru.md` untuk flow dan fitur role guru
- `superadmin.md` untuk flow dan fitur role superadmin
- `alur.md` untuk gambaran alur aplikasi
- `ERD.md` untuk relasi data
- `DFD.md` dan diagram terkait untuk analisis proses
- `errorbug.md` untuk daftar bug dan prioritas perbaikan

## Catatan Arsitektur

- Project ini masih memiliki schema aktif dan sebagian schema legacy yang hidup berdampingan
- Flow ujian aktif siswa dan simulasi guru memakai schema baru `ujian_sesis` dan `jawaban_siswas`
- Sebagian builder/admin compatibility masih memakai tabel snapshot lama
- Flow registrasi guru sekarang tersambung langsung dengan modul finance superadmin
- QR pembayaran disederhanakan menjadi model operasional per jenjang
- Aksi sensitif (hapus masal, import) dilindungi oleh **Laravel Policies** untuk otorisasi granular
- Beberapa modul superadmin besar masih layak diaudit dan dirapikan lebih lanjut dari sisi JS dan UX internal
