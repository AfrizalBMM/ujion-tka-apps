# Audit Bug, Error Flow, Kelengkapan Controller, dan Potensi Kebocoran Data

Audit ini dibuat dari static review codebase dan verifikasi cepat dengan `php artisan test` pada 16 April 2026. Test yang ada lulus, tetapi cakupan test masih sangat kecil dan belum menutup mayoritas flow bisnis di bawah.

## Status Ceklis Perbaikan

- [x] Prioritas 1: route `superadmin/*` sudah diproteksi `auth` dan `role:superadmin`
- [x] Prioritas 2: untuk modul admin sudah diputuskan satu jalur resmi
- [x] Prioritas 3: `Superadmin\ExamController` sudah diubah agar create ujian menyimpan `user_id` dan builder tidak lagi memakai `exam_id` fiktif pada tabel `questions`
- [x] Prioritas 4: route resmi bank soal admin sudah diarahkan ke `global-questions`, sementara `/superadmin/questions` dijadikan redirect kompatibilitas
- [x] Batch lanjutan: CTA landing ke registrasi guru sudah benar
- [x] Batch lanjutan: form profil guru sudah sinkron dengan field yang benar-benar diproses
- [x] Batch lanjutan: hapus `PersonalQuestion` lintas akun sudah diblok
- [x] Batch lanjutan: save builder `PersonalQuestion` sudah dibungkus transaction
- [x] Batch lanjutan: dashboard guru sudah memakai schema `ujian_sesis`
- [x] Batch lanjutan: modul ujian guru sudah tersambung ke join, histori, dan hasil berbasis data nyata
- [x] Batch lanjutan: analisis ujian superadmin sudah memakai ranking dan distribusi nyata
- [x] Batch lanjutan: dashboard superadmin sudah memakai query nyata untuk metrik utama
- [x] Batch lanjutan: registrasi guru sekarang memakai email asli dan password acak
- [x] Batch lanjutan: validasi unik registrasi guru aktif untuk email dan nomor WA
- [x] Batch lanjutan: aktivasi guru selalu menampilkan token baru secara one-time lewat flash
- [x] Batch lanjutan: refresh token guru sekarang konsisten format dengan token aktivasi
- [x] Batch lanjutan: artefak modul `questions` legacy yang tidak dipakai sudah dibersihkan dari jalur superadmin
- [x] Batch lanjutan: view siswa legacy yang tidak sinkron dengan flow aktif sudah dibersihkan dari repo
- [x] Batch lanjutan: audit log sekarang menyamarkan path dinamis, IP, dan user agent sebelum disimpan
- [x] Batch lanjutan: form bank soal pribadi cepat sekarang benar-benar memecah opsi sesuai petunjuk UI
- [x] Batch lanjutan: chat superadmin sekarang difilter per percakapan dan dipaginasi
- [x] Batch lanjutan: detail materi guru sekarang memakai hitungan referensi yang jujur terhadap schema aktif
- [x] Batch lanjutan: tombol edit bank soal global dan tombol export dashboard/analisis sudah tersambung
- [x] Batch lanjutan: hapus paket soal sekarang diblok bila masih dipakai ujian aktif atau punya riwayat sesi
- [x] Batch lanjutan: hapus teks bacaan sekarang diblok bila masih dipakai soal

Catatan implementasi prioritas 2:

- bank soal resmi admin: `global_questions`
- soal yang terpasang ke ujian: snapshot di tabel `questions`
- relasi ujian ke soal: pivot `exam_question`
- dengan ini, modul admin tidak lagi bergantung pada kolom `questions.exam_id` yang memang tidak ada

## Temuan Kritis

### 1. Area `superadmin/*` tidak diproteksi `auth` dan `role:superadmin`

Lokasi:

- `routes/web.php`
- `bootstrap/app.php`

Bukti:

- group `superadmin` hanya memakai middleware `audit`

Dampak:

- user anonim berpotensi mengakses controller superadmin
- data sensitif seperti daftar guru, token ujian, audit log, chat, pricing, QR pembayaran dapat terekspos
- request create/update/delete superadmin juga berpotensi dieksekusi tanpa proteksi yang benar

Status:

- selesai diperbaiki pada route group `superadmin`

### 2. Builder ujian superadmin memakai schema lama yang tidak lagi cocok dengan database aktif

Lokasi:

