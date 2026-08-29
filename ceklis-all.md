# Ceklis Audit Menyeluruh — Ujion TKA

> Dokumen master audit semua flow aktif aplikasi. Status aplikasi: **development (belum live)**.
> Prioritas audit: **seimbang** (security, fungsional, data integrity, UX, performa).
> Scope: **flow aktif saja** (legacy `participants`, `participant_answers`, `questions` tidak diaudit).

---

## 1. Metodologi Audit

Setiap flow akan diaudit dari **5 aspek**:

| Kode | Aspek               | Yang Dicek                                                                                                                                                       |
| ---- | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 🔒   | **Security**        | Otorisasi antar role, validasi input, CSRF, XSS, SQL injection, IDOR (akses data milik orang lain), keamanan upload file, rate limiting, exposure token/password |
| ⚙️   | **Fungsional**      | Happy path jalan?, edge case (input kosong/ekstrem, double submit, back button, refresh di tengah proses), error handling                                        |
| 🗄️   | **Integritas Data** | Transaksi DB (rollback saat gagal), race condition (dua user bersamaan), data yatim/orphan, konsistensi state (status akun, status pembayaran, status sesi)      |
| 🎨   | **UX**              | Pesan error jelas?, loading state, feedback setelah aksi, konsistensi UI antar halaman                                                                           |
| ⚡   | **Performa**        | N+1 query, pagination di list besar, query berat di halaman dashboard, ukuran response API                                                                       |

## 2. Klasifikasi Severity Temuan

| Level     | Arti                                                       | Contoh                                                             |
| --------- | ---------------------------------------------------------- | ------------------------------------------------------------------ |
| 🔴 Kritis | Celah security / data hilang / flow mati total             | Siswa bisa akses jawaban orang lain, pembayaran bisa di-approve 2x |
| 🟠 Tinggi | Logika bisnis salah / potensi masalah data                 | Skor ujian salah hitung, token bisa dipakai 2x bersamaan           |
| 🟡 Sedang | Edge case tidak tertangani / UX membingungkan / perf minor | Tidak ada loading state, error 500 saat upload file non-gambar     |
| 🟢 Rendah | Kualitas kode / kosmetik / nice-to-have                    | N+1 ringan, penamaan tidak konsisten                               |

## 3. Urutan Eksekusi Audit (Rekomendasi)

```
FASE 0 → J  (Fondasi keamanan — karena memengaruhi semua flow)
FASE 1 → C  (Auth — gerbang semua role)
FASE 2 → B  (Registrasi & Payment — melibatkan uang)
FASE 3 → F  (Ujian siswa — fitur inti produk)
FASE 4 → G  (Latihan materi siswa — fitur inti kedua)
FASE 5 → D  (Operasional guru)
FASE 6 → E  (Operasional superadmin)
FASE 7 → H  (Chat realtime)
FASE 8 → I  (Integrasi WhatsApp)
FASE 9 → A  (Landing & halaman publik)
```

**Alasan urutan:** fondasi keamanan dan auth diaudit duluan supaya temuan di flow lain bisa langsung dinilai dampaknya. Payment sebelum fitur lain karena melibatkan transaksi uang. Ujian siswa sebelum operasional guru/superadmin karena itu inti produk.

---

## 4. Tracker Progress

| Fase | Flow                          | Status     | Jumlah Temuan                                                      |
| ---- | ----------------------------- | ---------- | ------------------------------------------------------------------ |
| 0    | J — Cross-cutting & Fondasi   | ✅ Selesai | 18 (1 kritis, 6 tinggi, 9 sedang, 2 rendah) — SEMUA FIX DITERAPKAN |
| 1    | C — Auth Semua Role           | ✅ Selesai | 9 (0 kritis, 2 tinggi, 4 sedang, 3 rendah) — SEMUA FIX DITERAPKAN  |
| 2    | B — Registrasi Guru & Payment | ✅ Selesai | 8 (0 kritis, 2 tinggi, 4 sedang, 2 rendah) — SEMUA FIX DITERAPKAN  |
| 3    | F — Ujian Siswa               | ✅ Selesai | 7 (1 kritis, 2 tinggi, 3 sedang, 1 rendah) — SEMUA FIX DITERAPKAN  |
| 4    | G — Latihan Materi Siswa      | ✅ Selesai | 6 (0 kritis, 2 tinggi, 3 sedang, 1 rendah) — SEMUA FIX DITERAPKAN  |
| 5    | D — Operasional Guru          | ✅ Selesai | 7 (0 kritis, 2 tinggi, 4 sedang, 1 rendah) — SEMUA FIX DITERAPKAN  |
| 6    | E — Operasional Superadmin    | ✅ Selesai | 7 (0 kritis, 2 tinggi, 4 sedang, 1 rendah) — SEMUA FIX DITERAPKAN  |
| 7    | H — Chat Guru ↔ Superadmin    | ✅ Selesai | 4 (0 kritis, 1 tinggi, 2 sedang, 1 rendah)                         |
| 8    | I — Integrasi WhatsApp        | ✅ Selesai | 2 (0 kritis, 0 tinggi, 1 sedang, 1 rendah)                         |
| 9    | A — Landing & Publik          | ✅ Selesai | 3 (0 kritis, 0 tinggi, 2 sedang, 1 rendah)                         |
| 2    | B — Registrasi Guru & Payment | ⬜ Belum   | —                                                                  |
| 3    | F — Ujian Siswa               | ⬜ Belum   | —                                                                  |
| 4    | G — Latihan Materi Siswa      | ⬜ Belum   | —                                                                  |
| 5    | D — Operasional Guru          | ⬜ Belum   | —                                                                  |
| 6    | E — Operasional Superadmin    | ⬜ Belum   | —                                                                  |
| 7    | H — Chat Guru ↔ Superadmin    | ⬜ Belum   | —                                                                  |
| 8    | I — Integrasi WhatsApp        | ⬜ Belum   | —                                                                  |
| 9    | A — Landing & Publik          | ⬜ Belum   | —                                                                  |

Status legend: ⬜ Belum · 🔄 Berjalan · ✅ Selesai

---

## FASE 0 — J. Cross-cutting & Fondasi Keamanan

Diaudit SEBELUM flow lain karena hasilnya jadi baseline semua flow.

### J1. Enforcement Middleware & Route Protection

- [ ] J1.1 🔒 Pindai semua route: tidak ada route superadmin/guru/siswa yang lupa dikasih middleware `role`
- [ ] J1.2 🔒 Pastikan route API siswa (`siswa/api/save-answer`) memvalidasi sesi, bukan cuma token di URL/body
- [ ] J1.3 🔒 Pastikan tidak ada route yang hanya mengandalkan "hidden URL" (security by obscurity)
- [ ] J1.4 ⚙️ Cek behavior route `up` (health check) tidak membocorkan info sensitif

### J2. Policies & Gates Consistency

- [ ] J2.1 🔒 Pastikan `PaketSoalPolicy`, `SoalPolicy`, `GlobalQuestionPolicy`, `MaterialPolicy`, `ExamPolicy` benar-benar dipanggil di controller (bukan cuma didefinisikan)
- [ ] J2.2 🔒 Cek Gate `manage-mapel-soal` — guru jenjang A tidak bisa manipulasi paket jenjang B
- [ ] J2.3 🔒 Cek konsistensi: aksi destroy/update pakai policy, tapi aksi show/list apakah boleh dilihat semua guru?

### J3. Audit Logging Coverage

- [ ] J3.1 🔒 Aksi sensitif mana saja yang belum ter-cover middleware `audit`
- [ ] J3.2 🔒 Payload yang dicatat di `audit_logs` tidak menyimpan password/token/file path sensitif
- [ ] J3.3 ⚙️ Audit log tidak bisa dihapus/diedit oleh superadmin via UI

### J4. Keamanan Upload File

- [ ] J4.1 🔒 Validasi MIME/extension untuk: bukti pembayaran, gambar soal, gambar chat, avatar, logo landing
- [ ] J4.2 🔒 Nama file di-sanitize (path traversal)
- [ ] J4.3 🔒 File upload disimpan di luar public path atau divalidasi ekstensinya
- [ ] J4.4 🔒 Ukuran file maksimum dibatasi (DoS)
- [ ] J4.5 ⚙️ CleanupPaymentProofs command berjalan benar (tidak hapus file yang masih dibutuhkan)

### J5. Throttle & Rate Limiting

- [ ] J5.1 🔒 Route check-email/check-wa tidak bisa dipakai untuk enumerasi user
- [ ] J5.2 🔒 API webhook & landing-click tidak bisa di-spam
- [ ] J5.3 🔒 Route login siswa (token) throttled — brute force token ujian

### J6. Error Handling & Halaman Error

- [ ] J6.1 ⚙️ Halaman 403/404/419/500 custom ada dan informatif
- [ ] J6.2 ⚙️ Tidak ada exception message mentah bocor ke user saat APP_DEBUG=false
- [ ] J6.3 ⚙️ Log error terstruktur di `storage/logs`

### J7. Database Integrity

- [ ] J7.1 🗄️ Aksi multi-tabel (approve payment, submit ujian, regenerate paket latihan) dibungkus DB::transaction
- [ ] J7.2 🗄️ Foreign key + cascade behavior benar (hapus paket soal → soal, pilihan, sesi ikut/tertangani)
- [ ] J7.3 🗄️ Index tersedia di kolom yang sering di-query (token, reference code, foreign key)
- [ ] J7.4 🗄️ Tidak ada unique constraint yang bisa lolos di level aplikasi tapi gagal di DB (race condition)

### J8. Konfigurasi & Environment

- [ ] J8.1 🔒 `.env` tidak berisi secret production yang bocor ke repo
- [ ] J8.2 🔒 `SESSION_ENCRYPT`, `APP_DEBUG`, konfigurasi session cookie benar untuk production
- [ ] J8.3 ⚡ Config/route/view cache aman untuk production (ceklist deployment AGENTS.md valid)

---

## FASE 1 — C. Auth Semua Role

### C1. Login Superadmin (`/ngadumin/login`)

- [ ] C1.1 🔒 Throttle aktif (5 attempt/menit)
- [ ] C1.2 🔒 Tidak ada info leak "password salah" vs "email tidak terdaftar"
- [ ] C1.3 🔒 Session fixation: session di-regenerate setelah login
- [ ] C1.4 ⚙️ Guru yang coba login via form superadmin ditolak benar (bukan error 500)
- [ ] C1.5 🎨 Pesan error & redirect setelah login jelas

### C2. Login Guru (`/login` — nama + access token)

- [ ] C2.1 🔒 Access token di-hidden dari response API/serialize (cek `$hidden` model User)
- [ ] C2.2 🔒 Akun `pending`/`suspend` tidak bisa login (middleware `guru.active`)
- [ ] C2.3 🔒 Token tidak bocor di log/URL referer
- [ ] C2.4 ⚙️ Case sensitivity nama saat login — konsisten?
- [ ] C2.5 ⚙️ Logout menghapus state sesi ujian/simulasi yang menempel

### C3. Lupa Token Guru

- [ ] C3.1 🔒 Tidak bisa dipakai untuk enumerasi data guru
- [ ] C3.2 ⚙️ Flow kirim ulang token via WA — behavior jika WA gateway mati
- [ ] C3.3 ⚙️ Token lama invalidated atau masih berlaku setelah refresh?

### C4. Login Siswa (token ujian, `/siswa/login`)

- [ ] C4.1 🔒 Throttle brute force token
- [ ] C4.2 ⚙️ Token expired/single-use ditangani
- [ ] C4.3 🗄️ Dua siswa login token sama bersamaan — apa yang terjadi?
- [ ] C4.4 ⚙️ Ujian yang sudah selesai/disabled — token ditolak dengan pesan jelas

### C5. Login Siswa Latihan (`/siswa/latihan/login`)

- [ ] C5.1 🔒 Throttle + validasi token latihan
- [ ] C5.2 ⚙️ Token latihan yang di-regenerate — sesi lama masih valid?

### C6. Logout Semua Role

- [ ] C6.1 🔒 Session benar-benar terhapus, back button tidak bisa kembali ke halaman authenticated
- [ ] C6.2 ⚙️ Sesi ujian aktif siswa saat logout — jawaban tersimpan?

### C7. Session Security Global

- [ ] C7.1 🔒 Session lifetime 120 menit cukup untuk durasi ujian?
- [ ] C7.2 🔒 Cookie config: http_only, secure, same_site

---

## FASE 2 — B. Registrasi Guru & Payment Flow

### B1. Form Registrasi (`/register/guru`)

- [ ] B1.1 🔒 Validasi server-side lengkap (email, WA, jenjang, satuan pendidikan) — tidak hanya JS
- [ ] B1.2 🔒 check-email & check-wa tidak bocorkan data pendaftar (privacy)
- [ ] B1.3 🗄️ Double submit registrasi — tidak terjadi duplikat user
- [ ] B1.4 ⚙️ Format nomor WA dinormalisasi benar (PhoneNumber helper) untuk semua input
- [ ] B1.5 🎨 Error validation jelas per field

### B2. Halaman Pending Aktivasi

- [ ] B2.1 🔒 Guru A tidak bisa lihat pending page guru B (state di session, bukan URL parameter)
- [ ] B2.2 ⚙️ Resume pending (`POST /register/guru/pending/resume`) — validasi identitas benar
- [ ] B2.3 ⚙️ Nominal tampil sesuai jenjang yang dipilih — update harga saat tarif berubah

### B3. QRIS & Nominal (QrisService)

