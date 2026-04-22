# Ujion TKA

Platform ujian terintegrasi berbasis Laravel untuk `superadmin`, `guru/operator`, dan `siswa`.

Flow aktif aplikasi sekarang berpusat pada **paket lengkap per jenjang** yang otomatis memuat 4 bagian baku:

- `Bahasa Indonesia`
- `Matematika`
- `Survey Karakter`
- `Sulingjar`

Schema baru dipakai untuk flow ujian aktif, sementara sebagian tabel legacy masih dipertahankan untuk kompatibilitas builder/admin snapshot.

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
  - materi global per `jenjang + bagian paket`
  - bank soal global per `jenjang + bagian paket`
  - paket lengkap per jenjang
  - ujian berbasis paket lengkap, builder ujian, analisis, export CSV, dan print
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
  - melihat paket lengkap sesuai `jenjang`
  - mengelola soal dan teks bacaan hanya pada bagian paket yang memang boleh diakses
  - ikut simulasi ujian dengan token
  - melihat hasil simulasi
  - melihat dan menganalisis hasil ujian seluruh siswa
  - chat ke superadmin
  - melihat log aktivitas

### Siswa

- Masuk dari `/siswa/login` memakai token ujian
- Isi identitas peserta
- Sistem membuat `ujian_sesis`
- Siswa melihat halaman petunjuk lalu mulai ujian
- Pengerjaan berjalan per bagian paket dengan timer masing-masing
- Jawaban tersimpan ke `jawaban_siswas`
- Saat selesai:
  - bagian akademik dihitung dengan skor benar/salah
  - bagian survey dihitung sebagai persentase kelengkapan respons
  - sesi lalu ditutup otomatis

## End-to-End Flow Utama

1. Superadmin menyiapkan pricing plan dan QR pembayaran.
2. Guru registrasi dan mengirim bukti pembayaran.
3. Superadmin review pembayaran lalu mengaktifkan guru dan memberi token akses.
4. Superadmin membuat paket lengkap per jenjang.
5. Sistem otomatis membuat 4 bagian baku di dalam paket:
   - `Bahasa Indonesia`
   - `Matematika`
   - `Survey Karakter`
   - `Sulingjar`
6. Superadmin atau guru mengisi materi, teks bacaan, dan soal pada bagian yang relevan.
7. Superadmin membuat ujian cukup dengan memilih paket.
8. Siswa atau guru masuk ujian menggunakan token per bagian.
9. Sistem membuat sesi ujian dan menyimpan jawaban selama pengerjaan.
10. Sistem menghitung skor atau kelengkapan ketika ujian selesai.

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
- `global_questions` menyimpan snapshot materi:
  - `material_mapel`
  - `material_curriculum`
  - `material_subelement`
  - `material_unit`
  - `material_sub_unit`
- `questions` sebagai snapshot soal builder ujian admin
- `exam_question` sebagai pivot relasi ujian ke snapshot soal
- `participants` dan `participant_answers` masih ada untuk kompatibilitas modul lama tertentu
- `personal_questions` dipakai untuk bank soal pribadi guru

### Domain paket lengkap

- `paket_soals` sekarang diposisikan sebagai **paket lengkap per jenjang**
- `mapel_pakets` menjadi sumber 4 bagian baku dalam setiap paket
- `assessment_type` masih ada untuk domain/filter, tetapi pengalaman pengguna diarahkan ke pola:
  - pilih `jenjang`
  - kelola 4 bagian di dalamnya

## Modul Penting

### Superadmin

- Dashboard dengan metrik operasional
- Review pembayaran guru lengkap dengan preview bukti bayar
- Kelola token akses guru
- Paket lengkap per jenjang dengan 4 bagian baku otomatis
- Bank soal global dengan fitur:
  - create, update, delete, filter, dan hapus massal
  - teks bacaan (`reading_passage`) opsional untuk soal pilihan ganda
  - dukungan soal menjodohkan (`matching`)
  - split import: template dan import terpisah untuk pilihan ganda vs menjodohkan
  - picker materi bertingkat dari `bagian/mapel`, `curriculum`, sampai `sub_unit`
- Bank builder paket:
  - integrasi langsung untuk memasukkan soal dari bank global ke paket
  - cloning soal dari bank ke paket
- Builder ujian admin berbasis `exam_question`
- Analisis ujian dengan ranking, distribusi nilai, export CSV, dan print
- Audit log yang sudah disanitasi
- Chat per percakapan

### Guru

- Halaman pending aktivasi dan upload bukti pembayaran
- Materi sesuai `jenjang` dan fitur bookmark
- Bank soal pribadi dengan builder
- Paket lengkap yang difilter sesuai `jenjang`
- Simulasi ujian memakai engine yang sama dengan flow siswa
- Hasil simulasi dan pembahasan jawaban
- Analisis hasil ujian:
  - dashboard ringkasan ujian
  - statistik per bagian
  - leaderboard top 5 siswa
  - heatmap butir soal
  - detail jawaban per butir
  - export CSV

### Siswa

- Login token ujian
- Timer per bagian
- Autosave jawaban
- Perhitungan skor atau kelengkapan saat selesai

## Konsep UI Saat Ini

### Navigasi

- Sidebar `Materi` dan `Bank Soal` untuk `superadmin` disederhanakan menjadi:
  - `Semua Jenjang`
  - `SD`
  - `SMP`
  - `SMA`
- Konsep ini dipakai agar user masuk dari `jenjang` dulu, bukan dari istilah teknis domain.

### Filter

