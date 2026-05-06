# Resume Project: WhatsApp Gateway v4

Berdasarkan analisis *codebase* pada direktori ini, berikut adalah penjelasan lengkap mengenai project ini:

## 1. Apa Project Ini?
Project ini adalah sebuah **WhatsApp Gateway API** atau server *microservice* berbasis Node.js. Aplikasi ini bertindak sebagai jembatan (gateway) antara sistem eksternal (seperti web CRM, toko online, sistem notifikasi) dengan WhatsApp. 

Project ini dibangun menggunakan *library* utama **`@whiskeysockets/baileys`**, yang merupakan *library* populer untuk menghubungkan dan berinteraksi dengan WhatsApp Web API secara langsung tanpa menggunakan *browser* internal (seperti Puppeteer), sehingga lebih ringan dan cepat.

## 2. Apa Fungsi Utama Project Ini?
Fungsi utama dari project ini adalah untuk mengotomatisasi komunikasi via WhatsApp, dengan fitur-fitur berikut:
- **Manajemen Multi-Sesi (Multi-Device):** Mendukung penggunaan banyak nomor WhatsApp sekaligus di dalam satu server. Masing-masing sesi dipisahkan berdasarkan ID unik (Sender ID).
- **Koneksi Real-time via WebSockets:** Menyediakan *QR Code* secara real-time ke klien (seperti *dashboard* web) menggunakan `socket.io`, sehingga pengguna dapat melakukan *scan* untuk *login* WhatsApp.
- **Mengirim Pesan Teks (REST API):** Menyediakan *endpoint* API untuk mengirim pesan teks biasa ke nomor WhatsApp tujuan.
- **Mengirim Pesan Media (REST API):** Menyediakan *endpoint* API untuk mengirim berbagai jenis media (Gambar, Video, Audio, Dokumen, Sticker) dengan mengambil media tersebut dari sebuah URL publik.
- **Menerima Pesan & Integrasi Webhook:** Secara otomatis mendengarkan pesan WhatsApp yang masuk dan meneruskannya (forward) ke URL *Webhook* yang telah ditentukan. Jika server webhook memberikan respons balasan (`msg`), aplikasi ini akan membalas otomatis (auto-reply) ke pengirim.
- **Auto-Restore Session:** Menyimpan status *login* sehingga jika server di-*restart*, aplikasi akan otomatis menghubungkan kembali (reconnect) nomor-nomor WhatsApp yang sebelumnya sudah *login* tanpa perlu melakukan *scan QR* lagi.

## 3. Bagaimana Cara Kerjanya?

Cara kerja sistem ini dapat dibagi menjadi beberapa alur utama:

### A. Alur Autentikasi / Login (Socket.IO)
1. Klien (contoh: *frontend* web) melakukan koneksi via Socket.IO ke server ini dan mengirimkan *event* `create-session` beserta ID (sebagai identitas nomor) dan URL Webhook opsional.
2. Server membuat sesi menggunakan Baileys (`makeWASocket`) dan menghasilkan data kredensial di *folder* `./session/baileys-{id}`.
3. Server memunculkan **QR Code**. Melalui Socket.IO (`io.emit`), QR Code ini dikirimkan ke klien (dalam bentuk Data URL / gambar Base64) untuk di-*scan* menggunakan HP pengguna.
4. Setelah *scan* berhasil, status koneksi berubah menjadi `open`. Sistem akan mengirimkan pesan `"Whatsapp is ready!"` ke *frontend* dan menandai ID tersebut dalam daftar sesi siap pakai (`whatsapp-sessions.json`).

### B. Alur Pengiriman Pesan Teks (API `/send-message`)
1. Sistem luar menembak HTTP POST ke `http://<domain>:3000/send-message` dengan *payload* JSON berisi `sender` (ID sesi yang sudah login), `number` (Nomor tujuan), dan `message` (isi pesan).
2. Sistem akan mengecek apakah ID sesi (sender) sedang aktif.
3. Nomor tujuan akan diformat secara otomatis oleh fungsi utilitas (mengubah format `08xxx` menjadi kode negara `628xxx` dan diubah ke format JID WhatsApp `@s.whatsapp.net`).
4. Server memanggil fungsi internal Baileys (`sock.sendMessage()`) untuk mengirim pesan. Jika sukses, mengembalikan response JSON status 200 (sukses).

### C. Alur Pengiriman Media (API `/send-media`)
1. Sama seperti pengiriman teks, namun *payload* JSON-nya mencakup `url` (link gambar/dokumen).
2. Server akan mengunduh media dari URL tersebut terlebih dahulu (menggunakan `axios.get` dalam bentuk `arraybuffer`).
3. Sistem mendeteksi ekstensi dan *mime-type* file tersebut (apakah ia *image*, *video*, *audio*, dsb.).
4. Media tersebut diubah menjadi format *buffer* dan dikirimkan via Baileys berserta *caption* (teks pendamping).

