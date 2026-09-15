# Menjalankan Project di Perangkat Lain dengan Docker

Panduan memulai project **Ujion TKA** dari GitHub di komputer baru (Windows) menggunakan Docker Compose.

## Prasyarat

1. **Docker Desktop untuk Windows** dengan backend WSL2 — download di https://www.docker.com/products/docker-desktop/
2. **Git** terinstall.
3. **PHP/Composer/Node TIDAK diperlukan** — semua berjalan di dalam container.

> Port default yang dipakai: `8080` (web), `5173` (Vite), `33061` (MySQL). Jika port tersebut dipakai aplikasi lain, ubah lewat `.env` (lihat bagian [Ganti Port](#ganti-port-jika-bentrok)).

## 1. Clone Project

```bash
git clone https://github.com/AfrizalBMM/ujion-tka-apps.git
cd ujion-tka-apps
```

## 2. Siapkan File `.env`

File `.env` **tidak ikut ter-push** ke GitHub (berisi secret). Ada dua cara:

**Cara A — Copy dari perangkat lama (disarankan, kredensial tetap sama):**

Salin file `.env` dari perangkat lama ke root folder project (via flashdisk / secure transfer).

**Cara B — Mulai dari template:**

```bash
copy .env.docker .env
```

Lalu isi minimal yang berikut (lihat `.env` di perangkat lama untuk nilai sebenarnya):

```env
APP_KEY=            # kosong dulu, di-generate di langkah 4
GOOGLE_CLIENT_ID=...        # dari perangkat lama / Google Cloud Console
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
WA_WEBHOOK_KEY=...          # harus sama dengan konfigurasi WhatsApp Gateway
```

> Jika port 8080/5173 bentrok di komputer baru, tambahkan dulu `HTTP_PORT` / `VITE_PORT` di `.env` (lihat bagian bawah).

## 3. Jalankan Docker

```bash
docker compose up -d
```

Perintah pertama kali akan otomatis:
- Build image PHP 8.3-FPM (`ujion-app`)
- `composer install` (service `app`) — ke named volume `vendor`
- `npm install` (service `node`) — ke named volume `node_modules`
- Start MySQL 8, Nginx, queue worker, dan Vite dev server

> ⚠️ Start pertama memakan waktu (build image + install dependensi + warm-up Vite bisa 5–15 menit). Halaman **blank saat pertama kali dibuka adalah normal** — Vite masih warming. Tunggu ±1 menit setelah `docker compose logs node` menampilkan `VITE ready`, lalu hard refresh (Ctrl+F5).

Cek status semua service:

```bash
docker compose ps
```

Semua harus `Up` (db: `healthy`). Buka **http://localhost:8080** (atau port custom Anda).

## 4. Setup Database & Aplikasi (sekali saja)

```bash
# Generate APP_KEY (jika masih kosong)
docker compose exec app php artisan key:generate

# Migrasi + seed superadmin
docker compose exec app php artisan migrate --seed

# Link storage (agar upload tampil via /storage)
docker compose exec app php artisan storage:link
```

Login superadmin: `superadmin@ujion.com` / `password`

## 5. Verifikasi

| Cek | Cara |
|---|---|
| Web jalan | http://localhost:8080 tampil landing page |
| Hot reload | Edit file `.vue` → browser auto-update |
| Queue worker | `docker compose logs queue` — tidak ada error |
| Vite | `docker compose logs node` — `VITE ready` + `APP_URL: http://localhost:8080` |

## Ganti Port (jika bentrok)

Tambahkan di `.env` root project (dibaca docker compose saat `up`):

```env
HTTP_PORT=8088      # port webserver di host
VITE_PORT=5174      # port Vite di host
```

Sesuaikan juga `APP_URL` dan `GOOGLE_REDIRECT_URI` dengan port baru, lalu:

```bash
docker compose down
docker compose up -d
```

## Perintah Harian

```bash
docker compose up -d                          # start
docker compose down                           # stop (data DB aman)
docker compose ps                             # status
docker compose logs -f app                    # lihat log (app/db/node/queue/webserver)
docker compose exec app php artisan <cmd>     # artisan apa pun
docker compose exec app php artisan test      # run test suite
docker compose exec node npm install          # setelah package-lock.json berubah
docker compose exec app composer install      # setelah composer.lock berubah
```

## Restore Database dari Perangkat Lama (opsional)

Jika ingin membawa data dari perangkat lama, export dulu di sana:

```bash
docker compose exec db sh -c "mysqldump -uroot -ppassword tka-ujion" > backup-ujion.sql
```

Lalu di perangkat baru (setelah service jalan):

```bash
docker compose exec -T db sh -c "mysql -uroot -ppassword tka-ujion" < backup-ujion.sql
```

## Akses Database dari Windows

Gunakan TablePlus/HeidiSQL/MySQL Workbench:

| Setting | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `33061` |
| Database | `tka-ujion` |
| User | `ujion` |
| Password | `password` |

## Troubleshooting

| Masalah | Solusi |
|---|---|
| Halaman blank | Tunggu Vite warm-up (±1 menit), cek `docker compose logs node`, lalu Ctrl+F5 |
| Asset 404 setelah switch dev↔build | `docker compose exec app sh -c "rm -f public/hot"` |
| Port bentrok (`port is already allocated`) | Set `HTTP_PORT`/`VITE_PORT` di `.env`, `down` lalu `up -d` |
| `queue` restart terus | `docker compose logs queue` — biasanya migrate belum dijalankan |
| `node` restart (vite not found) | `docker compose run --rm node npm install` |
| Reset total database | `docker compose down -v` → `up -d` → `migrate --seed` (**semua data hilang**) |
| I/O lambat | Pastikan Docker Desktop pakai backend **WSL2**, project di drive lokal (bukan network drive) |

## Lanjutan

- Workflow develop/publish & Cloudflare Tunnel: lihat `alur-soft-hosted.md`
- Setup Docker lengkap: lihat `docker-setup.md`
