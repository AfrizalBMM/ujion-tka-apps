# Inventaris Menu, Halaman, dan Flow Aktif

Dokumen ini merangkum menu yang terlihat di UI, isi utama tiap halaman, aksi yang tersedia, serta flow penting yang saat ini aktif di aplikasi untuk role `guest`, `guru/operator`, `superadmin`, dan `siswa`.

## 1. Guest / Public

### Landing `/`

Isi:

- hero landing Ujion
- ringkasan fitur
- alur registrasi guru
- CTA login

Aksi:

- buka login guru
- buka login superadmin
- lanjut ke registrasi guru

### Register Guru `/register/guru`

Isi:

- form registrasi guru/operator

Input:

- `name`
- `email`
- `jenjang`
- `no_wa`
- `satuan_pendidikan`

Aksi:

- submit registrasi

Flow:

1. Calon guru mendaftar
2. Sistem membuat akun dengan status pending
3. Pengguna diarahkan ke halaman pending aktivasi

### Pending Aktivasi `/register/guru/pending`

Isi:

- ringkasan data pendaftaran
- nominal aktivasi sesuai jenjang
- tombol `Bayar Sekarang`
- modal QR pembayaran
- form upload bukti pembayaran

Aksi:

- buka modal QR
- upload bukti pembayaran
- lanjut ke WhatsApp admin

Flow:

1. Guru klik `Bayar Sekarang`
2. Modal menampilkan QR dan nominal
3. Guru upload bukti pembayaran
4. Sistem membuka WhatsApp admin dengan pesan otomatis berisi nama, email, nomor HP/WA, dan jenjang

Catatan:

- nomor WhatsApp admin diambil dari pengaturan finance superadmin

### Login Guru `/login`

Input:

- `name`
- `access_token`
- `remember`

Aksi:

- login guru/operator

### Login Superadmin `/ngadimin/login`

Input:

- `email`
- `password`

Aksi:

- login superadmin

### Login Siswa `/siswa/login`

Input:

- `token`

Aksi:

- validasi token ujian
- lanjut ke identitas siswa

## 2. Siswa

### Identitas Siswa `/siswa/identitas`

Input:

- `nama`
- `wa` opsional

Aksi:

- mulai sesi ujian

### Petunjuk Ujian `/siswa/petunjuk`

Isi:

- identitas peserta
- daftar mapel
- jumlah soal
- durasi
- informasi alur pengerjaan

Aksi:

- mulai ujian
- batal

### Pengerjaan Ujian `/siswa/ujian`

Isi:

- header ujian
- timer
- navigasi soal
- tab mapel
- soal, gambar, dan teks bacaan
- body jawaban

Model input:

- pilihan ganda
- menjodohkan

Aksi:

- pilih jawaban
- pindah soal
- pindah mapel
- tandai ragu
- selesai ujian

Catatan:

- autosave berjalan berkala
- KaTeX aktif untuk soal yang mengandung rumus

### Selesai Ujian `/siswa/selesai`

Isi:

- status selesai
- identitas peserta
- ringkasan hasil bila tersedia

## 3. Guru / Operator

Sumber menu utama:

- `resources/views/layouts/guru.blade.php`

Menu sidebar/mobile:

- Dashboard
- Chat
- Materi
- Soal dari Ujion
- Bank Soal
- Paket Soal
- Simulasi
- Profil
- Panduan

### Dashboard `/guru/dashboard`

Isi:

- hero dashboard
- metrik ringkas
- aktivitas terbaru
- shortcut ke fitur penting

### Chat `/guru/chat`

Isi:

- percakapan dengan superadmin
- bubble chat teks dan gambar
- modal info/tutorial chat

Input:

- `message`
- `image`

Aksi:

- kirim pesan
- kirim gambar
- preview gambar sebelum kirim

Catatan:

- maksimal lampiran gambar 2 MB
- gambar chat dibuka via route Laravel agar tidak 403

### Materi `/guru/materials`

Isi:

- hero materi
- live search
- live filter mapel
- live filter kurikulum
- kartu materi
- tombol bookmark
- mode `Bookmark Saya`

Aksi:

- buka detail materi
- buka link materi
- bookmark / unbookmark
- filter materi secara live

#### Detail Materi `/guru/materials/{material}`

Isi:

- ringkasan materi
- mapel
- kurikulum
- jenjang
- unit dan sub unit

