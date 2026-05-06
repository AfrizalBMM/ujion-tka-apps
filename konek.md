# Panduan Integrasi Laravel (TKA Ujion Guru) dengan WhatsApp Gateway

Dokumen ini berisi spesifikasi teknis (PRD) dan panduan kode untuk mengintegrasikan sistem **Ujian TKA (Tes Kompetensi Akademik) Guru** berbasis Laravel dengan **Node.js WhatsApp Gateway**.

---

## 1. Persiapan Environment (Laravel `.env`)

Tambahkan variabel berikut di file `.env` Laravel Anda agar URL dan ID Pengirim (Sender) tidak _hardcoded_ dan mudah diubah.

```env
# URL Node.js WA Gateway (Ubah ke domain asli saat di production)
WA_GATEWAY_URL="http://127.0.0.1:3000"

# ID Sesi / Nomor Pengirim yang aktif (Bisa nama unik misal: tka-admin)
WA_SENDER_ID="tka-admin"
```

---

## 2. Pembuatan Service Class (WhatsAppService)

Buat sebuah file service untuk menangani semua panggilan API ke WA Gateway agar kode lebih rapi dan bisa digunakan ulang di berbagai Controller.

Buat file: `app/Services/WhatsAppService.php`

```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;
    protected $senderId;

    public function __construct()
    {
        $this->baseUrl = env('WA_GATEWAY_URL', 'http://127.0.0.1:3000');
        $this->senderId = env('WA_SENDER_ID', 'admin');
    }

    /**
     * Mengirim pesan teks ke nomor tertentu
     */
    public function sendMessage($number, $message)
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/send-message", [
                'sender'  => $this->senderId,
                'number'  => $number,
                'message' => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WA Gateway Error (sendMessage): ' . $e->getMessage());
            return ['status' => false, 'response' => $e->getMessage()];
        }
    }

    /**
     * Mengirim media (PDF/Gambar) ke nomor tertentu
     */
    public function sendMedia($number, $url, $caption = '')
    {
        try {
            $response = Http::timeout(20)->post("{$this->baseUrl}/send-media", [
                'sender'  => $this->senderId,
                'number'  => $number,
                'url'     => $url,
                'message' => $caption,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WA Gateway Error (sendMedia): ' . $e->getMessage());
            return ['status' => false, 'response' => $e->getMessage()];
        }
    }
}
```

---

## 3. Implementasi Fitur / Skenario Penggunaan Sesuai Flow Ujion

### Skenario A: Notifikasi Aktivasi Registrasi Guru

Sesuai _Flow Aktif Utama_ di project Anda, guru mendaftar, masuk antrean pending, lalu _upload_ bukti bayar QRIS. Sistem lama menggunakan link `wa.me`, kini diganti menjadi notifikasi otomatis via API.

**Controller Pembayaran (`FinanceController.php` atau sejenisnya):**

```php
use App\Services\WhatsAppService;

public function uploadBuktiBayar(Request $request, WhatsAppService $waService)
{
    // 1. Validasi & Upload File
    $guru = Auth::user();
    // ... logika upload bukti ...

    // 2. Kirim Notifikasi ke Superadmin
    $nomorAdmin = env('QRIS_ADMIN_WHATSAPP', '081234567890');
    $pesanAdmin = "⚠️ *Pendaftaran Baru*\n\nGuru bernama {$guru->name} (Jenjang: {$guru->jenjang}) telah mengunggah bukti pembayaran aktivasi akun.\n\nSilakan cek dashboard Superadmin untuk verifikasi.";
    $waService->sendMessage($nomorAdmin, $pesanAdmin);

    // 3. Kirim Pesan Konfirmasi ke Guru
    $pesanGuru = "Halo {$guru->name}, terima kasih!\n\nBukti pembayaran Anda telah kami terima dan sedang diproses oleh Superadmin. Kami akan menghubungi Anda kembali setelah akun Anda diaktifkan.";
    $waService->sendMessage($guru->phone, $pesanGuru);

    return redirect()->route('guru.pending')->with('success', 'Bukti bayar berhasil diunggah.');
}

// Saat Superadmin Mengaktifkan Akun
public function activateGuru($id, WhatsAppService $waService)
{
    $guru = User::find($id);
    $guru->update(['status' => 'aktif']);

    $pesan = "🎉 *Selamat! Akun Anda Telah Aktif*\n\nHalo {$guru->name}, akun Ujion TKA Anda telah diaktifkan oleh Superadmin.\n\nBerikut adalah kredensial login Anda:\nNama: *{$guru->name}*\nAccess Token: *{$guru->access_token}*\n\nSimpan Access Token ini baik-baik, jangan diberikan ke siapapun.\nSilakan login ke portal untuk mulai mengakses materi dan soal.";
    $waService->sendMessage($guru->phone, $pesan);

    return back()->with('success', 'Guru berhasil diaktifkan.');
}
```

