1. Project Overview
   Objective: Mengintegrasikan fitur generator QRIS Statis dengan Nominal Tetap (Fixed Amount) ke dalam ekosistem Laravel Ujion TKA tanpa menggunakan payment gateway pihak ketiga (Midtrans/Xendit).
   Main Flow: User pilih paket -> Sistem generate QRIS dengan nominal paket -> User bayar -> User konfirmasi via WhatsApp.

2. Technical Requirements
   PHP Library: simplesoftwareio/simple-qrcode (untuk render gambar QR).

Master Data: Master Payload QRIS (dari GoPay) disimpan di .env.

Encryption/Checksum: Re-kalkulasi CRC16-CCITT (Polynomial: 0x1021, Initial: 0xFFFF).

3. Database Schema Update
   Programmer perlu menambahkan/memastikan tabel transactions memiliki kolom berikut:

PHP
Schema::table('transactions', function (Blueprint $table) {
$table->string('reference_code')->unique(); // Kode internal transaksi
$table->decimal('amount', 15, 2); // Nominal paket
$table->enum('status', ['pending', 'success', 'failed'])->default('pending');
}); 4. Core Logic (Service Layer)
Programmer harus membuat Service Class app/Services/QrisService.php untuk manipulasi string QRIS.

Logic Injeksi Tag 54:

Ambil GOPAY_MASTER_PAYLOAD dari .env.

Hapus 4 karakter terakhir (CRC lama).

Cek Tag 54. Jika ada, timpa nilainya. Jika tidak, sisipkan sebelum Tag 58 (Country Code ID).

Hitung ulang CRC16 dari string baru.

Kembalikan string utuh yang sudah valid.

5. User Interface (UI) Flow
   A. Halaman Checkout (Existing/New)
   Menampilkan detail paket yang dipilih.

Menampilkan nominal yang harus dibayar.

Tombol "Bayar Sekarang" akan membuat record di tabel transactions.

B. Halaman Pembayaran (The QRIS Page)
URL: /payments/{reference_code}

QRIS Display: Menampilkan QR Code hasil generate.

Instruksi:

Scan menggunakan aplikasi bank (BCA, Mandiri, dll) atau e-wallet (GoPay, OVO).

Pastikan nominal muncul otomatis senilai Rp [Amount].

Klik bayar.

WhatsApp Button:

Link: [https://wa.me/](https://wa.me/)[Admin_Number]?text=[Auto_Generated_Text]

Text: "Halo Admin Ujion, saya sudah bayar Paket [Nama_Paket] senilai Rp [Nominal]. [Link_Bukti_Bayar]"

6. Admin Dashboard Integration
   Tambahkan menu "Konfirmasi Pembayaran" di panel admin.

List semua transaksi berstatus pending.

Aksi: Tombol "Approve" untuk mengubah status transaksi menjadi success dan memberikan akses ujian ke user.

7. Security & Validation
   CRC Validation: Pastikan fungsi CRC16 akurat agar QRIS tidak invalid saat di-scan aplikasi Bank.

Sanitization: Nominal yang di-inject harus dibersihkan dari karakter non-numerik.

8. Checklist Eksekusi
   [ ] Install library simple-qrcode.

[ ] Ambil Raw String QRIS Statis via Google Lens/Scanner.

[ ] Tambahkan GOPAY_MASTER_PAYLOAD ke .env.

[ ] Implementasi QrisService.php.

[ ] Update View pembayaran agar memanggil Service tersebut.

[ ] Testing scan menggunakan minimal 2 aplikasi berbeda (misal: Livin' dan GoPay).

9. Admin Menu: Paket & QRIS Management (CRUD)
   A. Fitur CRUD Paket
   Programmer harus menyediakan menu khusus untuk mengelola paket ujian yang nantinya akan otomatis terhubung ke sistem QRIS.

Create/Edit Form:

Title: Nama paket (misal: "Paket Intensif TKA").

Amount: Nominal dalam angka murni (misal: 99000).

Description: Penjelasan singkat paket (opsional).

Table List:

Menampilkan daftar paket, harga, dan tombol aksi.

Tombol Khusus: Tambahkan tombol "Print Label QRIS" di setiap baris paket.

B. Fitur Cetak Label (The "Pajang Selamanya" Feature)
Ketika tombol "Print Label QRIS" diklik, sistem akan membuka new tab dengan tampilan minimalis yang dioptimalkan untuk kertas A4 atau thermal.

10. Template Print (HTML/CSS)
    Minta programmer untuk mengimplementasikan template berikut agar hasil cetakan rapi dan profesional saat dipajang di toko:

HTML

<!-- View: admin.packages.print -->
<div class="print-container" style="width: 100%; max-width: 400px; text-align: center; border: 2px solid #000; padding: 20px; font-family: sans-serif;">
    <h1 style="margin: 0; font-size: 24px;">UJION TKA</h1>
    <p style="margin: 5px 0; color: #555;">Sistem Ujian Terintegrasi</p>
    
    <hr style="border: 1px dashed #ccc;">
    
    <h2 style="margin: 15px 0;">{{ $package->title }}</h2>
    
    <!-- QRIS Image yang di-generate Laravel -->
    <div style="margin: 20px 0;">
        {!! QrCode::size(250)->generate($qrisPayload) !!}
    </div>
    
    <h1 style="margin: 0; font-size: 32px;">Rp {{ number_format($package->price, 0, ',', '.') }}</h1>
    
    <p style="font-size: 12px; margin-top: 20px;">
        *Pastikan nominal yang muncul di aplikasi sesuai.<br>
        Konfirmasi bukti bayar ke WhatsApp Admin.
    </p>
</div>

<script>
    window.print(); // Otomatis buka dialog print saat halaman dimuat
</script>

11. Final End-to-End Flow (Revised)
    Input Data: Admin input paket "Ujion Pro" seharga 99.000 di Laravel.

Display Web: User klik beli "Ujion Pro" di web, muncul QRIS hasil injeksi Rp99.000 + Tombol WA.

Display Fisik: Admin klik "Print Label", menempelkan hasil print di meja kasir. Pelanggan di lokasi scan QR yang sama, nominal Rp99.000 langsung muncul.

Konfirmasi:

User Web: Klik Tombol WA (Auto-text).

User Offline: Tunjukkan layar HP "Berhasil" ke Admin.

Approval: Admin masuk ke daftar transaksi, klik Approve, user mendapat akses ujian.

Catatan untuk Programmer:
"Gunakan CSS @media print { .no-print { display: none; } } untuk menyembunyikan elemen dashboard saat mencetak label, sehingga hanya kartu QRIS-nya saja yang tercetak bersih."

Tips untuk Programmer Anda:
Beritahu programmer Anda untuk berhati-hati pada Tag 54. Panjang karakter (length) harus 2 digit.

Jika nominal 99.000 (5 digit), maka tag-nya adalah 540599000.

Jika nominal 1.000.000 (7 digit), maka tag-nya adalah 54071000000.