- `app/Http/Controllers/Superadmin/ExamController.php`
- `app/Models/Question.php`
- `database/migrations/2026_04_14_010000_create_questions_table.php`

Bukti:

- controller memanggil `Question::whereNull('exam_id')`
- controller menghapus `Question::where('exam_id', $exam->id)`
- controller menyimpan `$q['exam_id'] = $exam->id`
- tetapi tabel `questions` tidak punya kolom `exam_id`

Dampak:

- halaman builder ujian sangat berpotensi error SQL saat dipakai
- import bank soal ke ujian menjadi tidak konsisten

Status:

- selesai diperbaiki
- builder sekarang memakai snapshot `questions` yang ditempel ke `exams` lewat pivot `exam_question`
- import bank soal admin kini mengambil sumber dari `global_questions`

### 3. Modul superadmin banyak aksi sensitif tanpa policy/authorization eksplisit

Lokasi:

- `TeacherController`
- `MaterialController`
- `GlobalQuestionController`
- `ExamController`
- `ChatController`
- `FinanceController`
- `PaymentQrController`
- `PricingPlanController`

Dampak:

- jika route protection bolong, tidak ada lapisan kedua di controller

### 4. Tampilan manajemen guru mengekspos token akses guru

Lokasi:

- `resources/views/superadmin/teachers.blade.php`

Bukti:

- token ditampilkan sebagian di layar dan full token diletakkan di atribut `title`

Dampak:

- token login guru bisa terbaca lewat hover, inspect, screenshot

### 5. Pembuatan ujian superadmin berpotensi gagal karena `user_id` tidak diisi

Lokasi:

- `app/Http/Controllers/Superadmin/ExamController.php`
- `database/migrations/2026_04_14_020000_create_exams_table.php`

Bukti:

- migration `exams` mewajibkan `user_id`
- `ExamController@store` hanya memvalidasi dan menyimpan `paket_soal_id`, `judul`, `tanggal_terbit`, `max_peserta`, `timer`, `status`
- tidak ada assignment `user_id` sebelum `Exam::create($data)`

Dampak:

- create ujian dapat gagal pada database constraint
- flow admin membuat ujian belum benar-benar aman dipakai

Status:

- selesai diperbaiki dengan pengisian `user_id` dari user superadmin yang sedang login

## Temuan Mayor

### 6. CTA registrasi di landing rusak

Lokasi:

- `resources/views/landing.blade.php`

Bukti:

- view memanggil `route('register')`
- route yang ada adalah `register.guru.form`

Dampak:

- user baru dari landing tidak bisa masuk ke flow registrasi yang benar

Status:

- selesai diperbaiki
- CTA landing sekarang langsung menuju `register.guru.form`

### 7. Profil guru menampilkan field yang tidak pernah diproses saat submit

Lokasi:

- `resources/views/guru/profile.blade.php`
- `app/Http/Controllers/Guru/ProfileController.php`

Bukti:

- UI menampilkan `jenjang`, `tingkat`, `satuan_pendidikan`, `no_wa`
- controller hanya memvalidasi `name`, `email`, `avatar`

Dampak:

- user merasa data berhasil disimpan padahal field lain diabaikan

Status:

- selesai diperbaiki
- controller profile sekarang memproses `jenjang`, `tingkat`, `satuan_pendidikan`, `no_wa`, dan `avatar`
- kolom `avatar` ditambahkan ke schema `users`

### 8. Guru dapat menghapus `PersonalQuestion` milik user lain

Lokasi:

- `app/Http/Controllers/Guru/PersonalQuestionController.php`

Bukti:

- `destroy(PersonalQuestion $question)` langsung delete tanpa cek ownership dan tanpa policy

Dampak:

- data soal pribadi berpotensi dihapus lintas akun

Status:

- selesai diperbaiki
- `destroy(PersonalQuestion $question)` sekarang memverifikasi ownership sebelum delete

### 9. Builder bank soal pribadi menghapus semua soal lama lebih dulu

Lokasi:

- `app/Http/Controllers/Guru/PersonalQuestionController.php::saveBuilder`

Bukti:

- semua soal user dihapus lalu dibuat ulang
- tidak dibungkus transaction

Dampak:

- risiko kehilangan data jika payload bermasalah atau proses terputus

Status:

- selesai diperbaiki
- `saveBuilder` sekarang dibungkus transaction agar proses replace soal lebih aman