### Skenario B: Pengiriman Notifikasi Hasil (Latihan Materi)

Sesuai _Flow Latihan Materi (Baru)_, guru bisa mendownload PDF per paket (paket 1-3) di web. Untuk menghemat beban server (tidak mengirim file PDF yang berat via WA), kita cukup mengirim pesan teks berisi tautan (link) unduhan.

**Controller Latihan Guru (`MaterialPracticeController.php`):**

```php
public function sendPdfLinkToWa($materialId, $paketNo, WhatsAppService $waService)
{
    $guru = Auth::user();

    // Tautan publik/internal untuk mengunduh PDF di web
    $pdfUrl = url("/guru/materials/{$materialId}/latihan/paket/{$paketNo}/pdf");

    $pesan = "Halo {$guru->name},\n\nRekap hasil Latihan Materi Paket {$paketNo} Anda telah selesai diproses.\n\nSilakan unduh dokumen PDF Anda melalui tautan berikut:\n{$pdfUrl}\n\n*(Harap login terlebih dahulu ke portal Ujion TKA untuk dapat mengunduhnya)*.";

    // Kirim pesan teks berisi link (menggunakan sendMessage, BUKAN sendMedia)
    $response = $waService->sendMessage($guru->phone, $pesan);

    if($response['status']) {
        return back()->with('success', 'Tautan unduhan PDF berhasil dikirim ke WhatsApp Anda!');
    }

    return back()->with('error', 'Gagal mengirim pesan ke WhatsApp.');
}
```

### Skenario C: Notifikasi Jadwal Ujian Baru (Publish Ujian)

Saat Superadmin membuat dan menerbitkan (publish) jadwal ujian baru, sistem otomatis menyiarkan notifikasi detail jadwal tersebut ke peserta terkait.
**Controller Manajemen Ujian (`ExamController.php` / Superadmin):**

```php
use App\Jobs\SendWhatsAppBlast;

public function storeExam(Request $request)
{
    // 1. Simpan Data Ujian Baru
    $exam = Exam::create([
        'title' => $request->title,
        'tanggal' => $request->tanggal, // format: Y-m-d
        'waktu_mulai' => $request->waktu_mulai, // format: H:i
    ]);

    // Format Hari, Tanggal, dan Jam menggunakan Carbon (Contoh: Senin, 20 Mei 2026)
    $hariTanggal = \Carbon\Carbon::parse($exam->tanggal)->translatedFormat('l, d F Y');
    $jam = \Carbon\Carbon::parse($exam->waktu_mulai)->format('H:i');

    // 2. Ambil target penerima (seluruh guru aktif)
    $guruList = User::where('role', 'guru')->where('status', 'aktif')->get();

    // 3. Masukkan ke Queue agar loading admin saat save tidak melambat
    foreach($guruList as $guru) {
        $pesan = "📢 *PENGUMUMAN JADWAL UJIAN BARU*\n\nHalo {$guru->name},\nSuperadmin baru saja menerbitkan jadwal ujian TKA terbaru:\n\n📚 *Materi:* {$exam->title}\n📅 *Hari/Tanggal:* {$hariTanggal}\n⏰ *Waktu:* {$jam} WIB\n\nHarap catat jadwal ini dan persiapkan siswa Anda. Login ke Ujion TKA untuk info lebih lanjut.";

        SendWhatsAppBlast::dispatch($guru->phone, $pesan)->onQueue('low');
    }

    return redirect()->route('superadmin.exams')->with('success', 'Ujian berhasil dibuat dan notifikasi WA sedang diproses.');
}
```

### Skenario D: Blast Pengingat H-1 (Wajib Pakai Laravel Queue)

**PENTING:** Untuk mengirim token latihan ke siswa atau mengingatkan jadwal ujian ke guru masal, **wajib menggunakan Laravel Jobs (Queue)** agar web tidak _hang_.

**1. Buat Job Baru:**

```bash
php artisan make:job SendWhatsAppBlast
```

**2. Isi Job (`app/Jobs/SendWhatsAppBlast.php`):**