- [ ] B3.1 🔒 Nominal di-inject benar — tidak bisa dimanipulasi dari client
- [ ] B3.2 ⚙️ QRIS payload valid untuk semua nominal (batas min/max)
- [ ] B3.3 ⚙️ `GOPAY_MASTER_PAYLOAD` kosong di env — graceful degradation
- [ ] B3.4 ⚙️ Perubahan tarif setelah guru melihat QR — konsistensi nominal saat approval

### B4. Upload Bukti Pembayaran

- [ ] B4.1 🔒 Hanya user pending ybs yang bisa upload ke transaksinya sendiri (IDOR check!)
- [ ] B4.2 🔒 Validasi file: tipe, ukuran, double extension
- [ ] B4.3 🗄️ Re-upload menimpa/membuat record baru — konsisten dengan status `submitted`
- [ ] B4.4 ⚙️ Upload gagal di tengah — tidak ada transaksi menggantung
- [ ] B4.5 ⚙️ Reference code unik dan tidak bisa ditebak (enumeration `/payments/{referenceCode}`)

### B5. Halaman Publik Pembayaran (`/payments/{referenceCode}`)

- [ ] B5.1 🔒 Tidak menampilkan data pribadi guru ke publik melebihi yang perlu
- [ ] B5.2 🔒 Reference code tidak bisa di-enumerate
- [ ] B5.3 ⚙️ Transaksi rejected/expired ditampilkan benar

### B6. Approval/Reject oleh Superadmin (PaymentApprovalService)

- [ ] B6.1 🔒 Hanya superadmin yang bisa approve/reject (middleware + policy)
- [ ] B6.2 🗄️ Approve 2x bersamaan (dua tab / dua admin) — tidak double aktivasi
- [ ] B6.3 🗄️ Status user + transaksi + role update atomic (transaction)
- [ ] B6.4 ⚙️ Reject dengan alasan — alasan tersimpan & sampai ke guru
- [ ] B6.5 ⚙️ Notifikasi WA ke guru setelah approve/reject — jika gateway mati?
- [ ] B6.6 🗄️ Guru re-register setelah reject — tidak bentrok data lama

---

## FASE 3 — F. Ujian Siswa (Flow Inti)

### F1. Login Token & Sesi

- [ ] F1.1 🗄️ Satu token = satu sesi aktif; reuse token ditangani
- [ ] F1.2 ⚙️ Ujian disabled/toggled off setelah siswa login — behavior?
- [ ] F1.3 🔒 Sesi ujian tidak bisa di-hijack (identitas sesi terkunci setelah isi identitas)

### F2. Identitas & Petunjuk

- [ ] F2.1 🔒 Validasi identitas server-side
- [ ] F2.2 ⚙️ Back/refresh saat isi identitas — tidak double-create sesi

### F3. Pengerjaan Ujian per Mapel (token mapel)

- [ ] F3.1 🗄️ ExamMapelToken single-use per mapel — token mapel tidak bisa dipakai ulang
- [ ] F3.2 ⚙️ Timer ujian — client-side timer vs server-side enforcement (refresh page = reset timer?)
- [ ] F3.3 ⚙️ Soal menjodohkan & PG — render benar, tidak ada jawaban bocor di HTML source
- [ ] F3.4 🔒 Jawaban benar (`is_benar`, `answer_key`) tidak terkirim ke client saat ujian berlangsung
- [ ] F3.5 ⚙️ Urutan soal konsisten per sesi (tidak berubah saat refresh)

### F4. Auto-save Jawaban (API `siswa/api/save-answer`)

- [ ] F4.1 🔒 API memvalidasi: sesi aktif + soal memang bagian ujian + jawaban valid
- [ ] F4.2 🗄️ Save answer bersamaan/berulang — tidak duplikat (unique constraint)
- [ ] F4.3 ⚙️ Network gagal — retry & feedback ke siswa
- [ ] F4.4 ⚙️ Jawaban menyodohkan (menjodohkan) — format kompleks tervalidasi
- [ ] F4.5 ⚡ Frekuensi auto-save — tidak membebani DB

### F5. Selesai Ujian & Skor

- [ ] F5.1 🗄️ Submit 2x (double click / race) — skor tidak double & status tetap benar
- [ ] F5.2 ⚙️ Perhitungan skor PG + menjodohkan — bobot benar, edge case jawaban kosong
- [ ] F5.3 🗄️ Waktu habis otomatis submit — siapa yang trigger? client/server
- [ ] F5.4 ⚙️ Sesi suspend/leave di tengah ujian — jawaban tersimpan dan bisa dilanjutkan?
- [ ] F5.5 🔒 Siswa tidak bisa submit setelah ujian selesai (replay)

### F6. Simulasi Guru via Token Ujian

- [ ] F6.1 ⚙️ Guru join simulasi — sesi simulasi tidak tercampur dengan hasil siswa asli
- [ ] F6.2 🔒 Guru hanya bisa simulasi ujian miliknya / paket jenjangnya

---

## FASE 4 — G. Latihan Materi Siswa

### G1. Token Latihan & Sesi

- [ ] G1.1 🗄️ Token latihan per materi — 1 token dipakai banyak siswa, sesi dipisah benar
- [ ] G1.2 ⚙️ Identitas: nama wajib, WA opsional — validasi format WA
- [ ] G1.3 🔒 Sesi siswa tidak bisa akses sesi siswa lain di token yang sama

### G2. Telaah Soal (retry, feedback)

- [ ] G2.1 🔒 Hanya 2 soal telaah aktif yang tampil; feedback tidak bocor sebelum submit
- [ ] G2.2 🗄️ Retry = update (bukan insert baru) — sesuai design
- [ ] G2.3 ⚙️ Pertanyaan telaah diganti superadmin — jawaban lama siswa masih konsisten?

### G3. Paket Latihan 1–3 (sekali submit)

- [ ] G3.1 🗄️ Enforce "sekali submit" di server (bukan cuma hide tombol di client)
- [ ] G3.2 🗄️ Snapshot per token — siswa semua lihat soal sama; regenerate → sesi lama tidak rusak
- [ ] G3.3 ⚙️ Submit bersamaan dua siswa di paket sama — tidak interferensi
- [ ] G3.4 ⚙️ Paket dengan soal < jumlah seharusnya (bank soal kurang) — graceful

### G4. Hasil & PDF (sisi guru)

- [ ] G4.1 🔒 PDF endpoint hanya role guru + jenjang/material sesuai
- [ ] G4.2 ⚙️ PDF render benar untuk soal PG & menjodohkan, KaTeX di PDF?
- [ ] G4.3 ⚡ Generate PDF banyak paket — tidak timeout

---

## FASE 5 — D. Operasional Guru

### D1. Dashboard

- [ ] D1.1 ⚡ Query ringkasan tidak N+1
- [ ] D1.2 ⚙️ Data sesuai jenjang guru (tidak lihat data lintas jenjang)

### D2. Profil & Password

- [ ] D2.1 🔒 Update profil validasi input (WA, satuan pendidikan, avatar upload)
- [ ] D2.2 🔒 Ubah password — password lama diverifikasi; logout sesi lain?
- [ ] D2.3 ⚙️ Avatar tersimpan & tampil benar (fallback ui-avatars)

### D3. Materi + Bookmark

- [ ] D3.1 ⚙️ List materi filter jenjang guru (middleware `guru.jenjang`)
- [ ] D3.2 🗄️ Bookmark/unbookmark idempotent (klik 2x tidak error)
- [ ] D3.3 🔒 Bookmark hanya untuk materi yang boleh diakses

### D4. Bank Soal Ujion (global) + Bookmark

- [ ] D4.1 🔒 Detail soal global — tidak membocorkan `answer_key`/`explanation` ke guru? (cek kebijakan bisnis)
- [ ] D4.2 ⚙️ Pagination & search di list besar

### D5. Bank Soal Pribadi (builder fullscreen)

- [ ] D5.1 🔒 Image upload di builder — validasi tipe/ukuran, serve dengan otorisasi
- [ ] D5.2 🗄️ Save builder batch — partial failure ditangani (transaction)
- [ ] D5.3 ⚙️ Soal import dari Ujion (source_global_question_id) — link konsisten saat soal global dihapus

### D6–D7. Paket Soal & Teks Bacaan (guru)

- [ ] D6.1 🔒 Guru hanya CRUD di paket jenjangnya (Gate `manage-mapel-soal`)
- [ ] D6.2 🗄️ Hapus soal/teks bacaan yang dipakai paket aktif — ditangani
- [ ] D6.3 ⚙️ ManagesSoalCrud trait — nomor soal duplikat ditolak (sudah ada, verifikasi)

### D8. Simulasi Ujian

- [ ] D8.1 ⚙️ Join via token — error handling token salah/kadaluarsa
- [ ] D8.2 ⚙️ Hasil simulasi tampil benar (overlap dengan F6)

### D9–D10. Hasil Ujian & Export

- [ ] D9.1 🔒 Guru hanya lihat hasil ujian siswa di paket/ujian jenjangnya
- [ ] D9.2 ⚙️ Export CSV/Excel — format konsisten, data lengkap
- [ ] D9.3 ⚡ Export ujian dengan banyak peserta — tidak memory blow up

### D11. Chat dengan Superadmin

- [ ] D11.1 🔒 Kirim gambar chat — validasi & otorisasi akses gambar (lihat H2)

---

## FASE 6 — E. Operasional Superadmin

### E1. Dashboard + Export/Print

- [ ] E1.1 ⚡ Statistik dashboard — query berat? N+1?
- [ ] E1.2 ⚙️ Export CSV & print — angka konsisten dengan tampilan

### E2. Keuangan & QR

- [ ] E2.1 🔒 Upload QR image per tarif — validasi file
- [ ] E2.2 ⚙️ Toggle active tarif — efek ke pending registration yang sedang berjalan
- [ ] E2.3 ⚙️ Ubah `QRIS_ADMIN_WHATSAPP` — fallback env vs DB benar

### E3–E4. Konfirmasi Pembayaran & Manajemen Guru

- [ ] E3.1 ⚙️ Approve/reject — lihat B6 (jangan dobel audit, verifikasi saja)
- [ ] E4.1 🔒 Refresh access token — token lama langsung mati?
- [ ] E4.2 ⚙️ Suspend guru yang sedang online — session di-invalidate?
- [ ] E4.3 🗄️ Activate guru tanpa pembayaran (edge case) — status konsisten

### E5–E6. Master Materi & Bank Soal Global

- [ ] E5.1 🔒 Import Excel — validasi baris rusak tidak setengah masuk (transaction)
- [ ] E5.2 ⚙️ Destroy-all material — materi yang dipakai paket latihan / bookmark guru
- [ ] E5.3 🗄️ Soft delete global questions & materials — relasi snapshot tidak rusak
- [ ] E6.1 ⚙️ Import PG & menjodohkan — format template konsisten dengan exporter

### E7–E9. Paket Soal, Soal & Ujian

- [ ] E7.1 🗄️ Hapus paket soal yang sudah dipakai exam/sesi — cascade benar
- [ ] E8.1 ⚙️ Bank builder — import dari bank soal, soal terhapus di tengah
- [ ] E9.1 ⚙️ Create exam + token mapel generate — token unik (TokenGenerator)
- [ ] E9.2 ⚙️ Toggle exam off saat siswa sedang mengerjakan
- [ ] E9.3 🗄️ Destroy exam — sesi & jawaban ikut/tergantung

### E10. Analisis Ujian

- [ ] E10.1 ⚙️ Angka analisis (rerata, distribusi) cocok dengan hasil manual
- [ ] E10.2 ⚡ Analisis ujian besar — performa

### E11. Config Latihan Materi

- [ ] E11.1 🗄️ Regenerate packages — sesi & jawaban lama tidak rusak (design snapshot)
- [ ] E11.2 ⚙️ Telaah question diganti — jawaban telaah lama siswa
- [ ] E11.3 ⚙️ Bank soal global per materi < 10/15 soal — paket latihan tidak gagal diam-diam

### E12. Audit Log

- [ ] E12.1 🔒 Filter/pagination audit log — tidak expose data sensitif
- [ ] E12.2 ⚡ Tabel audit_logs besar — index & pagination

### E13–E15. WhatsApp (Blast, Koneksi, Template)

- [ ] E13.1 🔒 Form blast — validasi target & pesan (injection ke WA template)
- [ ] E13.2 ⚙️ Blast dijadwalkan — queue worker mati saat jadwal, job tertunda ditangani
- [ ] E13.3 🗄️ Blast besar — job per pesan, random delay, tidak flood
- [ ] E14.1 ⚙️ Status koneksi gateway — error handling gateway down
- [ ] E15.1 ⚙️ Template CRUD + toggle — placeholder variabel tervalidasi

### E16. Landing Settings

- [ ] E16.1 🔒 Upload logo & hero mockup — validasi file
- [ ] E16.2 ⚙️ Toggle section — landing publik konsisten
- [ ] E16.3 ⚙️ CRUD FAQ — urutan tampil

### E17. Profil Superadmin

- [ ] E17.1 🔒 Ubah password — verifikasi password lama (sama seperti D2.2)

---

## FASE 7 — H. Chat Guru ↔ Superadmin

- [ ] H1.1 🔒 Endpoint chat hanya untuk 2 pihak terkait (guru tidak bisa kirim ke guru lain)
- [ ] H1.2 ⚙️ Kirim pesan kosong/terlalu panjang ditolak
- [ ] H2.1 🔒 **`ChatImageController` (kritikal):** gambar chat hanya bisa diakses pengirim/penerima — ini route shared guru & superadmin
- [ ] H3.1 ⚙️ Mark read — konsistensi badge/unread
- [ ] H4.1 🗄️ Destroy message / destroy-all — tidak merusak relasi chat lain
- [ ] H5.1 ⚙️ Realtime via Pusher — fallback jika Pusher tidak dikonfigurasi (env `log`)
- [ ] H5.2 ⚡ Polling/pagination history chat panjang

