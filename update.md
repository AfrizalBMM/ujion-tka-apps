# Update & Roadmap — Ujion TKA

> Dokumen rencana lanjutan setelah **Audit Menyeluruh 10 Fase** (lihat `ceklis-all.md`).
> Baseline saat dokumen ini dibuat: **112 test PASS (437 assertions)**, 71 temuan audit — 67 fix diterapkan, Pint bersih.

---

## Kondisi Saat Ini (Ringkas)

**Yang sudah kuat:**

- Otorisasi berlapis (middleware role + policy per-aksi + gate jenjang) konsisten di semua area
- Integritas ujian: timer server-side, kunci jawaban menjodohkan terenkripsi opaque, resume sesi terverifikasi
- Integritas data: approve payment & submit ujian/latihan atomic (transaction + lock), hapus ujian/paket paket berlindung konfirmasi
- Keamanan infra: webhook fail-closed, `env()` bebas di `app/`, file privat di disk non-public, IP dimask di log
- Test culture: 112 test regression, 31 di antaranya lahir dari audit

**Yang masih jadi beban:**

- ±50 pemakaian `Schema::hasColumn` per-request (query information_schema tiap halaman)
- Dua schema berjalan berdampingan (`questions`/`participants` legacy vs `soals`/`ujian_sesis` aktif)
- Realtime chat belum aktif (broadcast = log)
- Email verification tidak ada (keputusan produk, tapi menutup opsi self-service recovery)

---

## 1. Roadmap Keamanan

### Fase S1 — Sebelum Launch (wajib, est. 1–2 sesi kerja)

| Item                           | Detail                                                                                                                                 | Estimasi |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| Rate limit global API          | Tambah throttle di route API webhook & landing-click per-IP (sudah ada 120/menit, verifikasi cukup)                                    | 0,5 hari |
| Password policy superadmin     | Login superadmin hanya email+password tanpa 2FA — tambah minimal policy kompleksitas & lockout permanen counter                        | 0,5 hari |
| Review permission file storage | Pastikan `storage/app/private` (chat-images, payment-proofs, personal-question-images) tidak pernah ke-expose via symlink/config salah | 0,5 hari |
| Session timeout role           | Sesi superadmin lebih pendek (mis. 30 menit idle) daripada guru — tambah config per-role                                               | 1 hari   |
| Backup & restore drill         | Skrip backup DB + storage private; **tes restore** minimal sekali sebelum launch                                                       | 1 hari   |

### Fase S2 — Bulan Pertama Pasca-Launch

| Item                      | Detail                                                                                           | Estimasi |
| ------------------------- | ------------------------------------------------------------------------------------------------ | -------- |
| Login attempt monitoring  | Alert (log/WA ke admin) saat throttle terpicu berulang dari IP sama — deteksi brute force aktif  | 1 hari   |
| Audit log retention       | Policy purge `audit_logs` & `landing_click_logs` > 90 hari (command terjadwal)                   | 1 hari   |
| 2FA superadmin (opsional) | TOTP via package (mis. `pragmarx/google2fa`) — superadmin adalah akun paling berbahaya di sistem | 2–3 hari |
| Penetration test ringan   | Uji manual: IDOR di endpoint ber-ID, upload bypass, race condition submit                        | 2 hari   |
| CSP header                | Tambah `Content-Security-Policy` di middleware global — mitigasi XSS residual                    | 1 hari   |

### Fase S3 — Jangka Menengah (Q berikutnya)

- **Rotasi token otomatis**: token akses guru expire 90 hari + notifikasi WA H-7 (sekarang token abadi sampai admin refresh manual)
- **Password email+password untuk guru**: aktifkan jalur login alternatif (sudah ada kolomnya sejak fix F5-02)
- **Signed URL untuk asset privat**: gambar chat/proof pakai URL bertanda tangan dengan expiry, bukan session check per-request (lebih scalable)
- **Isolasi data per sekolah**: saat multi-sekolah tumbuh, pertimbangkan scope data guru per satuan pendidikan

---

## 2. Roadmap Fitur

### Prioritas Tinggi — Nilai Langsung Pengguna

| Fitur                                    | Alasan                                                                                                                                          | Estimasi |
| ---------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| **Analisis hasil per siswa tren waktu**  | Guru hanya lihat skor satu sesi — belum ada grafik perkembangan siswa antar ujian/latihan. Ini inti value proposition "memantau kesiapan siswa" | 3–4 hari |
| **Ekspor hasil latihan materi ke Excel** | PDF sudah ada; guru butuh olah data (rerata kelas, distribusi) — CSV/Excel export seperti hasil ujian                                           | 1–2 hari |
| **Bank soal pribadi → paket soal**       | Saat ini soal pribadi tidak bisa dipakai langsung ke paket — harus import ulang dari Ujion. Hubungkan dua modus                                 | 2–3 hari |
| **Notifikasi hasil ke siswa via WA**     | Setelah submit ujian/latihan, siswa dapat ringkasan skor via WA (data nomor_wa sudah ada)                                                       | 1–2 hari |

