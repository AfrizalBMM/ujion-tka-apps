# Fitur & Flow Role Guru / Operator

Dokumen ini merangkum perkembangan fitur yang saat ini sudah aktif untuk role `guru/operator`, termasuk alur kerja utama yang dipakai di aplikasi.

## 1. Dashboard

Fitur aktif:

- hero dashboard guru
- ringkasan metrik utama
- shortcut ke menu penting
- daftar aktivitas terbaru
- blok informasi/pengumuman

Flow:

1. Guru login menggunakan `nama` dan `access token`
2. Masuk ke dashboard
3. Dari dashboard guru bisa lanjut ke materi, bank soal, paket soal, simulasi, atau chat

## 2. Profil

Fitur aktif:

- edit profil dasar
- ubah password
- update data satuan pendidikan dan nomor WhatsApp
- halaman profil sudah dibuat responsif mengikuti lebar layar

Flow:

1. Guru membuka menu `Profil`
2. Guru memperbarui data akun
3. Sistem menyimpan perubahan dan menampilkan flash message

## 3. Panduan

Fitur aktif:

- halaman panduan penggunaan untuk guru
- card panduan sudah menyesuaikan lebar layar mobile dan desktop

Flow:

1. Guru membuka menu `Panduan`
2. Guru membaca langkah penggunaan menu-menu utama

## 4. Registrasi Guru & Aktivasi

Fitur aktif:

- form registrasi guru publik
- pemilihan `jenjang` via dropdown
- nominal aktivasi diambil otomatis dari master finance superadmin
- halaman `pending aktivasi`
- tombol `Bayar Sekarang` di halaman pending menampilkan modal QR, bukan pindah halaman
- setelah upload bukti, guru diarahkan ke WhatsApp admin dengan pesan otomatis berisi:
  - nama
  - email
  - nomor HP / WhatsApp
  - jenjang

Flow:

1. Calon guru membuka `/register/guru`
2. Mengisi data registrasi dan memilih `jenjang`
3. Sistem membuat akun pending
4. Pengguna diarahkan ke halaman `pending aktivasi`
5. Guru klik `Bayar Sekarang`
6. Modal pembayaran menampilkan QR dan keterangan nominal
7. Guru upload bukti pembayaran
8. Sistem membuka WhatsApp admin dengan template pesan konfirmasi

Catatan:

- nomor WhatsApp admin diambil dari pengaturan finance superadmin
- jika nomor admin sudah diisi, halaman finance menampilkan indikator centang

## 5. Materi

Fitur aktif:

- daftar materi berdasarkan jenjang guru
- live search
- live filter mapel dan kurikulum
- bookmark materi
- mode `Bookmark Saya`
- detail materi
- tombol buka link eksternal

Flow:

1. Guru membuka menu `Materi`
2. Sistem hanya menampilkan materi yang relevan dengan jenjang guru dan materi global yang sesuai
3. Guru bisa mencari materi secara live
4. Guru bisa bookmark materi penting
5. Guru bisa membuka mode `Bookmark Saya` untuk melihat hanya materi yang disimpan

## 6. Bank Soal Global Ujion

Fitur aktif:

- list soal global dari tim Ujion
- live search
- live filtering mapel dan kurikulum
- bookmark soal global
- mode `Bookmark Saya`
- halaman detail soal dengan tampilan modern
- badge status, tipe, mapel, jenjang, dan identitas soal
- highlight kunci jawaban dan pembahasan

Flow:

1. Guru membuka `Soal dari Ujion`
2. Guru mencari atau memfilter soal secara live
3. Guru bisa bookmark soal penting
4. Pada list, icon bookmark aktif ditandai warna merah transparan
5. Guru membuka detail soal untuk membaca soal, opsi, kunci, dan pembahasan

Catatan:

- guru hanya bisa melihat soal global yang aktif dan sesuai jenjangnya
- akses detail sudah diamankan dengan policy

## 7. Bank Soal Pribadi

Fitur aktif:

- tambah soal cepat dari halaman daftar
- edit soal
- hapus soal
- status `draft` dan `terbit`
- upload gambar
- preview gambar sebelum submit
- validasi ukuran gambar maksimal 2 MB
- live search dan live filter kategori/tipe
- pagination AJAX
- builder fullscreen
- partial form bersama untuk tambah/edit

Tipe soal aktif:

- `PG`
- `Checklist`
- `Singkat`

Flow halaman daftar:

1. Guru membuka `Bank Soal Pribadi`
2. Guru bisa cari soal, filter kategori, dan filter tipe secara live
3. Guru bisa tambah soal via modal
4. Guru bisa edit soal via modal
5. Guru bisa hapus soal
6. Guru bisa masuk ke `Builder Soal Fullscreen`

