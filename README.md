# Ujion TKA

Platform ujian terintegrasi berbasis Laravel untuk `superadmin`, `guru/operator`, dan `siswa`. Flow aktif project ini berpusat pada paket soal TKA, sesi ujian, dan penyimpanan jawaban berbasis schema baru, sementara sebagian tabel legacy masih dipertahankan untuk kompatibilitas builder/admin snapshot.

## Stack

- Laravel 12
- PHP 8.2+
- Tailwind CSS
- Vite
- MySQL/MariaDB

## Pintu Masuk Aplikasi

- Landing page: `/`
- Login guru: `/login`
- Registrasi guru: `/register/guru`
- Login superadmin: `/ngadimin/login`
- Login siswa: `/siswa/login`

## Flow Aktif Per Role

### Superadmin

- Login memakai `email + password`
- Workspace `superadmin/*` diproteksi `auth`, `role:superadmin`, dan `audit`
- Kelola:
    - dashboard
    - finance, pricing plan, dan QR pembayaran
    - guru, review pembayaran, aktivasi, suspend, dan refresh token akses
    - materi global
    - bank soal global
    - paket soal TKA
    - ujian, builder ujian, analisis, export CSV, dan print
    - chat dengan guru
    - audit log

### Guru / Operator

- Registrasi dari `/register/guru`
- Setelah registrasi, guru masuk ke halaman pending aktivasi
- Guru upload bukti pembayaran lalu diarahkan kembali ke login
- Login guru memakai `name + access_token`
- Akun `pending` dan `suspend` diblokir dari workspace guru oleh middleware `guru.active`
- Guru aktif dapat:
    - melihat dashboard
    - mengelola profil dan password
    - melihat materi sesuai `jenjang`
    - bookmark materi
    - mengelola bank soal pribadi
    - melihat paket soal sesuai `jenjang`
    - mengelola soal dan teks bacaan hanya pada paket yang memang boleh diakses
    - ikut simulasi ujian dengan token
    - melihat hasil simulasi
    - melihat dan menganalisis hasil ujian seluruh siswa (mandiri & resmi)
    - chat ke superadmin
    - melihat log aktivitas

### Siswa

- Masuk dari `/siswa/login` memakai token ujian
- Isi identitas peserta
- Sistem membuat `ujian_sesis`
- Siswa melihat halaman petunjuk lalu mulai ujian
- Pengerjaan ujian berjalan per mapel dengan timer masing-masing
- Jawaban tersimpan ke `jawaban_siswas`
- Saat selesai, sistem menghitung skor dari jawaban aktual lalu menutup sesi

## End-to-End Flow Utama

1. Superadmin menyiapkan pricing plan dan QR pembayaran.
2. Guru registrasi dan mengirim bukti pembayaran.
3. Superadmin review pembayaran lalu mengaktifkan guru dan memberi token akses.
4. Superadmin membuat paket soal TKA dan exam.
5. Superadmin atau guru mengisi mapel, teks bacaan, dan soal pada paket yang relevan.
6. Siswa atau guru masuk ujian menggunakan token exam.
7. Sistem membuat sesi ujian dan menyimpan jawaban selama pengerjaan.
8. Sistem menghitung skor ketika ujian selesai.

## Arsitektur Data Aktif

### Jalur utama ujian

- `paket_soals`
- `mapel_pakets`
- `teks_bacaans`
- `soals`
- `pilihan_jawabans`
- `pasangan_menjodohkans`
- `ujian_sesis`
- `jawaban_siswas`

### Jalur pendukung admin dan kompatibilitas

- `global_questions` sebagai bank soal global resmi
- `global_questions` sekarang menyimpan snapshot materi (`material_curriculum`, `material_subelement`, `material_unit`, `material_sub_unit`) selain `material_id`
- `questions` sebagai snapshot soal builder ujian admin
- `exam_question` sebagai pivot relasi ujian ke snapshot soal
- `participants` dan `participant_answers` masih ada untuk kompatibilitas modul lama tertentu
- `personal_questions` dipakai untuk bank soal pribadi guru

## Modul Penting

### Superadmin

