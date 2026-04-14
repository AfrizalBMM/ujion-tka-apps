# Audit Codebase Ujion TKA

Berdasarkan hasil investigasi mendalam terhadap *source code*, struktur direktori, dan relasi database project Ujion TKA, berikut adalah hasil audit komprehensif:

## 1. Flow & Arsitektur Sistem

### 🔴 Critical Bug & Konsep yang Salah pada Flow Guru
- **Fatal Error di Dashboard Guru:** Pada `app/Http/Controllers/Guru/DashboardController.php`, terdapat query `$ujianDiikuti = Exam::whereHas('participants', ...)` yang memposisikan Guru sebagai pengikut ujian (Siswa). Selain itu, relasi `participants` belum didefinisikan sama sekali di model `Exam`. Ini akan menyebabkan aplikasi *crash*.
- **Copywriting Variabel Salah:** Terdapat variabel `skorRata`, `progres`, dan `ujianDiikuti` di dashboard guru. Seharusnya dashboard analitik guru menampilkan "Jumlah Ujian Dibuat", "Total Siswa Mengerjakan", dan "Rata-rata Nilai Kelas".

### 🟠 Flow Siswa (Frontend-heavy, Rawan Data Hilang)
- Saat ini proses *flow* ujian hanya mengandalkan frontend dengan vanilla JavaScript (`siswa/ujian.blade.php`). Jawaban belum terhubung dengan state atau disinkronisasi ke database.
- Autentikasi siswa via Token ("Masuk Ujian Siswa") belum memiliki dukungan session manajemen ujian yang *robust*. Jika halaman terefresh atau browser *crash*, seluruh progress pengerjaan siswa akan hilang.
- Anti-cheat (Cegah pindah tab) menggunakan alert window bawaan JS yang bisa dibypass dengan mudah. Belum ada auto-record "upaya curang" ke API.

### 🟡 Flow Superadmin (Belum Seamless)
- Manajemen "QR Pembayaran" dan "Harga (Pricing)" sudah ada antar mukanya, tetapi tidak terintegrasi ke alur pendaftaran premium/berlangganan guru. Jika guru ingin berlangganan *Pricing Plan* tertentu, halaman invoice dan verifikasi dari superadmin belum disiapkan.

## 2. UI / UX (User Interface & Experience)

- **Landing Page Kaku:** Menggunakan Tailwind CSS & Flowbite yang cukup bersih, support dark mode, namun visualnya masih kurang "Wow". Kurang elemen dinamis seperti partikel *background*, *glassmorphism*, atau animasi interaksi (*micro-interactions*).
- **Dashboard Internal (Guru & Superadmin):**
  - Tampilan tabel masih sangat konvensional dan *basic*. Belum ada fitur *client-side searching* atau paginasi dinamis ala *DataTables*.
  - Ruang kosong *Dashboard Guru* sangat terasa karena belum adanya bentuk grafis (Chart) performa analitik nilai ulangan siswa.
- **Halaman Ujian Siswa Kurang Optimal:** 
  - Sidebar kontrol halaman (Navigasi Nomor Soal) terasa sesak (termasuk tombol "Selesaikan"). 
  - Siswa wajib fokus berjam-jam saat ujian, namun halaman ujian tidak menyediakan fitur *Dark Mode* yang sangat membantu mengurangi kelelahan mata.

## 3. Copywriting & Mikro-teks

- **Kurang Persuasif:** Teks pada pendaftaran guru dan halaman pricing terlihat *bare-bones* (terlalu fungsional). Misalnya "Coba sekarang", lebih pas jika dibubuhi "Gratis Coba 7 Hari - Tanpa Kartu Kredit".
- **Bahasa Asing vs Lokal:** Ada ketidak-konsistensi dalam pemilihan kata di dashboard (misalnya *Refresh Token*, *Suspend* vs bahasa lokal *Tangguhkan*, *Perbarui Akses*).
- **Context Salah:** Di *Dashboard Guru*, terdapat title "Progres Belajar" dan "Skor Rata-rata" yang membuat user bingung karena itu istilah untuk partisipan/siswa.

---
**Kesimpulan Audit:** Prioritas perbaikan utama ada di penataan ulang *Model* database yang berkaitan dengan ujian serta membenahi Controller Guru agar sesuai dengan rolenya. Selanjutnya, sistem AJAX realtime untuk *saving* ujian mahasiswa.