```php
namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;
    protected $message;

    public function __construct($phone, $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    public function handle(WhatsAppService $waService)
    {
        // Jeda waktu (delay) acak 2-5 detik antar pesan agar tidak dibanned WA
        sleep(rand(2, 5));
        $waService->sendMessage($this->phone, $this->message);
    }
}
```

**3. Eksekusi Blast dari Controller:**

```php
use App\Jobs\SendWhatsAppBlast;

public function blastTokenLatihan(Request $request)
{
    $peserta = $request->input('peserta'); // Array nomor HP
    $token = $request->input('token'); // Token Latihan

    foreach ($peserta as $phone) {
        $pesan = "Halo Siswa,\nIni adalah token Latihan Materi Ujion Anda: *{$token}*.\nSilakan login di /siswa/latihan/login.";

        SendWhatsAppBlast::dispatch($phone, $pesan);
    }

    return back()->with('success', 'Pesan token sedang diproses ke antrean server.');
}
```

### Skenario E: Webhook Cek Nilai Ujian (Otomatis membalas chat siswa)

Siswa login latihan dengan opsi input WA. Jika mereka ingin mengecek nilai, mereka bisa chat WA Admin.

**1. Route Webhook (`routes/api.php`):**

```php
Route::post('/wa-webhook', [WebhookController::class, 'handle']);
```

**2. Webhook Controller (`app/Http/Controllers/WebhookController.php`):**

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $from = $request->input('from'); // Nomor WA Pengirim (Format: 628xxx)
        $message = strtoupper(trim($request->input('message')));

        if (str_starts_with($message, 'CEK HASIL')) {
            // Asumsi mencari di tabel sesi baru (berdasarkan arsitektur Anda)
            $session = DB::table('material_practice_sessions')
                        ->where('phone', $from)
                        ->latest('created_at')
                        ->first();

            if ($session) {
                // Asumsi menghitung skor paket (material_practice_package_attempts)
                $balasan = "Halo {$session->nama},\nAnda telah menyelesaikan sesi latihan. Untuk melihat detail skor Telaah dan Paket 1-3, silakan minta PDF dari Guru Anda.";
            } else {
                $balasan = "Maaf, nomor WA Anda tidak terdaftar di sesi ujian Ujion manapun.";
            }

            return response()->json([
                'success' => true,
                'msg' => $balasan
            ]);
        }

        return response()->json(['success' => false]);
    }
}
```

### Skenario F: Lupa Token / Reset Token (Self-Service)

Guru seringkali lupa _Access Token_ mereka untuk login. Kita bisa membuat 2 metode untuk mengatasi ini menggunakan WA Gateway.

**Metode 1: Fitur Lupa Token di Web (API Request)**
Di halaman login guru, tambahkan tombol "Lupa Token". Guru menginput nomor WA, lalu sistem mengirim token ke WA tersebut.

```php
public function requestLupaToken(Request $request, WhatsAppService $waService)
{
    $request->validate(['phone' => 'required']);
    $guru = User::where('phone', $request->phone)->where('role', 'guru')->first();

    if(!$guru) {
        return back()->with('error', 'Nomor WA tidak ditemukan.');
    }

    $pesan = "🔒 *Request Lupa Token*\n\nHalo {$guru->name},\nSeseorang meminta pengingat token login dari halaman Ujion TKA. Berikut adalah Access Token Anda:\n\n*{$guru->access_token}*\n\nJika ini bukan Anda, abaikan pesan ini.";
    $waService->sendMessage($guru->phone, $pesan);

    return back()->with('success', 'Token Anda telah dikirim via WhatsApp.');
}
```

**Metode 2: Lupa Token via Chatbot Webhook (Auto-Reply)**
Tambahkan logika ini ke dalam `WebhookController` (Skenario E) agar guru cukup _chat_ "LUPA TOKEN" ke nomor bot WA Anda.

```php
        // Tambahkan di dalam fungsi handle() pada WebhookController
        if (str_starts_with($message, 'LUPA TOKEN')) {
            $guru = User::where('phone', $from)->where('role', 'guru')->first();

            if ($guru) {
                if ($guru->status == 'aktif') {
                    $balasan = "Halo {$guru->name}.\nBerikut adalah kredensial login Anda:\nNama: *{$guru->name}*\nAccess Token: *{$guru->access_token}*\n\nJangan bagikan token ini kepada orang lain.";
                } else {
                    $balasan = "Halo {$guru->name}, akun Anda saat ini sedang dalam status *Pending*. Harap tunggu Superadmin memverifikasi pembayaran Anda.";
                }
            } else {
                $balasan = "Nomor Anda tidak terdaftar sebagai Guru di platform Ujion TKA.";
            }

            return response()->json(['success' => true, 'msg' => $balasan]);
        }