- Halaman utama `materi`, `bank soal`, `paket`, `ujian`, `simulasi`, dan `hasil` memakai pola filter yang konsisten:
  - `search`
  - dropdown filter
  - badge `filter aktif`
  - tombol `Cari`
  - tombol `Reset`
- Filter `Bagian Paket` pada halaman utama memakai komponen dropdown `SSD`, sama seperti `Jenis Soal`.

### Paket Lengkap

- Setiap paket per jenjang otomatis berisi:
  - `Bahasa Indonesia`
  - `Matematika`
  - `Survey Karakter`
  - `Sulingjar`
- Ujian cukup memilih `paket`, lalu sistem membuat token per bagian secara otomatis.

## Catatan Arsitektur

- Project ini masih memiliki dua domain ujian yang hidup berdampingan:
  - schema baru untuk flow ujian aktif
  - schema lama untuk sebagian builder/admin compatibility
- Flow ujian nyata siswa dan simulasi guru sekarang memakai schema baru `ujian_sesis` dan `jawaban_siswas`
- Builder ujian superadmin masih memakai snapshot `questions` melalui pivot `exam_question`
- Modul `global_questions` memakai snapshot materi di tabelnya sendiri agar data edit tetap stabil walaupun relasi materi berubah
- Modul `global_questions` mendukung tipe soal `matching` dan `reading_passage`
- Paket lengkap tidak lagi dibatasi hanya satu yang aktif per jenjang; superadmin dapat mengaktifkan beberapa paket sekaligus
- Import soal dari bank global ke paket ujian dilakukan dengan metode cloning, sehingga perubahan di bank global setelah import tidak merusak integritas soal yang sudah ada di paket
- Penilaian survey dibaca di level `mapel_paket`, bukan di level `exam`, agar paket campuran akademik + survey tetap akurat
- Filtering akses guru memakai `jenjang` akun sebagai guard utama

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

## Riwayat Perubahan

### Sesi 2026-04-22 — Paket Lengkap, Survey, Sulingjar, dan Penyederhanaan UI

#### 1. Domain Paket Lengkap

- Tambah dukungan penuh untuk:
  - `Survey Karakter`
  - `Sulingjar`
- Paket disederhanakan menjadi **paket lengkap per jenjang**
- Saat paket dibuat, sistem otomatis membuat 4 bagian baku:
  - `bahasa_indonesia`
  - `matematika`
  - `survey_karakter`
  - `sulingjar`

#### 2. Materi dan Bank Soal

- Materi global dan bank soal global mendukung domain:
  - `tka`
  - `survey_karakter`
  - `sulingjar`
- Template import materi dan bank soal dipisahkan per domain
- Contoh data survey disederhanakan ke nama bagian:
  - `Survey Karakter`
  - `Sulingjar`

#### 3. Flow Ujian dan Penilaian

- Ujian cukup memilih `paket`
- Token dibuat per bagian paket
- Skor survey tidak lagi dipaksa menjadi benar/salah
- Bagian survey dihitung sebagai **kelengkapan respons**
- Logika survey dipindahkan ke level `mapel_paket`

#### 4. Hasil dan Analitik

- Hasil guru dan analitik superadmin dibedakan per bagian:
  - akademik -> skor
  - survey -> kelengkapan
- Detail jawaban siswa tidak lagi menandai survey sebagai benar/salah secara keliru

#### 5. Penyederhanaan Navigasi dan Layout

- Sidebar `Materi` dan `Bank Soal` disederhanakan ke:
  - `Semua Jenjang`
  - `SD`
  - `SMP`
  - `SMA`
- Sidebar guru dirapikan ke istilah:
  - `Paket Lengkap`
  - `Simulasi Paket`
  - `Hasil Ujian`
- Filter di halaman utama diseragamkan:
  - `Materi`
  - `Bank Soal`
  - `Paket`
  - `Ujian`
  - `Simulasi`
  - `Hasil`
- Dropdown filter `Bagian Paket` sekarang memakai komponen `SSD`, sama seperti `Jenis Soal`

#### 6. Regression Test

- Regression test utama tetap hijau setelah refactor:
  - `PaketSoalManagementTest`
  - `SiswaExamSessionTest`
  - `GuruAndAnalyticsFlowTest`
  - `RemainingFlowsHardeningTest`

### Sesi 2026-04-21 — Bank Builder, Paket Soal, dan UI Polish

#### 1. Hapus Semua Soal per Bagian

- Tambah route `DELETE /paket-soal/{paket}/mapel/{mapel}/soal-all`
- Tambah method `destroyAllSoals()` di `MapelPaketController`
- Tombol **Hapus Semua Soal** ditampilkan sejajar dengan **Simpan Konfigurasi**
- Konfirmasi memakai global confirm modal

#### 2. Bank Builder — Quota Enforcement

- Sisa slot dihitung di server
- Variabel `SLOT_SISA` diteruskan ke JavaScript
- Checkbox soal otomatis disabled saat kuota habis
- Fitur **Pilih Semua** dibatasi hanya sebanyak slot yang tersisa

#### 3. Bank Builder — Preview Modal

- Tombol submit diganti menjadi **Preview & Masukkan**
- Modal preview menampilkan:
  - nama bagian dan paket
  - jumlah soal yang akan ditambahkan
  - peringatan kuota
  - daftar soal bernomor

#### 4. Paket Soal Detail

- Navigasi dari halaman paket ke bank builder otomatis membawa filter jenjang dan bagian

#### 5. Dropdown Kebab Menu

- Kolom aksi pada paket soal index diganti menjadi dropdown aksi

#### 6. Infrastruktur UI

- Tambah `@stack('scripts')` ke `layouts/superadmin.blade.php`
- JS inject pattern dipakai untuk menghindari nested form
