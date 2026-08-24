# AGENTS.md

Panduan untuk AI agent yang bekerja di project **Ujion TKA**.

## Project Overview

Platform ujian terintegrasi berbasis Laravel 12 untuk tiga role: **superadmin**, **guru/operator**, dan **siswa****.

| Item | Value |
|---|---|
| Framework | Laravel 12 |
| PHP | ^8.3 |
| Database | MySQL 8.0+ (via Laragon) |
| Frontend | Vite 7, Tailwind CSS 4, Flowbite, KaTeX, Chart.js |
| Testing | PHPUnit 11 (SQLite in-memory) |
| Code Style | Laravel Pint (preset default) |
| Local Dev | Laragon (Apache/MySQL), `php artisan serve` |

## Commands

```bash
# Dev server
php artisan serve                          # http://localhost:8000

# Frontend
npm run dev                                # Vite watch (development)
npm run build                              # Production build

# Database
php artisan migrate --seed                 # Migrate + seed superadmin
php artisan migrate:fresh --seed           # Full reset + seed

# Testing
php artisan test                           # All tests (SQLite in-memory)
php artisan test --filter=SiswaExamSessionTest  # Single test class

# Code quality
composer pint                              # Format PHP (Laravel Pint)

# Run everything concurrently
composer dev                                # server + queue + pail + vite
```

## Environment & Database

- Copy `.env.example` ke `.env`, lalu `php artisan key:generate`.
- Database: MySQL, host `127.0.0.1:3306`, user `root`, password kosong (Laragon default).
- `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.
- `Schema::defaultStringLength(191)` di-set di `AppServiceProvider::boot()` untuk kompatibilitas MySQL utf8mb4 index length.
- Test environment otomatis pakai SQLite in-memory (lihat `phpunit.xml`).
- Superadmin seeder: `superadmin@ujion.com` / `password`.

## Arsitektur

### Roles & Auth

Tiga role dengan constant di `App\Models\User`:

- `User::ROLE_SUPERADMIN` — login via `/ngadumin/login` (email + password)
- `User::ROLE_GURU` — login via `/login` (nomor WhatsApp + access token)
- `User::ROLE_SISWA` — login via `/siswa/login` (token ujian)

Status akun: `pending`, `active`, `suspend`.
Payment status: `awaiting_payment`, `submitted`, `approved`, `rejected`.

### Routes

| File | Scope |
|---|---|
| `routes/web.php` | Landing, auth, superadmin, siswa, payments |
| `routes/guru.php` | Semua route guru (loaded as web route group) |
| `routes/api.php` | API: landing-click, WA webhook |
| `routes/console.php` | Artisan commands |

Middleware aliases (defined in `bootstrap/app.php`):

- `audit` — `AuditRequest` (log aksi sensitif)
- `role` — `EnsureRole` (gate per role)
- `guru.active` — `EnsureGuruAccountIsActive`
- `guru.jenjang` — `EnsureGuruJenjangAccess`

### Controllers

```
app/Http/Controllers/
  AuthController.php              # Auth guru & superadmin
  RegisterGuruController.php      # Registrasi + aktivasi guru
  PaymentController.php           # Halaman pembayaran
  LandingController.php            # Landing page
  ChatImageController.php          # Serve gambar chat (shared)
  Concerns/
    ManagesSoalCrud.php            # Trait CRUD soal (dipakai superadmin & guru)
  Guru/                            # 14 controllers
  Siswa/                           # 4 controllers
  Superadmin/                      # 20 controllers
  Api/                             # LandingClick, Webhook