---

## FASE 8 — I. Integrasi WhatsApp

- [ ] I1.1 🔒 WhatsAppService — request ke gateway punya auth? (localhost:3000 terbuka?)
- [ ] I1.2 ⚙️ Gateway timeout/down — error ditangani, tidak 500 ke user
- [ ] I2.1 🗄️ Job SendWhatsAppBlast gagal — retry & log ke whatsapp_logs benar
- [ ] I2.2 ⚙️ Random delay — range delay masuk akal (rate limit WA)
- [ ] I3.1 🔒 Webhook `api/wa-webhook` — verifikasi signature/payload, tidak bisa dipalsukan
- [ ] I4.1 ⚙️ Template otomatis terkirim di momen yang benar (register, approve, reject)

---

## FASE 9 — A. Landing & Publik

- [ ] A1.1 ⚙️ Landing dynamic content — cache/rebuild saat settings berubah
- [ ] A1.2 ⚡ Landing load — asset size, lazy load gambar hero mockup
- [ ] A1.3 🔒 Landing click log API — tidak simpan data pribadi berlebihan
- [ ] A2.1 ⚙️ Sitemap & OG image — dynamic, tidak error saat data kosong
- [ ] A3.1 🎨 Responsive & konsistensi visual landing vs area app

---

## 5. Format Laporan Audit per Flow

Setiap fase selesai, hasil dituangkan dengan format konsisten:

```markdown
## Audit [KODE FLOW] — [Nama Flow]

### Ringkasan

[1-2 paragraf kondisi umum flow ini]

### Temuan

| #   | Severity | Aspek | Lokasi (file:line) | Deskripsi | Dampak | Rekomendasi |
| --- | -------- | ----- | ------------------ | --------- | ------ | ----------- |

### Yang Sudah Baik

[Hal yang sudah benar — agar tidak diubah saat refactor]

### Verifikasi

[Perintah test/manual untuk membuktikan fix bekerja]
```

Temuan semua flow diakumulasi di bagian **Rekap Temuan** (dibuat setelah audit berjalan) dengan sorting berdasarkan severity.

---

# LAPORAN AUDIT

## FASE 0 — J. Cross-cutting & Fondasi Keamanan

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** static code review seluruh route, middleware, policy, upload handler, dan konfigurasi.

### Ringkasan

Fondasi keamanan project ini **lebih baik dari ekspektasi aplikasi tahap development**: route superadmin & guru terlindungi middleware berlapis, policy dipakai luas (51 call `authorize()`), audit log tersanitasi rapi, validasi upload konsisten, dan race condition registrasi tertangani. Namun ditemukan **satu celah kritis** pada webhook WhatsApp yang memungkinkan pengambilalihan akun guru, plus sejumlah temuan tinggi (throttle admin login, file private di disk public, payment approval non-atomic).

### Temuan

| #     | Severity  | Aspek | Lokasi                                                                                                                                 | Deskripsi                                                                                                                                                                                                                                                                       | Dampak                                                                                                                                                |
| ----- | --------- | ----- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| F0-01 | 🔴 KRITIS | 🔒    | `app/Http/Controllers/Api/WebhookController.php:23-29, 115-124`                                                                        | Proteksi `WA_WEBHOOK_KEY` bersifat **opsional** — jika kosong (default), endpoint `POST /api/wa-webhook` terbuka penuh. Ataukirim `{"from": "<WA korban>", "message": "LUPA TOKEN"}` → sistem generate token baru dan **mengembalikan token tersebut di response** ke attacker. | **Account takeover total guru**: attacker login dengan WA korban + token baru.                                                                        |
| F0-02 | 🔴 KRITIS | 🔒    | `WebhookController.php:21-23`                                                                                                          | `env('WA_WEBHOOK_KEY')` dipanggil langsung di controller, bukan via `config()`. Saat production menjalankan `config:cache` (ada di deployment checklist!), `env()` selalu return null → **proteksi webhook pasti nonaktif di production** meski key sudah diisi.                | Memperparah F0-01 — celah tidak bisa ditutup tanpa refactor config.                                                                                   |
| F0-03 | 🟠 TINGGI | 🔒    | `routes/web.php:46`                                                                                                                    | `POST /ngadumin/login` **tanpa throttle**. Bandingkan: login guru punya `throttle:5,1`, login siswa `throttle:10,1`.                                                                                                                                                            | Brute force password superadmin tanpa batas.                                                                                                          |
| F0-04 | 🟠 TINGGI | ⚙️🔒  | `routes/guru.php:22`                                                                                                                   | `POST /register/guru` **tanpa throttle** (hanya check-wa/check-email yang di-throttle 30/menit).                                                                                                                                                                                | Spam akun pending + polusi DB; kombinasi dengan upload proof memicu spam WA ke admin.                                                                 |
| F0-05 | 🟠 TINGGI | 🔒    | `Guru/ChatController.php:52`, `Guru/PersonalQuestionController.php:71,288`, `Guru/ProfileController.php:64` + `public/storage` symlink | File "private" (gambar chat, gambar soal pribadi, avatar) disimpan di disk `public` → **langsung accessible via `/storage/chat-images/...` tanpa lewat controller**. Pengecekan participant di `ChatImageController` ter-bypass total.                                          | Gambar chat guru↔superadmin bisa dilihat siapa pun yang punya URL. Kontrol akses jadi security theater.                                               |
| F0-06 | 🟠 TINGGI | 🗄️    | `app/Services/PaymentApprovalService.php:12-76`                                                                                        | `approve()`/`reject()` **tanpa `DB::transaction`** dan **tanpa guard status** (bisa approve transaksi yang sudah success).                                                                                                                                                      | Race dua admin bersamaan → state user/transaksi inkonsisten; approve 2x meregenerasi token dan mengunci guru yang sedang login keluar.                |
| F0-07 | 🟠 TINGGI | 🔒    | `routes/web.php:95` vs `routes/guru.php:35`                                                                                            | Middleware `audit` **hanya terpasang di route superadmin**. Aksi destruktif guru (hapus soal, hapus teks bacaan, destroy soal builder) tidak tercatat di `audit_logs`.                                                                                                          | Tidak ada jejak audit saat guru menghapus data — merugikan investigasi insiden.                                                                       |
| F0-08 | 🟠 TINGGI | 🗄️🔒  | `Siswa/ExamController.php:205-220`                                                                                                     | `apiSaveAnswer` menerima `mapel_paket_id` dari request **tanpa divalidasi terhadap `$sesi->mapel_paket_id`**.                                                                                                                                                                   | Siswa bisa menyimpan jawaban untuk soal mapel lain (data pollution; skor tidak terpengaruh karena kalkulasi terikat mapel sesi).                      |
| F0-09 | 🟡 SEDANG | ⚙️🎨  | `Siswa/ExamController.php:254-280`                                                                                                     | `GET /siswa/selesai` **mutasi state** (submit ujian + hitung skor + hapus session) lewat request GET.                                                                                                                                                                           | Browser prefetch/link preview bisa men-submits ujian tanpa sengaja; refresh halaman selesai aman, tapi akses URL saat ujian berjalan memaksa selesai. |
| F0-10 | 🟡 SEDANG | 🎨    | `resources/views/errors/`                                                                                                              | Hanya ada 404, 500, 503. **Tidak ada 403 (Forbidden) dan 419 (CSRF expired)**.                                                                                                                                                                                                  | UX production buruk: user yang session timeout lihat error mentah saat submit form; 403 tampil polos tanpa panduan.                                   |
| F0-11 | 🟡 SEDANG | 🔒    | `.env.example`, `config/session.php:172`, AGENTS.md deployment checklist                                                               | `SESSION_SECURE_COOKIE` tidak diset di `.env.example` dan tidak ada di deployment checklist.                                                                                                                                                                                    | Cookie session dikirim via HTTP di production jika HTTPS tidak enforced di kode (hanya `URL::forceScheme` yang di-set).                               |
| F0-12 | 🟡 SEDANG | 🗄️    | `app/Support/TokenGenerator.php:10-13`                                                                                                 | `uniqueTeacherToken()` **tidak mengecek keunikan** (nama method menyesatkan).                                                                                                                                                                                                   | Probabilitas kolisi sangat rendah + unique index menangkal, tapi kolisi → QueryException 500 saat aktivasi.                                           |
| F0-13 | 🟡 SEDANG | ⚙️🗄️  | `Guru/ChatController.php:32`                                                                                                           | Validasi pesan chat `nullable                                                                                                                                                                                                                                                   | string` **tanpa max length**.                                                                                                                         | Teks multi-MB bisa disimpan per pesan → pembengkakan DB + berat render chat. |
| F0-14 | 🟡 SEDANG | ⚙️    | `AuditRequest.php:21` vs `config/ujion.php`                                                                                            | `config('ujion.audit_enabled', true)` merujuk key **yang tidak ada** di config. Selalu return default.                                                                                                                                                                          | Dead config — flag audit tidak bisa dimatikan (mungkin disengaja, tapi misleading).                                                                   |
| F0-15 | 🟡 SEDANG | 🔒    | `routes/web.php:41`                                                                                                                    | `POST /lupa-token` tanpa throttle.                                                                                                                                                                                                                                              | Low impact (hanya redirect ke WA), tapi bisa jadi vector spam.                                                                                        |
| F0-16 | 🟡 SEDANG | ⚡    | `Guru/MaterialController.php:20-22,124` dll                                                                                            | `Schema::hasColumn()` dipanggil per-request (query information_schema tiap kali halaman materi dibuka).                                                                                                                                                                         | Query schema berulang di halaman bertraffic; seharusnya tidak perlu karena skema migrasi sudah fixed.                                                 |
| F0-17 | 🟢 RENDAH | ⚙️    | `Siswa/ExamController.php:284-292`                                                                                                     | `getActiveSession()` tidak mengecek `exam.is_active`/`status` — siswa yang sedang mengerjakan tetap bisa lanjut jika ujian dimatikan admin.                                                                                                                                     | Keputusan bisnis yang wajar (jangan ganggu siswa yang sedang ujian), tapi perlu didokumentasikan eksplisit.                                           |
| F0-18 | 🟢 RENDAH | 🔒    | `AuthController.php:93`, `Siswa/AuthController.php:28`                                                                                 | Perbandingan token pakai `===` bukan `hash_equals` (timing-unsafe).                                                                                                                                                                                                             | Risiko praktis nyaris nol (token space besar + throttle), tapi best practice tidak terpenuhi.                                                         |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. **Proteksi route role**: superadmin (`auth` + `role:superadmin` + `audit` + `scopeBindings`) dan guru (`auth` + `role:guru` + `guru.active`, plus `guru.jenjang` untuk CRUD soal) — berlapis dan konsisten.
2. **Policy coverage**: 51 pemanggilan `authorize()` di controller guru & superadmin, termasuk `ownedQuestion()` guard di bank soal pribadi.
3. **`PaymentController::authorizeSessionAccess`**: halaman `/payments/{referenceCode}` tidak bisa di-enumerate publik — hanya superadmin atau pemilik transaksi (via session).
4. **AuditRequest sanitasi**: masking ID/UUID/token di path, masking IP (IPv4 & IPv6), UA disederhanakan + hash — desain privasi yang matang.
5. **Validasi upload konsisten**: `image|mimes|max` di 4 titik upload (bukti bayar, chat, builder, avatar).
6. **`saveBuilder` teliti**: `DB::transaction` + cek ownership + sanitasi path (anti traversal, anti URL injeksi) + cleanup file orphan.
7. **Session hygiene**: `regenerate()` setelah login, `invalidate()` + `regenerateToken()` saat logout, suspend → auto-logout via middleware.
8. **Timer ujian server-side**: `syncTimerStateForMapel` menghitung sisa waktu dari `started_at`, bukan dari client.
9. **`apiSaveAnswer` race-safe**: `updateOrCreate` + unique constraint `(ujian_sesi_id, soal_id)`.
10. **Registrasi race-safe**: duplikat email/WA tertangani `QueryException` catch + retry logic yang tepat.
11. **Throttle di endpoint utama**: login guru (5/menit), login siswa & latihan (10/menit), check-wa/email (30/menit), API publik (120/menit).
12. **Webhook key comparison** sudah pakai `hash_equals` (implementasinya benar — masalahnya di opsionalitas & `env()`).