- Dashboard dengan metrik operasional
- Review pembayaran guru lengkap dengan preview bukti bayar
- Kelola token akses guru
- Paket Soal TKA dengan dukungan **multi-aktif** per jenjang (lebih dari satu paket bisa aktif sekaligus)
- Bank soal global dengan fitur:
    - Create, update, delete, filter, dan hapus massal
    - **Teks Bacaan (Reading Passage)** opsional untuk soal Pilihan Ganda
    - **Dukungan Soal Menjodohkan (Matching)** dengan struktur pasangan kiri-kanan
    - **Split Import**: Pemisahan alur import dan template untuk Pilihan Ganda vs Menjodohkan
    - Picker materi bertingkat dari `curriculum` sampai `sub_unit`
- **Bank Builder Paket**: Integrasi langsung untuk memasukkan soal dari bank global ke paket ujian menggunakan UI seleksi terfilter
- Builder ujian admin berbasis `exam_question`
- Analisis ujian dengan ranking, distribusi nilai, export CSV, dan print
- Audit log yang sudah disanitasi
- Chat per percakapan

### Guru

- Halaman pending aktivasi dan upload bukti pembayaran
- Materi sesuai `jenjang` dan fitur bookmark
- Bank soal pribadi dengan builder
- Paket soal TKA yang difilter sesuai `jenjang`
- Simulasi ujian memakai engine yang sama dengan flow siswa
- Hasil simulasi dan pembahasan jawaban
- **Analisis Hasil Ujian (Full Feature)**:
    - Dashboard ringkasan ujian (mandiri & resmi dari superadmin)
    - Statistik mapel: rata-rata, skor tertinggi, skor terendah
    - Leaderboard/Ranking Top 5 siswa
    - **Analisis Butir Soal (Heatmap)**: Visualisasi tingkat akurasi per nomor soal
    - **Detail Jawaban Per Butir**: Transparansi jawaban siswa (PG/pembahasan)
    - Export data nilai ke format CSV/Excel

### Siswa

- Login token ujian
- Timer per mapel
- Autosave jawaban
- Perhitungan skor saat selesai

## Catatan Arsitektur

- Project ini masih memiliki dua domain ujian yang hidup berdampingan:
    - schema baru untuk flow ujian aktif
    - schema lama untuk sebagian builder/admin compatibility
- Flow ujian nyata siswa dan simulasi guru sekarang memakai schema baru `ujian_sesis` dan `jawaban_siswas`
- Builder ujian superadmin masih memakai snapshot `questions` melalui pivot `exam_question`
- Modul `global_questions` sekarang memakai snapshot materi di tabelnya sendiri agar data edit tetap tampil stabil walaupun relasi materi berubah
- Modul `global_questions` mendukung tipe soal `matching` dan `reading_passage` untuk konten literasi
- Paket soal TKA tidak lagi dibatasi hanya satu yang aktif per jenjang; superadmin dapat mengaktifkan beberapa paket sekaligus
- Import soal dari bank global ke paket ujian dilakukan dengan metode **cloning**, sehingga perubahan data di bank soal global setelah import tidak akan merusak integritas soal yang sudah ada di dalam paket
- Filtering akses guru memakai `jenjang` akun sebagai guard utama
- Paket soal TKA milik superadmin tampil terbatas di sisi guru sesuai aturan akses yang ada

## Struktur Folder Penting

```text
app/
  Http/Controllers/
    Guru/
    Siswa/
    Superadmin/
  Http/Middleware/
  Models/
database/
  migrations/
  seeders/
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

## Dokumen Pendukung

- `alur.md` untuk pembacaan flow aplikasi
- `ERD.md` untuk relasi data
- `DFD.md` dan file diagram terkait untuk analisis proses
- `allmenu.md` untuk peta menu
- `errorbug.md` untuk daftar bug, status ceklis, dan prioritas perbaikan

## Catatan Refactor

- Tabel legacy belum dihapus karena masih dipakai sebagian flow admin
- Titik refactor terbesar berikutnya adalah memutus ketergantungan builder/admin dari schema lama `questions`, `participants`, dan `participant_answers`