### Prioritas Menengah — Operasional

| Fitur                                       | Alasan                                                                                           | Estimasi |
| ------------------------------------------- | ------------------------------------------------------------------------------------------------ | -------- |
| **Grafik analisis soal per mapel**          | Heatmap sudah ada; tambah rekomendasi otomatis ("soal 7 dijawab benar 12% — perlu di-review")    | 2 hari   |
| **Draft/publish paket soal**                | Paket langsung aktif setelah buat — tambah state draft agar guru bisa persiapkan diam-diam       | 1–2 hari |
| **Multi-token per mapel (token per kelas)** | Satu token per mapel untuk semua kelas — token per kelas memudahkan kontrol & analisis per kelas | 2–3 hari |
| **Duplikat paket soal**                     | Guru bikin paket serapak untuk tryout berikutnya — harus salin manual sekarang                   | 1 hari   |

### Prioritas Rendah / Eksplorasi

- Ruang diskusi per materi (thread guru↔guru per jenjang)
- Mode ujian adaptif (soal berikut tergantung jawaban sebelumnya)
- Integrasi kalendar (jadwal ujian → Google Calendar)
- Aplikasi mobile pembungkus (PWA offline-safe untuk ujian dengan koneksi lab tidak stabil)

---

## 3. Tech-Debt Cleanup (jalan terpisah, tidak blokir fitur)

| Item                                          | Detail                                                                                                                                           | Prioritas |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | --------- |
| **Hapus `Schema::hasColumn` per-request**     | ±50 pemakaian di controller superadmin/landing. Skema sudah stabil via migrasi — hapus semua guard, tulis test untuk memastikan kolom memang ada | Tinggi    |
| **Konsolidasi schema legacy**                 | Buat rencana migrasi data `questions`/`participants` → `soals`/`ujian_sesis`, lalu drop tabel legacy. Do it BEFORE data production menumpuk      | Tinggi    |
| **Chat pagination penuh**                     | Index guru masih limit 200 — pecah jadi halaman dengan tombol "muat lebih lama"                                                                  | Sedang    |
| **Satu set route latihan**                    | `/siswa/latihan/*` dan `/materi/*` duplikat — jadikan `/materi/*` kanonis, redirect set satunya                                                  | Sedang    |
| **Dashboard guru aktivitas domain**           | Ganti AuditLog path teknis dengan ringkasan aktivitas bermakna                                                                                   | Rendah    |
| **Service layer untuk ExamController siswa**  | Controller 450+ baris — ekstrak skor/timer ke service agar teruji unit                                                                           | Rendah    |
| **Hapus `Route::has('login')` di error page** | Ganti deteksi role-based langsung tanpa cek route                                                                                                | Rendah    |

---

## 4. Kualitas & Testing

- **Target coverage**: minimal 70% pada `app/Http/Controllers` area kritikal (siswa & payment) — ukur dengan `phpunit --coverage`
- **Test race condition nyata**: test concurrency ( dua request paralel via `Http::fake` + process isolation) untuk approve payment & submit ujian
- **Smoke test E2E**: skrip Playwright 1 flow ujian end-to-end siswa (login → kerjakan → submit → skor) — jalan di CI opsional
- **CSRF & session test**: tambah test eksplisit untuk 419 flow setelah fix F0-10

---

## 5. Operasional & Deployment

- **CI pipeline** (GitHub Actions): `composer pint --test` + `php artisan test` + `npm run build` di setiap push
- **Monitoring**: error tracking (Sentry gratisan/flare) + uptime check route `/up`
- **Queue worker supervision**: pastikan `queue:work` jalan sebagai service (Laragon: NSSM/scheduled task; production: supervisor) — **tanpa ini semua notifikasi WA mati**
- **Backup otomatis**: DB harian + storage private mingguan, disimpan di lokasi beda mesin

---

## Urutan Eksekusi yang Direkomendasikan

```
Minggu 1-2  : Fase S1 keamanan pre-launch + tech-debt Schema::hasColumn
Minggu 3-4  : Fitur prioritas tinggi #1 (tren analisis siswa) + #2 (export excel)
Minggu 5    : Penetration test ringan + perbaikan temuan
Minggu 6-8  : Fitur #3 (bank soal → paket) + #4 (notifikasi WA hasil)
Bulan 2     : Fase S2 keamanan + fitur menengah sesi feedback user
Bulan 3     : Konsolidasi schema legacy + S3 (rotasi token, 2FA)
```

---

## Cara Kerja dengan Dokumen Ini

1. Setiap item selesai → centang & tulis tanggal di sini (atau pindahkan ke bagian "Selesai")
2. Setiap item baru (dari user feedback/incident) → tambahkan dengan format sama: alasan + estimasi
3. Review dokumen ini tiap akhir sprint — pindahkan prioritas sesuai kondisi lapangan
4. Item yang ditunda 2× review berturut-turut → hapus atau tulis alasan eksplisit kenapa masih ada
