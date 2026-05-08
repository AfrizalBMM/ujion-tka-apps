# Tutorial Upload Ujion TKA ke Shared Hosting

Panduan lengkap untuk mem-deploy aplikasi Laravel Ujion TKA ke shared hosting (cPanel).

## Persiapan Awal

### 1. Siapkan File di Lokal

Sebelum upload, pastikan:

```bash
# Bersihkan cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Generate production build
npm run build

# Install dependency tanpa dev
composer install --optimize-autoloader --no-dev
```

### 2. Buat File Folder/Archive

Disarankan membuat archive untuk upload lebih cepat:

```bash
# Hapus folder yang tidak perlu diunggah
rm -rf node_modules/ tests/ storage/logs/*
rm -rf .git .github .env.example

# Buat archive (zip)
# Windows: Klik kanan > Send to > Compressed folder
# Atau pakai PowerShell:
Compress-Archive -Path . -DestinationPath ujion-tka.zip -Exclude @('node_modules','tests','.git','.github','storage/logs/*')
```

## Langkah-Langkah Upload

### 3. Akses File Manager / FTP

**Opsi A: File Manager cPanel (Recommended)**

1. Login ke cPanel (https://yourdomain.com:2083)
2. Cari menu **File Manager**
3. Navigasi ke folder **public_html** (atau subfolder jika ingin di subdomain)
4. Upload file `ujion-tka.zip` ke sana

**Opsi B: FTP (FileZilla / WinSCP)**

1. Buka FileZilla atau WinSCP
2. Koneksi dengan:
    - Host: `ftp.yourdomain.com` atau `yourdomain.com`
    - Username: FTP username dari cPanel
    - Password: FTP password dari cPanel
    - Port: 21 (FTP) atau 22 (SFTP)
3. Navigasi ke `public_html` di remote server
4. Upload folder `ujion-tka` atau file `ujion-tka.zip` ke sana

### 4. Extract Archive di Server

**Jika Upload File ZIP**:

1. Di File Manager cPanel, klik kanan file `ujion-tka.zip`
2. Pilih **Extract** → Extract to current folder
3. Tunggu proses selesai
4. Hapus file `ujion-tka.zip` yang sudah di-extract

**Jika Upload Folder**:

Pastikan struktur final di `public_html` seperti:

```
public_html/
├── ujion-tka/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   ├── index.php
│   │   └── ...
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── composer.json
│   ├── .env
│   └── ...
```

Atau jika langsung di `public_html` (tanpa subfolder):

```
public_html/
├── app/
├── bootstrap/
├── config/
├── ...
├── public/
│   ├── index.php
│   └── ...
├── vendor/
├── .env
└── ...
```

### 5. Setup File .env

1. Di File Manager, navigasi ke folder aplikasi utama
2. Temukan atau buat file `.env` (jika tidak ada, copy dari `.env.example`)
3. Edit file `.env` dengan konfigurasi hosting Anda:

```env
APP_NAME="Ujion TKA"
APP_ENV=production
APP_KEY=base64:xxxxxx... # JANGAN ULANGI dari lokal, buat baru di server
APP_DEBUG=false
APP_URL=https://yourdomain.com/ujion-tka # atau https://yourdomain.com jika di root

LOG_CHANNEL=stack

# Database (sesuaikan dengan database hosting)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Email
MAIL_MAILER=smtp
MAIL_HOST=your.smtp.server
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Opsional - Payment Gateway (QRIS)
GOPAY_MASTER_PAYLOAD=your_qris_payload_here
QRIS_ADMIN_WHATSAPP=62xxxxx # nomor admin dengan kode negara

# Opsional - WhatsApp Gateway
# Sesuaikan dengan URL gateway Anda
WA_GATEWAY_URL=http://your-gateway-server:3000
```

**PENTING**:

- `APP_KEY` harus di-generate baru di server, bukan copy dari lokal
- Jangan commit `.env` ke repository
- Setup database sebelum melanjutkan

### 6. Generate APP_KEY Baru

Jika menggunakan SSH/Terminal di cPanel (Advanced Users):

```bash
cd /home/username/public_html/ujion-tka
php artisan key:generate --force
```

Jika tidak ada akses SSH, skip ini dulu dan ikuti langkah 7.

### 7. Setup Database

**Opsi A: cPanel - MySQL/MariaDB Management**

1. Login cPanel
2. Cari menu **MySQL Databases** atau **MariaDB Database Manager**
3. Buat database baru:
    - Database Name: `yourusername_ujion_tka` (biasanya auto-prefixed)
    - Catat nama database lengkap
4. Buat user baru atau gunakan user yang ada:
    - Username: `yourusername_ujion_user`
    - Password: (generate kuat)
5. Assign user ke database dengan privilege **ALL PRIVILEGES**
6. Update `.env` dengan database credentials yang baru

**Opsi B: Upload Database Dump (Jika Ada Data Existing)**

1. Export database dari lokal:
    ```bash
    mysqldump -u root -p ujion_tka > ujion_tka_dump.sql
    ```
2. Upload file `ujion_tka_dump.sql` ke server via FTP/File Manager
3. Di cPanel, buka **phpMyAdmin**
4. Pilih database target
5. Tab **Import** → Upload file `ujion_tka_dump.sql`
6. Klik **Import**

### 8. Jalankan Migration

**Jika Ada SSH Access**:

```bash
cd /home/username/public_html/ujion-tka
php artisan migrate --force
# Opsional: Seed data awal
php artisan db:seed --force
```

**Jika Tidak Ada SSH Access**:

1. Buat file route sementara di `routes/web.php` atau buat file baru `migrate.php` di root:

    ```php
    // File: public/migrate.php (temporary)
    <?php
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

    $status = $kernel->handle(
        $input = new \Symfony\Component\Console\Input\ArrayInput(['command' => 'migrate', '--force' => true]),
        new \Symfony\Component\Console\Output\BufferedOutput
    );

    echo "Migration complete!";
    ```

2. Akses di browser: `https://yourdomain.com/migrate.php`
3. Hapus file `migrate.php` setelah selesai

### 9. Setup Storage Link (PENTING)

File upload guru (bukti pembayaran, gambar soal) memerlukan symlink storage.

**Jika Ada SSH Access**:

```bash
cd /home/username/public_html/ujion-tka
php artisan storage:link
```

**Jika Tidak Ada SSH Access**:

1. Buat file symlink manual via File Manager:
    - Masuk ke folder `public_html/ujion-tka/public`
    - Buat folder `storage` (jika belum ada)
    - Via SSH atau terminal hosting:
        ```bash
        ln -s ../storage/app/public storage
        ```

2. Atau gunakan alternative route di `routes/web.php`:

    ```php
    Route::get('/storage/{path}', function ($path) {
        $file = storage_path('app/public/' . $path);
        if (!file_exists($file)) {
            abort(404);
        }
        return response()->file($file);
    })->where('path', '.*');
    ```

### 10. Konfigurasi Web Server (Important)

Edit file `.htaccess` di folder aplikasi atau contact hosting support.

**Lokasi: `public_html/ujion-tka/.htaccess`** (jika di subfolder):

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews
    </IfModule>

    RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ public/index.php [QSA,L]
</IfModule>
```

**Atau Setup Public Folder sebagai Document Root** (Recommended):

Jika hosting memungkinkan, set document root ke `public_html/ujion-tka/public` atau `public_html/public` (jika di root).

Hubungi hosting support untuk mengubah document root di cPanel.

### 11. Optimasi File di Production

1. Clear Cache:

    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

    Jika tidak ada SSH, buat file `clear-cache.php` di root dan akses via browser (lalu hapus file tersebut).

2. Periksa Folder Permission:

    Folder berikut harus writable (744 atau 755):
    - `storage/`
    - `bootstrap/cache/`
    - `public/storage/` (symlink)

    Di File Manager cPanel:
    - Klik kanan folder → **Change Permissions**
    - Set ke `755` untuk folder, `644` untuk file

## Testing & Verifikasi

### Buka Aplikasi di Browser

1. Akses: `https://yourdomain.com/ujion-tka` (atau `https://yourdomain.com` jika di root)
2. Seharusnya muncul landing page
3. Test login:
    - Superadmin: `/ngadimin/login`
    - Guru: `/login` (dengan nama + token dari database)
    - Siswa: `/siswa/login` (dengan token ujian)

### Debug Jika Ada Error

**Jika 500 Error / Blank Page**:

1. Cek error log di `storage/logs/`
2. Edit `config/app.php` temporary ubah `APP_DEBUG=true` (jangan lupa balik ke false)
3. Cek file permission (storage, bootstrap/cache harus 755)
4. Cek database connection di `.env`

**Jika Folder Upload Tidak Bekerja (404)**:

1. Pastikan `php artisan storage:link` sudah dijalankan
2. Pastikan folder `storage/app/public/` ada dan writable
3. Cek path di view: `/storage/...` harus mengarah ke `public/storage/...`

**Jika Queue Tidak Bekerja (Email/WhatsApp Tidak Terkirim)**:

Shared hosting biasanya tidak support queue background job. Alternative:

1. Setup dengan CRON job scheduler:

    ```bash
    * * * * * cd /home/username/public_html/ujion-tka && php artisan schedule:run >> /dev/null 2>&1
    ```

2. Atau ubah `config/queue.php`:
    ```php
    'default' => 'sync', // Ubah dari 'database' ke 'sync' untuk execution langsung
    ```

## WhatsApp Blast & External Gateway (Optional)

Jika ingin menggunakan fitur WhatsApp Blast dengan gateway eksternal:

1. **WhatsApp Gateway** harus berjalan di server terpisah atau VPS (tidak bisa di shared hosting)
2. Ubah `.env` dengan URL gateway:

    ```env
    WA_GATEWAY_URL=http://your-gateway-server:3000
    ```

3. Setup queue worker di VPS gateway untuk pengiriman

## Post-Deployment Checklist

- [ ] Database migrate berhasil
- [ ] File upload bisa diakses (test upload bukti pembayaran guru)
- [ ] Landing page loading normal
- [ ] Login semua role bisa diakses
- [ ] Email/notifikasi dikirim
- [ ] Queue job berjalan (atau ubah ke sync)
- [ ] Storage symlink working
- [ ] `.env` sudah dikonfigurasi dengan production values
- [ ] `APP_DEBUG=false` di production
- [ ] HTTPS aktif (SSL certificate)
- [ ] Backup database & file teratur

## Troubleshooting Common Issues

### 1. "Call to undefined function" errors

**Solusi**: Jalankan `composer install` di server

```bash
cd /home/username/public_html/ujion-tka
composer install --no-dev --optimize-autoloader
```

### 2. Laravel Routes Not Working (404 errors)

**Solusi**:

- Pastikan `.htaccess` sudah setup di root aplikasi
- Atau ubah `.env`: `APP_URL=https://yourdomain.com/ujion-tka/public` (jika document root tidak di-set ke folder public)

### 3. File Upload / Storage Not Found (404)

**Solusi**:

- Jalankan `php artisan storage:link`
- Atau buat symlink manual: `ln -s ../storage/app/public storage`

### 4. Memory Limit / Timeout Error

**Solusi**: Hubungi hosting, minta edit `.htaccess`:

```apache
php_value memory_limit 256M
php_value max_execution_time 300
```

### 5. Database Connection Error

**Solusi**:

- Periksa `.env` DB credentials
- Pastikan database & user sudah dibuat di cPanel
- Test connection: `php artisan tinker` → `DB::connection()->getPdo()`

## Update & Maintenance

### Update Aplikasi

1. Download versi terbaru dari repository
2. Upload file yang berubah (atau ulang langkah 3-5)
3. Jalankan migration jika ada:
    ```bash
    php artisan migrate --force
    ```
4. Clear cache:
    ```bash
    php artisan cache:clear
    php artisan view:clear
    ```

### Backup Reguler

1. **Database Backup** (via cPanel phpMyAdmin atau mysqldump):

    ```bash
    mysqldump -h localhost -u user -p database_name > backup_$(date +%Y%m%d).sql
    ```

2. **File Backup** (via cPanel File Manager atau FTP):
    - Archive folder `ujion-tka` secara berkala
    - Download ke storage lokal

3. **Storage Backup** (upload files dari guru/siswa):
    - Download folder `storage/app/public/` secara berkala

## Kontak & Support

Jika ada pertanyaan atau masalah:

- Dokumentasi: Baca README.md di root aplikasi
- Laravel Docs: https://laravel.com/docs
- Shared Hosting Help: Hubungi support team hosting Anda
- Database Issues: Cek log di cPanel atau phpMyAdmin
