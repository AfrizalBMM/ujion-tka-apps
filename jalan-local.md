# Tutorial Menjalankan Ujion TKA Lokal

Panduan lengkap untuk setup dan menjalankan aplikasi **Ujion TKA** beserta **WhatsApp Gateway** di mesin lokal.

## Prasyarat

Pastikan sudah install:

- **PHP 8.3+** (dengan extensions: curl, json, mbstring, pdo, pdo_mysql)
- **Composer** (latest)
- **Node.js 16+** & **npm** (atau yarn)
- **MySQL / MariaDB** (atau SQLite untuk development)
- **Git** (opsional, untuk clone repo)

### Verifikasi Instalasi

```bash
php --version
composer --version
node --version
npm --version
mysql --version  # atau mariadb --version
```

## 1. Setup Database Lokal

### Opsi A: MySQL / MariaDB (Recommended)

**Buat database untuk aplikasi**:

```bash
mysql -u root -p
```

Di dalam MySQL prompt:

```sql
CREATE DATABASE ujion_tka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ujion_user'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON ujion_tka.* TO 'ujion_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Update `.env`** dengan credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ujion_tka
DB_USERNAME=ujion_user
DB_PASSWORD=password123
```

### Opsi B: SQLite (Lightweight, Untuk Testing)

SQLite sudah built-in di PHP. Edit `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/full/path/to/database.sqlite
```

Buat file SQLite (optional, Laravel buat otomatis):

```bash
touch database/database.sqlite
```

## 2. Setup Ujion TKA Project

### Clone atau Extract Project

**Jika menggunakan Git**:

```bash
cd C:\xampp\htdocs
git clone <repository-url> ujion-tka-apps
cd ujion-tka-apps
```

**Jika sudah punya folder**:

```bash
cd C:\xampp\htdocs\ujion-tka-apps
```

### Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### Setup Environment File

```bash
# Copy .env.example ke .env
cp .env.example .env
```

Edit file `.env` dan sesuaikan:

```env
APP_NAME="Ujion TKA"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=single

# Database (sesuaikan dengan setup di step 1)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ujion_tka
DB_USERNAME=ujion_user
DB_PASSWORD=password123

# Mail (untuk testing lokal, pakai 'array' atau MailHog)
MAIL_MAILER=array

# WhatsApp Gateway URL (akan aktif setelah setup gateway)
WA_GATEWAY_URL=http://localhost:3000
```

### Generate APP_KEY

```bash
php artisan key:generate
```

Output akan menunjukkan: `Application key set successfully.`

### Setup Storage Link

```bash
php artisan storage:link
```

Ini membuat symlink dari `public/storage` ke `storage/app/public`.

### Database Migration & Seeding

```bash
# Jalankan migration (membuat table)
php artisan migrate

# Opsional: Seeding data awal
php artisan db:seed
```

## 3. Menjalankan Laravel Project

Ada 2 cara untuk menjalankan dev server Laravel:

### Cara 1: Menggunakan Artisan Serve (Recommended)

```bash
cd C:\xampp\htdocs\ujion-tka-apps
php artisan serve
```

Output:

```
   INFO  Server running on http://127.0.0.1:8000.

  Press Ctrl+C to stop the server
```

Aplikasi akan berjalan di: **http://localhost:8000**

### Cara 2: Menggunakan XAMPP Apache (Opsional)

1. Copy folder `ujion-tka-apps` ke `C:\xampp\htdocs\`
2. Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf` dan tambahkan:

    ```apache
    <VirtualHost *:80>
        ServerName ujion-tka.local
        DocumentRoot "C:/xampp/htdocs/ujion-tka-apps/public"

        <Directory "C:/xampp/htdocs/ujion-tka-apps">
            Options +FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>
    ```

3. Edit `C:\Windows\System32\drivers\etc\hosts` dan tambahkan:

    ```
    127.0.0.1 ujion-tka.local
    ```

4. Restart Apache di XAMPP Control Panel
5. Akses: **http://ujion-tka.local**

### Build Frontend Assets

Setiap kali mengubah CSS/JavaScript, jalankan:

```bash
# Development build (dengan source maps)
npm run dev

# Production build (minified)
npm run build
```

### Watch Mode (untuk development)

Terminal terpisah, jalankan:

```bash
npm run dev
```

Ini akan auto-compile CSS/JS setiap kali ada perubahan.

## 4. Menjalankan WhatsApp Gateway

WhatsApp Gateway adalah service Node.js terpisah yang menangani pengiriman pesan WA.

### Setup Gateway

```bash
cd C:\xampp\htdocs\WA_Gateway_v4
npm install
```

### Konfigurasi Gateway

Edit file konfigurasi (biasanya `config.js` atau `.env`):