### Rekomendasi Perbaikan — SEMUA FIX SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                                                                                                                             | Status            |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| P0        | **F0-01 + F0-02**: key webhook pindah ke `config/services.php` (`services.wa_webhook.key`), **fail-closed** (503 + log critical jika key kosong), `WA_WEBHOOK_KEY` + `WA_WEBHOOK_DEBUG` masuk `.env.example` & `.env` lokal                                                                                                                                                     | ✅                |
| P0        | **F0-03**: `throttle:5,1` di `POST /ngadumin/login`                                                                                                                                                                                                                                                                                                                             | ✅                |
| P1        | **F0-04**: `throttle:10,1` di `POST /register/guru`                                                                                                                                                                                                                                                                                                                             | ✅                |
| P1        | **F0-05**: chat image, gambar soal pribadi, dan **bukti pembayaran** pindah ke disk `local` (private). Bukti pembayaran kini diserve via route superadmin-only `superadmin/payment-proofs/{path}` (2 view diupdate). Model event `Chat` & `CleanupPaymentProofs` ikut disesuaikan                                                                                               | ✅                |
| P1        | **F0-06**: `PaymentApprovalService` dibungkus `DB::transaction` + `lockForUpdate` + guard double-approve/reject (return null/false → flash warning "sudah diproses" di 2 controller caller)                                                                                                                                                                                     | ✅                |
| P1        | **F0-07**: middleware `audit` ditambahkan ke group route guru                                                                                                                                                                                                                                                                                                                   | ✅                |
| P1        | **F0-08**: `apiSaveAnswer` validasi `mapel_paket_id` harus sama dengan `mapel_paket_id` sesi (403 jika beda)                                                                                                                                                                                                                                                                    | ✅                |
| P2        | **F0-09**: `GET /siswa/selesai` kini hanya auto-finalize jika timer habis; akses manual saat waktu tersisa → halaman konfirmasi (`ujian/konfirmasi-selesai`) dengan statistik dijawab/belum + sisa waktu. Submit via `POST /siswa/selesai` (modal UI eksisting kirim POST). Hasil tetap bisa dilihat setelah selesai (participant_token dipertahankan, key login-stage dihapus) | ✅                |
| P2        | **F0-10**: halaman error **403, 419, 429** custom dengan branding + tombol aksi kontekstual (419 = "Login Ulang"; 403 = "Ke Dashboard Saya" sesuai role; 429 = tombol tunggu 60 detik). `minimal.blade.php` kini support section `actions`                                                                                                                                      | ✅                |
| P2        | **F0-11**: `SESSION_SECURE_COOKIE` (komentar + penjelasan) di `.env.example`; checklist deployment AGENTS.md ditambah `SESSION_SECURE_COOKIE=true` + `WA_WEBHOOK_KEY`                                                                                                                                                                                                           | ✅                |
| P3        | **F0-12**: `TokenGenerator::uniqueTeacherToken()` kini loop cek keunikan di DB                                                                                                                                                                                                                                                                                                  | ✅                |
| P3        | **F0-13**: pesan chat `max:2000` di kedua ChatController                                                                                                                                                                                                                                                                                                                        | ✅                |
| P3        | **F0-14**: `audit_enabled` kini key config nyata (`UJION_AUDIT_ENABLED` env)                                                                                                                                                                                                                                                                                                    | ✅                |
| P3        | **F0-16**: `Schema::hasColumn` per-request dihapus di `Guru/MaterialController` (jalur tersering). Sisanya (±50 pemakaian di controller lain) dicatat sebagai tech-debt                                                                                                                                                                                                         | ✅ sebagian       |
| P3        | **F0-18**: perbandingan token guru login pakai `hash_equals`                                                                                                                                                                                                                                                                                                                    | ✅                |
| P3        | **F0-17**: keputusan bisnis "siswa yang sedang mengerjakan tetap bisa lanjut walau ujian dimatikan admin" — didokumentasikan di laporan ini sebagai intended behavior                                                                                                                                                                                                           | 📄 terdokumentasi |

**Perbaikan tambahan yang ditemukan saat eksekusi:**

- Migration prefix-index MySQL kini driver-aware (SQLite test suite sebelumnya rusak karena raw SQL MySQL).
- Extension `pdo_sqlite` + `sqlite3` diaktifkan di php.ini Laragon (test suite sebelumnya tidak bisa jalan).
- Script `composer pint` ditambahkan ke composer.json (AGENTS.md sudah mereferensikannya tapi belum ada).
- Test upload pembayaran + builder image diupdate ke disk lokal; test WA dispatch dibuat hermetic dengan `Queue::fake()`.
- Fixture `SiswaExamSessionTest` dilengkapi `mapel_paket_id` (sebelumnya tidak mencerminkan flow produksi).

### Regresi Test (5 test baru, semua PASS)

`tests/Feature/SecurityHardeningTest.php`:

1. Webhook menolak request saat key kosong (503)
2. Webhook menolak key salah (401)
3. Webhook tanpa key valid **tidak** me-reset token guru
4. Siswa tidak bisa menyimpan jawaban untuk soal mapel lain (403)
5. Login superadmin throttled setelah 5 percobaan gagal (429)

**Status akhir: 86 test PASS (314 assertions), 215 route terdaftar, Pint bersih.**

---

## FASE 1 — C. Auth Semua Role

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** static review controller auth (guru/superadmin/siswa/practice), model token, config session, view login, migration FK latihan.

### Ringkasan

Fondasi auth sudah solid setelah fix Fase 0 (throttle, hash_equals, fail-closed webhook). Namun ditemukan **dua temuan tinggi yang berdampak ke integritas ujian**: fitur "resume sesi via nomor WA" memungkinkan pengambilalihan sesi siswa lain hanya dengan mengetahui nomor WA mereka, dan fitur regenerate paket latihan diam-diam menghapus seluruh hasil pengerjaan siswa karena foreign key cascade.

### Temuan

| #     | Severity  | Aspek | Lokasi                                                                                | Deskripsi                                                                                                                                                                                                                                                                                             | Dampak                                                                                                                      |
| ----- | --------- | ----- | ------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| F1-01 | 🟠 TINGGI | 🔒    | `Siswa/ExamController.php:47-57`, `Siswa/MaterialPracticeController.php:43-54`        | **Resume sesi hanya berdasarkan nomor WA.** Token ujian/latihan bersifat kelas (shared), dan resume tidak memverifikasi apa pun selain WA. Siapa pun yang tahu token kelas + nomor WA temannya bisa melanjutkan sesi korban: melihat jawaban, mengubah, dan mensubmit ujian/latihan atas nama korban. | Impersonasi siswa dalam ujian sungguhan — merusak integritas penilaian.                                                     |
| F1-02 | 🟠 TINGGI | 🗄️    | `MaterialPracticeToken.php:72-117` + migration `mpr_attempt_pkg_fk` (cascadeOnDelete) | **Regenerate paket menghapus semua hasil siswa.** `regeneratePackages()` menghapus packages lama; FK `material_practice_package_attempts.package_id` bersifat `cascadeOnDelete` → seluruh attempt + jawaban + skor siswa terhapus tanpa peringatan saat superadmin klik "regenerate".                 | Kehilangan data hasil latihan siswa secara massal & permanen; analisis guru & PDF ikut kosong.                              |
| F1-03 | 🟡 SEDANG | 🔒    | `Siswa/AuthController.php:43-49`, `Siswa/MaterialPracticeAuthController.php:37-42`    | Tidak ada `session()->regenerate()` setelah validasi token sukses (guru & superadmin sudah regenerate; siswa belum).                                                                                                                                                                                  | Session fixation teoretis (risiko rendah karena auth berbasis token kelas, tapi trivial untuk ditutup).                     |
| F1-04 | 🟡 SEDANG | ⚙️    | `Siswa/ExamController.php@mulai`, `Superadmin/ExamController.php`                     | **`max_peserta` tidak pernah di-enforce** — hanya field metadata saat create/import exam. Pembuatan sesi tidak memeriksa jumlah peserta.                                                                                                                                                              | Fitur mati (dead feature): admin mengisi nilai yang tidak berefek apa pun — menyesatkan operator ujian.                     |
| F1-05 | 🟡 SEDANG | 🎨    | `README.md`, `AGENTS.md`, `jalan-local.md` vs `AuthController@login`                  | Dokumentasi bilang login guru = "nama + access token", padahal implementasi = **nomor WA + token**.                                                                                                                                                                                                   | Guru kebingungan saat login (input nama tidak ada di form — form sudah benar, docs yang salah).                             |
| F1-06 | 🟡 SEDANG | 🔒    | `Siswa/ExamController.php@finalizeSession` (implikasi fix F0-09)                      | `participant_token` & `material_practice_session_token` tetap tinggal di session setelah ujian selesai (trade-off agar halaman hasil bisa di-refresh). Di perangkat bersama, pengguna berikutnya yang buka `/siswa/selesai` melihat nama + skor siswa sebelumnya.                                     | Kebocoran data ringan di perangkat bersama (warnet/lab).                                                                    |
| F1-07 | 🟢 RENDAH | 🔒    | `AuthController.php:88-91`                                                            | `whereIn('no_wa', variants)->first()` memilih user arbitrer jika ada 2 akun dengan nomor varian berbeda dari angka yang sama (unique index hanya mencegah duplikat string identik).                                                                                                                   | Edge case: login ke akun salah jika data duplikat varian lolos (registrasi sudah mencegah, tapi data lama/race bisa lolos). |
| F1-08 | 🟢 RENDAH | ⚙️    | `routes/web.php:64-91`                                                                | Dua set route login latihan identik (`/siswa/latihan/*` dan `/materi/*`) menunjuk controller sama.                                                                                                                                                                                                    | Redundansi kecil — rawan perbedaan perlakuan middleware di masa depan.                                                      |
| F1-09 | 🟢 RENDAH | 🔒    | `routes/web.php` (`POST /logout`)                                                     | Logout tanpa throttle & tanpa proteksi khusus — logout-CSRF (attacker bisa memaksa logout user via halaman pihak ketiga).                                                                                                                                                                             | Gangguan ringan; standar Laravel juga begini.                                                                               |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. Throttle semua endpoint login (guru 5/mnt, admin 5/mnt, siswa 10/mnt, registrasi 10/mnt) — hasil Fase 0.
2. `access_token` di `$hidden` model User — tidak bocor via serialization.
3. Perbandingan token pakai `hash_equals` (timing-safe).
4. Akun `pending`/`suspend` ditolak login dengan pesan jelas; middleware `guru.active` auto-logout + invalidate session.
5. Session `regenerate()` setelah login guru & superadmin (anti fixation).
6. Logout: `invalidate()` + `regenerateToken()`.
7. Lupa token tanpa enumeration (semua hanya generate link WA admin).
8. Login siswa: validasi token + cek `exam.is_active` + `status=terbit` sekaligus.
9. Desain token kelas (shared) + sesi per-siswa adalah arsitektur yang tepat untuk ujian massal.
10. Hint UX form token siswa jelas (8 karakter, uppercase otomatis, filter input).
11. Token generator unik dengan loop + cek DB (exam & practice).
12. Cookie: `http_only=true`, `same_site=lax`, `SESSION_ENCRYPT=true`.

### Rekomendasi Perbaikan — SEMUA FIX P1/P2/P3 UTAMA SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                                                                                                                                                     | Status      |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| P1        | **F1-01**: resume sesi ujian & latihan kini wajib **WA + nama cocok** (normalisasi nama + gelar via class baru `App\Support\NameMatcher`, dipakai ulang oleh resume pending registrasi — deduplikasi logika). Nama tidak cocok → sesi baru dibuat, bukan resume                                                                                                                                         | ✅          |
| P1        | **F1-02 (arsip)**: FK `material_practice_package_attempts.package_id` diubah `cascadeOnDelete` → `nullOnDelete` + kolom snapshot `paket_no` (backfill via migration `2026_08_24_000001_archive_material_practice_attempts.php`). Regenerate paket kini **mengarsipkan** attempt; view hasil prefer snapshot; tombol PDF disabled untuk arsip; cek "selesai 3 paket" hanya hitung attempt generasi aktif | ✅          |
| P2        | **F1-04**: `max_peserta` di-enforce di `mulai()` — kuota penuh → "Kuota peserta ujian ini sudah penuh"; resume sesi sendiri tetap diizinkan                                                                                                                                                                                                                                                             | ✅          |
| P2        | **F1-03**: `session()->regenerate()` di `validateToken` siswa ujian & latihan                                                                                                                                                                                                                                                                                                                           | ✅          |
| P2        | **F1-06**: state sesi lama di-clear saat siswa login dengan token baru                                                                                                                                                                                                                                                                                                                                  | ✅          |
| P2        | **F1-05**: README, AGENTS.md, jalan-local.md dikoreksi — login guru = **nomor WhatsApp + token**                                                                                                                                                                                                                                                                                                        | ✅          |
| P3        | **F1-07**: login guru memprioritaskan akun `active` saat match varian WA                                                                                                                                                                                                                                                                                                                                | ✅          |
| P3        | **F1-08**: dua set route latihan dibiarkan sebagai alias — tech-debt                                                                                                                                                                                                                                                                                                                                    | 📄 diterima |
| P3        | **F1-09**: logout sudah POST form di UI — risiko diterima                                                                                                                                                                                                                                                                                                                                               | 📄 diterima |

### Regresi Test (5 test baru di `tests/Feature/AuthFlowHardeningTest.php`, semua PASS)

1. Resume ujian dengan nama beda → sesi BARU (bukan hijack)
2. Resume dengan nama sama + gelar → resume berhasil
3. Kuota penuh → sesi baru ditolak
4. Kuota penuh → resume sendiri tetap diizinkan
5. Regenerate paket → attempt terARSIP, paket_no & skor utuh

**Status akhir Fase 1: 91 test PASS (335 assertions), Pint bersih.**

### Verifikasi (setelah fix diimplementasikan)

```bash
# F0-01/02: webhook tanpa key harus ditolak
php artisan config:cache && curl -X POST http://localhost:8000/api/wa-webhook -d '{"from":"628123456789","message":"LUPA TOKEN"}' # expect 500/401, bukan token baru

# F0-03: admin login brute force
# 6x POST /ngadumin/login dengan password salah → expect 429 pada attempt ke-6

# Test suite regression
php artisan test
```

---

## FASE 2 — B. Registrasi Guru & Payment Flow

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** review RegisterGuruController, PaymentController, QrisService, PhoneNumber, PricingPlanController, view registrasi/pending/payments + verifikasi fix F0 (IDOR, approval atomic).

### Ringkasan

Fondasi flow pembayaran sudah kuat setelah Fase 0 (IDOR tertutup, approval atomic, race registrasi tertangani, reference code tidak bisa ditebak). Namun ditemukan **dua temuan tinggi yang berdampak finansial**: fallback tarif lintas jenjang (guru bisa ditagih nominal jenjang yang salah) dan QRIS crash saat master payload belum dikonfigurasi.

