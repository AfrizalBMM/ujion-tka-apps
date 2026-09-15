PRD: Migrasi Total Lingkungan Pengembangan & Deployment dari Laragon ke Full Docker

1. Latar Belakang & Tujuan (Background & Objective)
   Latar Belakang: Project Laravel + Vue saat ini dikembangkan menggunakan Laragon. Untuk menghilangkan risiko environment mismatch (perbedaan versi/ekstensi antara lokal dan publik) serta menyeragamkan standar kerja modern, seluruh siklus project dipindahkan sepenuhnya ke Docker.

Tujuan (Objective):

Menjadikan Docker sebagai satu-satunya lingkungan kerja (development) sekaligus server publik lokal (production-like).

Memanfaatkan Cloudflare Tunnel untuk akses publik yang aman.

Menerapkan workflow Git branch develop (untuk ngoding harian di Docker) dan main (untuk publik).

2. Ruang Lingkup (Scope of Work)
   Full Containerization: Menyiapkan konfigurasi Docker Compose untuk menangani PHP, Nginx, MySQL, dan Node.js/Vite secara terisolasi.

Local Development Setup: Mengonfigurasi volume mapping agar perubahan kode di editor Windows langsung terasa di dalam container secara real-time (Hot Reload).

Database & Storage Isolation: Memindahkan total database dari Laragon ke MySQL Docker, serta memastikan folder storage persisten.

Public Exposure: Mengintegrasikan Nginx Docker dengan Cloudflare Tunnel agar domain bisa diakses publik.

3. Spesifikasi Teknis (Technical Requirements)
   OS Target: Windows (Wajib menggunakan Docker Desktop dengan backend WSL2 untuk performa I/O file yang optimal).

Tech Stack di Docker:

Web Server: Nginx (Alpine)

Backend: PHP 8.2+ (FPM)

Database: MySQL 8.0

Frontend Asset/Vite: Node.js (untuk proses build atau hot-reload)

Access Control: Cloudflare Tunnel (cloudflared)

4. Alur Kerja Git & Development Workflow
   Development (Branch develop):

Programmer melakukan coding di branch develop.

Docker dijalankan secara lokal (docker compose up -d), di mana file kode di Windows terhubung (bind mount) ke dalam container, sehingga perubahan langsung terlihat.

Deployment ke Publik (Branch main):

Setelah fitur matang di develop, lakukan merge ke branch main.

Di komputer server lokal, lakukan pembaruan:

Bash
git checkout main
git pull origin main
docker compose down
docker compose up -d --build 5. Daftar Tugas Pengerjaan (Task Breakdown / Checklist)
Tahap 1: Pembersihan & Persiapan
[ ] Mematikan layanan Laragon untuk project ini (agar port 80/3306 tidak bentrok).

[ ] Membuat file Dockerfile untuk PHP dan docker-compose.yml di root project.

[ ] Menyiapkan konfigurasi Nginx (default.conf) yang mengarah ke folder /var/www/public.

Tahap 2: Konfigurasi Docker Compose (Multi-Service)
[ ] Mengatur service app (PHP-FPM) dengan ekstensi Laravel lengkap.

[ ] Mengatur service webserver (Nginx) dan menghubungkannya dengan port publik/lokal.

[ ] Mengatur service db (MySQL 8.0) dengan named volume agar data tidak hilang saat container direstart.

[ ] Mengatur service node (Opsional) untuk menjalankan npm run dev via Docker.

Tahap 3: Penyesuaian Project (.env & Database)
[ ] Menyesuaikan koneksi database di .env:

Cuplikan kode
DB_HOST=db
DB_DATABASE=nama_db
DB_USERNAME=root
DB_PASSWORD=password_anda
[ ] Export data dari Laragon dan import ke container MySQL Docker.

Tahap 4: Setup Akses Publik (Cloudflare Tunnel)
[ ] Install dan konfigurasi cloudflared di Windows.

[ ] Menghubungkan domain utama ke port Nginx yang berjalan di Docker.

6. Kriteria Keberhasilan (Acceptance Criteria)
   [ ] Seluruh project dapat menyala utuh hanya dengan satu perintah: docker compose up -d.

[ ] Perubahan kode PHP/Vue di editor lokal langsung merespons di browser (localhost atau domain publik).

[ ] Proses git pull dari branch main memperbarui aplikasi publik secara mulus tanpa error dependensi.