```js
// config.js atau similar
module.exports = {
    PORT: 3000,
    // Konfigurasi WhatsApp session, API credentials, dll
};
```

Konsultasikan dengan dokumentasi WA_Gateway_v4 untuk detail konfigurasi.

### Menjalankan Gateway

Di terminal terpisah:

```bash
cd C:\xampp\htdocs\WA_Gateway_v4
npm start
# atau
node server.js
```

Output akan menunjukkan:

```
Server running on http://localhost:3000
```

**PENTING**: Gateway harus berjalan agar fitur WhatsApp Blast bekerja.

## 5. Queue Worker (Untuk Background Jobs)

Ujion TKA menggunakan Laravel Queue untuk:

- Pengiriman email
- WhatsApp Blast (pengiriman WA asinkron)

### Menjalankan Queue Worker

Di terminal terpisah:

```bash
cd C:\xampp\htdocs\ujion-tka-apps
php artisan queue:work --queue=high,low
```

Output akan menunjukkan:

```
Processing jobs from the [high, low] queues.

[2026-05-09 10:30:15] Processing: App\Jobs\SendWhatsAppBlast
```

**Catatan**:

- Queue worker harus selalu berjalan di background
- Untuk stop: tekan `Ctrl+C`
- Jika ada error, lihat log di `storage/logs/`

### Queue Driver (Local Development)

Default di `.env` sudah diatur `QUEUE_CONNECTION=database`. Alternatif untuk development:

```env
# Synchronous (execute langsung, tanpa queue)
QUEUE_CONNECTION=sync

# Database (recommended untuk development)
QUEUE_CONNECTION=database

# Redis (jika sudah install redis)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 6. Terminal Setup (Recommended)

Untuk development yang smooth, buka **4 terminal terpisah**:

**Terminal 1: Laravel Dev Server**

```bash
cd C:\xampp\htdocs\ujion-tka-apps
php artisan serve
```

→ Berjalan di `http://localhost:8000`

**Terminal 2: Asset Watch (CSS/JS)**

```bash
cd C:\xampp\htdocs\ujion-tka-apps
npm run dev
```

→ Auto-compile saat ada perubahan di `resources/css/` atau `resources/js/`

**Terminal 3: Queue Worker**

```bash
cd C:\xampp\htdocs\ujion-tka-apps
php artisan queue:work --queue=high,low
```

→ Proses background jobs (email, WhatsApp)

**Terminal 4: WhatsApp Gateway**

```bash
cd C:\xampp\htdocs\WA_Gateway_v4
npm start
```

→ WhatsApp service pada port 3000

**Layout Optimal** (Windows Terminal):

```
┌─────────────────────────────────────────────┐
│ Tab 1: Laravel Serve | Tab 2: NPM Dev        │
├─────────────────────────────────────────────┤
│ Tab 3: Queue Worker  | Tab 4: WA Gateway     │
└─────────────────────────────────────────────┘
```

## 7. Akses Aplikasi

### Landing Page

```
http://localhost:8000/
```

### Login Superadmin

```
http://localhost:8000/ngadimin/login
```

Default credentials (jika seed data ada):

- Email: `admin@ujion.local`
- Password: `password`

Untuk membuat superadmin baru:

```bash
php artisan tinker
# Di dalam tinker:
> $user = new \App\Models\User(['name' => 'Admin', 'email' => 'admin@test.com', 'role' => 'superadmin']);
> $user->password = Hash::make('password123');
> $user->save();
> exit
```

### Login Guru

```
http://localhost:8000/login
```

Guru login menggunakan nomor WhatsApp + access token (buat dari superadmin area).

### Login Siswa

```
http://localhost:8000/siswa/login
```

Siswa login menggunakan token ujian.

## 8. Testing Features

### Test WhatsApp Blast

1. Pastikan **WA Gateway** dan **Queue Worker** sedang berjalan
2. Login sebagai Superadmin
3. Navigasi ke: `/superadmin/wa-blast`
4. Isi form:
    - Target: Semua guru aktif
    - Pesan: "Test message"
    - Jadwal: Kosongkan (kirim segera)
5. Klik **Jadwalkan Blast**
6. Lihat di Queue Worker terminal → job processing
7. Lihat di database → tabel `whatsapp_logs` atau monitoring dashboard

### Test Email Sending

1. Queue Worker harus berjalan
2. Trigger event yang mengirim email (e.g., guru registrasi)
3. Cek `storage/logs/` untuk debug
4. Atau gunakan config `MAIL_MAILER=array` untuk melihat email di storage

### Test Upload File

1. Guru upload bukti pembayaran atau soal gambar
2. Cek file di `storage/app/public/`
3. Pastikan accessible via `http://localhost:8000/storage/...`

### Check Database

```bash
php artisan tinker
# Di dalam tinker:
> \App\Models\User::count()
> \App\Models\WhatsAppLog::get()
> \App\Models\Exam::get()
```