### Temuan

| #     | Severity  | Aspek | Lokasi                                                                                                        | Deskripsi                                                                                                                                                                                                                                                      | Dampak                                                                                                     |
| ----- | --------- | ----- | ------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| F2-01 | 🟠 TINGGI | 🗄️    | `RegisterGuruController@resolvePlanForJenjang` (dipakai showPending, createPayment, paymentData, uploadProof) | **Fallback tarif lintas jenjang.** Jika tarif untuk jenjang guru (mis. SMA) belum dibuat admin, sistem fallback ke tarif aktif pertama di tabel — bisa jadi tarif SD/SMP. Guru SMA ditagih nominal jenjang lain.                                               | Tagihan salah nominal; kebingungan verifikasi admin; risiko klaim pembayaran berlebih/kurang.              |
| F2-02 | 🟠 TINGGI | ⚙️    | `PaymentController.php:26`, `RegisterGuruController@paymentData`, `PricingPlanController@printLabel`          | **QRIS crash tanpa graceful degradation.** `generateFixedAmountPayload()` melempar `RuntimeException` jika `GOPAY_MASTER_PAYLOAD` kosong — tidak ada try/catch di ketiga pemanggil → error 500 mentah.                                                         | Setup baru/pindah server lupa isi env → flow pembayaran mati total dengan error teknis, bukan pesan jelas. |
| F2-03 | 🟡 SEDANG | 🗄️    | `PricingPlanController@store/update`                                                                          | **Validasi harga longgar**: `price` hanya `required                                                                                                                                                                                                            | string                                                                                                     | max:40`— tanpa cek numeric, tanpa minimum. Input "gratis", "0", atau teks acak tersimpan;`sanitizeAmount` lalu mengubahnya jadi 0 → transaksi Rp 0. | Transaksi bernilai 0 lewat ke approval; admin harus menebak maksudnya. |
| F2-04 | 🟡 SEDANG | 🗄️    | `RegisterGuruController@uploadPaymentProof`                                                                   | Update `$transaction` dan `$teacher` **tanpa `DB::transaction`**. Gagal di tengah → status transaksi PENDING+proof tapi user masih AWAITING (atau sebaliknya). Edge tambahan: plan kosong → `payment_status=SUBMITTED` **tanpa transaksi** — admin lihat aneh. | State inkonsisten user vs transaksi pada kegagalan langka; kasus tanpa-transaksi membingungkan verifikasi. |
| F2-05 | 🟡 SEDANG | 🗄️    | `createPayment`/`paymentData` lookup `where('amount', $planAmount)`                                           | **Transaksi pending menumpuk.** Lookup reuse hanya untuk amount yang sama persis. Tarif berubah → transaksi pending lama tak pernah dipakai/dibatalkan, transaksi baru dibuat setiap kali harga berubah.                                                       | Tabel transaksi berisi pending yatim; laporan keuangan berisik.                                            |
| F2-06 | 🟡 SEDANG | 🎨    | `resources/views/payments/show.blade.php`                                                                     | Halaman publik pembayaran **selalu merender QR** meski transaksi `FAILED` (perlu kirim ulang) atau `SUCCESS` — tanpa penanganan khusus per status.                                                                                                             | Guru dengan transaksi rejected bisa scan QR lagi & bayar lagi padahal harus upload ulang bukti.            |
| F2-07 | 🟢 RENDAH | 🎨    | `RegisterGuruController@showPending`                                                                          | `latestTransaction` diambil tanpa filter status → "Referensi Terakhir" bisa menampilkan transaksi FAILED.                                                                                                                                                      | Kebingungan kecil guru.                                                                                    |
| F2-08 | 🟢 RENDAH | 🔒    | `check-email`/`check-wa`                                                                                      | Endpoint publik konfirmasi keberadaan email/WA (enumeration possible) — sudah throttled 30/menit.                                                                                                                                                              | Trade-off UX standar form registrasi; **diterima dengan catatan**.                                         |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. **IDOR upload bukti tertutup** (fix F0): akses via `pending_registration` session, bukan parameter.
2. **Approval atomic** (fix F0): `DB::transaction` + `lockForUpdate` + guard double-process.
3. **Race double-registrasi**: unique index + `QueryException` catch + redirect ke pending yang benar (dengan test).
4. **Reference code tidak bisa ditebak**: `UJN-yymmdd-` + 8 random chars, loop cek unik.
5. **Halaman pembayaran**: `authorizeSessionAccess` + `noindex,nofollow` + throttled.
6. **Validasi upload bukti**: image + mimes + 4MB, re-scale 1600px + EXIF auto-rotate via `PaymentProofStorage`, disimpan private (fix F0).
7. **Countdown honest**: setelah habis tampil "Pembayaran tetap bisa dilanjutkan" — bukan dark pattern.
8. **Normalisasi WA konsisten** via `PhoneNumber::variants()` di semua titik (registrasi, login, webhook).
9. **Notifikasi WA async** via queue + template terpusan (`GuruNotificationTemplates`).
10. **Nominal live** di halaman pending: `resolvePlanForJenjang` di-load tiap request.

### Rekomendasi Perbaikan — SEMUA FIX SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                              | Status      |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| P1        | **F2-01**: `resolvePlanForJenjang` kini: plan jenjang → **plan global (jenjang NULL)** → tolak. Semua fallback bebas (`PricingPlan::where('is_active')->first()`) di 6 caller dihapus. Guru SMA tidak akan pernah ditagih tarif SD/SMP                                           | ✅          |
| P1        | **F2-02**: generate QRIS dibungkus try/catch di 3 titik — `payments/show` tampil kartu "QR belum tersedia, hubungi admin", `paymentData` return JSON 503 dengan pesan ramah, `printLabel` abort 503 dengan instruksi ke admin                                                    | ✅          |
| P2        | **F2-03**: validasi `price` → `required\|numeric\|min:1000\|max:100000000` dengan pesan error Indonesia di store & update                                                                                                                                                        | ✅          |
| P2        | **F2-04**: update proof dibungkus `DB::transaction`; plan kosong → upload DITOLAK dengan flash "Tarif jenjang belum tersedia" (tidak ada lagi status SUBMITTED tanpa transaksi)                                                                                                  | ✅          |
| P2        | **F2-05**: helper baru `resolveOrCreatePendingTransaction()` (DB::transaction + lockForUpdate): reuse transaksi pending nominal sama; transaksi pending nominal beda otomatis dibatalkan (`failed` + alasan "Dibatalkan otomatis karena tarif berubah") sebelum create yang baru | ✅          |
| P2        | **F2-06**: `payments/show` render per status: SUCCESS → kartu hijau "Sudah Diverifikasi" + tombol login; FAILED → kartu merah "Perlu Dikirim Ulang" + catatan admin (QR tidak dirender); PENDING → QR seperti biasa                                                              | ✅          |
| P3        | **F2-07**: `latestTransaction` difilter ke status PENDING/SUCCESS                                                                                                                                                                                                                | ✅          |
| P3        | **F2-08**: diterima (sudah throttled 30/menit)                                                                                                                                                                                                                                   | 📄 diterima |

**Catatan implementasi:**

- 2 test lama diupdate dengan fixture `PricingPlan` (perilaku "tolak upload tanpa tarif" memang baru).
- `resolveOrCreatePendingTransaction` memakai `lockForUpdate` agar race dua request tidak menghasilkan dobel transaksi.

### Regresi Test (5 test baru di `tests/Feature/PricingPlanFallbackTest.php`, semua PASS)

1. Guru SMA tanpa tarif SMA → **tidak** ditagih tarif SD (ditolak dengan flash)
2. Guru SMA dengan tarif SMA → ditagih tarif SMA yang benar
3. Guru SMP tanpa tarif SMP tapi ada plan global → fallback ke plan global
4. Upload proof tanpa plan tersedia → ditolak, status tetap AWAITING
5. Tarif berubah → transaksi pending lama otomatis dibatalkan + transaksi baru nominal baru

**Status akhir Fase 2: 96 test PASS (351 assertions), Pint bersih.**

---

## FASE 3 — F. Ujian Siswa (Flow Inti)

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** review ExamController siswa (showUjian/apiSaveAnswer/finalize/skor), Guru\ExamController (simulasi), Guru\ExamResultController (hasil), view pengerjaan JS (auto-save, render PG/menjodohkan, timer).

### Ringkasan

Fondasi ujian sudah matang: timer di-enforce server-side, jawaban PG tidak bocor di payload, save-answer race-safe, resume aman (fix F1). Namun ditemukan **satu celah kritis integritas ujian**: jawaban benar soal menjodohkan **tersirat langsung di payload** yang dikirim ke browser — siswa yang membuka DevTools bisa tahu kunci jawaban tanpa mengerjakan. Ditambah dua temuan tinggi: auto-save yang gagal senyap (siswa tidak tahu jawabannya hilang) dan skor simulasi guru yang tercampur ke hasil siswa.

### Temuan

| #     | Severity  | Aspek | Lokasi                                                                           | Deskripsi                                                                                                                                                                                                                                                                                                                                                                                                              | Dampak                                                                                                                         |
| ----- | --------- | ----- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| F3-01 | 🔴 KRITIS | 🔒    | `Siswa/ExamController@showUjian` payload + `pengerjaan.blade.php` renderMatching | **Kunci jawaban menjodohkan bocor di payload.** Payload mengirim `pasangan: [{id, teks_kiri, teks_kanan}]` (baris soal) dan `matching_options: [{id, label}]` (opsi jawaban) — keduanya memuat `pair.id` mentah. Skor benar = `match_id === pair_id` (pasangan dengan id sama). Siswa yang membuka DevTools → Network → payload JSON tinggal mencocokkan baris dengan opsi **ber-ID sama** → skor 100% tanpa berpikir. | Kecurangan menjodohkan total — bisa dilakukan siswa mana pun yang paham DevTools.                                              |
| F3-02 | 🟠 TINGGI | ⚙️🎨  | `pengerjaan.blade.php:292-328` (`postAnswer`)                                    | **Auto-save gagal senyap.** `fetch(...).catch(() => {})` — error network ditelan tanpa retry, tanpa indikator. Indikator soal langsung hijau (optimistic) padahal jawaban belum sampai server. Auto-save 30 detik juga hanya mengirim soal aktif.                                                                                                                                                                      | Siswa yakin jawaban tersimpan padahal hilang saat koneksi lab/warnet drop — kehilangan jawaban di ujian sungguhan tanpa sadar. |
| F3-03 | 🟠 TINGGI | 🗄️    | `Guru/ExamResultController@mapel/export/show` + `Guru/ExamController@join`       | **Skor simulasi guru tercampur hasil siswa.** Sesi simulasi guru disimpan dengan `user_id` terisi, tapi query hasil siswa (`mapel()`, `export()`, heatmap, `withCount('ujianSesis')`) tidak memfilter `user_id` → skor simulasi guru ikut ranking, rata-rata, dan CSV export siswa.                                                                                                                                    | Analisis & export terkontaminasi; ranking siswa tidak valid.                                                                   |
| F3-04 | 🟡 SEDANG | 🗄️    | `Siswa/ExamController@submitSelesai`/`finalizeSession`                           | **Submit double tanpa lock.** Dua POST bersamaan (double-click atau dua tab) → keduanya lolos cek `status !== selesai` dan sama-sama menjalankan finalize (hitung skor + update). Nilai idempotent tapi tetap race; WA notifikasi (jika ditambahkan nanti) bisa dobel.                                                                                                                                                 | Race minor hari ini, landmine untuk fitur masa depan.                                                                          |
| F3-05 | 🟡 SEDANG | ⚙️    | `Guru/ExamResultController@mapel` heatmap                                        | **Analisis soal menjodohkan selalu 0%.** Kode heatmap `return false` untuk tipe menjodohkan (berkomentar "Simplified for now") — statistik benar per soal tidak pernah dihitung untuk menjodohkan.                                                                                                                                                                                                                     | Guru melihat heatmap menyesatkan untuk soal menjodohkan.                                                                       |
| F3-06 | 🟡 SEDANG | 🎨    | `pengerjaan.blade.php` renderMatching                                            | `matching_options` di-`shuffle()` per request — refresh halaman mengubah urutan opsi. Jawaban tetap valid (berdasar id), tapi posisi opsi "melompat" saat refresh bisa membingungkan siswa yang sedang memeriksa.                                                                                                                                                                                                      | Minor UX; terkait erat dengan fix F3-01.                                                                                       |
| F3-07 | 🟢 RENDAH | ⚙️    | `Siswa/ExamController@showUjian`                                                 | Timer mulai saat `waktu_mulai` diset (status mengerjakan) — siswa bisa buka petunjuk berlama-lama tanpa timer jalan. By design (petunjuk ≠ mengerjakan), tapi tidak didokumentasikan.                                                                                                                                                                                                                                  | Intended behavior — dokumentasikan.                                                                                            |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. **Timer server-side**: `syncTimerStateForMapel` menghitung sisa waktu dari `started_at` di server; client hanya display; save-answer menolak 409 saat expired dan mengembalikan `redirect_url` — siswa tidak bisa curang tambah waktu via client.
2. **Jawaban PG tidak bocor**: payload pilihan hanya `kode/teks/gambar_url` — `is_benar` tidak pernah dikirim.
3. **Save-answer race-safe**: `updateOrCreate` + unique constraint `(ujian_sesi_id, soal_id)` (fix F0-08 termasuk validasi mapel sesi).
4. **Validasi menyodohkan di server**: `jawaban_menjodohkan.*.pair_id/match_id` divalidasi integer + soal harus milik mapel sesi.
5. **Submit setelah selesai (replay) ditolak**: `apiSaveAnswer` 401 saat status selesai; `selesai()` idempotent.
6. **Timer habis auto-submit**: client redirect ke `selesai` → server cek remaining ≤ 0 → finalize. Tidak ada jalur submit paksa saat waktu tersisa tanpa konfirmasi (fix F0-09).
7. **Simulasi guru**: guard jenjang 403, resume sesi simulasi sendiri, hasil hanya milik user ybs (`sessionQueryForUser`).
8. **Urutan soal konsisten**: `orderBy('nomor_soal')` — refresh tidak mengacak.
9. **Sanitasi XSS di client**: `sanitizeRichText` whitelist tag + strip atribut; `escapeHtml` untuk teks opsi.
10. **Modal konfirmasi selesai** menampilkan ringkasan dijawab/ragu/belum — UX matang.

