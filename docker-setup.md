# Panduan Setup & Workflow Docker

Migrasi dari Laragon ke full Docker (development + serving publik via Cloudflare Tunnel). Lihat PRD: `Migrasi laragon to docker.md`.

## Prasyarat

- Docker Desktop untuk Windows dengan backend **WSL2**.
- Matikan Apache & MySQL Laragon agar port 80/3306 tidak bentrok (Docker memakai port 8080, 5173, 33061 — sebenarnya tidak bentrok, tapi Laragon MySQL di 3306 sengaja tidak dipakai Docker).
- Project harus berada di filesystem yang di-mount WSL2 (mis. `C:\Users\...` atau `D:\...` — bukan network drive).

## Layanan (docker-compose.yml)

| Service | Fungsi | Port host |
|---|---|---|
| `app` | PHP 8.3-FPM + ekstensi Laravel + Composer | — |
| `queue` | Worker `queue:work` (job WA Blast, dll.) | — |
| `webserver` | Nginx (Alpine), root `public/` | **8080** (`HTTP_PORT`) |
| `db` | MySQL 8.0 (database `tka-ujion`, user `ujion`, password `password`) | 33061 |
| `node` | Vite dev server (hot reload) | **5173** (`VITE_PORT`) |
| `cloudflared` | Cloudflare Tunnel (profile `tunnel`) | — |

Port webserver & Vite bisa di-override lewat variabel `HTTP_PORT` / `VITE_PORT` di `.env` (dibaca docker compose saat `up`). Di mesin ini `HTTP_PORT=8088` dan `VITE_PORT=5174` karena port default dipakai service lain.

Kredensial DB diatur di environment service `db` pada `docker-compose.yml` dan harus sama dengan `DB_*` di `.env`.

## Setup Pertama Kali

```bash
# 1. Siapkan .env untuk Docker
copy .env.docker .env

# 2. Generate APP_KEY (didalam container)
docker compose up -d
docker compose exec app php artisan key:generate

# 3. Migrasi + seed database
docker compose exec app php artisan migrate --seed

# 4. Storage link (agar upload bisa diakses via /storage)
docker compose exec app php artisan storage:link
```

Buka **http://localhost:8088** (sesuai `HTTP_PORT` di `.env`). Login superadmin: `superadmin@ujion.com` / `password`.

> Catatan: `composer install` (service `app`) dan `npm install` (service `node`) berjalan otomatis saat pertama kali start. `vendor/` dan `node_modules/` disimpan di named volume Docker (bukan folder Windows), jadi ringan dan cepat.

## Workflow Development Harian (branch `develop`)

```bash
docker compose up -d
```

- Edit kode di editor Windows (VS Code, dsb.) — bind mount langsung memetakan folder project ke container.
- Perubahan PHP langsung terlihat di http://localhost:8088 setelah refresh.
- Perubahan Vue/CSS/JS ter-compile **hot reload** via Vite di port 5174 (jangan akses 5174 langsung; buka 8088).

Setelah `git pull` / ganti branch yang mengubah dependensi:

```bash
docker compose exec app composer install
docker compose exec node npm install
```

Perintah artisan tetap seperti biasa, tapi dijalankan didalam container:

```bash
docker compose exec app php artisan test
docker compose exec app php artisan pint
docker compose exec app php artisan pail
```

Matikan semua: `docker compose down` (data database tetap aman di volume `db-data`).

## Menjalankan Mode Publik (branch `main`)

Saat siap mempublikasikan lewat domain (atau sekadar mematikan Vite dev server):

```bash
git checkout main
git pull origin main

docker compose down
docker compose up -d
docker compose stop node

# Build asset produksi (hasilnya public/build via bind mount)
docker compose run --rm node sh -c "npm install && npm run build"

# Pastikan tidak ada file "hot" (menyisa dari sesi dev) yang membuat asset 404
docker compose exec app sh -c "rm -f public/hot"

docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Sesuaikan `.env` untuk mode publik:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tka-ujion.com
SESSION_SECURE_COOKIE=true
GOOGLE_REDIRECT_URI=https://tka-ujion.com/auth/google/callback
PUSHER_HOST=tka-ujion.com
PUSHER_SCHEME=https
```