Aksi:

- bookmark
- buka link
- kembali

### Soal dari Ujion `/guru/soal-ujion`

Isi:

- bank soal global Ujion
- live search
- live filter mapel
- live filter kurikulum
- tombol bookmark
- mode `Bookmark Saya`

Aksi:

- buka detail soal
- bookmark / unbookmark
- filter soal secara live

#### Detail Soal Ujion `/guru/soal-ujion/{question}`

Isi:

- header soal modern
- badge status, tipe, mapel, jenjang
- pertanyaan
- opsi jawaban
- ringkasan
- kunci jawaban
- pembahasan

Aksi:

- bookmark / unbookmark
- kembali ke list

### Bank Soal Pribadi `/guru/personal-questions`

Isi:

- filter live
- tabel daftar soal pribadi
- modal tambah soal
- modal edit soal
- tombol ke builder fullscreen

Input soal:

- `kategori`
- `tipe`
- `pertanyaan`
- `opsi[]`
- `jawaban_benar`
- `pembahasan`
- `image`
- `status`

Aksi:

- tambah soal
- edit soal
- hapus soal
- live search/filter
- buka builder fullscreen

Catatan:

- opsi objektif memakai `A-E`
- gambar bisa dipreview sebelum submit
- maksimal gambar 2 MB

#### Builder Soal Pribadi `/guru/personal-questions/builder`

Isi:

- editor fullscreen
- sidebar daftar soal
- preview soal
- upload gambar

Input per soal:

- `tipe`
- `pertanyaan`
- `opsi[]`
- `jawaban_benar`
- `pembahasan`
- `kategori`
- `status`
- `image`

Aksi:

- tambah soal
- hapus soal
- prev/next
- upload gambar
- simpan semua soal

Catatan:

- maksimal 5 opsi
- `PG/Checklist` memakai jawaban benar `A-E`
- `Singkat` memakai input teks biasa

### Paket Soal TKA `/guru/paket-soal`

Isi:

- daftar paket soal sesuai jenjang guru

Aksi:

- buka detail paket

#### Detail Paket `/guru/paket-soal/{paket}`

Isi:

- kartu per mapel
- preview soal
- konfigurasi mapel
- token ujian aktif per mapel

Input konfigurasi:

- `jumlah_soal`
- `durasi_menit`
- `urutan`

Aksi:

- simpan konfigurasi
- buka kelola soal
- copy token mapel

#### Kelola Soal per Mapel `/guru/.../soal`

Isi:

- daftar soal per mapel
- akses ke teks bacaan

Aksi:

- tambah soal
- edit soal
- hapus soal
- buka teks bacaan

#### Teks Bacaan `/guru/.../teks-bacaan`

Isi:

- form tambah bacaan
- daftar bacaan
- modal edit bacaan

Input:

- `judul`
- `konten`

Aksi:

- tambah
- edit
- hapus

### Simulasi Ujian `/guru/exams`

Isi:

- form join simulasi via token
- daftar ujian yang bisa dicoba
- riwayat simulasi

Input:

- `token`

Aksi:

- join ujian
- copy token mapel
- lihat hasil simulasi

### Profil `/guru/profile`

Isi:

- edit profil
- ganti password

Input:

- `name`
- `email`
- `jenjang`
- `satuan_pendidikan`
- `no_wa`
- `avatar`
- `password`
- `password_confirmation`

Aksi:

- simpan profil
- ubah password

### Panduan `/guru/guide`

Isi:

- card panduan penggunaan fitur guru

## 4. Superadmin

Sumber menu utama:

- `resources/views/layouts/superadmin.blade.php`

Menu sidebar/mobile:

- Dashboard
- Keuangan
- Konfirmasi
- Chat
- Guru
- Materi
- Bank Soal
- Paket Soal
- Ujian
- Audit
- Panduan

### Dashboard `/superadmin`

Isi:

- hero analytics
- kartu statistik
- grafik aktivitas
- quick action

Aksi:

- navigasi ke modul utama

### Keuangan & QR `/superadmin/finance`

Isi:

- pengelolaan QR per jenjang
- nominal aktivasi per jenjang
- nomor WhatsApp admin
- tabel daftar QR yang sudah dibuat

Input:

- `judul`
- `jenjang`
- `nominal`
- `keterangan`
- `subtitle`
- `image`
- `no_whatsapp_admin`

Aksi:

- add QR via modal
- edit data
- hapus data

Flow penting:

- data di halaman ini dipakai langsung oleh flow registrasi guru

### Chat `/superadmin/chat`

Isi:

- daftar guru di panel kiri
- unread badge
- area percakapan
- form kirim chat
- modal detail akun guru

Input:

- `to_user_id`
- `message`
- `image`

Aksi:

- kirim pesan
- kirim gambar
- preview gambar sebelum kirim
- hapus semua pesan satu guru
- hapus semua pesan semua guru

### Daftar Guru `/superadmin/teachers`

Isi:

- daftar guru/operator
- status akun
- token akses

Aksi:

- aktivasi akun
- refresh token
- suspend/nonaktifkan

### Master Materi `/superadmin/materials`

Isi:

- filter jenjang
- form tambah materi
- daftar materi

Input:

- `jenjang`
- `mapel`
- `curriculum`
- `subelement`
- `unit`
- `sub_unit`
- `link`

Aksi:

- tambah materi
- hapus materi
- filter materi

### Bank Soal Global `/superadmin/global-questions`

Isi:

- form tambah soal global
- import bank soal
- daftar soal global

Input utama:

- `question_type`
- `material_id`
- `question_text`
- `options_raw`
- `answer_key`
- `explanation`
- `is_active`

Aksi:

- tambah soal
- import
- download template
- hapus soal

Catatan:

- menjadi sumber halaman `Soal dari Ujion` di sisi guru

### Paket Soal TKA `/superadmin/paket-soal`

Isi:

- filter paket
- daftar paket
- tombol buat paket

Aksi:

- buat paket
- edit paket
- toggle aktif
- hapus paket
- buka detail paket

#### Detail Paket `/superadmin/paket-soal/{paket}`

Isi:

- konfigurasi mapel
- preview soal
- token ujian per mapel
- tombol hapus semua soal per mapel

Input konfigurasi:

- `jumlah_soal`
- `durasi_menit`
- `urutan`

Aksi:

- simpan konfigurasi
- copy token mapel
- buka kelola soal
- hapus semua soal mapel

#### Kelola Soal per Mapel `/superadmin/.../soal`

Isi:

- daftar soal per mapel

Aksi:

- tambah soal
- edit soal
- hapus soal
- buka teks bacaan

#### Teks Bacaan `/superadmin/.../teks-bacaan`

Isi:

- tambah teks bacaan
- daftar teks bacaan
- edit
- hapus

### Manajemen Ujian `/superadmin/exams`

Isi:

- form buat ujian
- modal import ujian
- tabel ujian
- token per mapel

Input:

- `paket_soal_id`
- `judul`
- `tanggal_terbit`
- `max_peserta`
- `timer`
- `status`

Aksi:

- buat ujian
- import ujian
- download template import
- copy token mapel
- toggle aktif
- hapus
- buka detail

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
- buka builder soal

#### Builder Ujian `/superadmin/exams/{exam}/builder`

Isi:

- editor fullscreen soal ujian

Aksi:

- tambah soal
- hapus soal
- simpan
- import dari bank soal lama

### Audit Log `/superadmin/audit-logs`

Isi:

- tabel aktivitas sistem

Kolom umum:

- waktu
- method
- path
- IP
- route
- user

### Panduan `/superadmin/guide`

Isi:

- ringkasan penggunaan menu superadmin

## 5. UX Global dan Teknis

Berlaku lintas role:

- jam realtime di header
- light/dark mode
- kontrol font size
- dropdown profil mobile
- sidebar desktop collapse
- `KaTeX` auto-render via helper JS global
- pemisahan script mulai dipindah ke:
  - `resources/js/core`
  - `resources/js/utils`
  - `resources/js/pages`

## 6. Halaman Legacy / Catatan Repo

Masih ada file lama yang bukan flow utama aktif:

- `resources/views/siswa/ujian.blade.php`
- `resources/views/siswa/petunjuk.blade.php`
- `resources/views/siswa/selesai.blade.php`
- `resources/views/welcome.blade.php`

Catatan:

- flow siswa aktif sekarang memakai `resources/views/ujian/*`
- beberapa halaman besar superadmin masih layak diaudit lebih lanjut dari sisi JS dan UX internal