```

### Models (37)

Model utama per domain:

- **User & auth**: `User`, `Transaction`, `PricingPlan`
- **Paket soal**: `PaketSoal`, `MapelPaket`, `TeksBacaan`, `Soal`, `PilihanJawaban`, `PasanganMenjodohan`
- **Ujian**: `Exam`, `ExamMapelToken`, `UjianSesi`, `JawabanSiswa`
- **Bank soal**: `GlobalQuestion`, `PersonalQuestion`, `Question` (legacy)
- **Materi**: `Material`, `Jenjang`
- **Latihan materi**: `MaterialPracticeToken`, `MaterialTelaahQuestion`, `MaterialPracticePackage`, `MaterialPracticePackageQuestion`, `MaterialPracticeSession`, `MaterialTelaahAnswer`, `MaterialPracticePackageAttempt`, `MaterialPracticePackageAnswer`
- **Chat**: `Chat`
- **WhatsApp**: `WhatsAppLog`, `WaMessageTemplate`
- **Landing**: `LandingContent`, `LandingFaq`, `LandingBranding`, `LandingHeroMockup`, `LandingClickLog`
- **Lain**: `AuditLog`, `AppSetting`, `Participant` (legacy), `ParticipantAnswer` (legacy)

### Policies

`ExamPolicy`, `GlobalQuestionPolicy`, `MaterialPolicy`, `PaketSoalPolicy`, `SoalPolicy`.
Custom Gate: `manage-mapel-soal` (superadmin atau guru per jenjang).

### Services

| Service | Tanggung jawab |
|---|---|
| `WhatsAppService` | Kirim WA via gateway (Node.js, port 3000) |
| `QrisService` | Generate/parse QRIS dinamis dari GoPay master payload |
| `PaymentApprovalService` | Approval/reject bukti pembayaran guru |
| `PaymentProofStorage` | Simpan/ambil file bukti pembayaran |
| `WaMessageTemplateService` | Manage template pesan otomatis WA |

### Jobs

- `SendWhatsAppBlast` — queue job untuk kirim WA massal (async, random delay).

### Support (Helpers)

- `PhoneNumber` — normalisasi nomor WA
- `TokenGenerator` — generate token ujian & access token
- `SpreadsheetTable` / `SpreadsheetTemplateExporter` — export Excel template
- `SurveyAnalytics` — analisis hasil survei
- `GuruNotificationTemplates` — template notifikasi guru

### Console Commands

- `CleanupPaymentProofs` — hapus bukti pembayaran lama.

## Frontend Structure

```
resources/
  css/app.css                     # Entry CSS (Tailwind 4)
  js/
    app.js                         # Entry JS (Vite)
    bootstrap.js                   # Axios setup, Echo, Pusher
    superadmin.js                  # Superadmin-specific bundle
    ui.js                          # UI helpers
    core/                          # Script global (katex, layout, action-menus)
    utils/                         # Helper reusable (copy-text, live-filter)
    pages/                         # Script per-halaman (17 files)
  views/
    layouts/                       # guest, guru, superadmin, ujian
    components/ui/                 # confirm-modal, flash
    guru/                          # View guru
    superadmin/                    # View superadmin
    siswa/                         # View siswa
    ujian/                         # View ujian
    auth/                          # Login forms
    payments/                      # Payment views
    partials/                      # Partial views