> Setelah mengubah `.env`, jalankan ulang `docker compose exec app php artisan config:cache` dan `docker compose restart app queue`.

> `APP_ENV=production` membuat Laravel menyajikan error 500 tanpa detail. Untuk staging-like, boleh pakai `APP_ENV=production` + `APP_DEBUG=true` sementara.

## Cloudflare Tunnel (akses publik https://tka-ujion.com)

Menggunakan token-based tunnel (remote-managed via dashboard, paling simpel):

1. Buka **Cloudflare Zero Trust** dashboard → Networks → Tunnels → **Create a tunnel** → pilih **Cloudflared** → beri nama (mis. `ujion-server`).
2. Salin **token** yang ditampilkan (bagian setelah `--token` pada perintah install).
3. Buat/edit `.env` (root project) atau file `.env` compose, tambahkan:
   ```env
   TUNNEL_TOKEN=eyJ....
   ```
   Docker Compose otomatis membaca variabel dari `.env` di root project.
4. Di dashboard tunnel, tambahkan **Public Hostname**:
   - Subdomain: (root / `@` atau `www`), Domain: `tka-ujion.com`
   - Service: `HTTP` → `webserver:80`
5. Jalankan tunnel:
   ```bash
   docker compose --profile tunnel up -d cloudflared
   ```
6. Cek: buka https://tka-ujion.com dari jaringan seluler.

Cloudflare Tunnel sudah menyediakan HTTPS (terminSSL di edge), jadi komputer lokal tidak perlu sertifikat sendiri. Pastikan mode publik (asset build) sudah dijalankan agar visitor publik tidak dialihkan ke Vite dev server `localhost:5173`.

## Backup & Restore Database

```bash
# Export
docker compose exec db sh -c "mysqldump -uroot -ppassword tka-ujion" > backup-ujion.sql

# Import
docker compose exec -T db sh -c "mysql -uroot -ppassword tka-ujion" < backup-ujion.sql
```

File upload user tersimpan di folder `storage/` project (bind mount) — ikut ter-backup bersama repo folder (pastikan `.gitignore` storage tetap).

## Troubleshooting

| Masalah | Solusi |
|---|---|
| Halaman blank / asset 404 setelah mode dev | `docker compose exec app sh -c "rm -f public/hot"` |
| `composer.lock`/`package-lock.json` berubah setelah pull | `docker compose exec app composer install` dan `docker compose exec node npm install` |
| Port 8080/5173/33061 bentrok | Ubah mapping port di `docker-compose.yml` |
| Container `queue` menunggu terus | Cek `docker compose logs app` (composer install gagal?) |
| Akses DB dari Windows (TablePlus/HeidiSQL) | Host `127.0.0.1`, port `33061`, user `ujion` / password `password` |
| `host.docker.internal` tidak resolve | Pastikan service punya `extra_hosts` (sudah ada) dan Docker Desktop versi terbaru |
| Reset total database | `docker compose down -v` lalu `docker compose up -d` + `migrate --seed` (**hapus semua data**) |
| Vite tidak connect dari browser | Pastikan port 5173 tidak diblokir firewall & service `node` jalan (`docker compose ps`) |

## Integrasi yang Perlu Diperhatikan

- **WhatsApp Gateway** (Node.js, port 3000) tetap berjalan di Windows host, bukan di Docker. Dari container diakses via `WA_GATEWAY_URL=http://host.docker.internal:3000`. Pastikan gateway bind `127.0.0.1` tetap aman dari luar.
- **Webhook Doku** untuk produksi wajib memakai domain publik: `https://tka-ujion.com/api/payments/doku/notification`.
- **Pusher** untuk chat realtime: host/port di `.env` (`PUSHER_*` + `VITE_PUSHER_*`) harus menyesuaikan domain saat mode publik.
- **Ziggy** menghasilkan URL dari `APP_URL` — setelah ubah `APP_URL`, jalankan `docker compose exec app php artisan ziggy:generate` jika file `resources/js/ziggy.js` perlu di-regenerate, lalu build ulang asset.
