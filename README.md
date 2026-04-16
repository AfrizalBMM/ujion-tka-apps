# Ujion TKA

Platform ujian terintegrasi berbasis Laravel untuk superadmin, guru/operator, dan siswa. Project ini sekarang memakai satu jalur utama untuk flow ujian siswa berbasis `paket_soal`, `mapel_paket`, `soal`, `ujian_sesi`, dan `jawaban_siswa`, sambil tetap mempertahankan beberapa tabel snapshot legacy yang masih dipakai builder ujian admin.

## Stack

- Laravel 12
- PHP 8.2+
- Tailwind CSS
- Vite
- MySQL/MariaDB

## Peran dan Akses

### Superadmin
- Login dari `/ngadimin/login`
- Kelola guru, review pembayaran, token akses, materi, bank soal global, paket soal, ujian, audit log, chat, dan finance
- Route `superadmin/*` sudah diproteksi `auth` dan `role:superadmin`

### Guru / Operator
- Login dari `/login`
- Registrasi dari `/register/guru`
- Upload bukti pembayaran dari halaman pending aktivasi
- Kelola profil, bank soal pribadi, paket soal sesuai jenjang, ikut ujian guru, dan chat

### Siswa
- Login dari `/siswa/login`
- Masuk ujian memakai token ujian
- Flow aktif siswa memakai view `resources/views/ujian/*`

## Arsitektur Data Aktif

### Jalur utama ujian
- `paket_soals`
- `mapel_pakets`
- `soals`
- `teks_bacaans`
- `ujian_sesis`
- `jawaban_siswas`

### Jalur pendukung admin
- `global_questions` sebagai bank soal global resmi
- `questions` sebagai snapshot soal yang ditempel ke ujian admin
- `exam_question` sebagai pivot relasi ujian ke snapshot soal

## Modul Penting

### Superadmin
- Dashboard metrik nyata, bukan placeholder
- Bank soal global dengan create, import CSV, edit, delete
- Builder ujian admin berbasis pivot `exam_question`
- Analisis ujian dengan ranking, distribusi nilai, export CSV, dan versi cetak
- Audit log dengan data yang sudah disanitasi
- Chat per percakapan dengan pagination
- Review pembayaran guru dengan status, preview bukti bayar, filter, dan approve/reject
- Template pesan siap kirim untuk aktivasi, approval, rejection, dan reminder

### Guru
- Registrasi guru terhubung ke status pembayaran nyata
- Halaman pending aktivasi mendukung upload bukti pembayaran dan status review
- Profil guru sinkron dengan field yang benar-benar diproses
- Bank soal pribadi dengan form cepat dan fullscreen builder
- Dashboard guru berbasis `ujian_sesis`
- Join ujian guru, histori, dan hasil berbasis data nyata
- Guard hapus soal pribadi lintas akun

### Siswa
- Mulai ujian dari token
- Timer per mapel
- Penyimpanan jawaban ke schema baru
- Penyelesaian ujian menghitung skor dari jawaban aktual

## Perubahan Besar Yang Sudah Dirapikan

- Proteksi route `superadmin/*`
- Konsolidasi jalur bank soal admin ke `global_questions`
- Perbaikan create ujian superadmin agar menyimpan `user_id`
- Builder ujian admin tidak lagi memakai `questions.exam_id` yang tidak ada
- Registrasi guru sekarang memakai email asli, password acak, dan validasi unik email/no WA
- Flow registrasi guru sekarang menyimpan status pembayaran, upload bukti transfer, approval/rejection admin, dan aktivasi token yang konsisten
- Token aktivasi dan refresh guru sekarang konsisten dan ditampilkan one-time lewat flash
- Halaman manajemen guru sekarang mendukung ringkasan status pembayaran, filter pencarian, preview bukti bayar, dan guard aktivasi manual saat review masih berjalan
- View/controller legacy yang membingungkan sudah dibersihkan
- Audit log sekarang menyamarkan path dinamis, IP, dan user agent
- Chat superadmin difilter per percakapan
- Guard bisnis untuk hapus paket soal dan teks bacaan yang masih dipakai

## Struktur Folder Penting

```text
app/
  Http/Controllers/
    Guru/
    Siswa/
    Superadmin/
  Models/
database/
  migrations/
resources/
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

Status terakhir setelah batch audit dan hardening:

- 36 test lulus
- 118 assertion lulus

## Dokumen Audit

- `errorbug.md` berisi daftar bug, status ceklis, dan prioritas perbaikan
- `ERD.md`, `allmenu.md`, `alur.md`, dan file DFD dipakai sebagai dokumen pendukung analisis

## Catatan

- Beberapa tabel legacy masih ada karena masih dipakai untuk kompatibilitas builder/admin snapshot
- Flow pembayaran guru aktif sekarang memakai kolom status di tabel `users` untuk fase `awaiting_payment`, `submitted`, `approved`, dan `rejected`
- Jika ingin refactor lanjutan, titik utama berikutnya adalah memutus total ketergantungan modul lama `questions/participants`