### 10. Dashboard guru memakai tabel peserta lama, bukan schema ujian baru

Lokasi:

- `app/Http/Controllers/Guru/DashboardController.php`

Bukti:

- memakai `Participant`
- flow siswa aktif sekarang menulis ke `UjianSesi`

Dampak:

- metrik dashboard bisa kosong atau tidak akurat

Status:

- selesai diperbaiki
- dashboard guru sekarang menghitung statistik dari `ujian_sesis` berdasarkan nomor WhatsApp guru

### 11. Modul ujian guru masih placeholder dan tidak selesai end-to-end

Lokasi:

- `app/Http/Controllers/Guru/ExamController.php`

Bukti:

- ada `TODO` untuk histori, attach peserta, hasil, dan pembahasan

Dampak:

- menu ujian guru terlihat aktif di UI tetapi flow bisnisnya belum jadi

Status:

- selesai diperbaiki untuk flow inti
- guru sekarang bisa join ujian aktif berdasarkan token
- join akan membuat atau memakai `ujian_sesi` yang sesuai lalu meneruskan ke flow ujian aktif
- histori dan halaman hasil guru sekarang mengambil data nyata dari `ujian_sesis` dan `jawaban_siswas`

### 12. Analisis ujian superadmin masih dummy

Lokasi:

- `app/Http/Controllers/Superadmin/ExamAnalysisController.php`

Bukti:

- ranking dan distribusi dibuat hardcoded

Dampak:

- laporan analisis tidak bisa dipercaya

Status:

- selesai diperbaiki
- ranking, jumlah peserta selesai, rata-rata skor, dan distribusi nilai sekarang dihitung dari `ujian_sesis`

### 13. Dashboard superadmin masih berisi placeholder penting

Lokasi:

- `app/Http/Controllers/Superadmin/DashboardController.php`

Bukti:

- `ongoingExamsCount => 0`
- `totalRevenue => '0'`
- `topTeacherName` hanya guru pertama

Dampak:

- dashboard memberi gambaran operasional yang menyesatkan

Status:

- selesai diperbaiki
- `ongoingExamsCount` sekarang dihitung dari sesi `mengerjakan`
- `totalRevenue` sekarang memakai query nyata berbasis guru aktif dan paket aktif
- `topTeacherName` sekarang memakai kontribusi soal pribadi terbit

### 14. Register guru membuat email dummy dan password default statis

Lokasi:

- `app/Http/Controllers/RegisterGuruController.php`

Bukti:

- email diisi `no_wa.'@dummy.email'`
- password diisi `Hash::make('password')`

Dampak:

- kualitas data user buruk
- berisiko jika login password diaktifkan di masa depan

Status:

- selesai diperbaiki
- registrasi guru sekarang menyimpan email asli dari form
- password akun dibuat acak dan tidak lagi memakai nilai statis

### 15. Tidak ada validasi unik pada registrasi guru

Lokasi:

- `app/Http/Controllers/RegisterGuruController.php`

Dampak:

- potensi user ganda untuk nomor WA yang sama

Status:

- selesai diperbaiki
- registrasi sekarang memvalidasi unik untuk `email` dan `no_wa`
- nomor WA disimpan dalam bentuk ternormalisasi numerik

### 16. Aktivasi guru berpotensi tidak menampilkan token baru meski token dibuat

Lokasi:

- `app/Http/Controllers/Superadmin/TeacherController.php`

Bukti:

- flash message memakai `$teacher->wasRecentlyUpdated`

Dampak:

- admin bisa gagal melihat token baru di pesan sukses

Status:

- selesai diperbaiki
- aktivasi sekarang selalu mengembalikan token one-time lewat flash jika akun diaktifkan
- tabel guru tidak lagi menaruh full token pada atribut `title`

### 17. Token refresh guru tidak konsisten format dengan token aktivasi

Lokasi:

- `TeacherController@activate`
- `TeacherController@refreshToken`

Bukti:

- aktivasi: token 10 karakter uppercase
- refresh: token 32 karakter campuran

Dampak:

- UX dan operasional token tidak konsisten

Status:

- selesai diperbaiki
- aktivasi dan refresh sekarang memakai generator token yang sama dengan format `10` karakter uppercase

### 18. `global_questions` sudah dipakai, tetapi modul `questions` lama masih tetap hidup

Lokasi:

- `Superadmin\GlobalQuestionController`
- `Superadmin\QuestionController`
- `app/Models/Question.php`

Dampak:

- membingungkan developer
- rawan salah pakai model/table

Status:

- selesai diperbaiki
- controller legacy `Superadmin\QuestionController` yang sudah tidak dipakai dihapus dari codebase
- jalur resmi bank soal admin tetap `global_questions`, sementara route kompatibilitas tetap diarahkan ke modul resmi

### 19. Route `/superadmin/questions` masih menunjuk modul lama, tetapi view yang dipakai sudah modul baru

Lokasi:

- `app/Http/Controllers/Superadmin/QuestionController.php`
- `resources/views/superadmin/questions.blade.php`
- `resources/views/superadmin/dashboard.blade.php`

Bukti:

- `QuestionController@index` mengirim variabel `$questions`
- view `superadmin.questions` merender `$globalQuestions`
- tombol "Bank Soal" di dashboard menuju `route('superadmin.questions.index')`

Dampak:

- halaman bank soal berpotensi error karena variabel view tidak sinkron
- admin diarahkan ke route legacy yang salah untuk bank soal global

Status:

- selesai diperbaiki
- route resmi bank soal admin adalah `superadmin.global-questions.*`
- route lama `superadmin.questions.index` dipertahankan hanya sebagai redirect kompatibilitas

### 20. Modul siswa lama masih tertinggal di repo dan tidak sinkron dengan flow aktif

Lokasi:

- `resources/views/siswa/ujian.blade.php`
- `resources/views/siswa/petunjuk.blade.php`
- `resources/views/siswa/selesai.blade.php`

Bukti:

- file lama masih memakai variabel seperti `$participant`
- flow aktif siswa sekarang memakai `resources/views/ujian/*`

Dampak:

- developer mudah salah edit view

Status:

- selesai diperbaiki
- view siswa legacy yang masih memakai flow `$participant` lama sudah dibersihkan dari repo
- flow aktif siswa tetap memakai `resources/views/ujian/*`

### 21. Audit log merekam metadata sensitif dan tampil di area yang belum aman

Lokasi:

- `app/Http/Middleware/AuditRequest.php`
- `resources/views/superadmin/audit-logs.blade.php`

Dampak:

- path, IP, dan user agent mudah terekspos bila area superadmin diakses pihak tidak berwenang

Status:

- selesai diperbaiki
- middleware audit sekarang menyamarkan segmen path dinamis, IP address, dan user agent sebelum data disimpan
- halaman audit log sekarang hanya menampilkan metadata yang sudah disanitasi

## Temuan Menengah

### 22. Form bank soal pribadi cepat tidak cocok dengan petunjuk UI opsi

Lokasi:

- `resources/views/guru/personal-questions.blade.php`
- `app/Http/Controllers/Guru/PersonalQuestionController.php`

Bukti:

- UI meminta opsi dipisahkan koma
- input yang dikirim adalah `opsi[]` tunggal

Dampak:

- user bisa menyimpan `"A,B,C,D"` sebagai satu item, bukan 4 opsi

Status:

- selesai diperbaiki
- form cepat bank soal pribadi sekarang mengirim `options_raw`
- controller memecah opsi berdasarkan koma atau baris baru agar hasil simpan sesuai petunjuk UI

### 23. Chat superadmin memuat semua chat tanpa filter percakapan

Lokasi:

- `app/Http/Controllers/Superadmin/ChatController.php`

Dampak:

- halaman makin berat seiring data bertambah
- semua percakapan bercampur

Status:

- selesai diperbaiki
- chat superadmin sekarang menampilkan daftar percakapan per guru
- isi chat difilter ke percakapan terpilih saja dan dipaginasi
- pesan masuk pada percakapan yang dibuka juga langsung ditandai terbaca

### 24. Material detail guru menghitung keterikatan soal dari tabel lama `questions`

Lokasi:

- `app/Http/Controllers/Guru/MaterialController.php`

Dampak:

- angka "soal terikat" tidak merepresentasikan `soals` paket TKA baru

Status:

- selesai diperbaiki
- detail materi guru sekarang memisahkan hitungan referensi bank soal global dan snapshot builder ujian
- label statistik diperjelas agar tidak lagi mengklaim angka palsu sebagai keterikatan `soals` paket baru

### 25. Beberapa tombol UI masih dummy atau belum tersambung

