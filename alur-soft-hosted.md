# Alur Menjalankan Project (Soft-Hosted via Docker + Cloudflare Tunnel)

Resume singkat dari `docker-setup.md` — untuk detail lengkap lihat file tersebut.

## Menjalankan (Development)

```bash
docker compose up -d
```

Semua service naik: PHP-FPM (`app`), `queue` worker, Nginx (`webserver`), MySQL (`db`), Vite (`node`).

- Aplikasi: **http://localhost:8088** (port dari `HTTP_PORT` di `.env`)
- Hot reload aktif — edit kode di editor Windows, langsung terlihat setelah refresh
- Superadmin: `superadmin@ujion.com` / `password`
- Matikan: `docker compose down` (data DB aman di volume)

Perintah artisan tetap seperti biasa, tapi di dalam container:

```bash
docker compose exec app php artisan <command>
```

## Alur Kerja Harian

1. **Coding di branch `develop`** — `docker compose up -d` sekali, lalu ngoding biasa.
2. **Setelah `git pull` / dependensi berubah:**
   ```bash
   docker compose exec app composer install    # jika composer.lock berubah
   docker compose exec node npm install        # jika package-lock.json berubah
   ```
3. **Commit & push ke `develop`**, lalu merge PR ke `main` saat fitur matang.

## Alur Publish (main → publik)

Di komputer server:

```bash
git checkout main
git pull origin main

docker compose down
docker compose up -d
docker compose stop node

# Build asset produksi + bersihkan hot file
docker compose run --rm node sh -c "npm install && npm run build"
docker compose exec app sh -c "rm -f public/hot"

docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Sebelum publish, pastikan `.env` mode publik:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tka-ujion.com
SESSION_SECURE_COOKIE=true
GOOGLE_REDIRECT_URI=https://tka-ujion.com/auth/google/callback
PUSHER_HOST=tka-ujion.com
PUSHER_SCHEME=https
```

Jalankan ulang `config:cache` + `docker compose restart app queue` setelah ubah `.env`.

## Cloudflare Tunnel (akses publik https://tka-ujion.com)

Tunnel berjalan sebagai service Docker (profile `tunnel`):

1. Cloudflare Zero Trust → Networks → Tunnels → **Create a tunnel** → salin **token**.
2. Tambah `TUNNEL_TOKEN=eyJ...` di `.env` root project.
3. Public Hostname di dashboard: Domain `tka-ujion.com` → Service `HTTP` → `webserver:80`.
4. Jalankan:
   ```bash
   docker compose --profile tunnel up -d cloudflared
   ```
5. Cek dari jaringan seluler: https://tka-ujion.com

## Cheat Sheet

| Aksi | Perintah |
|---|---|
| Start semua | `docker compose up -d` |
| Stop | `docker compose down` |
| Status | `docker compose ps` |
| Log service | `docker compose logs -f app` (atau `db`/`node`/`queue`/`webserver`) |
| Test | `docker compose exec app php artisan test` |
| Reset DB total | `docker compose down -v` lalu `up -d` + `migrate --seed` (hapus semua data!) |
| Backup DB | `docker compose exec db sh -c "mysqldump -uroot -ppassword tka-ujion" > backup.sql` |

## Port di Mesin Ini

| Port host | Service | Catatan |
|---|---|---|
| 8088 | Nginx (`HTTP_PORT`) | 8080 dipakai `AgentService` |
| 5174 | Vite (`VITE_PORT`) | 5173 dipakai project Docker lain |
| 33061 | MySQL (`db`) | Akses dari Windows: `127.0.0.1:33061`, user `ujion` / `password` |

## Perhatian

- **WhatsApp Gateway** (Node.js port 3000) berjalan di host Windows, bukan Docker. Periksa port 3000 — di mesin ini dipakai container `docker_bis-workspace-1`, jadi pastikan gateway jalan di port lain atau container itu dihentikan.
- **Webhook Doku** produksi: `https://tka-ujion.com/api/payments/doku/notification` — wajib didaftarkan di dashboard Doku.
- Jangan jalankan mode dev (service `node`) saat domain publik aktif — visitor akan dialihkan ke Vite `localhost:5174`.
