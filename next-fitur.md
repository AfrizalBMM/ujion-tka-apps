# Rencana Fitur Baru Ujion TKA (Tindak Lanjut Audit)

Untuk menjadikan platform Ujion TKA lebih modern, kompleks, dan kompetitif secara bisnis, berikut adalah rancangan penambahan dan pengembangan fitur baru secara detail per *role*:

## 1. Landing Page & Fitur Publik
* **Interactive Live Demo:** Menambahkan panel sandbox di mana pengunjung (calon Guru) dapat mencoba antarmuka pembuatan soal / ujian secara nyata tanpa perlu mendaftar.
* **Testimonial & Social Proof:** Carousel yang dinamis untuk menampilkan "Trusted By" (logo sekolah) dan testimoni pengguna.
* **Pricing Calculator Interaktif:** Slider UI (Berapa banyak siswa yang sekolah Anda kelola?) yang secara dinamis merekomendasikan paket dan diskon.
* **Support PWA (Progressive Web Application):** Seluruh platform bisa di *install* shortcut-nya langsung ke Homescreen HP / Desktop secara native.

## 2. Role Superadmin
* **Sistem Langganan & Billing Terautomasi:** Fitur approval berlangganan untuk memvalidasi pembayaran manual atau via Midtrans Payment Gateway secara otomatis.
* **System Health Monitor Dashboard:** Log metrik CPU/RAM server secara real-time, *error tracking* (jika ada siswa gagal submit ujian), dan kuota penggunaan media (gambar soal).
* **Broadcast Announcement:** Superadmin dapat mengirim pesan notifikasi *push/pop-up* yang akan menempel pada landing guru terkait pemeliharaan *server* atau promo khusus.

## 3. Role Guru (Pusat Kendali Pengajar)
* **Live Exam Monitoring (Real-time View):** Dasbor khusus saat ujian berlangsung, di mana guru bisa melihat *live tracker*: `30 / 40 Siswa Telah Login`, `Budi: Sedang Mengerjakan Soal 15`, `Siti: Sudah Selesai`. 
* **Anti-Cheat Alert Centre:** Tabel log kecurangan spesifik tiap ujian: `Andi mencoba pindah tab 3 kali`, membedakan statusnya jadi mencurigakan agar guru dapat mendiskualifikasi atau mengurangi nilai.
* **Auto-grading & Analisis Butir Soal:** 
  - Analisis otomatis item (Tingkat Kesukaran & Daya Pembeda) menggunakan *Item Response Theory* (IRT).
  - Tampilan visual per materi mana yang paling sulit dijawab agar pengajar tahu apa yang harus diajarkan kembali (Remedial).
* **Kolaborasi Bank Soal (Shared Bank):** Guru dalam satu sekolah dapat memilih opsi *Share Bank Soal*, agar kolega bisa meng-copy soal yang sudah dibuat untuk mata pelajaran yang sama.
* **Export PDF Laporan Detail & Excel Rekap:** 1-Click untuk men-download e-rapor *analytic sheet* ke kepala sekolah atau format e-Excel.

## 4. Role Siswa (Exam Taker)
* **Real-time Auto-Save to Backend (AJAX):** Setiap satu *radio button* yang dipilih, atau setiap 30 detik pada essay, jawaban otomatis dikirim ke API backend, ditambah *Local Storage*. (100% aman walaupun laptop *hang*, mati listrik, atau loss koneksi).
* **Strict Anti-Cheat / Kiosk Mode:** 
  - Jika siswa keluar dari layar *fullscreen*, ujian otomatis nge-*blur* dan hitungan mundur *warning* berjalan. 3 kali keluar = auto ter-submit.
* **Accessible Eye-Comfort System:** 
  - Tombol Dark / Light mode pada halaman ujian.
  - Opsi *FontSize* interaktif dan opsi Text-to-Speech untuk inklusivitas (bagi anak inklusi / kebutuhan khusus).
* **Interactive Exam Timer:** Progress bar melingkar warna-warni yang berubah zona warna perlahan (Hijau > Kuning > Merah) apabila aktu mau habis, beserta bunyi notifikasi 'beep' ketika sisa 5 menit.

---
**Roadmap Eksekusi:** Mulai dengan menyusun kembali basis database (Student & Participants Record), mem-fix dashboard Guru, membuat AJAX Auto-Save ujian Siswa, dan kemudian meremajakan UI (Tahapan Lanjutan Penguatan UX).