### Rekomendasi Perbaikan — SEMUA FIX SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            | Status |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| P0        | **F3-01**: class baru `App\Support\MatchingKey` — baris soal & opsi menjodohkan dikirim sebagai key opaque (`sha1` 16-char dengan salt beda per role + seed `session_token`). Payload tidak lagi memuat `pair.id` mentah maupun `teks_kanan` di baris soal; jawaban tersimpan dikonversi balik ke key saat render. `apiSaveAnswer` menerima `{row_key, opt_key}`, resolve ke pair id via lookup, tolak 422 untuk key tak dikenal, tetap simpan `pair_id/match_id` di DB (skema tidak berubah). Kesamaan string antar baris-opsi tidak lagi membocorkan jawaban | ✅     |
| P1        | **F3-02**: badge status simpan di header ("Tersimpan" hijau / "Menyimpan..." / "Koneksi terputus — mencoba lagi..." merah) + **auto-retry** dengan backoff eksponensial (3s→30s) untuk jawaban gagal; guard `beforeunload` saat ada jawaban belum tersimpan; tombol "Ya, Selesai" flush pending dulu & blokir dengan peringatan jika masih gagal; auto-save 30 detik hanya kirim soal yang berisi jawaban                                                                                                                                                      | ✅     |
| P1        | **F3-03**: hasil siswa kini murni — `mapel()`, `export()`, `show()`, `withCount` semuanya difilter `whereNull('user_id')`. Sesi simulasi guru (user_id terisi) tidak pernah masuk ranking/rata-rata/CSV siswa. Aturan ini didokumentasikan di AGENTS.md                                                                                                                                                                                                                                                                                                        | ✅     |
| P2        | **F3-04**: `finalizeSession` dibungkus `DB::transaction` + `lockForUpdate` + re-check status di dalam lock — submit double/thread race aman                                                                                                                                                                                                                                                                                                                                                                                                                    | ✅     |
| P2        | **F3-05**: heatmap menjodohkan kini dihitung (soal dianggap benar jika semua pasangan tepat) — sebelumnya selalu 0%                                                                                                                                                                                                                                                                                                                                                                                                                                            | ✅     |
| P3        | **F3-06**: urutan opsi menjodohkan distabilkan per sesi (sort by `sha1('shuffle:'.pairId.seed)`) — refresh tidak mengacak posisi lagi                                                                                                                                                                                                                                                                                                                                                                                                                          | ✅     |
| P3        | **F3-07**: intended behavior timer didokumentasikan di AGENTS.md ("timer mulai saat pertama membuka halaman pengerjaan")                                                                                                                                                                                                                                                                                                                                                                                                                                       | ✅     |

**Catatan implementasi:**

- Guru simulasi melewati jalur render yang sama → key opaque otomatis berlaku juga untuk simulasi.
- Jawaban menjodohkan lama (format pair_id dari sesi sebelum deploy) tidak bisa di-render ulang ke key — dikonversi saat payload dibangun, jadi tetap kompatibel (dev-stage, tidak ada data produksi).

### Regresi Test (4 test baru di `tests/Feature/MatchingAnswerSecurityTest.php`, semua PASS)

1. Payload ujian TIDAK memuat id pasangan mentah / teks_kanan di baris — hanya key opaque, row key & opt key tidak overlap
2. Save menjodohkan dengan key valid → tersimpan benar sebagai pair_id/match_id (pasangan benar = skor penuh)
3. Save dengan key asing/bogus → 422, tidak ada jawaban tersimpan
4. Sesi simulasi guru (user_id terisi) TIDAK muncul di halaman hasil mapel siswa

**Status akhir Fase 3: 100 test PASS (388 assertions), Pint bersih.**

---

## FASE 4 — G. Latihan Materi Siswa

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** review MaterialPracticeController (mulai/telaah/paket/submit), MaterialPracticeAuthController, view dashboard & paket latihan, MaterialPracticeResultController (statistik telaah), model + migration.

### Ringkasan

Berbeda dengan ujian (F3), **tidak ada kebocoran kunci jawaban di latihan** — radio paket latihan bernilai teks opsi (bukan indeks/ID), penilaian dihitung server-side saat submit, dan pembahasan telaah hanya tampil setelah siswa menjawab. "Sekali submit" juga sudah di-enforce server-side. Masalah utama justru di **UX dan race**: pilihan jawaban paket latihan yang belum disubmit hilang total saat refresh, dan ada dua race condition kecil di pembuatan attempt & submit.

### Temuan

| #     | Severity  | Aspek | Lokasi                                                          | Deskripsi                                                                                                                                                                                                                                                                            | Dampak                                                                           |
| ----- | --------- | ----- | --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| F4-01 | 🟠 TINGGI | ⚙️🎨  | `showPaket` + `paket.blade.php`                                 | **Draft jawaban tidak tersimpan.** Pilihan radio hanya hidup di HTML — tidak ada auto-save ke server (attempt answers hanya dibuat saat submit) dan tidak ada draft lokal. Siswa kerjakan 10 soal → refresh/tak sengaja back → **semua pilihan hilang**, mulai dari nol.             | Frustrasi nyata siswa; untuk latihan 10–15 soal ini kehilangan kerja signifikan. |
| F4-02 | 🟠 TINGGI | 🗄️    | `showPaket` (pembuatan attempt)                                 | **Race GET dobel → 500.** Dua request bersamaan (double-click "Kerjakan", dua tab) → keduanya `first()` null → keduanya `create` → unique constraint `(session_id, package_id)` → salah satu kena `QueryException` tak tertangani.                                                   | Error 500 sesekali; siswa harus refresh manual.                                  |
| F4-03 | 🟡 SEDANG | 🗄️    | `submitPaket`                                                   | **Race submit dobel.** Dua POST bersamaan (double-click "Ya, Kumpulkan" atau dua tab) → keduanya lolos cek `status === selesai` → skor dihitung & disimpan dua kali dari payload yang berbeda — skor akhir tidak deterministik.                                                      | Skor bisa tercatat dari payload yang tidak lengkap.                              |
| F4-04 | 🟡 SEDANG | 🗄️    | `MaterialPracticeResultController@show` telaahStats             | **Statistik telaah menghitung jawaban orphan.** Saat admin mengganti soal telaah, jawaban siswa lama tetap ada di `material_telaah_answers` → `telaahStats` group by `global_question_id` TANPA filter ke soal telaah aktif → soal yang sudah diganti tetap muncul di analisis guru. | Analisis telaah menampilkan soal yang bukan lagi bagian latihan.                 |
| F4-05 | 🟡 SEDANG | ⚙️    | `submitTelaah` (`max:255`) + `submitPaket` (tanpa max)          | **Validasi panjang jawaban vs kolom DB.** Kolom `jawaban` VARCHAR(191), tapi validasi telaah max:255 & paket tanpa max — teks opsi panjang (>255) gagal validasi, antara 192–255 lolos validasi lalu **QueryException** saat insert.                                                 | Siswa tidak bisa menjawab soal yang opsinya panjang; atau error 500.             |
| F4-06 | 🟢 RENDAH | 🎨    | `dashboard.blade.php` badge `{{ $telaahQuestions->count() }}/2` | Hardcoded "/2" — kalau admin menyetel >2 soal telaah, badge salah.                                                                                                                                                                                                                   | Kosmetik.                                                                        |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. **Tidak ada kebocoran kunci jawaban**: radio value = teks opsi (bukan indeks), `answer_key`/`explanation` tidak pernah dirender sebelum submit/jawab.
2. **Pembahasan telaah conditional**: hanya tampil `@if($answer && $q?->explanation)` — sesuai desain feedback langsung.
3. **Sekali-submit enforced server-side**: `showPaket` & `submitPaket` menolak attempt selesai (bukan cuma tombol disabled).
4. **Skor dihitung server-side** di `submitPaket` dari jawaban POST — client tidak bisa mengirim skor.
5. **`submitTelaah` anti-forge**: verifikasi soal memang telaah untuk materi token sesi (bukan soal global sembarangan).
6. **Isolasi sesi**: semua endpoint pakai `material_practice_session_token` milik sendiri; PDF guru diverifikasi attempt milik sesi + jenjang material.
7. **Attempt unik** per (sesi, paket) via unique constraint + idempotent `updateOrCreate` untuk answers.
8. **Resume sesi latihan aman** (fix F1-01: WA + nama), regenerate arsip (fix F1-02), session hygiene (fix F1-03/06).
9. **Pool soal kurang**: `regeneratePackages` throw dengan pesan jelas di sisi superadmin (tidak ada paket diam-diam setengah jadi).

### Rekomendasi Perbaikan — SEMUA FIX SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                                   | Status |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| P1        | **F4-01**: draft jawaban paket latihan disimpan otomatis ke **localStorage** per (sesi, paket-no) — dipulihkan saat halaman dibuka kembali (dengan toast "Pilihan jawaban terakhir Anda dipulihkan"), flag ragu ikut tersimpan, draft dibersihkan saat submit. Tanpa perubahan server | ✅     |
| P1        | **F4-02**: pembuatan attempt dibungkus `DB::transaction` + `lockForUpdate` + re-fetch — double-click "Kerjakan"/dua tab tidak lagi menyebabkan QueryException 500                                                                                                                     | ✅     |
| P2        | **F4-03**: `submitPaket` dibungkus transaction + `lockForUpdate` attempt + re-check status di dalam lock — submit dobel idempotent dan deterministik (yang pertama menang, yang kedua flash "sudah diselesaikan")                                                                     | ✅     |
| P2        | **F4-04**: `telaahStats` guru kini hanya menghitung jawaban untuk soal telaah **aktif** (`MaterialTelaahQuestion` materi tsb) — jawaban orphan soal yang sudah diganti admin dikecualikan dari relasi sebelum agregasi                                                                | ✅     |
| P2        | **F4-05**: validasi jawaban telaah `max:191` (sesuai kolom DB) + validasi per-item jawaban paket `max:191`                                                                                                                                                                            | ✅     |
| P3        | **F4-06**: badge telaah kini dinamis ("N soal", bukan hardcoded "/2")                                                                                                                                                                                                                 | ✅     |

### Regresi Test (3 test baru di `tests/Feature/MaterialPracticeHardeningTest.php`, semua PASS)

1. GET paket dobel (simulasi race) → 1 attempt saja, kedua request 200
2. Submit dobel → idempotent (status selesai, jumlah attempt tetap 1, flash warning di submit kedua)
3. Statistik telaah hanya menghitung soal telaah aktif (jawaban orphan soal yang diganti tidak muncul)

**Status akhir Fase 4: 103 test PASS (401 assertions), Pint bersih.**

---

## FASE 5 — D. Operasional Guru

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** review DashboardController, ProfileController, SoalUjionController, SoalGuruController (termasuk importFromUjion + trait ManagesSoalCrud), GlobalQuestionPolicy, schema pasangan_menjodohkans.

### Ringkasan

Otorisasi area guru sangat solid (policy per-aksi, `abort_if` konsistensi relasi paket↔mapel↔soal, gate jenjang, `ownedQuestion` guard). Namun ditemukan **satu bug fungsional nyata yang sudah ada sejak awal**: import soal menjodohkan dari bank Ujion gagal diam-diam — kolom yang diisi (`pernyataan/jawaban`) tidak ada di schema model (`teks_kiri/teks_kanan`), sehingga pasangan tidak pernah tersimpan. Ditambah itu: guru tidak punya fitur ubah password sama sekali, dan dashboard menampilkan angka yang salah makna.

### Temuan

