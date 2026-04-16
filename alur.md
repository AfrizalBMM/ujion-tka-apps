# Alur Aplikasi Ujion TKA

Dokumen ini disusun dari pembacaan `routes`, controller, model, view, middleware, dan migrasi pada codebase saat ini pada 16 April 2026. Fokusnya adalah alur yang benar-benar terlihat di kode, bukan asumsi produk.

## 1. Peta role dan pintu masuk

- Landing page: `/` via `LandingController@index`.
- Guru/operator login: `/login`.
- Registrasi guru/operator: `/register/guru`.
- Superadmin login: `/ngadimin/login`.
- Siswa login token ujian: `/siswa/login`.

Role yang aktif di codebase:

- `superadmin`
- `guru`
- `siswa`

Catatan:

- Alur guru dan superadmin memakai autentikasi Laravel.
- Alur siswa tidak memakai akun `users`, tetapi memakai token ujian lalu membuat sesi di `ujian_sesis`.
- Modul ujian masih menyisakan arsitektur lama (`participants`, `participant_answers`, `questions`) dan arsitektur baru (`ujian_sesis`, `jawaban_siswas`, `soals`). Ini penting karena beberapa flow masih campur.

## 2. Alur dari landing page

### 2.1 Landing page

File utama:

- `routes/web.php`
- `app/Http/Controllers/LandingController.php`
- `resources/views/landing.blade.php`

Alur:

1. User membuka `/`.
2. Sistem mengambil preview paket harga aktif dari tabel `pricing_plans` bila tabel tersedia.
3. Halaman menampilkan:
   - hero section
   - ringkasan role siswa dan guru
   - section "Alur Masuk Guru"
   - preview harga
4. CTA yang sekarang tersedia di UI:
   - login guru: valid
   - CTA register free trial: saat ini salah route, karena view memanggil `route('register')`, padahal route yang ada adalah `register.guru.form`

### 2.2 Registrasi guru/operator

File utama:

- `routes/guru.php`
- `app/Http/Controllers/RegisterGuruController.php`
- `resources/views/register-guru.blade.php`
- `resources/views/pending-aktivasi.blade.php`

Alur:

1. Calon guru membuka `/register/guru`.
2. Form meminta:
   - nama + gelar
   - jenjang (`SD`, `SMP`)
   - tingkat (`4`-`9`)
   - nomor WhatsApp
   - satuan pendidikan
3. Submit ke `RegisterGuruController@register`.
4. Sistem membuat user baru dengan:
   - `role = guru`
   - `account_status = pending`
   - email dummy dari nomor WA
   - password default `"password"` yang di-hash
5. Sistem menampilkan halaman `pending-aktivasi` dengan nominal harga dan QR aktif.
6. Secara bisnis, user menunggu aktivasi oleh superadmin.

Catatan:

- Tidak ada upload bukti bayar.
- Tidak ada verifikasi unik untuk `no_wa` atau email dummy.
- Tidak ada notifikasi otomatis ke superadmin.

### 2.3 Login guru/operator

File utama:

- `app/Http/Controllers/AuthController.php`
- `resources/views/auth/login.blade.php`

Alur:

1. Guru membuka `/login`.
2. Form meminta:
   - `name`
   - `access_token`
   - opsi `remember`
3. Sistem mencari user guru berdasarkan kombinasi nama + token akses.
4. Jika status akun bukan `active`, login ditolak.
5. Jika sukses, user diarahkan ke `guru.dashboard`.

Catatan:

- Login guru berbasis nama + token, bukan email/password.
- Jika ada nama ganda, flow bisa membingungkan karena identitas login tidak unik secara UX.

### 2.4 Login superadmin

File utama:

- `app/Http/Controllers/AuthController.php`
- `resources/views/auth/admin-login.blade.php`

Alur:

1. Superadmin membuka `/ngadimin/login`.
2. Form meminta:
   - email
   - password
3. `Auth::attempt` dijalankan.
4. Setelah login, sistem cek role harus `superadmin`.
5. Jika sukses diarahkan ke `superadmin.dashboard`.

Catatan penting:

- Route login superadmin benar.
- Tetapi area `superadmin/*` sendiri saat ini belum diproteksi middleware `auth` dan `role:superadmin`, sehingga flow pasca login tidak aman. Detail ada di `errorbug.md`.

## 3. Alur guru/operator setelah login

Route group:

- prefix `guru`
- middleware `auth`, `role:guru`

### 3.1 Dashboard guru

Alur:

1. Login sukses mengarah ke `/guru/dashboard`.
2. Dashboard membaca:
   - jumlah ujian dari relasi `user->exams()`
   - total peserta dan rata-rata skor dari tabel lama `participants`
   - 10 log audit terbaru user
3. Dashboard menampilkan ringkasan metrik, log aktivitas, dan placeholder pengumuman.

Catatan:

- Dashboard guru masih memakai tabel lama `participants`, padahal flow siswa aktif memakai `ujian_sesis`.