### D. Alur Penerimaan Pesan & Webhook (Chatbot Engine)
1. Setiap kali ada pesan teks atau media yang masuk ke nomor WhatsApp yang terkoneksi, *event listener* `messages.upsert` di Baileys akan terpicu.
2. Server mengambil teks yang dikirim pengirim dan nomor si pengirim.
3. Jika pada saat membuat sesi sebelumnya terdapat URL Webhook yang disimpan (di *file* `./session/webhook-{id}.json`), server akan mengirim HTTP POST ke URL Webhook tersebut berisi data pesan yang masuk (`from`, `message`, `device`).
4. **Auto Reply:** Jika Webhook dari *server* pengguna merespons dengan JSON `{ "success": true, "msg": "Teks balasan..." }`, maka Gateway ini akan langsung membalas chat tersebut secara otomatis ke pengirim pesan awal.

### E. Penyimpanan State & Konfigurasi
- **Port Server:** Berjalan pada Port `3000` secara *default*.
- **`whatsapp-sessions.json`:** *File* ini mencatat ID mana saja yang sedang aktif (`ready: true/false`). Digunakan saat server baru di-*restart* agar bisa mengaktifkan ulang koneksi secara otomatis melalui fungsi `restoreReadySessions()`.
- **Eksekusi:** Dijalankan dengan perintah bawaan Node.js (`node server.js`) atau *process manager* seperti PM2.

## Teknologi Utama yang Digunakan
- **Node.js:** *Environment runtime*.
- **Express.js:** *Framework* HTTP untuk membuat REST API.
- **@whiskeysockets/baileys:** *Library* core untuk komunikasi Web Socket dengan WhatsApp API.
- **Socket.io:** Untuk komunikasi 2 arah secara *real-time* ke sistem *frontend* (misal untuk QR code).
- **Axios:** Untuk melakukan permintaan ke API lain (mengunduh media URL dan mengirim data ke Webhook).
- **qrcode & mime-types:** Untuk pemrosesan kode QR dan deteksi tipe file.

---

## 4. Penyelarasan dengan Project Laravel (Ujion TKA)

Berdasarkan gambaran sistem ujian `Ujion TKA` yang Anda bangun dengan Laravel 12, Node.js WhatsApp Gateway ini sangat relevan dan dapat menutupi beberapa alur kerja agar lebih otomatis, yaitu:

### A. Otomatisasi Alur Registrasi Guru
- **Kondisi Saat Ini:** Guru mendaftar, *upload* bukti transfer bayar, lalu sistem hanya membuka link `wa.me` agar guru manual *chat* ke admin.
- **Solusi dengan Gateway:** Begitu guru mendaftar atau *upload* bukti bayar, Laravel dapat langsung menembak API Node.js ini untuk:
  1. Mengirim pesan notifikasi otomatis ke nomor Superadmin ("Ada guru baru mendaftar: [Nama]").
  2. Mengirim kode OTP atau pesan sambutan ke nomor guru ("Halo, pendaftaran Anda sedang diproses").
  3. Mengirim notifikasi akun aktif saat Superadmin mengklik "Approve".

### B. Otomatisasi Jadwal Baru & Pengingat Ujian
- **Kondisi Saat Ini:** Modul ujian dan latihan materi, di mana jadwal ujian harus dikomunikasikan secara manual kepada guru.
- **Solusi dengan Gateway:** Saat Superadmin me-*publish* jadwal ujian baru, Laravel otomatis mem-*blast* pesan berisi detail materi, hari, tanggal, dan jam ke WA seluruh guru secara *real-time*. Kemudian, H-1 sebelum ujian, sistem juga dapat mem-*blast* pesan pengingat ujian (menggunakan *Laravel Queue*) kepada peserta yang terdaftar tanpa membuat beban *loading* server web menjadi lambat.

### C. Notifikasi Pengiriman Hasil (Latihan Materi)
- **Kondisi Saat Ini:** Terdapat *endpoint* unduh PDF (`/guru/materials/{material}/latihan/paket/{paketNo}/pdf`) yang digenerate oleh `laravel-dompdf`.
- **Solusi dengan Gateway:** Setelah guru atau siswa menyelesaikan ujian/latihan materi, daripada membebani server dengan mengirim file PDF yang berat via WA, Laravel akan memanggil API `/send-message` pada Node.js Gateway untuk sekadar mengirimkan notifikasi teks berisi *Link Download* PDF tersebut. Guru lalu diarahkan untuk mengunduhnya secara langsung di *web* Ujion TKA.

### D. Interaktif via Live Chat
- **Kondisi Saat Ini:** Sistem memiliki fitur *live chat* guru dengan superadmin.
- **Solusi dengan Gateway:** Fitur Webhook (*chatbot engine*) pada Node.js ini dapat dihubungkan ke tabel chat Laravel Anda, sehingga guru bisa membalas *chat* Superadmin cukup lewat WhatsApp mereka, dan pesan itu otomatis masuk ke *dashboard* web Superadmin.

Secara teknis, **Gateway Node.js ini berdiri terpisah** dari *stack* `Laravel + Vite + Tailwind` Anda, sehingga ia aman dari error pada aplikasi utama, namun saling berkomunikasi menggunakan REST API di port lokal (`localhost:3000`).