## 9. Common Issues & Solutions

### Port 8000 Sudah Dipakai

```bash
# Gunakan port berbeda
php artisan serve --port=8001

# Atau cari proses yang pakai port 8000
netstat -ano | findstr :8000  # Windows
lsof -i :8000  # macOS/Linux
```

### Port 3000 (Gateway) Sudah Dipakai

```bash
# Kill process di port 3000
taskkill /PID <process-id> /F  # Windows
```

### Database Connection Error

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solusi**:

- Pastikan MySQL/MariaDB running
- Cek `.env` credentials (host, port, user, password)
- Jalankan test:

    ```bash
    php artisan tinker
    > DB::connection()->getPdo()
    ```

### Migration Stuck / Rollback Error

```bash
# Refresh database (bersihkan dan jalankan ulang)
php artisan migrate:refresh

# Atau full reset dengan seed
php artisan migrate:fresh --seed
```

### Node_modules Error / npm Build Fail

```bash
# Clear npm cache
npm cache clean --force

# Reinstall
rm -r node_modules
npm install

# Rebuild
npm run dev
```

### Storage Link Not Working

```bash
# Hapus symlink lama
rm public/storage

# Buat ulang
php artisan storage:link
```

### Queue Job Tidak Terproses

**Debug**:

```bash
# Check queue table
php artisan tinker
> \Illuminate\Queue\DatabaseQueue::all()

# Retry failed jobs
php artisan queue:retry all
```

## 10. Useful Artisan Commands

```bash
# Database
php artisan migrate              # Run migrations
php artisan migrate:refresh      # Refresh (reset + migrate)
php artisan migrate:fresh --seed # Full reset dengan seed
php artisan db:seed              # Seed database

# Cache & Config
php artisan cache:clear          # Clear app cache
php artisan view:clear           # Clear compiled views
php artisan config:clear         # Clear config cache

# Queue
php artisan queue:work           # Process queued jobs
php artisan queue:retry all      # Retry failed jobs

# Tinker (Interactive Shell)
php artisan tinker               # Debug console

# Routes
php artisan route:list           # List all routes

# Make Commands
php artisan make:model Model     # Generate model
php artisan make:migration       # Generate migration
php artisan make:controller      # Generate controller
php artisan make:job             # Generate job class
```

## 11. Development Tips

### Enable Query Logging (untuk debug)

Di `config/database.php` atau runtime:

```php
// Di route atau controller
DB::enableQueryLog();
// ... kode query ...
dd(DB::getQueryLog());
```

### Tinker untuk Testing

```bash
php artisan tinker
```

Contoh commands:

```php
# Create user
> $user = \App\Models\User::create(['name' => 'John', 'email' => 'john@test.com', 'password' => Hash::make('password')]);

# Query data
> \App\Models\Exam::with('paketSoal')->get();

# Update
> $exam = \App\Models\Exam::find(1);
> $exam->update(['title' => 'New Title']);

# Delete
> \App\Models\Exam::find(1)->delete();

# Exit
> exit
```

### Hot Reload (Frontend)

File CSS/JS akan auto-compile dengan `npm run dev` berjalan. Browser perlu refresh manual (atau install Live Reload extension).

### Logging

Cek log di:

```
storage/logs/laravel.log
```

Atau tail real-time:

```bash
# Windows PowerShell
Get-Content .\storage\logs\laravel.log -Tail 20 -Wait

# macOS/Linux
tail -f storage/logs/laravel.log
```

## 12. Production Checklist (Sebelum Deploy)

Sebelum upload ke server:

- [ ] Set `APP_DEBUG=false` di `.env`
- [ ] Set `APP_ENV=production` di `.env`
- [ ] Generate production build: `npm run build`
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`
- [ ] Optimize autoloader: `composer install --optimize-autoloader --no-dev`
- [ ] Database backup sebelum migrate
- [ ] Test semua features di local sebelum upload

Lihat [upload-hosting.md](upload-hosting.md) untuk detail deployment.

## 13. Next Steps

Setelah setup berhasil:

1. **Baca dokumentasi** di [README.md](README.md)
2. **Explore superadmin area** dan setup master data (jenjang, paket soal, materi)
3. **Test workflow** (guru registrasi → superadmin aktivasi → guru akses → siswa ujian)
4. **Setup WhatsApp Gateway** dengan proper config jika ada
5. **Backup database** secara berkala selama development

## Support & Resources

- **Laravel Docs**: https://laravel.com/docs
- **Laravel Queue**: https://laravel.com/docs/queues
- **Node.js Docs**: https://nodejs.org/docs/
- **MySQL Docs**: https://dev.mysql.com/doc/
- **Git**: https://git-scm.com/doc

Selamat coding! 🚀