Flow builder fullscreen:

1. Guru membuka `/guru/personal-questions/builder`
2. Guru mengelola banyak soal dalam satu layar
3. Guru bisa pindah soal via sidebar kanan
4. Opsi jawaban memakai label `A-E`
5. Maksimal opsi dibatasi sampai 5
6. Jawaban benar untuk `PG/Checklist` berupa dropdown huruf `A-E`
7. Tipe `Singkat` memakai input teks biasa
8. Guru bisa upload gambar dan melihat preview langsung
9. File di atas 2 MB ditolak dengan pesan error
10. Kategori dipakai untuk pengelompokan dan filter di halaman bank soal pribadi

Catatan:

- builder sekarang memakai route upload image yang aman lewat Laravel, tidak bergantung langsung ke `/storage`
- logic JS halaman sudah dipisah ke file `resources/js/pages/personal-question-builder.js`

## 8. Paket Soal TKA

Fitur aktif:

- daftar paket soal sesuai jenjang guru
- detail paket per mapel
- pengaturan jumlah soal, durasi, dan urutan mapel untuk paket yang dikelola guru
- preview soal per mapel
- akses ke kelola soal mapel
- akses teks bacaan
- tampilan detail paket sudah mendukung copy token simulasi per mapel

Flow:

1. Guru membuka `Paket Soal TKA`
2. Guru memilih paket
3. Di halaman detail paket, guru melihat mapel, jumlah soal, durasi, dan preview soal
4. Jika paket milik guru, guru dapat mengatur konfigurasi mapel
5. Guru dapat masuk ke halaman kelola soal mapel
6. Guru dapat menyalin token ujian aktif untuk simulasi

## 9. Teks Bacaan

Fitur aktif:

- tambah teks bacaan
- edit teks bacaan via modal
- hapus teks bacaan
- relasi dengan soal mapel

Flow:

1. Guru membuka menu teks bacaan dari halaman soal mapel
2. Guru menambah bacaan baru
3. Guru dapat mengedit bacaan dari modal
4. Bacaan bisa dipakai oleh beberapa soal sekaligus

## 10. Simulasi Ujian

Fitur aktif:

- form input token simulasi
- daftar ujian yang bisa dicoba
- riwayat simulasi
- copy token mapel pada halaman daftar ujian/paket
- tombol lihat hasil simulasi

Flow:

1. Guru membuka menu `Simulasi Ujian`
2. Guru bisa memasukkan token untuk mencoba alur siswa
3. Guru bisa melihat daftar ujian aktif yang tersedia
4. Guru bisa menyalin token per mapel
5. Setelah ikut simulasi, guru bisa membuka histori hasil

## 11. Live Chat dengan Superadmin

Fitur aktif:

- percakapan langsung guru ke superadmin
- kirim pesan teks
- kirim gambar
- preview gambar sebelum kirim
- informasi ukuran file maksimal 2 MB
- modal info/tutorial chat
- background kotak-kotak seperti role superadmin
- gambar chat dibuka lewat route Laravel agar tidak 403

Flow:

1. Guru membuka `Live Chat`
2. Guru menulis pesan atau memilih gambar
3. Saat gambar dipilih, preview muncul sebelum kirim
4. Guru mengirim pesan
5. Gambar chat yang terkirim dapat dibuka tanpa error 403

## 12. Header, Sidebar, dan UX Global

Fitur aktif:

- jam realtime
- perbesar/perkecil font
- light/dark mode
- dropdown profil
- di mobile, kontrol font dan dark mode digabung ke dropdown profil
- sidebar desktop bisa collapse
- icon dropdown profil sudah disejajarkan

Catatan teknis:

- script global dan script halaman sudah mulai dipisah ke `resources/js/core`, `resources/js/utils`, dan `resources/js/pages`
- `KaTeX` auto-render sudah dipindah dari inline `onload` ke helper global JS

## Ringkasan Status

Sudah aktif dan berjalan:

- dashboard
- profil
- panduan
- registrasi guru dan pending aktivasi
- finance sync dengan flow pembayaran guru
- materi + bookmark
- bank soal global + bookmark
- bank soal pribadi + builder fullscreen
- paket soal TKA
- teks bacaan
- simulasi ujian
- live chat
- perapihan responsive dan UX mobile

Masih layak dikembangkan lagi:

- analitik dashboard guru yang lebih dalam
- pengalaman edit massal soal yang lebih kaya
- sinkronisasi lebih lanjut antara soal global, materi, dan builder paket
