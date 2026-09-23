# AGENTS.md

Panduan untuk AI agent yang bekerja di project **Ujion TKA**.

## Project Overview

Platform ujian terintegrasi berbasis Laravel 12 untuk tiga role: **superadmin**, **guru/operator**, dan **siswa****.

| Item | Value |
|---|---|
| Framework | Laravel 12 |
| PHP | ^8.3 |
| Database | MySQL 8.0+ (Docker service `db`) |
| Frontend | Inertia.js 2 + Vue 3 (app), Blade (halaman publik SEO), Vite 7, Tailwind CSS 4, Flowbite, KaTeX, Chart.js, Ziggy |
| Testing | PHPUnit 11 (SQLite in-memory) |
| Code Style | Laravel Pint (preset default) |
| Local Dev | Docker Compose (PHP-FPM + Nginx + MySQL 8 + Vite) — lihat `docker-setup.md` |

## Commands

```bash
# Docker (utama — semua service: app, webserver, db, queue, vite)
docker compose up -d                       # Start semua (dev, hot reload aktif)
docker compose down                        # Stop (data DB aman di volume)
docker compose ps                          # Status service

# Perintah artisan di dalam container
docker compose exec app php artisan test           # All tests (SQLite in-memory)
docker compose exec app php artisan pint           # Format PHP
docker compose exec app php artisan migrate --seed # Migrate + seed superadmin
docker compose exec app composer install           # Setelah composer.lock berubah
docker compose exec node npm install               # Setelah package-lock.json berubah

# Frontend
npm run dev                                # Vite watch (development, di container: service "node")
npm run build                              # Production build

# Run everything concurrently (legacy, tanpa Docker)
composer dev                                # server + queue + pail + vite
```

## Environment & Database

- Copy `.env.example` ke `.env`, lalu `php artisan key:generate`.
- Database: MySQL di Docker (service `db`, port host 33061, database `tka-ujion`, user `ujion`). Kredensial DB di `docker-compose.yml` harus sama dengan `DB_*` di `.env`.
- `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.
- `Schema::defaultStringLength(191)` di-set di `AppServiceProvider::boot()` untuk kompatibilitas MySQL utf8mb4 index length.
- Test environment otomatis pakai SQLite in-memory (lihat `phpunit.xml`).
- Superadmin seeder: `superadmin@ujion.com` / `password`.

## Arsitektur

### Roles & Auth

Tiga role dengan constant di `App\Models\User`:

- `User::ROLE_SUPERADMIN` — login via `/ngadumin/login` (email + password)
- `User::ROLE_GURU` — login via `/login` (nomor WhatsApp + access token) atau **Google OAuth** (`/auth/google`)
- `User::ROLE_SISWA` — login via `/siswa/login` (token ujian)

Status akun: `pending`, `active`, `suspend`.
Payment status: `awaiting_payment`, `submitted`, `approved`, `rejected`.

**Login Google (guru)** — `GoogleAuthController` (`/auth/google`, `/auth/google/callback`, `/auth/google/lengkapi-data`). Pakai `laravel/socialite`. Env: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (wajib sama dengan Authorized redirect URI di Google Cloud Console). Pencocokan akun by `email` atau `google_id` (kolom baru di `users`), role guru saja. Alur: akun aktif → langsung dashboard; pending → auto-login + dashboard (menu locked); belum ada → simpan data Google ke session `google_registration` → halaman "Lengkapi Data" (jenjang, satuan pendidikan, no WA) → buat akun pending + auto-login → bayar dari dashboard. Avatar Google disimpan di kolom `google_avatar` dan diprioritaskan di accessor `avatar_url`.

**Flow pembayaran (guru)** — setelah registrasi (manual/Google) guru pending **auto-login** dan masuk dashboard dengan menu terkunci (middleware `guru.active` hanya mengizinkan `guru.dashboard`, `guru.profile*`, `guru.chat*`, `guru.guide`; route lain → redirect dashboard + flash). Klik menu terkunci / tombol "Bayar Sekarang" (JS `core/doku-checkout.js`, config via `data-doku-config` di body layout guru) → checkout Doku terbuka di **window baru** (`window.open`) → halaman utama polling status tiap 3 detik → sukses → popup auto-close (postMessage `doku-payment-finished` + `window.close()`) → halaman utama redirect ke `payments/doku.finish` yang menampilkan token akses + menu terbuka. Batal → `callback_url_cancel` (`payments.doku.cancel`) → popup-close view. Halaman `pending-aktivasi` & resume dihapus — guru yang kehilangan session cukup daftar ulang dengan email/WA sama (deteksi akun pending → auto-login).

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
- **Landing**: `LandingContent`, `LandingFaq`, `LandingBranding`, `LandingHeroMockup`, `LandingClickLog`, `BlogPost`, `Testimonial`
- **Lain**: `AuditLog`, `AppSetting`, `Participant` (legacy), `ParticipantAnswer` (legacy)

### Policies

`ExamPolicy`, `GlobalQuestionPolicy`, `MaterialPolicy`, `PaketSoalPolicy`, `SoalPolicy`.
Custom Gate: `manage-mapel-soal` (superadmin atau guru per jenjang).

### Services

| Service | Tanggung jawab |
|---|---|
| `WhatsAppService` | Kirim WA via gateway (Node.js, port 3000) |
| `DokuService` | Klien API Doku (checkout URL, cek status, verifikasi signature webhook) |
| `PaymentApprovalService` | Approval/reject bukti pembayaran guru |
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

Arsitektur hybrid: **halaman aplikasi (auth, guru, superadmin, siswa/ujian) dirender Inertia.js + Vue 3**; **halaman publik SEO (landing, ujian-online, kisi-kisi, artikel, register-guru) + PDF/print/export + error pages tetap Blade**.

```
resources/
  css/app.css                     # Entry CSS (Tailwind 4)
  js/
    app.js                         # Entry Inertia (createInertiaApp + glob Pages)
    public.js                      # Entry untuk halaman Blade yang tersisa
    bootstrap.js                   # Axios setup, Echo, Pusher
    ziggy.js                       # Route config Ziggy (generate: php artisan ziggy:generate)
    Layouts/                       # GuestLayout, GuruLayout, SuperadminLayout, UjianLayout (Vue)
    Pages/                         # Halaman Inertia per modul (Auth/, Guru/, Siswa/, Superadmin/, Ujian/)
    Components/                    # Komponen Vue (Ui/, Guru/, Superadmin/)
    core/                          # Script global (legacy-init, katex, ssd, theme, doku-checkout, dsb)
    utils/                         # Helper reusable (copy-text, live-filter)
  views/
    app.blade.php                  # Root Inertia
    landing.blade.php              # Landing (SEO)
    layouts/                       # guest, public (Blade, untuk halaman yang tersisa)
    components/ui/                 # confirm-modal, flash (untuk halaman Blade tersisa)
    guru/material-practice/        # PDF views
    superadmin/exports/            # Print views
    ujian-online/, kisi-kisi/, artikel/, payments/, errors/
