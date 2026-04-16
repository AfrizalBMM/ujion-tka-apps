# DFD Ujion TKA

Dokumen ini merangkum Data Flow Diagram berdasarkan codebase saat ini. Karena sistem masih memiliki flow lama dan flow baru untuk modul ujian, DFD ini fokus pada flow yang paling nyata dipakai sekarang, lalu saya tambahkan catatan untuk flow legacy.

## 1. Context Diagram

```mermaid
flowchart LR
    guru["Guru / Operator"]
    siswa["Siswa"]
    superadmin["Superadmin"]

    ujion["Sistem Ujion TKA"]

    guru -->|login token, kelola profil, materi, soal, chat| ujion
    ujion -->|dashboard, paket soal, materi, chat, log| guru

    siswa -->|token ujian, identitas, jawaban ujian| ujion
    ujion -->|petunjuk, soal, timer, hasil akhir| siswa

    superadmin -->|login, kelola guru, pricing, QR, materi, paket, ujian| ujion
    ujion -->|dashboard, audit, token ujian, data guru, laporan| superadmin
```

## 2. DFD Level 1

```mermaid
flowchart TB
    guru["Guru / Operator"]
    siswa["Siswa"]
    superadmin["Superadmin"]

    p1["1.0 Autentikasi & Manajemen Akun"]
    p2["2.0 Manajemen Materi"]
    p3["3.0 Manajemen Bank Soal & Paket TKA"]
    p4["4.0 Manajemen Ujian"]
    p5["5.0 Pengerjaan Ujian Siswa"]
    p6["6.0 Chat & Audit"]
    p7["7.0 Pricing & Pembayaran"]

    d1[("D1 Users")]
    d2[("D2 Materials")]
    d3[("D3 Global Questions")]
    d4[("D4 Paket Soal TKA")]
    d5[("D5 Exams")]
    d6[("D6 Ujian Sesis")]
    d7[("D7 Jawaban Siswa")]
    d8[("D8 Chats")]
    d9[("D9 Audit Logs")]
    d10[("D10 Pricing Plans")]
    d11[("D11 Payment QRs")]
    d12[("D12 Personal Questions")]

    guru -->|nama, token akses, data profil| p1
    superadmin -->|email, password, aktivasi guru| p1
    p1 <--> d1
    p1 -->|status login, data akun| guru
    p1 -->|status login, data guru| superadmin

    superadmin -->|input materi, filter, hapus| p2
    guru -->|lihat materi, bookmark| p2
    p2 <--> d2
    p2 -->|daftar materi, detail materi| guru
    p2 -->|daftar materi| superadmin

    superadmin -->|input soal global, kelola paket| p3
    guru -->|kelola soal mapel, teks bacaan, soal pribadi| p3
    p3 <--> d3
    p3 <--> d4
    p3 <--> d12
    p3 -->|paket soal, detail mapel, daftar soal| guru
    p3 -->|bank soal global, paket, teks bacaan| superadmin

    superadmin -->|buat ujian, toggle, token| p4
    guru -->|lihat ujian tersedia| p4
    p4 <--> d5
    p4 <--> d4
    p4 -->|jadwal ujian, token, detail ujian| superadmin
    p4 -->|daftar ujian| guru

    siswa -->|token ujian, identitas, jawaban| p5
    p5 <--> d5
    p5 <--> d4
    p5 <--> d6
    p5 <--> d7
    p5 -->|petunjuk, soal, timer, skor| siswa

    guru -->|pesan chat| p6
    superadmin -->|pesan chat, baca audit| p6
    p6 <--> d8
    p6 <--> d9
    p6 -->|percakapan| guru
    p6 -->|percakapan, audit trail| superadmin

    superadmin -->|kelola harga dan QR| p7
    p7 <--> d10
    p7 <--> d11
    p7 -->|preview harga & QR| superadmin
```

## 3. DFD Level 2 - Pengerjaan Ujian Siswa