```

Pola frontend: script halaman dipisah ke `resources/js/pages/`, helper ke `utils/`, global ke `core/`. Jangan numpuk script inline di Blade.

## Code Conventions

- **Tidak ada komentar** di kode PHP/JS kecuali diminta.
- **PHP**: Laravel Pint preset default. Gunakan `composer pint` sebelum commit.
- **Naming**: PascalCase untuk class, camelCase untuk method/variable, snake_case untuk tabel/kolom DB.
- **Controller**: satu method per aksi, return `view()` atau `redirect()`. Validasi di controller atau FormRequest.
- **Model**: define `$fillable`, `$casts`, `$hidden`. Constant untuk enum-like values.
- **Migration**: penamaan `YYYY_MM_DD_HHMMSS_deskripsi_table.php`.
- **Blade**: gunakan `@extends` / `@section` atau component. Layout per role.
- **JS**: modular per halaman, import via `Vite`. Hindari inline `<script>` di Blade.

## Database Notes

- **MySQL utf8mb4**: `Schema::defaultStringLength(191)` aktif. Kolom string default 191 char.
- **Composite unique keys** dengan banyak kolom VARCHAR: gunakan prefix index via `DB::statement` jika total melebihi 3072 bytes.
- **Schema legacy**: `participants`, `participant_answers`, `questions`, `exam_question` masih dipakai untuk modul tertentu. Schema aktif: `ujian_sesis`, `jawaban_siswas`.
- **Latihan materi**: pakai snapshot per token (bukan per siswa) agar konsisten dengan PDF.
- **Ujian menjodohkan**: payload ke client memakai key opaque (`App\Support\MatchingKey`, seed = session_token) — ID pasangan mentah TIDAK boleh dikirim ke browser (kunci jawaban tersirat). Urutan opsi di-seed per sesi agar stabil saat refresh.
- **Timer ujian**: mulai berjalan saat siswa pertama kali membuka halaman pengerjaan (`waktu_mulai`), bukan saat isi identitas — intended behavior. Sisa waktu selalu dihitung server-side dari `started_at`.
- **Sesi simulasi guru**: ditandai `ujian_sesis.user_id` terisi (siswa asli = NULL). Semua statistik hasil siswa WAJIB filter `whereNull('user_id')`.

## Testing

- PHPUnit 11, SQLite in-memory (otomatis via `phpunit.xml`).
- 12 feature test files mencakup: exam session, paket soal, payment flow, teacher token, superadmin access, chat cleanup, audit privacy, dll.
- Run: `php artisan test` atau `php artisan test --filter=NamaTestClass`.

## External Services

- **WhatsApp Gateway** (Node.js, `../WA_Gateway_v4`, port 3000) — kirim WA aktual. Harus running untuk fitur WA Blast.
- **Pusher** — realtime chat. Konfigurasi di `.env` (`PUSHER_*`).
- **GoPay QRIS** — `GOPAY_MASTER_PAYLOAD` di `.env` (raw string QRIS statis untuk inject nominal dinamis).

## Security

- **Jangan commit `.env`** atau file secrets. Hanya `.env.example` sebagai template.
- Aksi sensitif (hapus massal, import) dilindungi oleh **Policies** + **Gates**.
- `AuditRequest` middleware mencatat aksi sensitif ke tabel `audit_logs`.
- `APP_DEBUG=false` di production.
- Throttle pada route auth: `throttle:5,1` (login), `throttle:30,1` (check-email/wa).

## Deployment Checklist

```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set `APP_ENV=production`, `APP_DEBUG=false` di `.env` server.

Wajib juga di server production:
- `SESSION_SECURE_COOKIE=true` (HTTPS) — cookie session hanya dikirim via HTTPS.
- `WA_WEBHOOK_KEY` terisi string acak panjang dan sama dengan konfigurasi WhatsApp Gateway. Webhook menolak semua request jika key kosong.
- **WhatsApp Gateway harus localhost-only / di-belakang firewall** (bind 127.0.0.1, bukan 0.0.0.0) — endpoint gateway tidak punya auth sendiri; hanya Laravel yang boleh bisa mengaksesnya.
- Jalankan `php artisan config:cache` **setelah** semua env diisi (env yang dibaca setelah cache hanya via `config()`).

## File Dokumentasi Pendukung

| File | Isi |
|---|---|
| `README.md` | Overview project, stack, flow, modul |
| `jalan-local.md` | Panduan setup & menjalankan lokal |
| `upload-hosting.md` | Panduan deployment ke shared hosting/VPS |
| `konek.md` | Koneksi database & service |
| `implementasi-wa-gateway.md` | Implementasi WhatsApp Gateway |
| `perencanaan-wa-gateway.md` | Perencanaan WhatsApp Gateway |
| `step-wa-gateway.md` | Step-by-step WA Gateway |
| `resume.md` | Resume progress project |
