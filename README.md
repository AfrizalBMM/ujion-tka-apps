<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

# Ujion TKA - Platform Platform Ujian Terintegrasi

Ujion TKA adalah sistem manajemen ujian (LMS) modern yang dirancang untuk memfasilitasi ujian kompetensi guru dan siswa dengan fokus pada keamanan, kemudahan penggunaan, dan estetika premium.

---

## 🚀 Arsitektur & Teknologi

Sistem ini dibangun menggunakan fondasi teknologi terbaru:

- **Framework**: [Laravel 11.x](https://laravel.com)
- **Frontend Tools**: [Vite](https://vitejs.dev/) & [Tailwind CSS 4.0](https://tailwindcss.com)
- **Icons & UI**: FontAwesome 6 (Pro-grade setup) & Google Fonts (Inter)
- **Database**: MySQL/MariaDB
- **State Management**: Dual Session (traditional session-based for admins, token-persistent for examination)

---

## 🔐 Sistem Otentikasi Terpisah (Multi-Path Auth)

Sistem ini menggunakan alur login yang unik untuk setiap peran guna meningkatkan keamanan dan privasi:

1. **Superadmin (Ngadimin)**
   - **URL**: `/ngadimin/login`
   - **Metode**: Email & Password tradisional.
   - **Tujuan**: Mengelola ekosistem, kurikulum, dan aktivasi guru.

2. **Guru / Operator**
   - **URL**: `/login`
   - **Metode**: Nama Lengkap & **Token Akses** (di-generate otomatis setelah aktivasi).
   - **Alur**: Registrasi -> Menunggu Aktivasi Superadmin -> Mendapatkan Token via WhatsApp -> Login.

3. **Siswa**
   - **URL**: `/siswa/login`
   - **Metode**: Token Ujian khusus.
   - **Fitur**: Anti-cheat (blokir perpindahan tab) dan fullscreen examination.

---

## 🛠️ Modul Utama

### 🖥️ Superadmin Dashboard (Analytics Hub)
- **Analytics**: Grafik aktivitas harian dan statistik guru aktif.
- **Finance Module**: Manajemen QR Pembayaran dan Paket Harga (Pricing Plans).
- **User Management**: Aktivasi manual, suspend, dan rotasi token guru.
- **Master Data**: Kelola struktur materi (Kurikulum Merdeka/K-13).
- **Global Bank Soal**: Database soal pusat yang dapat di-import via CSV.
- **Audit Logs**: Rekaman jejak IP dan aktivitas untuk keamanan sistem.

### 👨‍🏫 Guru / Operator Dashboard
- **Personal Bank Soal**: Guru dapat menyusun bank soal sendiri dari materi yang tersedia.
- **Exam Builder**: Membuat jadwal dan mengatur durasi ujian.
- **Student Monitoring**: Memantau hasil dan progress siswa peserta ujian.

### ✍️ Siswa Examination Room
- **Fluid UI**: Desain minimalis untuk meminimalkan gangguan saat ujian.
- **Indikator Status**: Penomoran soal interaktif dengan status jawaban (ragu-ragu/selesai).
- **Anti-Cheat System**: Mendeteksi jika siswa mencoba membuka tab lain atau memperkecil jendela browser.

---

## 📂 Struktur Codebase

```text
app/
├── Http/Controllers/
│   ├── AuthController.php          # Handle Guru & Admin Auth
│   ├── Superadmin/                 # Modul khusus Admin
│   ├── Siswa/                      # Modul khusus Siswa
│   └── Guru/                       # Modul khusus Guru
├── Models/                         # User, Material, GlobalQuestion, AuditLog, etc.
database/
├── migrations/                     # Schema database yang ter-normalisasi
└── seeders/                        # SuperadminGuruSeeder untuk inisialisasi awal
resources/
├── css/app.css                     # Tailwind 4 configuration & Design System
├── views/
│   ├── auth/                       # Halaman login spesifik
│   ├── layouts/                    # Layout @vite (guest, superadmin, guru)
│   ├── superadmin/                 # View per-modul (finance, teachers, etc)
│   └── components/                 # UI components reusable (flash messages, etc)
```

---

## 🛠️ Instalasi & Pengembangan

1. **Clone & Setup**:
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Database & Seeding**:
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Run Dev Server**:
   ```bash
   php artisan serve
   # Di terminal terpisah
   npm run dev
   ```

---

## 📝 Catatan Pengembangan Terbaru
- [x] Migrasi Dashboard Superadmin ke sistem multi-halaman (Multi-page routing).
- [x] Implementasi Tailwind 4.0 Design System.
- [x] Integrasi Audit Logs untuk monitoring aktivitas sensitif.
- [x] Perbaikan pemuatan aset CSS menggunakan direktif `@vite`.

---
&copy; 2026 Ujion TKA Project. Built with ❤️ for Education.
