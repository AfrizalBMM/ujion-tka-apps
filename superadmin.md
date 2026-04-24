# Fitur & Flow Role Superadmin

Dokumen ini merangkum perkembangan fitur yang saat ini aktif untuk role `superadmin`, termasuk alur operasional utama yang terhubung dengan flow guru.

## 1. Dashboard

Fitur aktif:

- hero dashboard superadmin
- kartu statistik utama
- grafik aktivitas sistem
- quick action ke menu penting
- aktivitas terbaru

Flow:

1. Superadmin login
2. Masuk ke dashboard utama
3. Dari dashboard superadmin bisa lanjut ke finance, guru, chat, materi, bank soal, paket, dan audit

## 2. Keuangan & QR

Fitur aktif:

- halaman finance sudah diselaraskan dengan flow pendaftaran guru
- model finance sekarang berfokus pada `1 data QR per jenjang`
- input QR dilakukan melalui modal `Add QR`
- tabel daftar QR yang sudah terinput
- preview gambar QR saat input
- field nomor WhatsApp admin untuk flow konfirmasi pembayaran
- indikator centang jika nomor WhatsApp admin sudah diisi

Struktur data aktif di halaman finance:

- judul
- jenjang
- nominal
- keterangan
- subtitle opsional
- gambar QR
- nomor WhatsApp admin

Flow:

1. Superadmin membuka menu `Keuangan & QR`
2. Superadmin klik `Add QR`
3. Mengisi data tarif aktivasi per jenjang sekaligus gambar QR
4. Menyimpan data
5. Guru yang mendaftar akan memakai nominal sesuai jenjang dari halaman ini
6. Setelah upload bukti, sistem guru mengarahkan ke WhatsApp admin menggunakan nomor yang disimpan di sini

Catatan:

- flow lama `QR master + pricing plan terpisah` sudah disederhanakan agar selaras dengan pendaftaran guru
- nominal aktivasi ditentukan per jenjang: `SD`, `SMP`, `SMA`

## 3. Konfirmasi Flow Registrasi Guru

Peran superadmin dalam flow ini:

- menyiapkan QR dan nominal aktivasi
- menyiapkan nomor WhatsApp admin
- menerima bukti pembayaran dari guru lewat WhatsApp dan/atau chat
- mengaktivasi akun guru

Flow lintas role:

1. Guru daftar
2. Guru masuk halaman pending
3. Guru bayar via QR sesuai jenjang
4. Guru upload bukti dan diarahkan ke WhatsApp admin
5. Superadmin memverifikasi
6. Superadmin mengaktifkan akun guru

## 4. Live Chat Guru / Operator

Fitur aktif:

- daftar guru di panel kiri
- unread badge per guru
- area percakapan model bubble
- kirim pesan teks
- kirim gambar
- preview gambar sebelum kirim
- hapus semua pesan satu percakapan
- hapus semua pesan semua guru
- modal detail akun guru
- gambar chat dibuka lewat route Laravel agar tidak 403

Flow:

1. Superadmin membuka `Live Chat`
2. Memilih guru dari daftar kiri
3. Membaca percakapan
4. Mengirim balasan teks atau gambar
5. Jika perlu, membuka modal detail akun guru
6. Bisa menghapus seluruh percakapan jika dibutuhkan

## 5. Daftar Guru

Fitur aktif:

- daftar guru/operator
- pengelolaan status akun
- aktivasi akun
- refresh/generate token akses
- suspend/nonaktifkan akun
- tampilan data guru yang menjadi penghubung ke flow registrasi dan aktivasi

Flow:

1. Superadmin membuka `Daftar Guru`
2. Memeriksa guru yang baru mendaftar
3. Mengaktifkan akun dan/atau refresh token
4. Mengelola status akun sesuai kebutuhan operasional

## 6. Master Materi

Fitur aktif:

- tambah materi
- filter materi berdasarkan jenjang
- hapus materi
- live operasional master materi di dashboard superadmin

Data utama:

- jenjang
- mapel
- kurikulum
- subelemen
- unit
- sub unit
- link

