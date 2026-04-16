# Inventaris Menu dan Isi Halaman

Dokumen ini merangkum semua menu yang terlihat di UI per role, isi utama tiap halaman, aksi yang tersedia, dan bentuk input berdasarkan route, controller, dan view yang ada saat ini.

## 1. Guest / Public

### Landing `/`

Isi halaman:

- hero landing dan branding Ujion TKA
- ringkasan fitur siswa dan guru
- section alur masuk guru
- preview pricing plan aktif

Aksi:

- toggle tema
- CTA ke login guru
- CTA pricing ke section alur guru

Catatan:

- tombol "Mulai Free Trial" saat ini salah route

### Register Guru `/register/guru`

Isi halaman:

- form registrasi guru/operator

Input:

- `name` text
- `jenjang` select: `SD`, `SMP`
- `tingkat` select: `4,5,6,7,8,9`
- `no_wa` text
- `satuan_pendidikan` text

Aksi:

- submit registrasi

Halaman lanjutan:

- `pending-aktivasi` menampilkan QR pembayaran dan nominal

### Login Guru `/login`

Input:

- `name` text
- `access_token` text
- `remember` checkbox

Aksi:

- submit login
- link ke registrasi guru

### Login Superadmin `/ngadimin/login`

Input:

- `email` email
- `password` password

Aksi:

- submit login
- link kembali ke landing

### Login Siswa `/siswa/login`

Input:

- `token` text

Aksi:

- masuk ke identitas jika token valid

### Identitas Siswa `/siswa/identitas`

Input:

- `nama` text wajib
- `wa` text opsional

Aksi:

- lanjut membuat sesi ujian

### Petunjuk Ujian `/siswa/petunjuk`

Isi:

- judul ujian
- nama peserta
- daftar mapel dalam paket
- jumlah soal dan durasi per mapel
- catatan timer per mapel

Aksi:

- mulai mengerjakan
- batal ke login siswa

### Pengerjaan Ujian `/siswa/ujian`

Isi:

- header ujian
- tab mapel
- timer
- indikator soal
- pertanyaan
- gambar soal jika ada
- panel teks bacaan jika ada
- body jawaban
- grid navigasi soal
- tombol selesai ujian

Model input:

- `pilihan_ganda`: tombol pilihan A-D
- `menjodohkan`: select dropdown per pasangan kiri

Aksi:

- pilih jawaban
- tandai ragu
- pindah soal
- pindah mapel
- selesai ujian
- autosave tiap 30 detik

### Selesai Ujian `/siswa/selesai`

Isi:

- status selesai
- nama peserta
- estimasi skor bila tersedia

Aksi:

- kembali ke beranda

## 2. Menu Guru / Operator

Sumber menu utama:

- `resources/views/layouts/guru.blade.php`

Menu sidebar/mobile:

- Dashboard
- Live Chat
- Log Aktivitas
- Materi
- Bank Soal Pribadi
- Paket Soal TKA
- Ujian
- Cara Menggunakan
- Profil

### Dashboard `/guru/dashboard`

Isi:

- hero dashboard
- metrik ujian dibuat, rata-rata skor kelas, total peserta
- aktivitas terbaru
- pengumuman penting

Aksi:

- shortcut ke materi
- shortcut ke ujian

### Live Chat `/guru/chat`

Isi:

- daftar percakapan guru dengan superadmin
- bubble pesan dan gambar
- waktu kirim

Input:

- `message` text
- `image` file image

Aksi:

- kirim chat ke superadmin

### Log Aktivitas `/guru/logs`

Isi:

- tabel log aktivitas pribadi

Kolom:

- waktu
- IP
- device / user agent
- route

### Materi `/guru/materials`

Isi:

- filter materi
- grid kartu materi

Input filter:

- `jenjang`
  - default: global + jenjang saya
  - `GLOBAL`

Aksi kartu:

- detail materi
- buka link eksternal
- bookmark
- hapus bookmark

#### Detail Materi `/guru/materials/{material}`

Isi:

- ringkasan jenjang
- kurikulum
- unit dan sub unit
- statistik jumlah soal terikat

Aksi:

- kembali
- buka link materi
- bookmark / unbookmark

### Bank Soal Pribadi `/guru/personal-questions`

Isi:

- form tambah soal cepat
- tabel daftar soal personal
- tombol builder fullscreen

Input form cepat:

- `jenjang` text
- `kategori` text
- `tipe` select: `PG`, `Checklist`, `Singkat`
- `pertanyaan` textarea
- `opsi[]` input
- `jawaban_benar` text
- `pembahasan` textarea
- `image` file
- `status` select: `draft`, `terbit`

Aksi:

- tambah soal
- hapus soal
- buka builder

#### Builder Soal Pribadi `/guru/personal-questions/builder`

Isi:

- sidebar daftar soal
- editor fullscreen per soal
- preview soal

Input per item:

- `tipe`
- `pertanyaan`
- `opsi[]`
- `jawaban_benar`
- `pembahasan`
- `image` string URL
- `jenjang`
- `kategori`
- `status`

Aksi:

- tambah soal
- hapus soal
- prev/next
- simpan semua soal

### Paket Soal TKA `/guru/paket-soal`

Isi:

- daftar paket sesuai jenjang guru
- badge mapel dalam paket

Aksi:

- lihat detail paket

#### Detail Paket `/guru/paket-soal/{paket}`

Isi:

- kartu per mapel
- ringkasan jumlah soal / target
- durasi
- preview 5 soal pertama
- form konfigurasi mapel

Input form mapel:

- `jumlah_soal` number
- `durasi_menit` number
- `urutan` number

Aksi:

- simpan konfigurasi
- kelola mapel

#### Kelola Soal per Mapel `/guru/paket-soal/{paket}/mapel/{mapel}/soal`

Isi:

- tabel daftar soal per mapel

Kolom:

- nomor
- tipe
- indikator
- teks bacaan
- isi jawaban
- aksi

Aksi:

- tambah PG
- tambah menjodohkan
- buka teks bacaan
- edit soal
- hapus soal

#### Tambah/Edit Soal `/guru/.../soal/create|edit`

Input umum:

- `nomor_soal`
- `tipe_soal`
- `teks_bacaan_id`
- `bobot`
- `indikator`
- `pertanyaan`
- `gambar`

Input tambahan PG:

- `pilihan[0..3][kode]` readonly A-D
- `pilihan[0..3][teks]`
- `jawaban_benar` radio A-D
- `pilihan_gambar[A-D]`

Input tambahan menjodohkan:

- `pasangan[n][teks_kiri]`
- `pasangan[n][teks_kanan]`

Aksi:

- simpan/perbarui soal
- kembali
- tambah pasangan
- hapus pasangan

#### Teks Bacaan `/guru/.../teks-bacaan`

Isi:

- form tambah teks bacaan
- daftar teks bacaan
- modal edit teks

Input:

- `judul` text opsional
- `konten` textarea wajib

Aksi:

- simpan
- edit
- hapus
- kembali ke soal

### Ujian `/guru/exams`

Isi:

- form join ujian
- tabel ujian tersedia
- tabel histori ujian

Input:

- `token` text

Aksi:

- join ujian

Catatan:

- histori dan hasil masih placeholder

### Cara Menggunakan `/guru/guide`

Isi:

- daftar menu
- tips penggunaan

### Profil `/guru/profile`

Isi:

- form edit profil
- form ganti password

Input profil:

- `name`
- `email`
- `jenjang`
- `tingkat`
- `satuan_pendidikan`
- `no_wa`
- `avatar`

Input password:

- `password`
- `password_confirmation`

Aksi:

- simpan profil
- ganti password

## 3. Menu Superadmin

Sumber menu utama:

- `resources/views/layouts/superadmin.blade.php`

Menu sidebar/mobile:

- Dashboard
- Keuangan / QR
- Live Chat
- Daftar Guru
- Master Materi
- Bank Soal Global
- Paket Soal TKA
- Manajemen Ujian
- Log Aktivitas
- Panduan

### Dashboard `/superadmin`

Isi:

- hero analytics
- kartu metrik
- grafik aktivitas sistem
- aksi terbaru
- quick action card

Aksi:

- ke audit logs
- ke guru
- ke finance
- ke bank soal
- ke chat

### Keuangan `/superadmin/finance`

Isi:

- blok QR pembayaran
- blok pricing plan

#### QR pembayaran

Input tambah/edit:

- `label`
- `sort_order`
- `image`

Aksi:

- tambah QR
- edit detail
- toggle status
- hapus

#### Paket harga

Input tambah/edit:

- `name`
- `subtitle`
- `price`
- `original_price`
- `period`
- `sort_order`

Aksi:

- tambah paket
- edit paket
- toggle aktif
- toggle promo
- hapus

### Live Chat `/superadmin/chat`

Isi:

