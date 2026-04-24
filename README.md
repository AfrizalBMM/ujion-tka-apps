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

## Pintu Masuk Aplikasi

- Landing: `/`
- Register guru: `/register/guru`
- Login guru: `/login`
- Login superadmin: `/ngadimin/login`
- Login siswa: `/siswa/login`

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

### Siswa

- Login token
- Identitas peserta
- Petunjuk ujian
- Pengerjaan ujian
- Selesai ujian

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
npm run dev
php artisan serve
```

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
- Beberapa modul superadmin besar masih layak diaudit dan dirapikan lebih lanjut dari sisi JS dan UX internal
