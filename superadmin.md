# Breakdown Fitur & Flow Superadmin

## 1. Dashboard

- Analisis harian (grafik aktivitas, jumlah login, dsb)
- Aktivitas terbaru (log aktivitas semua role)
- Jumlah guru, ujian aktif, guru aktif
- Top score guru membuat soal
- Statistik lain (opsional)

## 2. QR Harga

- CRUD QR (nama, jenjang, harga coret, harga normal, info tambahan, gambar QR)
- Aktif/nonaktifkan QR
- Hapus QR

## 3. Pricing Plan

- CRUD harga & promo
- Aktif/nonaktifkan harga
- Promo aktif/nonaktif

## 4. Live Chat

- Daftar chat guru/operator
- Balas pesan, kirim gambar
- Hapus chat/isi chat
- Status pesan terbaca/belum
- Tampilan seperti WhatsApp

## 5. Daftar Guru

- CRUD guru/operator
- Generate & refresh token akses
- Tabel guru mendaftar, tab aktif/suspend
- Aktif/nonaktifkan, suspend guru

## 6. Materi

- CRUD materi (jenjang, subelemen, unit, sub unit, akses link, kurikulum)
- Filter materi berdasarkan jenjang
- Aktif/nonaktifkan materi
- Hapus materi
- Status draft/terbit

## 7. Bank Soal

- CRUD soal (berdasarkan jenjang, tingkat, materi)
- Input image, multi-jawaban, jawaban singkat, pembahasan
- Status draft/terbit
- Hapus soal
- Aktif/nonaktifkan soal
- Filter soal berdasarkan jenjang
- Kategori soal (mudah, sedang, susah)

## 8. Ujian

- CRUD ujian (judul, tanggal, jam, peserta, soal)
- Builder soal fullscreen
- Management soal ujian
- Edit soal (update ke bank soal)
- Edit tanggal/jam ujian
- Aktif/nonaktifkan ujian
- Generate & share token/kode ujian
- Analisis ujian: ranking, pembahasan, jawaban benar

## 9. Log Aktivitas

- Lihat aktivitas semua role
- Tampilkan IP, lokasi, info pendukung
- Keterangan aktivitas detail

## 10. Cara Menggunakan

- Panduan per card/menu

## 11. Header & Footer

- Jam live
- Dropdown font size
- Light/dark mode
- Avatar + dropdown pengaturan/keluar
- Footer tahun & copyright

---

**Catatan:**

- Fitur yang sudah ada: Dashboard, QR Harga, Pricing Plan, Guru, Materi, Audit Log.
- Fitur yang perlu dibuat/ditingkatkan: Live Chat, Bank Soal, Ujian, Analisis Dashboard, Log detail, Panduan.
- Setiap fitur perlu controller, model, migration, view, dan route sesuai breakdown di atas.