```

---

## 4. Fitur Enterprise & Profesional (Tingkat Lanjut)

Agar integrasi WhatsApp di Ujion TKA terasa seperti platform profesional kelas atas, terapkan fitur-fitur tambahan berikut pada sistem Laravel Anda:

### 1. Sistem Log Riwayat Pesan (Monitoring)

Buat tabel `whatsapp_logs` untuk mencatat setiap pesan yang keluar. Sangat berguna jika ada guru yang komplain "Saya belum menerima token/notifikasi". Superadmin bisa langsung mengecek status pengiriman di database.
**Migration:**

```php
$table->id();
$table->string('phone');
$table->text('message');
$table->string('status'); // 'sukses', 'gagal'
$table->text('response_data')->nullable(); // pesan error dari gateway
$table->timestamps();
```

**Di dalam `WhatsAppService.php`**, simpan log setelah melakukan pemanggilan `Http::post`:

```php
// ... setelah request HTTP ...
\DB::table('whatsapp_logs')->insert([
    'phone' => $number,
    'message' => $message,
    'status' => $response->successful() ? 'sukses' : 'gagal',
    'response_data' => $response->body(),
    'created_at' => now(),
]);
```

### 2. Job Retry & Fallback (Penanganan Anti Gagal)

Jika API Node.js sedang _down_ sesaat atau jaringan putus, _Laravel Queue_ tidak boleh menyerah. Tambahkan limit _Retry_ di `SendWhatsAppBlast.php`.

```php
// Tambahkan properti ini di dalam class Job Anda
public $tries = 3; // Maksimal coba kirim 3 kali jika gagal
public $backoff = [60, 180, 300]; // Jeda jika gagal: coba lagi setelah 1 menit, lalu 3 menit, lalu 5 menit

// Method penanganan jika benar-benar gagal 3x
public function failed(\Throwable $exception)
{
    // Kirim notifikasi Telegram/Email ke Admin IT
    \Log::error("Blast WA gagal total untuk nomor {$this->phone}. Error: " . $exception->getMessage());
}
```

### 3. Sapaan Waktu Humanis (Greeting Otomatis)

Buat fungsi kecil agar pesan tidak terkesan kaku seperti robot, melainkan bisa menyapa berdasarkan jam zona waktu pengguna (Pagi/Siang/Sore/Malam).

```php
// Buat fungsi Helper / taruh di Controller
public function greeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour <= 11) return "Selamat Pagi 🌤️";
    if ($hour >= 12 && $hour <= 14) return "Selamat Siang ☀️";
    if ($hour >= 15 && $hour <= 18) return "Selamat Sore ⛅";
    return "Selamat Malam 🌙";
}

// Cara Penggunaan:
$pesan = $this->greeting() . " {$guru->name},\n\nAkun TKA Anda telah aktif...";
```

### 4. Keamanan Webhook & Endpoint (API Key & IP Whitelist)

Sangat berbahaya jika endpoint `/send-message` Node.js atau `/wa-webhook` Laravel Anda terbuka bebas.

- **Di Laravel (Webhook Auth):** Buat middleware, pastikan payload dari Node.js menyertakan token rahasia di Header. (Catatan: Anda perlu memodifikasi _source code_ `server.js` di bagian `axios.post` untuk menambahkan header otorisasi).
- **Di Node.js (IP Whitelist):** Tambahkan logika di `server.js` agar _endpoint_ pengiriman pesan hanya mau menerima _request_ dari IP server Laravel Anda (contoh `127.0.0.1` atau IP VPS server TKA), sehingga orang iseng dari luar tidak bisa menembak API tersebut.

### 5. Prioritas Antrean (Queue Prioritization)

Jangan satukan antrean pengiriman pesan masal (_blast_) dengan pesan OTP/Aktivasi.

- **Di Job OTP/Token:** Panggil dengan `dispatch()->onQueue('high');`
- **Di Job Blast:** Panggil dengan `dispatch()->onQueue('low');`
  Jalankan _worker_ di server VPS dengan urutan prioritas: `php artisan queue:work --queue=high,low`. Dengan begini, pesan kritis seperti OTP akan selalu menyela (mendahului) ribuan antrean _blast_.

### 6. Interactive Chatbot Menu

Buat menu interaktif saat guru sekadar chat sapaan umum ke sistem.

```php
// Di Webhook Controller
if ($message == 'HALO' || $message == 'MENU' || $message == 'P') {
    $balasan = "Halo! Pusat Layanan Ujion TKA.\nBalas dengan angka:\n1️⃣ Cek Nilai Terakhir\n2️⃣ Lupa Token Login\n3️⃣ Jadwal Ujian Terdekat";
    return response()->json(['success' => true, 'msg' => $balasan]);
}
// Lalu tambahkan kondisi jika $message == '1', jalankan logika cek nilai
```

### 7. Ping Server & Disconnect Alert

Gunakan _Task Scheduling_ Laravel di `routes/console.php` (Laravel 11/12) untuk melakukan ping ke `http://127.0.0.1:3000/` setiap 10 menit. Jika API merespons _error_ atau _timeout_, otomatis kirim peringatan ke _Email_ atau _Telegram_ Admin IT bahwa WA Gateway sedang putus.