Lokasi:

- tombol edit pada daftar bank soal global
- export Excel/PDF pada analisis ujian
- tombol PDF/CSV pada dashboard superadmin

Dampak:

- UI terlihat lengkap tetapi fungsi belum ada

Status:

- selesai diperbaiki untuk tombol utama yang sebelumnya dummy
- edit pada bank soal global sekarang membuka modal edit dan menyimpan perubahan ke backend
- dashboard superadmin sekarang punya export CSV dan versi cetak
- analisis ujian sekarang punya export CSV dan versi cetak

### 26. Hapus paket soal tidak memperlihatkan guard bisnis untuk dependensi ujian aktif

Lokasi:

- `app/Http/Controllers/Superadmin/PaketSoalController.php::destroy`

Dampak:

- perilaku bergantung pada constraint database, bukan validasi bisnis eksplisit

Status:

- selesai diperbaiki
- hapus paket soal sekarang diblok jika paket masih dipakai ujian aktif atau sudah memiliki riwayat sesi ujian
- user mendapat flash warning yang eksplisit, bukan gagal diam-diam dari constraint database

### 27. Hapus teks bacaan tidak memberi warning saat masih dipakai soal

Lokasi:

- `Guru\TeksBacaanGuruController@destroy`
- `Superadmin\TeksBacaanController@destroy`

Dampak:

- referensi teks di soal bisa hilang tiba-tiba secara bisnis

Status:

- selesai diperbaiki
- controller guru dan superadmin sekarang memeriksa relasi `soals()` sebelum menghapus teks bacaan
- jika masih dipakai, proses delete dibatalkan dan user mendapat flash warning

## Flow yang Kurang / Belum Lengkap

### Registrasi dan pembayaran guru

Kurang:

- upload bukti transfer
- status pembayaran
- approval workflow
- notifikasi ke superadmin
- notifikasi token ke guru

### Ujian guru

Kurang:

- join ujian yang benar
- histori ikut ujian
- hasil ujian per guru
- pembahasan

### Analitik ujian

Kurang:

- ranking peserta real
- distribusi nilai real
- export nyata
- rekap per mapel

### Operasional umum

Kurang:

- guard delete untuk data aktif
- pagination di list besar
- search/filter nyata di banyak halaman

## Controller yang Perlu Dirapikan / Dilengkapi

Prioritas tinggi:

- `app/Http/Controllers/Superadmin/ExamController.php`
- `app/Http/Controllers/Guru/ExamController.php`
- `app/Http/Controllers/Guru/ProfileController.php`
- `app/Http/Controllers/Guru/PersonalQuestionController.php`
- `app/Http/Controllers/Superadmin/ChatController.php`
- `app/Http/Controllers/RegisterGuruController.php`

Prioritas arsitektur:

- konsolidasikan modul lama `questions/participants` dengan modul baru `soals/ujian_sesis`
- tentukan satu sumber kebenaran untuk bank soal dan sesi ujian

## Rekomendasi Urutan Perbaikan

1. Amankan route `superadmin` dengan `auth` dan `role:superadmin`.
2. Putuskan migrasi final: pakai schema lama atau schema baru untuk modul ujian, lalu hapus jalur campuran.
3. Perbaiki `Superadmin\ExamController` agar create ujian mengisi `user_id` dan builder ujian tidak memakai `exam_id` pada `questions`.
4. Putuskan route resmi bank soal admin: `global-questions` atau `questions`, lalu hapus jalur yang tidak dipakai dan sinkronkan view/controller/dashboard.
5. Tambahkan authorization ownership pada `PersonalQuestionController`.
6. Perbaiki CTA landing dan sinkronkan flow registrasi guru.
7. Sinkronkan form profil guru dengan field yang benar-benar diproses.
8. Ganti data dummy/placeholder di dashboard dan analisis dengan query nyata.
9. Kurangi eksposur token akses guru di UI.
10. Bersihkan view/controller legacy yang tidak dipakai agar developer tidak salah jalur.

## Verifikasi singkat

Perintah yang sempat dijalankan:

- `php artisan route:list --except-vendor`
- `php artisan test`

Hasil test:

- 31 test lulus

Keterbatasan audit:

- ini audit static code review, bukan full click-test browser
- jadi beberapa issue saya tandai sebagai potensi error/putus flow saat bukti kuat ada di controller, migrasi, atau route