| #     | Severity  | Aspek | Lokasi                                                        | Deskripsi                                                                                                                                                                                                                                                                                                                              | Dampak                                                                                                    |
| ----- | --------- | ----- | ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| F5-01 | 🟠 TINGGI | ⚙️🗄️  | `SoalGuruController@importFromUjion:160-167`                  | **Import menjodohkan gagal diam-diam.** `pasanganMenjodohkans()->create(['pernyataan' => ..., 'jawaban' => ...])` — kolom ini TIDAK ada (schema & `$fillable`: `teks_kiri/teks_kanan`). Mass assignment mengabaikan key asing → baris pasangan tersimpan tapi **teks kosong**. Guru melihat soal menjodohkan import yang isinya blank. | Fitur rusak total untuk tipe menjodohkan; guru harus isi ulang manual.                                    |
| F5-02 | 🟠 TINGGI | ⚙️🔒  | `Guru/ProfileController` (hanya `show` & `update`)            | **Tidak ada fitur ubah password.** Guru tidak pernah bisa ganti password (registrasi membuat password random 24-char yang tidak diketahui guru; login pakai WA+token). Password random itu jadi rahasia abadi. Superadmin PUNYA fitur password, guru tidak.                                                                            | Asimetri fitur; password user tidak bisa dirotasi/dikelola; jika email bocor, tidak ada mitigasi mandiri. |
| F5-03 | 🟡 SEDANG | 🎨    | `DashboardController:26-31`                                   | **Statistik dashboard menyesatkan.** `totalPeserta` = sesi SELESAI milik guru sendiri (semuanya simulasi); `rataRataKelas` = rata-rata skor simulasi guru sendiri. Label view kemungkinan menyebutnya peserta/kelas. Guru mengira ini data siswa.                                                                                      | Angka dashboard salah interpretasi.                                                                       |
| F5-04 | 🟡 SEDANG | ⚡    | `SoalUjionController@index:48`, `MaterialController@index:56` | **List tanpa pagination.** Bank soal Ujion & materi di-load `->get()` semua baris (dengan filter search LIKE). Bank soal ratusan-ribuan baris → halaman berat, query LIKE lambat.                                                                                                                                                      | Performa menurun seiring pertumbuhan data.                                                                |
| F5-05 | 🟡 SEDANG | 🗄️    | `Guru/ProfileController@update` + `user_id` chat              | **Email bisa diubah tanpa validasi dampak.** Email dipakai sebagai identitas unik; ubah email aman-ish (login pakai WA), tapi tidak ada verifikasi email baru — konsisten dengan baseline (tidak ada email verification di mana pun). Dicatat sebagai keputusan produk.                                                                | Diterima dengan catatan.                                                                                  |
| F5-06 | 🟡 SEDANG | ⚙️    | `DashboardController:33`                                      | Dashboard menampilkan **AuditLog milik guru** ("Aktivitas terakhir") — setelah fix F0-07 (audit middleware di guru group), ini kini berfungsi (sebelumnya selalu kosong). Namun log audit berisi PATH yang disanitasi — tidak human-readable untuk guru.                                                                               | Nilai informasi rendah; perlu label ramah atau diganti aktivitas domain.                                  |
| F5-07 | 🟢 RENDAH | 🎨    | `SoalUjionController@index`                                   | Query builder `$mapels` & `$curriculums` duplikat kondisi (3 query terpisah) — bisa digabung, tapi bukan masalah besar.                                                                                                                                                                                                                | Kosmetik.                                                                                                 |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. **Otorisasi berlapis konsisten**: `abort_if(mapel.paket_soal_id !== paket.id)` di setiap method + `authorize()` policy — tidak ada celah relasi silang.
2. **GlobalQuestionPolicy ketat**: view hanya soal aktif + jenjang cocok; manage/deleteAll superadmin saja.
3. **Bookmark idempotent** (materi & soal Ujion): cek `in_array` sebelum tambah; `array_values(array_diff())` untuk hapus.
4. **Import Ujion**: `DB::transaction` + cap `jumlah_soal` + laporan jumlah imported/skipped + unique IDs.
5. **Builder soal pribadi**: transaction + ownership check + sanitasi path anti-traversal + cleanup file orphan (dari audit F0).
6. **Profil**: duplikat WA dicek via `PhoneNumber::variants`; avatar lama dihapus saat ganti.
7. **Guard `ownedQuestion`** personal question → 404 (bukan 403) — tidak membocorkan keberadaan soal orang lain.
8. **Simulasi guru**: guard jenjang 403 + resume sesi sendiri + hasil terisolasi per user (fix F3-03 memastikan tidak bocor ke hasil siswa).

### Rekomendasi Perbaikan — SEMUA FIX SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                                                                                                                                                                  | Status      |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| P1        | **F5-01**: import menjodohkan diperbaiki — kolom benar `teks_kiri`/`teks_kanan` (sebelumnya `pernyataan`/`jawaban` yang tidak ada di schema → soal import kosong). Saat eksekusi ditemukan & difix 2 bug tambahan di jalur yang sama: `indikator` NOT NULL (sekarang fallback "Diimpor dari bank soal Ujion"), `bobot` NOT NULL (fallback 1), `arah_skor` ber-default (null eksplisit menimpa default → `'positif'`) | ✅          |
| P1        | **F5-02**: fitur password guru — method `password()` di Guru/ProfileController (set bebas + konfirmasi, min 8, tidak boleh sama dengan token akses) + route `guru.profile.password` + kartu form password di halaman profil dengan penjelasan "login utama tetap WA+token"                                                                                                                                           | ✅          |
| P2        | **F5-03**: dashboard guru kini pakai **data siswa nyata**: "Peserta Selesai" = siswa unik (non-simulasi) yang selesai ujian di paket jenjang guru, "Rata-rata Skor" = rerata skor siswa, + kartu baru "Simulasi Selesai" (simulasi guru sendiri, terpisah jujur)                                                                                                                                                     | ✅          |
| P2        | **F5-04**: pagination di index bank soal Ujion (24/halaman) & materi guru (30/halaman) + `withQueryString()` + render links                                                                                                                                                                                                                                                                                          | ✅          |
| P3        | F5-05: email tanpa verifikasi — diterima (baseline produk)                                                                                                                                                                                                                                                                                                                                                           | 📄 diterima |
| P3        | F5-06: aktivitas terakhir (AuditLog) dibiarkan — nilai jaringan kecil, dicatat tech-debt                                                                                                                                                                                                                                                                                                                             | 📄 ditunda  |
| P3        | F5-07: query mapels/curriculums dirapikan (format konsisten)                                                                                                                                                                                                                                                                                                                                                         | ✅          |

**Catatan implementasi:**

- 3 test dashboard lama diupdate ke perilaku baru (statistik siswa nyata + label simulasi terpisah).
- Test lama "guru profile does not show password form" dibalik menjadi 3 test baru (form tampil, set password sukses, konfirmasi mismatch ditolak).
- Import Ujion kini teruji end-to-end untuk soal menjodohkan — sebelumnya jalur ini tidak pernah ada test-nya (makanya bug kolom lolos bertahun-tahun... relatif).

### Regresi Test (4 test baru + 3 test diubah, semua PASS)

1. `ImportUjionMatchingTest`: import soal menjodohkan → 2 pasangan tersimpan **dengan teks lengkap** (menangkap 3 bug sekaligus)
2. Guru bisa set password + konfirmasi valid
3. Konfirmasi password mismatch → error, password lama tak berubah
4. Form password tampil di halaman profil guru

**Status akhir Fase 5: 106 test PASS (411 assertions), Pint passed.**

---

## FASE 6 — E. Operasional Superadmin

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** review DashboardController, ExamController (store/import/destroy/toggle/builder), WhatsAppGatewayController (blast/koneksi), GlobalQuestionController (destroyAll), schema FK exam↔sesi. Approval payment, QRIS, harga, upload, token generator sudah ter-cover fix F0/F2.

### Ringkasan

Area superadmin sudah banyak yang diperkuat oleh fix fase sebelumnya (approval atomic, QRIS graceful, validasi harga, private disk, destroyAll pakai confirm text + policy). Namun ditemukan **dua temuan tinggi**: hapus ujian ternyata **menghapus seluruh hasil siswa secara permanen** via cascade FK tanpa peringatan apa pun, dan fitur blast WhatsApp target siswa memakai **tabel legacy yang tidak lagi terisi** — praktis rusak sejak schema `ujian_sesis` aktif.

### Temuan

| #     | Severity  | Aspek | Lokasi                                                                         | Deskripsi                                                                                                                                                                                                                                                                                                                      | Dampak                                                                                                                                       |
| ----- | --------- | ----- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| F6-01 | 🟠 TINGGI | 🗄️    | `Superadmin/ExamController@destroy` + FK `ujian_sesis.exam_id cascadeOnDelete` | **Hapus ujian = hapus semua hasil siswa.** `destroy()` menghapus exam; FK cascade menghapus semua `ujian_sesis` → semua `jawaban_siswas` ikut terhapus. Tidak ada peringatan jumlah peserta terdampak. Test hanya melindungi PAKET dari penghapusan bila dipakai exam — exam sendiri bebas dihapus walau berisi ratusan hasil. | Kehilangan data hasil ujian siswa secara massal & permanen karena satu klik.                                                                 |
| F6-02 | 🟠 TINGGI | ⚙️    | `WhatsAppGatewayController@sendBlast` target `siswa_all`/`siswa_paket`         | **Blast siswa pakai tabel legacy `participants`** — siswa nyata sejak schema baru tersimpan di `ujian_sesis.nomor_wa`. Tabel legacy tidak lagi terisi → blast siswa mengirim ke daftar kosong/stale.                                                                                                                           | Fitur blast siswa tidak berfungsi; superadmin mengira pesan terkirim.                                                                        |
| F6-03 | 🟡 SEDANG | 🎨    | `ExamController@store` (blast `event_exam_published`)                          | **Publikasi ujian blast ke SEMUA guru aktif tanpa filter jenjang.** Guru SD menerima notifikasi ujian paket SMA. Tidak ada filter `paketSoal.jenjang == guru.jenjang`.                                                                                                                                                         | Spam notifikasi lintas jenjang; guru menganggap WA admin berisik → abaikan notifikasi penting.                                               |
| F6-04 | 🟡 SEDANG | 🗄️    | `DashboardController@buildMetrics`                                             | **"Total Revenue" = estimasi** (jumlah guru aktif × harga plan per jenjang), bukan penjumlahan transaksi `STATUS_SUCCESS` nyata. Guru yang bayar di harga lama tetap dihitung harga baru.                                                                                                                                      | Angka keuangan dashboard menyesatkan pengambil keputusan.                                                                                    |
| F6-05 | 🟡 SEDANG | 🔒    | `WhatsAppGatewayController@connection`                                         | `env('WA_GATEWAY_URL')` + `env('WA_SENDER_ID')` dibaca langsung di controller — **rusak saat `config:cache`** (pola sama dengan F0-02 yang sudah di-fix di webhook).                                                                                                                                                           | Halaman koneksi WA menampilkan default salah di production.                                                                                  |
| F6-06 | 🟢 RENDAH | ⚙️    | `ExamController@store` validasi `max_peserta`                                  | `required                                                                                                                                                                                                                                                                                                                      | integer`tanpa`min:0` — nilai negatif tersimpan; negatif berarti unlimited (count >= negatif selalu true). Efek sama dengan 0, jadi kosmetik. | Minor. |
| F6-07 | 🟢 RENDAH | ⚙️    | `GlobalQuestionController@destroyAll`                                          | Menghapus semua soal global walau dipakai paket latihan aktif — snapshot `material_practice_package_questions` aman karena FK ke id (soft delete tidak memicu FK), tapi paket latihan akan kehilangan akses ke konten jika soal di-hard-delete. Confirm text "HAPUS SEMUA" sudah ada.                                          | Risiko rendah (soft delete); dicatat.                                                                                                        |

### Yang Sudah Baik (jangan diubah saat perbaikan)

1. **destroyAll global questions**: confirm text eksplisit + policy `deleteAll` + laporan jumlah.
2. **Blast guru**: `chunkById(200)` + delay random per pesan + queue `low` — anti-flood cukup.
3. **Validasi blast**: target whitelist, field syarat per target, jadwal masa depan, pesan max 2000.
4. **Import bank soal ke ujian**: `DB::transaction` + dedup `source_global_question_id` + fallback material dengan pesan jelas.
5. **bankQuestions AJAX**: paginated 15 + search — tidak load semua.
6. **Dashboard**: query agregasi efisien (bukan N+1), grafik 14 hari via `selectRaw GROUP BY DATE`.
7. **Import ujian/exam CSV**: pakai `SpreadsheetTable` dengan parsing tanggal & status ternormalisasi + skipped counter.
8. **Toggle exam**: satu klik tanpa form berat — pas untuk fungsinya.
9. Soft delete global questions & materials → snapshot & bookmark tetap konsisten.

### Rekomendasi Perbaikan — SEMUA FIX SUDAH DIIMPLEMENTASIKAN ✅

| Prioritas | Fix                                                                                                                                                                                                                                                                                                                                                             | Status      |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- | --- |
| P1        | **F6-01 (konfirmasi ketik)**: `destroy` exam kini cek jumlah sesi — bila ada hasil peserta, wajib input `confirm_text = HAPUS`; tanpa/salah konfirmasi → error dengan jumlah peserta terdampak. UI: tombol berubah "Hapus (N hasil)" + input konfirmasi + dialog JS; index exam eager-load `withCount('ujianSesis')`. Ujian tanpa hasil tetap hapus sekali klik | ✅          |
| P1        | **F6-02**: target siswa blast (`siswa_all`/`siswa_paket`) kini query `UjianSesi` (`whereNull('user_id')`, `whereNotNull('nomor_wa')`, distinct; filter paket via `paket_soal_id`) — tabel legacy `participants` tidak lagi dipakai                                                                                                                              | ✅          |
| P2        | **F6-03**: blast `event_exam_published` kini hanya ke guru dengan jenjang = jenjang paket ujian ybs                                                                                                                                                                                                                                                             | ✅          |
| P2        | **F6-04**: "Total Revenue" = `SUM(transactions.amount WHERE status=success)` — angka nyata; logika estimasi (guru × harga plan) & `normalizeCurrency` dihapus                                                                                                                                                                                                   | ✅          |
| P2        | **F6-05**: `WA_GATEWAY_URL` & `WA_SENDER_ID` pindah ke `config/services.php` (`services.wa_gateway.*`) — dipakai oleh `WhatsAppGatewayController@connection` **dan** `WhatsAppService` (pemakaian `env()` tersisa di seluruh `app/` kini nol — aman `config:cache`) + masuk `.env.example`                                                                      | ✅          |
| P3        | **F6-06**: validasi `max_peserta` → `integer                                                                                                                                                                                                                                                                                                                    | min:0`      | ✅  |
| P3        | F6-07: diterima (soft delete melindungi snapshot latihan)                                                                                                                                                                                                                                                                                                       | 📄 diterima |