- form kirim pesan ke guru
- daftar seluruh chat

Input:

- `to_user_id` select guru
- `message` textarea
- `image` file

Aksi:

- kirim pesan
- hapus pesan

### Daftar Guru `/superadmin/teachers`

Isi:

- tabel data guru

Kolom:

- nama
- email
- status akun
- token akses
- aksi

Aksi:

- aktivasi
- refresh token
- suspend

### Master Materi `/superadmin/materials`

Isi:

- filter jenjang
- form tambah materi
- daftar materi

Input:

- `jenjang`
- `curriculum`
- `subelement`
- `unit`
- `sub_unit`
- `link`

Aksi:

- terapkan filter
- tambah materi
- hapus materi

### Bank Soal Global `/superadmin/global-questions`

Isi:

- form input soal global
- panel import CSV
- daftar soal global

Input:

- `question_type`
- `material_id`
- `question_text`
- `options_raw`
- `answer_key`
- `is_active`
- `explanation`

Aksi:

- simpan soal
- import CSV
- download template
- hapus soal

Catatan:

- tombol edit masih coming soon

### Paket Soal TKA `/superadmin/paket-soal`

Isi:

- filter paket
- tabel daftar paket
- tombol paket baru

Input filter:

- `jenjang_id`
- `tahun_ajaran`

Aksi tabel:

- detail
- edit
- aktif/nonaktifkan
- hapus

#### Buat/Edit Paket `/superadmin/paket-soal/create|{paket}/edit`

Input:

- `jenjang_id`
- `tahun_ajaran`
- `nama`
- `is_active`

Aksi:

- simpan/perbarui paket
- batal

#### Detail Paket `/superadmin/paket-soal/{paket}`

Isi:

- kartu per mapel
- preview 5 soal
- form konfigurasi mapel

Input konfigurasi:

- `jumlah_soal`
- `durasi_menit`
- `urutan`

Aksi:

- simpan konfigurasi
- kelola soal

#### Kelola Soal per Mapel `/superadmin/.../soal`

Isi:

- tabel daftar soal per mapel

Aksi:

- tambah PG
- tambah menjodohkan
- teks bacaan
- edit
- hapus

#### Tambah/Edit Soal `/superadmin/.../soal/create|edit`

Isi dan model input:

- sama dengan modul guru karena memakai partial `partials.soal-form`

#### Teks Bacaan `/superadmin/.../teks-bacaan`

Isi dan input:

- sama dengan modul guru

Aksi:

- tambah
- edit
- hapus

### Manajemen Ujian `/superadmin/exams`

Isi:

- form buat ujian
- tabel daftar ujian

Input form:

- `paket_soal_id`
- `judul`
- `tanggal_terbit`
- `max_peserta`
- `timer`
- `status`

Aksi tabel:

- copy token
- detail
- toggle aktif
- hapus

#### Detail Ujian `/superadmin/exams/{exam}`

Isi:

- token ujian
- judul
- tanggal terbit
- max peserta
- status
- status aktif

Aksi:

- copy token
- builder soal

#### Builder Ujian `/superadmin/exams/{exam}/builder`

Isi:

- daftar soal ujian
- editor fullscreen
- import dari bank soal lama

Input builder:

- `tipe`
- `pertanyaan`
- `opsi`
- `jawaban_benar`
- `pembahasan`
- `image`

Aksi:

- tambah soal
- hapus soal
- prev/next
- simpan semua soal
- import dari bank soal

Catatan:

- builder ini masih memakai model `Question` lama, bukan `Soal` schema baru

#### Analisis Ujian `/superadmin/exams/{exam}/analysis`

Isi:

- tabel ranking peserta
- tabel distribusi nilai
- tombol export Excel/PDF

Catatan:

- isi masih dummy

### Log Aktivitas `/superadmin/audit-logs`

Isi:

- tabel audit log

Kolom:

- waktu
- method
- path
- IP
- route
- user

### Panduan `/superadmin/guide`

Isi:

- ringkasan menu
- tips penggunaan

## 4. Halaman legacy yang masih ada di repo

Masih ada file lama yang tidak menjadi flow utama aktif:

- `resources/views/siswa/ujian.blade.php`
- `resources/views/siswa/petunjuk.blade.php`
- `resources/views/siswa/selesai.blade.php`
- `resources/views/welcome.blade.php`

Catatan:

- flow siswa aktif sekarang memakai `resources/views/ujian/*`
- file lama berpotensi membingungkan developer saat maintenance