Flow:

1. Superadmin membuka `Master Materi`
2. Menambahkan atau memfilter materi
3. Materi yang aktif akan tampil di sisi guru sesuai jenjang

## 7. Bank Soal Global

Fitur aktif:

- tambah soal global
- daftar soal global
- filter berdasarkan jenjang
- pengelolaan mapel/materi terkait
- halaman ini menjadi sumber `Bank Soal Global Ujion` di sisi guru

Flow:

1. Superadmin membuat soal global
2. Soal dikaitkan dengan materi/mapel/jenjang
3. Guru melihat soal yang aktif dan sesuai jenjang pada halaman `Soal dari Ujion`

Catatan:

- di sisi guru sudah ada bookmark soal global dan detail soal modern
- sisi superadmin masih bisa terus ditingkatkan untuk edit massal dan builder bank soal

## 8. Paket Soal TKA

Fitur aktif:

- daftar paket soal
- filter paket
- buat dan edit paket
- detail paket per mapel
- konfigurasi jumlah soal, durasi, urutan
- akses kelola soal per mapel
- akses teks bacaan
- tampilan token ujian per mapel di detail paket
- tombol hapus semua soal per mapel pada detail paket

Flow:

1. Superadmin membuat paket soal
2. Mengisi konfigurasi per mapel
3. Mengelola soal dan teks bacaan
4. Paket digunakan untuk membuat ujian
5. Token yang dihasilkan bisa dibagikan ke guru untuk simulasi

## 9. Manajemen Ujian

Fitur aktif:

- buat ujian
- import ujian via modal
- download template import
- tabel daftar ujian
- copy token per mapel
- toggle aktif/nonaktif
- hapus ujian
- detail ujian
- builder ujian fullscreen

Flow:

1. Superadmin membuka `Manajemen Ujian`
2. Membuat ujian dari paket soal
3. Sistem menghasilkan token akses per mapel
4. Token bisa disalin dari daftar ujian atau detail paket
5. Guru dapat memakai token tersebut untuk simulasi

Detail ujian:

- token utama
- judul
- tanggal terbit
- max peserta
- status
- status aktif
- tombol copy token
- tombol ke builder soal

Catatan:

- `exam builder` masih area besar yang layak dirapikan lebih lanjut

## 10. Teks Bacaan

Fitur aktif:

- tambah teks bacaan
- edit teks bacaan
- hapus teks bacaan
- dipakai bersama oleh soal dalam satu mapel/paket

Flow:

1. Superadmin masuk ke detail mapel paket
2. Membuka halaman teks bacaan
3. Menambah atau mengedit bacaan
4. Bacaan bisa direlasikan ke beberapa soal

## 11. Audit Log

Fitur aktif:

- daftar log aktivitas sistem
- monitoring tindakan admin dan user
- mendukung kebutuhan audit internal

Flow:

1. Superadmin membuka `Log Aktivitas`
2. Menelusuri aksi user dan route yang diakses

## 12. Panduan

Fitur aktif:

- halaman panduan penggunaan untuk superadmin
- membantu orientasi menu operasional utama

## 13. Header, Sidebar, dan UX Global

Fitur aktif:

- jam realtime
- kontrol font size
- light/dark mode
- dropdown profil
- di mobile, kontrol tampilan dipindah ke dropdown profil
- sidebar desktop bisa collapse
- `KaTeX` auto-render sudah dipindah ke helper global JS

## Ringkasan Status

Sudah aktif dan selaras dengan flow saat ini:

- dashboard
- keuangan & QR
- integrasi flow registrasi guru
- live chat
- daftar guru
- master materi
- bank soal global
- paket soal TKA
- manajemen ujian
- teks bacaan
- audit log
- panduan

Masih layak dikembangkan lagi:

- builder dan editor soal global yang lebih kuat
- analisis ujian dan dashboard yang lebih dalam
- perapihan modul superadmin besar seperti `materials`, `questions`, `teachers`, dan `finance` dari sisi JS/UX internal