```mermaid
flowchart TB
    siswa["Siswa"]

    p51["5.1 Validasi Token Ujian"]
    p52["5.2 Input Identitas Peserta"]
    p53["5.3 Generate Sesi Ujian"]
    p54["5.4 Ambil Paket, Mapel, dan Soal"]
    p55["5.5 Simpan Jawaban & Timer"]
    p56["5.6 Hitung Skor & Tutup Sesi"]

    d5[("D5 Exams")]
    d4[("D4 Paket Soal TKA")]
    d6[("D6 Ujian Sesis")]
    d7[("D7 Jawaban Siswa")]

    siswa -->|token ujian| p51
    p51 <--> d5
    p51 <--> d4
    p51 -->|status valid / invalid| siswa

    siswa -->|nama, nomor WA| p52
    p52 --> p53

    p53 <--> d6
    p53 <--> d5
    p53 <--> d4
    p53 -->|session token, status menunggu| siswa

    siswa -->|mulai ujian / pilih mapel| p54
    p54 <--> d6
    p54 <--> d4
    p54 -->|petunjuk, soal, timer per mapel| siswa

    siswa -->|jawaban PG, jawaban menjodohkan, ragu-ragu| p55
    p55 <--> d7
    p55 <--> d6
    p55 -->|autosave status| siswa

    siswa -->|selesai ujian / waktu habis| p56
    p56 <--> d6
    p56 <--> d7
    p56 <--> d4
    p56 -->|skor akhir, sesi ditutup| siswa
```

## 4. DFD Level 2 - Manajemen Paket Soal TKA

```mermaid
flowchart TB
    superadmin["Superadmin"]
    guru["Guru / Operator"]

    p31["3.1 Kelola Metadata Paket"]
    p32["3.2 Kelola Konfigurasi Mapel"]
    p33["3.3 Kelola Teks Bacaan"]
    p34["3.4 Kelola Soal per Mapel"]
    p35["3.5 Kelola Bank Soal Pribadi / Global"]

    d3[("D3 Global Questions")]
    d4a[("D4a Paket Soal")]
    d4b[("D4b Mapel Paket")]
    d4c[("D4c Teks Bacaan")]
    d4d[("D4d Soals")]
    d4e[("D4e Pilihan Jawaban")]
    d4f[("D4f Pasangan Menjodohkan")]
    d12[("D12 Personal Questions")]

    superadmin -->|buat/edit/hapus paket| p31
    p31 <--> d4a
    p31 -->|detail paket| superadmin

    superadmin -->|jumlah soal, durasi, urutan| p32
    guru -->|jumlah soal, durasi, urutan| p32
    p32 <--> d4b
    p32 -->|konfigurasi mapel| superadmin
    p32 -->|konfigurasi mapel| guru

    superadmin -->|judul dan konten bacaan| p33
    guru -->|judul dan konten bacaan| p33
    p33 <--> d4c
    p33 -->|daftar teks bacaan| superadmin
    p33 -->|daftar teks bacaan| guru

    superadmin -->|input soal, edit, hapus| p34
    guru -->|input soal, edit, hapus| p34
    p34 <--> d4d
    p34 <--> d4e
    p34 <--> d4f
    p34 <--> d4c
    p34 -->|daftar soal mapel| superadmin
    p34 -->|daftar soal mapel| guru

    superadmin -->|input soal global| p35
    guru -->|input soal pribadi| p35
    p35 <--> d3
    p35 <--> d12
    p35 -->|bank soal global| superadmin
    p35 -->|bank soal pribadi| guru
```

## 5. Catatan DFD

- DFD ini dibuat dari flow aplikasi yang saat ini paling nyata di codebase.
- Modul `questions`, `participants`, dan `participant_answers` masih ada sebagai flow legacy.
- Flow ujian siswa aktif sekarang terutama memakai:
  - `exams`
  - `paket_soals`
  - `mapel_pakets`
  - `soals`
  - `ujian_sesis`
  - `jawaban_siswas`
- Jika kamu mau, saya bisa lanjut pecah jadi:
  - `DFD-context.png`
  - `DFD-level1.png`
  - `DFD-level2-siswa.png`
  - `DFD-level2-paket.png`

