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
- `global_questions` sekarang menyimpan snapshot materi (`material_mapel`, `material_curriculum`, `material_subelement`, `material_unit`, `material_sub_unit`) selain `material_id`
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
    - Picker materi bertingkat dari `mapel`, `curriculum` sampai `sub_unit`
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

---

## Riwayat Perubahan

### Sesi 2026-04-21 — Bank Builder, Paket Soal & UI Polish

#### 1. Hapus Semua Soal per Mapel (Paket Soal Detail)

- Tambah route `DELETE /paket-soal/{paket}/mapel/{mapel}/soal-all` → `superadmin.mapel.soal.destroy-all`
- Tambah method `destroyAllSoals()` di `MapelPaketController` — menghapus semua soal beserta `pilihan_jawabans` dan `pasangan_menjodohkans` secara cascade
- Tombol **Hapus Semua Soal** ditampilkan sejajar dengan **Simpan Konfigurasi** hanya jika mapel sudah punya soal
- Konfirmasi menggunakan global confirm modal (bukan `window.confirm()` native)
- Perbaikan nested form: form hapus dipisah secara DOM dari form konfigurasi agar tidak ter-intercept browser

#### 2. Bank Builder — Quota Enforcement

- Sisa slot dihitung di server: `$slotSisa = $mapel->jumlah_soal - $mapel->soals()->count()`
- Variabel `SLOT_SISA` diteruskan ke JavaScript sebagai konstanta
- Saat jumlah soal yang dipilih mencapai `SLOT_SISA`, semua checkbox yang belum dipilih otomatis **disabled** dan card menjadi semi-transparan (opacity 40%)
- Unchecking salah satu soal langsung mengaktifkan kembali slot yang tersedia
- Fitur **Pilih Semua** dibatasi hanya memilih sebanyak slot yang tersisa
- Footer sticky menampilkan counter "sisa slot: X" yang diperbarui secara real-time

#### 3. Bank Builder — Preview Modal (Checkout)

- Tombol submit langsung diganti dengan **Preview & Masukkan**
- Klik → modal preview terbuka (tidak langsung submit)
- Modal menampilkan:
  - Nama mapel dan paket
  - Jumlah soal yang akan ditambahkan
  - Peringatan amber jika jumlah melebihi kuota
  - Daftar soal bernomor dengan badge tipe (Pilihan Ganda / Menjodohkan) dan cuplikan pertanyaan
- Tombol **Kembali Pilih** menutup modal tanpa reset pilihan
- Tombol **Konfirmasi & Import** menonaktifkan diri sendiri (loading state) lalu submit form

#### 4. Tombol Hapus pada Halaman `paket-soal/show.blade.php`

- Navigasi dari halaman paket-soal detail ke bank builder otomatis membawa filter `jenjang_id` dan `material_mapel` dari card mapel yang diklik
- Implementasi **auto-filter**: URL bank builder dibangun dengan `http_build_query` sehingga filter langsung aktif saat halaman dibuka

#### 5. Dropdown Kebab Menu — Paket Soal Index

- Kolom Aksi yang sebelumnya memiliki 4 tombol flat (Detail, Edit, Toggle, Hapus) diganti dengan satu tombol ikon **⋮** (titik tiga vertikal)
- Klik ikon → dropdown muncul dengan daftar aksi:
  - **Detail** — navigasi ke halaman detail
  - **Edit** — navigasi ke form edit
  - **Aktifkan / Nonaktifkan** — submit PATCH, icon berubah sesuai status
  - *separator*
  - **Hapus** — submit DELETE dengan global confirm modal
- Dropdown menutup otomatis saat: klik di luar, klik item lain, atau tekan `ESC`

#### 6. Global Infrastructure

- Tambah `@stack('scripts')` sebelum `</body>` pada `layouts/superadmin.blade.php` — sebelumnya semua `@push('scripts')` di child views tidak pernah dirender
- JS inject pattern untuk tombol hapus: tombol dibuat secara DOM via JavaScript dan disisipkan ke elemen `<span>` placeholder di dalam Blade, menghindari kebutuhan nested form

#### File yang diubah

| File | Perubahan |
|---|---|
| `routes/web.php` | Tambah route `DELETE mapel.soal.destroy-all` |
| `MapelPaketController.php` | Tambah method `destroyAllSoals()` |
| `paket-soal/show.blade.php` | Form hapus semua soal, JS inject konfirmasi global |
| `paket-soal/index.blade.php` | Dropdown kebab menu kolom aksi |
| `soal/bank-builder.blade.php` | Quota enforcement, preview modal checkout |
| `layouts/superadmin.blade.php` | Tambah `@stack('scripts')` |