### 3.2 Profil guru

Alur:

1. Guru membuka `/guru/profile`.
2. Halaman menampilkan data profil dan form ganti password.
3. Update profil submit ke `guru.profile.update`.
4. Ganti password submit ke `guru.profile.password`.

### 3.3 Materi

Alur:

1. Guru membuka `/guru/materials`.
2. Sistem memfilter materi global dan materi sesuai jenjang guru.
3. Di list, guru bisa buka detail, buka link, bookmark, dan unbookmark.
4. Detail materi menampilkan ringkasan dan jumlah soal terikat dari tabel `questions`.

### 3.4 Bank soal pribadi

Alur:

1. Guru membuka `/guru/personal-questions`.
2. Guru bisa menambah soal personal cepat dari form inline.
3. Data disimpan ke tabel `personal_questions`.
4. Guru bisa hapus soal.
5. Guru bisa membuka builder fullscreen di `/guru/personal-questions/builder`.
6. `saveBuilder` menghapus seluruh soal lama milik guru lalu menyimpan ulang payload baru.

### 3.5 Paket soal TKA

Alur:

1. Guru membuka `/guru/paket-soal`.
2. Sistem hanya menampilkan paket yang jenjangnya sama dengan `user->jenjang`.
3. Guru klik detail paket `/guru/paket-soal/{paket}`.
4. Di detail paket, guru dapat:
   - melihat mapel
   - mengubah `jumlah_soal`, `durasi_menit`, `urutan`
   - melihat preview soal
   - masuk ke halaman kelola soal per mapel

### 3.6 Kelola soal TKA guru

Alur:

1. Guru membuka `/guru/paket-soal/{paket}/mapel/{mapel}/soal`.
2. Sistem memuat daftar soal, teks bacaan, pilihan jawaban, dan pasangan.
3. Guru bisa tambah, edit, hapus soal, atau masuk ke modul teks bacaan.

Model data baru:

- `paket_soals`
- `mapel_pakets`
- `teks_bacaans`
- `soals`
- `pilihan_jawabans`
- `pasangan_menjodohkans`

### 3.7 Teks bacaan per mapel

Alur:

1. Guru membuka `/guru/paket-soal/{paket}/mapel/{mapel}/teks-bacaan`.
2. Guru bisa tambah, edit lewat modal, dan hapus teks bacaan.
3. Teks ini dipakai ulang di form soal.

### 3.8 Ujian pada role guru

Alur di kode saat ini:

1. Guru membuka `/guru/exams`.
2. Sistem menampilkan list `Exam` dengan `status = terbit`.
3. Guru dapat memasukkan token untuk join.
4. Ada halaman histori dan hasil.

Kondisi nyata:

- modul ini masih placeholder
- belum ada attach peserta guru ke ujian
- histori kosong
- hasil ujian kosong

### 3.9 Chat dan log aktivitas

Chat:

1. Guru membuka `/guru/chat`.
2. Sistem memuat semua chat yang melibatkan user login.
3. Guru kirim pesan atau gambar ke superadmin pertama yang ditemukan.

Log:

1. Guru membuka `/guru/logs`.
2. Sistem menampilkan 100 audit log terbaru milik user tersebut.

## 4. Alur superadmin setelah login

Route group:

- prefix `superadmin`
- middleware saat ini hanya `audit`

### 4.1 Dashboard superadmin

Alur:

1. Superadmin membuka `/superadmin`.
2. Sistem mengambil jumlah guru aktif, activity chart 14 hari, dan audit log terbaru.
3. Dashboard menampilkan quick action ke guru, finance, bank soal, dan chat.

Catatan:

- `ongoingExamsCount` dan `totalRevenue` masih placeholder.

### 4.2 Finance, QR, pricing

Alur:

1. Buka `/superadmin/finance`.
2. Halaman memuat dua blok: QR pembayaran dan pricing plan.
3. Superadmin dapat tambah/edit/hapus/toggle QR.
4. Superadmin dapat tambah/edit/hapus/toggle active/toggle promo pricing plan.

### 4.3 Manajemen guru

Alur:

1. Buka `/superadmin/teachers`.
2. Sistem menampilkan semua user dengan role guru.
3. Superadmin dapat aktivasi akun, suspend akun, dan refresh token akses.

### 4.4 Master materi

Alur:

1. Buka `/superadmin/materials`.
2. Superadmin dapat filter per jenjang.
3. Superadmin dapat tambah dan hapus materi global.

### 4.5 Bank soal global

Alur:

1. Buka `/superadmin/global-questions`.
2. Superadmin dapat input soal global manual.
3. Superadmin dapat import CSV massal dan download template.
4. Soal global dapat dihapus.

Catatan:

- Ini modul bank soal global baru dengan tabel `global_questions`.
- Masih hidup berdampingan dengan modul lama `questions`.

### 4.6 Paket soal TKA

Alur:

1. Buka `/superadmin/paket-soal`.
2. Superadmin dapat filter paket berdasarkan jenjang dan tahun ajaran.
3. Superadmin dapat buat paket baru.
4. Saat paket dibuat, sistem otomatis membuat 2 mapel default:
   - `matematika`
   - `bahasa_indonesia`
5. Superadmin dapat lihat detail, edit metadata, aktif/nonaktifkan, dan hapus paket.
6. Di detail paket, superadmin dapat ubah konfigurasi mapel dan masuk ke kelola soal.

### 4.7 Kelola soal dan teks bacaan paket TKA

Alur:

1. Superadmin membuka halaman soal per mapel.
2. Superadmin dapat tambah/edit/hapus soal.
3. Superadmin dapat buka modul teks bacaan.
4. Form soal mendukung pilihan ganda, menjodohkan, gambar, teks bacaan, dan bobot.

### 4.8 Manajemen ujian

Alur sekarang:

1. Superadmin membuka `/superadmin/exams`.
2. Superadmin membuat ujian dengan memilih paket soal, judul, tanggal terbit, max peserta, timer, dan status.
3. Sistem membuat token 6 karakter.
4. List ujian menampilkan token, status, aktif/nonaktif, dan detail.
5. Halaman detail ujian menampilkan token dan metadata.
6. Dari detail, superadmin bisa masuk ke builder soal.
7. Ada route analisis ujian.

Kondisi implementasi:

- flow siswa yang aktif memakai `paket_soal_id`
- tetapi builder ujian superadmin masih memakai tabel lama `questions`
- analisis ujian masih dummy

## 5. Alur siswa

### 5.1 Login token

Alur:

1. Siswa buka `/siswa/login`.
2. Input token ujian.
3. Sistem mencari `Exam` berdasarkan token dan `is_active = true`.
4. Sistem memastikan exam punya `paketSoal` dan paket punya mapel.
5. Jika valid, token disimpan ke session `siswa_token`.
6. Redirect ke `/siswa/identitas`.

### 5.2 Identitas peserta

Alur:

1. Siswa isi nama wajib dan nomor WhatsApp opsional.
2. Submit ke `/siswa/mulai`.
3. Sistem membuat record `ujian_sesis` dengan `timer_state` per mapel.
4. `participant_token` disimpan di session.
5. Redirect ke halaman petunjuk.

### 5.3 Petunjuk ujian

Alur:

1. Sistem mengambil sesi aktif dari `participant_token`.
2. Menampilkan judul ujian, nama peserta, daftar mapel, jumlah soal, dan durasi.
3. Tombol mulai masuk ke `/siswa/ujian`.

### 5.4 Pengerjaan ujian

Alur:

1. Saat halaman ujian dibuka, status sesi `menunggu` diubah menjadi `mengerjakan`.
2. Mapel dipilih dari query `?mapel=...` atau default mapel pertama.
3. Sistem memuat semua `soals` untuk mapel itu.
4. Frontend menampilkan timer per mapel, navigasi soal, tandai ragu, dan autosave.
5. Jawaban tersimpan ke `jawaban_siswas`.
6. Timer state diperbarui per mapel.

### 5.5 Penyelesaian ujian

Alur:

1. `selesai()` mengambil sesi aktif.
2. Jika sesi belum selesai:
   - load soal dan jawaban
   - hitung skor
   - update status `selesai`
   - isi waktu selesai
   - simpan skor
   - hapus session siswa
3. Halaman selesai menampilkan ucapan selesai dan estimasi skor.

## 6. Arsitektur domain yang sedang dipakai

### Domain baru yang relatif konsisten

- `jenjangs`
- `paket_soals`
- `mapel_pakets`
- `teks_bacaans`
- `soals`
- `pilihan_jawabans`
- `pasangan_menjodohkans`
- `ujian_sesis`
- `jawaban_siswas`

Dipakai oleh:

- paket soal TKA
- pengerjaan ujian siswa terbaru

### Domain lama yang masih tersisa

- `questions`
- `exam_question`
- `participants`
- `participant_answers`
- `personal_questions`

Dipakai oleh:

- builder ujian superadmin
- dashboard guru lama
- modul ujian guru lama
- bank soal pribadi guru

## 7. Ringkasan end-to-end paling realistis saat ini

Flow yang paling utuh di codebase:

1. Landing page
2. Registrasi guru
3. Aktivasi guru oleh superadmin
4. Superadmin membuat paket soal TKA
5. Guru/superadmin mengisi soal dan teks bacaan pada paket
6. Superadmin membuat exam yang mengacu ke paket
7. Siswa login dengan token exam
8. Siswa isi identitas
9. Siswa mengerjakan ujian per mapel
10. Jawaban tersimpan ke schema baru
11. Sistem menghitung skor akhir saat selesai

Flow yang belum utuh:

- join ujian oleh guru
- histori hasil guru
- analisis ujian superadmin berbasis data nyata
- integrasi builder ujian superadmin dengan schema baru
- pembayaran registrasi dan verifikasi aktivasi yang lengkap