```

Pola penting:
- Controller mengembalikan `Inertia::render('Guru/Dashboard', [...])` — nama komponen cocok dengan path `resources/js/Pages/Guru/Dashboard.vue`.
- Shared props via `App\Http\Middleware\HandleInertiaRequests`: `auth.user`, `csrf_token`, `flash`, `status`, `guruLayout`, `superadminLayout`.
- `route()` di JS disediakan Ziggy (`ZiggyVue` plugin) — selalu tersedia di template & via `inject('route')` di script setup.
- Form: `useForm` untuk form biasa; form yang submitnya lewat confirm-modal (`data-confirm`) WAJIB native form + hidden `_token` (confirm modal memanggil `form.submit()` native).
- Perilaku DOM global (SSD, katex, flash countdown, action menus) di-init ulang per navigasi Inertia via `core/legacy-init.js`.
- Konvensi lengkap migrasi ada di `MIGRASI-INERTIA.md`.

## Code Conventions

- **Tidak ada komentar** di kode PHP/JS kecuali diminta.
- **PHP**: Laravel Pint preset default. Gunakan `composer pint` sebelum commit.
- **Naming**: PascalCase untuk class, camelCase untuk method/variable, snake_case untuk tabel/kolom DB.
- **Controller**: satu method per aksi, return `Inertia::render()` atau `redirect()`. Validasi di controller atau FormRequest.
- **Model**: define `$fillable`, `$casts`, `$hidden`. Constant untuk enum-like values.
- **Migration**: penamaan `YYYY_MM_DD_HHMMSS_deskripsi_table.php`.
- **Vue**: Composition API `<script setup>`; markup Tailwind disalin apa adanya dari desain; tanpa komentar.
- **Blade**: hanya untuk halaman publik SEO/PDF/print/error; layout `guest`/`public`.
- **JS**: modular per halaman, import via `Vite`. Hindari inline `<script>` di Blade.

## Database Notes

- **MySQL utf8mb4**: `Schema::defaultStringLength(191)` aktif. Kolom string default 191 char.
- **Composite unique keys** dengan banyak kolom VARCHAR: gunakan prefix index via `DB::statement` jika total melebihi 3072 bytes.
- **Schema legacy**: `participants`, `participant_answers`, `questions`, `exam_question` masih dipakai untuk modul tertentu. Schema aktif: `ujian_sesis`, `jawaban_siswas`.
- **Latihan materi**: pakai snapshot per token (bukan per siswa) agar konsisten dengan PDF.
- **Ujian menjodohkan**: payload ke client memakai key opaque (`App\Support\MatchingKey`, seed = session_token) — ID pasangan mentah TIDAK boleh dikirim ke browser (kunci jawaban tersirat). Urutan opsi di-seed per sesi agar stabil saat refresh.
- **Timer ujian**: mulai berjalan saat siswa pertama kali membuka halaman pengerjaan (`waktu_mulai`), bukan saat isi identitas — intended behavior. Sisa waktu selalu dihitung server-side dari `started_at`.
- **Sesi simulasi guru**: ditandai `ujian_sesis.user_id` terisi (siswa asli = NULL). Semua statistik hasil siswa WAJIB filter `whereNull('user_id')`.
- **SEO landing**: meta title/description landing dikelola superadmin via kolom `seo_title`/`seo_description` pada row `landing_contents` section `hero` (kosong = fallback ke nama aplikasi + judul/kicker hero). `robots.txt` di-serve dinamis oleh `RobotsController` (BUKAN file statis di `public/`) — daftar path privat yang di-disallow ada di konstanta `DISALLOWED_PATHS`. Semua layout privat (`guru`, `superadmin`, `ujian`, `guest`) mengirim `noindex,nofollow`; halaman guest yang boleh ter-index (misal register guru) opt-out via `@section('robots', 'index,follow')`.
- **Konten publik SEO**: dua modul halaman publik yang indexable — `/kisi-kisi/{jenjang}/{mapel}` (direktori topik dari tabel `materials`, termasuk kolom `link` sebagai referensi; slug mapel = `Str::slug(mapel)`) dan `/artikel/{slug}` (blog; model `BlogPost` dengan `getRouteKeyName() = 'slug'`, hanya `is_published = true` yang tampil publik). CRUD blog di superadmin `/superadmin/blog` (menu "Blog / Artikel"); konten ditulis **Markdown**, di-render via `Str::markdown`, distyling kelas `.article-content` di `app.css`. Keduanya memakai `layouts/public.blade.php` (index,follow + canonical + OG + BreadcrumbList/Article JSON-LD) dan otomatis masuk `sitemap.xml` — jika menambah halaman publik baru, tambahkan juga ke `SitemapController`.

## Testing

- PHPUnit 11, SQLite in-memory (otomatis via `phpunit.xml`).
- 12 feature test files mencakup: exam session, paket soal, payment flow, teacher token, superadmin access, chat cleanup, audit privacy, dll.
- Run: `php artisan test` atau `php artisan test --filter=NamaTestClass`.

## External Services

- **WhatsApp Gateway** (Node.js, `../WA_Gateway_v4`, port 3000) — kirim WA aktual. Harus running untuk fitur WA Blast.
- **Pusher** — realtime chat. Konfigurasi di `.env` (`PUSHER_*`).
- **Doku Payment Gateway** — kredensial (Client-Id `BRN-...`, Secret-Key `SK-...`) dikelola superadmin via menu Keuangan (disimpan di `app_settings`, bukan `.env`). Endpoint production `https://api.doku.com`. Webhook: `POST /api/payments/doku/notification` — **URL ini wajib didaftarkan sebagai Notification URL di dashboard Doku** saat deploy; di lokal (tanpa URL publik) flow tetap jalan via polling `GET /orders/v1/status/{invoice}`.

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
| `docker-setup.md` | Setup Docker, workflow dev/publik, Cloudflare Tunnel |
| `alur-soft-hosted.md` | Resume singkat menjalankan project + alur dev/publish |
| `upload-hosting.md` | Panduan deployment ke shared hosting/VPS |
| `konek.md` | Koneksi database & service |
| `implementasi-wa-gateway.md` | Implementasi WhatsApp Gateway |
| `perencanaan-wa-gateway.md` | Perencanaan WhatsApp Gateway |
| `step-wa-gateway.md` | Step-by-step WA Gateway |
| `resume.md` | Resume progress project |