**Catatan implementasi:**

- Test dashboard superadmin diupdate: revenue kini dari transaksi `STATUS_SUCCESS` nyata (fixture menambah transaksi).
- Bonus saat sweep `env()`: `WhatsAppService` ternyata juga baca `env()` langsung (jalur pengiriman WA aktual!) — ikut dipindah ke config. Tanpa ini, semua blast WA produksi akan gagal diam-diam setelah `config:cache`.

### Regresi Test (2 test baru di `tests/Feature/ExamDeletionProtectionTest.php`, semua PASS)

1. Hapus ujian dengan hasil tanpa konfirmasi → ditolak; konfirmasi salah → ditolak; konfirmasi "HAPUS" → terhapus + pesan jumlah hasil
2. Hapus ujian tanpa hasil → sekali klik, tanpa konfirmasi

**Status akhir Fase 6: 108 test PASS (425 assertions), Pint bersih.**

---

## FASE 7 — H. Chat Guru ↔ Superadmin

**Tanggal audit:** 24 Agustus 2026 · **Status:** SELESAI · **Metode:** review kedua ChatController, ChatImageController (sudah di-fix F0-05), model Chat event, view chat.

### Temuan

| #     | Severity  | Aspek | Lokasi                                           | Deskripsi                                                                                                                                                                                                                                                                                                                                                                                      | Dampak                                      |
| ----- | --------- | ----- | ------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| F7-01 | 🟠 TINGGI | 🔒    | `Superadmin/ChatController@markRead`, `@destroy` | **Tanpa ownership check.** `markRead(Chat $chat)` dan `destroy(Chat $chat)` menerima ID chat apa pun — superadmin bisa mark-read/hapus chat yang bukan percakapannya. Dalam praktik hanya superadmin yang akses route ini (middleware role), jadi dampaknya superadmin-superadmin lain. Diturunkan dari kritis karena single-admin assumption, tapi bila ada >1 superadmin jadi masalah nyata. | Manipulasi state chat lintas admin.         |
| F7-02 | 🟡 SEDANG | ⚙️    | `Guru/ChatController@index`                      | **History chat tanpa pagination** — semua pesan guru↔admin diload. Percakapan panjang (ratusan pesan berisi teks 2000 char) → halaman berat.                                                                                                                                                                                                                                                   | Performa menurun seiring usia percakapan.   |
| F7-03 | 🟡 SEDANG | ⚙️🔒  | `Superadmin/ChatController@store`                | `to_user_id` divalidasi `exists:users,id` saja — superadmin bisa mengirim chat ke user superadmin lain / dirinya (percakapan aneh muncul di list). Tidak diverifikasi target adalah guru.                                                                                                                                                                                                      | Data percakapan tidak bersih.               |
| F7-04 | 🟢 RENDAH | ⚙️    | config `BROADCAST_CONNECTION=log`                | Realtime Pusher tidak aktif default (log fallback); chat berjalan polling/refresh manual. Ini keputusan setup (env), bukan bug — didokumentasikan.                                                                                                                                                                                                                                             | UX chat bukan realtime; intended per setup. |

### Yang Sudah Baik

1. `ChatImageController` private + participant check (fix F0-05) — kunci IDOR tertutup.
2. Chat image disimpan disk private + cleanup saat delete (model event).
3. Guru chat store: target otomatis superadmin (tidak bisa pilih penerima) — tidak ada celah kirim ke guru lain dari sisi guru.
4. Index superadmin: paginated 25 + withCount unread + mark-read massal saat buka percakapan.
5. Pesan divalidasi max 2000 (fix F0-13), image max 2MB.

### Fix yang Diimplementasikan

- **F7-01**: `markRead` & `destroy` kini cek `auth()->id()` adalah salah satu participant chat → 403 jika bukan (helper `authorizeChatParticipant`).
- **F7-03**: `to_user_id` divalidasi `Rule::exists('users','id')->where('role','guru')` — chat superadmin hanya bisa ditujukan ke guru.
- **F7-02**: index guru chat dibatasi 200 pesan terakhir (order desc lalu sort asc) — tech-debt pagination penuh dicatat.
- **F7-04**: diterima (keputusan setup env `BROADCAST_CONNECTION`).

---

## FASE 8 — I. Integrasi WhatsApp

**Status:** SELESAI · **Metode:** review WhatsAppService, SendWhatsAppBlast, WebhookController (sudah di-fix F0-01/02 & F6-05).

### Temuan

| #     | Severity  | Aspek | Lokasi                                             | Deskripsi                                                                                                                                                                                                                                                                        | Dampak                              |
| ----- | --------- | ----- | -------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------- |
| F8-01 | 🟡 SEDANG | 🗄️    | `SendWhatsAppBlast` (tries=3, backoff 60/180/300s) | Job retry 3x — tapi `sendMessage` sudah menandai failed di `whatsapp_logs` untuk SETIAP percobaan → satu pesan gagal menghasilkan hingga 3 baris log "failed" + 1 baris sukses (jika retry berhasil). Statistik dashboard WA (terkirim/gagal) menghitung percobaan, bukan pesan. | Statistik blast menyesatkan.        |
| F8-02 | 🟢 RENDAH | 🔒    | `WhatsAppService::sendMessage`                     | Request ke gateway TANPA auth (API key) — siapa pun di jaringan yang bisa reach gateway:3000 bisa kirim pesan. Mitigasi: gateway hanya listen localhost di setup lokal; di produksi harus di-firewall/localhost-only.                                                            | Tergantung hardening infra gateway. |

### Yang Sudah Baik

1. Webhook fail-closed + `hash_equals` + via config (fix F0) — account takeover tertutup.
2. `env()` bebas di seluruh `app/` (fix F6-05) — aman `config:cache`.
3. Normalisasi nomor konsisten + timeout 15s + try/catch lengkap dengan log kontekstual.
4. `tryLog` swallow-exception — log failure tidak pernah merusak flow utama.
5. Random delay 2-7s per pesan + chunk 200 + queue terpisah (high untuk notifikasi, low untuk blast) — anti-flood.
6. WA bot webhook: LID mapping + fallback suffix-9 + template terpusat — matang.

### Fix yang Diimplementasikan

- **F8-01**: statistik dashboard WA blast kini dihitung per pesan — `MAX(id) GROUP BY phone, message` lalu agregasi status dari log terbaru saja; pesan yang sukses saat retry tidak lagi tercatat "gagal" di statistik.
- **F8-02**: checklist deployment AGENTS.md ditambah aturan **gateway wajib localhost-only / firewall** (endpoint gateway tanpa auth sendiri).

---

## FASE 9 — A. Landing & Publik

**Status:** SELESAI · **Metode:** review LandingController, LandingClickController, SitemapController, OgImageController (skim), config landing.

### Temuan

| #     | Severity  | Aspek | Lokasi                         | Deskripsi                                                                                                                                                                  | Dampak                                                                 |
| ----- | --------- | ----- | ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| F9-01 | 🟡 SEDANG | 🔒    | `LandingClickController@store` | **IP address penuh disimpan tanpa masking** di `landing_click_logs` — inkonsisten dengan AuditRequest yang mem-mask IP. Data PII pengunjung publik tersimpan plain.        | Privasi: kepatuhan data pengunjung (trafik landing bisa sangat besar). |
| F9-02 | 🟡 SEDANG | ⚡    | `LandingController@index`      | Landing query stats (material + question GROUP BY) + semua section setiap request tanpa cache. Landing adalah halaman tersering publik — tiap visit memicu query agregasi. | Performa landing menurun seiring trafik.                               |
| F9-03 | 🟢 RENDAH | ⚙️    | `SitemapController`            | Sitemap hanya 2 URL statis — memang pas (tidak ada konten publik lain).                                                                                                    | —                                                                      |

### Yang Sudah Baik

1. Landing click API: validasi ketat + throttle 120/menit + `noindex` di halaman pembayaran.
2. Sitemap & OG image dinamis dengan fallback aman.
3. Section toggle via `landing_contents` — admin bisa sembunyikan section tanpa deploy.
4. Pricing landing dari DB live (is_active) — konsisten dengan flow registrasi.

### Fix yang Diimplementasikan

- **F9-01**: IP dimasking sebelum disimpan (IPv4: oktet terakhir jadi `x`; IPv6: 4 blok pertama) — pola sama dengan AuditRequest.
- **F9-02**: stats landing di-cache 10 menit via `Cache::remember` — agregasi berat tidak lagi per-request.

### Regresi Test (4 test baru di `tests/Feature/ChatParticipantGuardTest.php`, semua PASS)

1. Superadmin A tidak bisa hapus chat milik superadmin B ↔ guru (403)
2. Participant chat bisa hapus chat percakapannya sendiri
3. Superadmin tidak bisa kirim chat ke non-guru (validation error)
4. Landing click log menyimpan IP yang sudah dimask

---

# REKAP AKHIR AUDIT — SEMUA FASE SELESAI

| Fase      | Flow                   | Temuan | Fix    | Diterima |
| --------- | ---------------------- | ------ | ------ | -------- |
| 0         | Fondasi keamanan       | 18     | 18     | —        |
| 1         | Auth semua role        | 9      | 8      | 1        |
| 2         | Registrasi & payment   | 8      | 7      | 1        |
| 3         | Ujian siswa            | 7      | 7      | —        |
| 4         | Latihan materi         | 6      | 6      | —        |
| 5         | Operasional guru       | 7      | 7      | —        |
| 6         | Operasional superadmin | 7      | 7      | —        |
| 7         | Chat realtime          | 4      | 3      | 1        |
| 8         | Integrasi WA           | 2      | 1      | 1        |
| 9         | Landing & publik       | 3      | 3      | —        |
| **TOTAL** |                        | **71** | **67** | **4**    |

**Hasil test akhir: 112 PASS (437 assertions), Pint bersih.**

### Bug Kritis yang Berhasil Ditutup (highlight)

1. 🔴 **Account takeover guru via webhook WA** (F0-01/02) — webhook terbuka + mengembalikan token baru ke pengirim
2. 🔴 **Kunci jawaban menjodohkan bocor di payload** (F3-01) — siswa bisa 100% via DevTools
3. 🟠 **Impersonasi sesi siswa via nomor WA** (F1-01) — resume tanpa verifikasi nama
4. 🟠 **Regenerate paket = mass delete hasil siswa** (F1-02) — cascade FK
5. 🟠 **Hapus ujian = mass delete hasil siswa** (F6-01) — cascade FK
6. 🟠 **Import soal Ujion rusak total** (F5-01 + bonus) — 3 bug bertumpuk: kolom salah + 2× NOT NULL violation
7. 🟠 **Blast WA produksi gagal diam-diam setelah config:cache** (F6-05 bonus) — `env()` di WhatsAppService
8. 🟠 **Fallback tarif lintas jenjang** (F2-01) — guru ditagih nominal jenjang lain
9. 🟠 **Blast siswa query tabel legacy kosong** (F6-02)

### Tech-Debt Tercatat (untuk sesi berikutnya)

- `Schema::hasColumn` per-request masih ±50 pemakaian di controller superadmin/landing (satu hot-path sudah dibersihkan)
- Chat guru belum pagination penuh (limit 200)
- Dua set route latihan (`/siswa/latihan/*` & `/materi/*`) sebagai alias
- Aktivitas terakhir dashboard guru menampilkan path audit teknis

### Checklist "Siap Launch" (ringkasan tindakan opsional)

- [ ] Isi `WA_WEBHOOK_KEY` di server production
- [ ] Set `SESSION_SECURE_COOKIE=true` (HTTPS)
- [ ] Pastikan WA Gateway bind 127.0.0.1 (bukan 0.0.0.0)
- [ ] Jalankan `php artisan config:cache` SETELAH semua env terisi
- [ ] `composer install --no-dev` + `npm run build` + migrate --force

### Verifikasi (setelah fix diimplementasikan)

```bash
php artisan test --filter=SuperadminAccessAndExamBuilderTest
php artisan test --filter=RemainingFlowsHardeningTest
# Manual: hapus ujian yang punya hasil siswa → harus ditolak dengan pesan jumlah peserta
# Manual: blast target siswa → penerima diambil dari ujian_sesis (bukan participants)
# Manual: publish ujian jenjang SMA → hanya guru SMA dapat WA
```

### Verifikasi (setelah fix diimplementasikan)

```bash
php artisan test --filter=GuruProfileAndRegistrationFlowTest
php artisan test --filter=SuperadminProfileTest
# Manual: import soal menjodohkan dari bank Ujion → soal hasil import harus berisi teks pasangan
# Manual: profil guru → harus ada form ubah password
```

### Verifikasi (setelah fix diimplementasikan)

```bash
php artisan test --filter=AuthFlowHardeningTest
# Manual: kerjakan paket latihan setengah → refresh → pilihan harus tetap ada
# Manual: double-click "Kerjakan"/"Kumpulkan" cepat → tidak boleh ada 500
# Manual: ganti soal telaah via superadmin → statistik telaah guru hanya menampilkan soal aktif
```

### Verifikasi (setelah fix diimplementasikan)

```bash
php artisan test --filter=SiswaExamSessionTest
php artisan test --filter=AuthFlowHardeningTest
# Manual: buka /siswa/ujian → DevTools Network → payload TIDAK boleh memuat id pasangan mentah yang match antar baris-opsi
# Manual: matikan koneksi saat mengerjakan → jawaban harus menandai "belum tersimpan" & retry saat kembali
# Manual: selesaikan simulasi guru → hasil siswa TIDAK memuat skor simulasi
```
