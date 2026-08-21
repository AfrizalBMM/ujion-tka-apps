<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('body');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('wa_message_templates')->insert([
            [
                'key' => 'bot_menu',
                'title' => 'Bot: Menu utama',
                'body' => "Halo! Pusat Layanan Ujion TKA.\n\nBalas dengan kata/angka berikut:\n1️⃣ CEK HASIL\n2️⃣ LUPA TOKEN\n3️⃣ CEK STATUS AKUN\n4️⃣ KIRIM ULANG LINK LOGIN/DASHBOARD\n5️⃣ JADWAL\n\nKetik MENU untuk menampilkan bantuan.",
                'description' => 'Balasan ketika user mengetik MENU/HALO/P.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bot_token_reset_success',
                'title' => 'Bot: Lupa token (sukses)',
                'body' => "Halo {name},\n\nBerikut token akses baru Anda:\n{token}\n\nSilakan login menggunakan nama terdaftar dan token di atas.\nJangan bagikan token ini ke siapa pun.\n\nSalam,\nAdmin Ujion",
                'description' => 'Balasan ketika LUPA TOKEN berhasil.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bot_status',
                'title' => 'Bot: Cek status akun',
                'body' => "Halo {name},\n\nStatus akun Anda:\n- Status akun: {account_status}\n- Status pembayaran: {payment_status}\n\n{status_note}",
                'description' => 'Balasan untuk STATUS/CEK STATUS AKUN.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bot_login_link',
                'title' => 'Bot: Kirim ulang link login/dashboard',
                'body' => "Berikut link login/dashboard Ujion:\n{login_url}\n\nJika Anda lupa token, balas dengan LUPA TOKEN.",
                'description' => 'Balasan untuk LINK/LOGIN/DASHBOARD.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bot_jadwal',
                'title' => 'Bot: Jadwal ujian',
                'body' => "Halo {name},\n\n{jadwal_lines}\n\nJika butuh bantuan lain, ketik MENU.",
                'description' => 'Balasan untuk JADWAL/UJIAN.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'event_activation_token',
                'title' => 'Event: Akun aktif + token',
                'body' => "Halo {name},\n\nAkun Ujion Anda sudah aktif.\nToken akses Anda: {token}\n\nSilakan login menggunakan nama yang terdaftar dan token akses di atas.\nMohon simpan token ini dan jangan dibagikan ke pihak lain.\n\nTerima kasih.\nAdmin Ujion",
                'description' => 'Template untuk aktivasi manual/refresh token.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'event_payment_approved',
                'title' => 'Event: Pembayaran disetujui',
                'body' => "Halo {name},\n\nPembayaran Anda sudah kami verifikasi.\nAkun Anda telah diaktifkan dan siap digunakan.\nToken akses Anda:\n {token}\n\nSilakan login menggunakan nama yang terdaftar dan token akses tersebut.\nJika ada kendala saat login, balas pesan ini ya.\n\nSalam,\nAdmin Ujion",
                'description' => 'Dipakai saat approve pembayaran (TeacherController/PaymentConfirmationController).',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'event_payment_rejected',
                'title' => 'Event: Pembayaran ditolak',
                'body' => "Halo {name},\n\nTerima kasih, bukti pembayaran Anda sudah kami cek.\nSaat ini pembayaran belum bisa kami verifikasi.\nCatatan admin: {reason}\n\nSilakan kirim ulang bukti pembayaran yang lebih jelas atau hubungi admin untuk bantuan lebih lanjut.\n\nSalam,\nAdmin Ujion",
                'description' => 'Dipakai saat reject pembayaran.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'event_exam_published',
                'title' => 'Event: Ujian diterbitkan',
                'body' => "📢 PENGUMUMAN UJIAN/Tryout Baru\n\nHalo {name},\nJadwal ujian/tryout baru *{exam_title}* telah diterbitkan dan akan dimulai pada *{exam_date}*.\n\nSilakan persiapkan siswa Anda dan cek detailnya di dashboard Ujion.",
                'description' => 'Dipakai saat ujian berstatus terbit.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'followup_payment_pending_24h',
                'title' => 'Follow-up: Pending pembayaran > 24 jam',
                'body' => "Halo {name},\n\nKami mengingatkan kembali untuk menyelesaikan pembayaran sesuai instruksi pada halaman pendaftaran.\nSetelah pembayaran diverifikasi, akun dan token akses Anda akan segera diproses.\n\nSalam,\nAdmin Ujion",
                'description' => 'Dikirim otomatis via scheduler, max 1x/hari per guru.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'followup_payment_no_proof_2h',
                'title' => 'Follow-up: Belum upload bukti > 2 jam',
                'body' => "Halo {name},\n\nKami belum menerima bukti pembayaran Anda. Jika Anda sudah melakukan transfer, silakan unggah bukti pembayaran agar bisa kami verifikasi.\n\nSalam,\nAdmin Ujion",
                'description' => 'Dikirim otomatis via scheduler, max 1x per guru.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'alert_gateway_down',
                'title' => 'Alert: Gateway down',
                'body' => "[Ujion] WA Gateway DOWN\nURL: {url}\nTime: {time}\nError: {error}",
                'description' => 'Dikirim ke admin saat healthcheck gagal.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'alert_queue_backlog',
                'title' => 'Alert: Queue menumpuk',
                'body' => "[Ujion] Queue menumpuk\nJobs: {jobs}\nOldest: {oldest}\nTime: {time}",
                'description' => 'Dikirim ke admin saat antrian jobs besar/terlalu lama.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_message_templates');
    }
};
