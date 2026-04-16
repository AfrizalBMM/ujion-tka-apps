# ERD Ujion TKA

ERD ini dibuat dari migrasi yang ada di `database/migrations` pada 16 April 2026. Saya bagi menjadi 2 bagian:

- domain aplikasi
- tabel infrastruktur Laravel

Catatan penting:

- codebase ini masih memiliki 2 jalur domain ujian:
  - schema lama: `questions`, `participants`, `participant_answers`, `exam_question`
  - schema baru: `paket_soals`, `mapel_pakets`, `soals`, `ujian_sesis`, `jawaban_siswas`
- field `users.bookmarks` adalah JSON, bukan foreign key ke `materials`
- tabel `sessions` punya `user_id` yang di-index, tetapi di migrasi default ini tidak dideklarasikan sebagai foreign key

## 1. ERD Domain Aplikasi

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string role
        string account_status
        string access_token UK
        string jenjang
        string tingkat
        string satuan_pendidikan
        string no_wa
        json bookmarks
        timestamp email_verified_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    PRICING_PLANS {
        bigint id PK
        string name
        string subtitle
        string price
        string original_price
        boolean promo_active
        string period
        boolean is_active
        int sort_order
        timestamp created_at
        timestamp updated_at
    }

    PAYMENT_QRS {
        bigint id PK
        string label
        string image_path
        boolean is_active
        int sort_order
        timestamp created_at
        timestamp updated_at
    }

    MATERIALS {
        bigint id PK
        string jenjang
        string curriculum
        string subelement
        string unit
        string sub_unit
        string link
        timestamp created_at
        timestamp updated_at
    }

    GLOBAL_QUESTIONS {
        bigint id PK
        bigint material_id FK
        string question_type
        text question_text
        json options
        string answer_key
        text explanation
        boolean is_active
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string method
        string path
        string route_name
        string ip_address
        text user_agent
        string country
        string city
        timestamp created_at
        timestamp updated_at
    }

    CHATS {
        bigint id PK
        bigint from_user_id FK
        bigint to_user_id FK
        text message
        string image_path
        boolean is_read
        timestamp created_at
        timestamp updated_at
    }

    QUESTIONS {
        bigint id PK
        bigint material_id FK
        string jenjang
        string tingkat
        string kategori
        string tipe
        text pertanyaan
        json opsi
        string jawaban_benar
        text pembahasan
        string image_path
        enum status
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    EXAMS {
        bigint id PK
        bigint user_id FK
        bigint paket_soal_id FK
        string judul
        datetime tanggal_terbit
        int max_peserta
        string token UK
        int timer
        enum status
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    EXAM_QUESTION {
        bigint id PK
        bigint exam_id FK
        bigint question_id FK
        int order
        timestamp created_at
        timestamp updated_at
    }

    PERSONAL_QUESTIONS {
        bigint id PK
        bigint user_id FK
        string jenjang
        string kategori
        string tipe
        text pertanyaan
        json opsi
        string jawaban_benar
        text pembahasan
        string image_path
        enum status
        timestamp created_at
        timestamp updated_at
    }

    PARTICIPANTS {
        bigint id PK
        bigint exam_id FK
        string nama
        string nomor_wa
        string session_token UK
        timestamp waktu_mulai
        timestamp waktu_selesai
        float skor
        enum status_ujian
        timestamp created_at
        timestamp updated_at
    }

    PARTICIPANT_ANSWERS {
        bigint id PK
        bigint participant_id FK
        bigint question_id FK
        text jawaban
        boolean ragu_ragu
        timestamp created_at
        timestamp updated_at
    }

    JENJANGS {
        bigint id PK
        string kode UK
        string nama
        int urutan
        timestamp created_at
        timestamp updated_at
    }

    PAKET_SOALS {
        bigint id PK
        bigint jenjang_id FK
        string nama
        string tahun_ajaran
        boolean is_active
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }

    MAPEL_PAKETS {
        bigint id PK
        bigint paket_soal_id FK
        enum nama_mapel
        int jumlah_soal
        int durasi_menit
        int urutan
        timestamp created_at
        timestamp updated_at
    }

    TEKS_BACAANS {
        bigint id PK
        bigint mapel_paket_id FK
        string judul
        longtext konten
        timestamp created_at
        timestamp updated_at
    }

    SOALS {
        bigint id PK
        bigint mapel_paket_id FK
        bigint teks_bacaan_id FK
        int nomor_soal
        enum tipe_soal
        text indikator
        text pertanyaan
        string gambar
        int bobot
        timestamp created_at
        timestamp updated_at
    }

    PILIHAN_JAWABANS {
        bigint id PK
        bigint soal_id FK
        enum kode
        text teks
        string gambar
        boolean is_benar
        timestamp created_at
        timestamp updated_at
    }

    PASANGAN_MENJODOHKANS {
        bigint id PK
        bigint soal_id FK
        text teks_kiri
        text teks_kanan
        int urutan
        timestamp created_at
        timestamp updated_at
    }

    UJIAN_SESIS {
        bigint id PK
        bigint exam_id FK
        bigint paket_soal_id FK
        string nama
        string nomor_wa
        string session_token UK
        json timer_state
        enum status
        timestamp waktu_mulai
        timestamp waktu_selesai
        decimal skor
        timestamp created_at
        timestamp updated_at
    }

    JAWABAN_SISWAS {
        bigint id PK
        bigint ujian_sesi_id FK
        bigint soal_id FK
        enum tipe_soal
        string jawaban_pg
        json jawaban_menjodohkan
        boolean is_ragu
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ GLOBAL_QUESTIONS : creates
    MATERIALS ||--o{ GLOBAL_QUESTIONS : references

    USERS ||--o{ AUDIT_LOGS : generates

    USERS ||--o{ CHATS : sends
    USERS ||--o{ CHATS : receives

    MATERIALS ||--o{ QUESTIONS : owns
    USERS ||--o{ PERSONAL_QUESTIONS : owns

    USERS ||--o{ EXAMS : creates
    PAKET_SOALS ||--o{ EXAMS : used_by

    EXAMS ||--o{ EXAM_QUESTION : contains
    QUESTIONS ||--o{ EXAM_QUESTION : assigned_to

    EXAMS ||--o{ PARTICIPANTS : has
    PARTICIPANTS ||--o{ PARTICIPANT_ANSWERS : answers
    QUESTIONS ||--o{ PARTICIPANT_ANSWERS : answered

    JENJANGS ||--o{ PAKET_SOALS : groups
    USERS ||--o{ PAKET_SOALS : created_by
    PAKET_SOALS ||--o{ MAPEL_PAKETS : contains
    MAPEL_PAKETS ||--o{ TEKS_BACAANS : has
    MAPEL_PAKETS ||--o{ SOALS : has
    TEKS_BACAANS ||--o{ SOALS : referenced_by
    SOALS ||--o{ PILIHAN_JAWABANS : has
    SOALS ||--o{ PASANGAN_MENJODOHKANS : has

    EXAMS ||--o{ UJIAN_SESIS : has
    PAKET_SOALS ||--o{ UJIAN_SESIS : uses
    UJIAN_SESIS ||--o{ JAWABAN_SISWAS : has
    SOALS ||--o{ JAWABAN_SISWAS : answered
```

## 2. ERD Infrastruktur Laravel

```mermaid
erDiagram
    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    SESSIONS {
        string id PK
        bigint user_id IDX
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    CACHE {
        string key PK
        mediumtext value
        int expiration
    }

    CACHE_LOCKS {
        string key PK
        string owner
        int expiration
    }

    JOBS {
        bigint id PK
        string queue
        longtext payload
        int attempts
        int reserved_at
        int available_at
        int created_at
    }

    JOB_BATCHES {
        string id PK
        string name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        mediumtext options
        int cancelled_at
        int created_at
        int finished_at
    }

    FAILED_JOBS {
        bigint id PK
        string uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }
```

## 3. Ringkasan Relasi Penting

### Relasi akun dan operasional

- `users` -> `audit_logs`
- `users` -> `chats` sebagai pengirim
- `users` -> `chats` sebagai penerima
- `users` -> `paket_soals` sebagai pembuat
- `users` -> `exams` sebagai pembuat
- `users` -> `personal_questions` sebagai pemilik
- `users` -> `global_questions` sebagai pembuat

### Relasi konten lama

- `materials` -> `questions`
- `questions` <-> `exams` lewat `exam_question`
- `exams` -> `participants` -> `participant_answers`

### Relasi konten baru TKA

- `jenjangs` -> `paket_soals`
- `paket_soals` -> `mapel_pakets`
- `mapel_pakets` -> `teks_bacaans`
- `mapel_pakets` -> `soals`
- `teks_bacaans` -> `soals`
- `soals` -> `pilihan_jawabans`
- `soals` -> `pasangan_menjodohkans`
- `exams` -> `ujian_sesis`
- `ujian_sesis` -> `jawaban_siswas`

## 4. Catatan Desain

- Schema baru TKA sudah lebih normal dan eksplisit untuk multi-mapel.
- Schema lama dan schema baru masih hidup bersamaan, jadi ERD ini sengaja menampilkan keduanya.
- Jika nanti kamu ingin, saya bisa lanjut bikin versi kedua:
  - `ERD-active-only.md` yang hanya berisi schema aktif produksi
  - atau `ERD.png` dari diagram ini dalam bentuk gambar