---

## 5. Integrasi UI Dashboard Superadmin (Scan QR & Live Chat)

Anda dapat membuat halaman khusus di dalam **Dashboard Superadmin Laravel** agar Admin TKA memiliki _interface_ layaknya WhatsApp Web!

### 1. Menampilkan Scan QR Code Langsung di Web Laravel

Node.js pada project ini memancarkan sinyal QR Code menggunakan WebSockets (`socket.io`). Anda tinggal mengaitkan _script client_ di halaman Blade Superadmin.

Buat View Blade (`resources/views/superadmin/wa-koneksi.blade.php`):

```html
<div>
  <h2>Status Koneksi WA Gateway</h2>
  <img id="qr-image" src="" alt="Menunggu QR Code..." style="width:250px;" />
  <p id="status-teks">Menghubungkan ke server...</p>
</div>

<!-- Ambil library socket.io dari Node.js -->
<script src="http://127.0.0.1:3000/socket.io/socket.io.js"></script>
<script>
  // Hubungkan ke Port WA Gateway
  const socket = io("http://127.0.0.1:3000");

  // Minta session atas nama 'tka-admin'
  socket.emit("create-session", { id: "tka-admin" });

  // Tangkap gambar QR Code jika belum login
  socket.on("qr", function (data) {
    document.getElementById("status-teks").innerText =
      "Silakan scan QR Code ini lewat HP Admin";
    document.getElementById("qr-image").src = data.src;
  });

  // Jika koneksi sukses
  socket.on("ready", function (data) {
    document.getElementById("status-teks").innerHTML =
      "<strong style='color:green'>WhatsApp Berhasil Terhubung dan Aktif!</strong>";
    document.getElementById("qr-image").style.display = "none";
  });
</script>
```

_Dengan cara ini, Superadmin tidak perlu membuka terminal VPS hitam putih. Mereka cukup buka menu "Koneksi WA Gateway" di web Ujion, lalu scan QR yang muncul di layar laptop._

### 2. Live Chat Inbox (Fitur Helpdesk)

Karena setiap pesan masuk ditangkap oleh Webhook Laravel Anda:

1. Setiap pesan masuk dari Webhook bisa Anda simpan (insert) ke tabel database `chats`.
2. Anda bisa mendesain halaman UI "Live Chat" di Superadmin layaknya WA Web. Sebelah kiri daftar nomor guru, sebelah kanan isi _chat_.
3. Saat Superadmin membalas pesan dari halaman UI tersebut, Laravel memanggil `WhatsAppService->sendMessage()`.
4. Hasilnya: Anda memiliki fitur Helpdesk/CRM Chat 2-arah yang sempurna tanpa harus memegang HP fisik!

---

## 6. Best Practices (Mencegah Pemblokiran/Banned WhatsApp)

1. **Jeda Pengiriman (Delay):** Saat melakukan fitur _Blast_ / Pengingat, terapkan _delay_ atau `sleep()` 2 hingga 7 detik per antrean seperti di skenario `Queue` di atas. Mengirim 100 pesan dalam 1 detik akan langsung membuat nomor terblokir.
2. **Kustomisasi Pesan:** Hindari mengirim teks yang sama 100% persis ke ratusan orang. Gunakan penyebutan nama target (misal: "Halo Pak Budi") untuk membuat pesan terkesan organik dan bervariasi.
3. **Minta Izin (Opt-in):** Pastikan guru sadar bahwa mereka akan menerima notifikasi WA.
4. **Nomor Terpisah:** Gunakan nomor WhatsApp sekunder khusus sebagai nomor Admin Notifikasi Gateway.
