<?php

namespace App\Services;

use App\Models\WaMessageTemplate;

class WaMessageTemplateService
{
    /**
     * Default templates used when DB templates are missing.
     * Admin can override by creating a template with matching `key`.
     */
    public function defaults(): array
    {
        return [
            'bot_menu' => trim(implode("\n", [
                'Halo! Pusat Layanan Ujion TKA.',
                '',
                'Balas dengan kata/angka berikut:',
                '1️⃣ CEK HASIL',
                '2️⃣ LUPA TOKEN',
                '3️⃣ CEK STATUS AKUN',
                '4️⃣ KIRIM ULANG LINK LOGIN/DASHBOARD',
                '5️⃣ JADWAL',
                '',
                'Ketik MENU untuk menampilkan bantuan.',
            ])),

            'bot_lupa_token_group_only' => 'Untuk keamanan, fitur LUPA TOKEN hanya bisa melalui chat pribadi. Silakan chat bot ini secara personal dan ketik LUPA TOKEN.',

            'bot_lid_unreadable' => 'Nomor WhatsApp pengirim tidak dapat dibaca / terdaftar di akun ujion, sehingga tidak bisa dicocokkan ke akun guru. Silakan gunakan nomor WA yang terdaftar sebagai akun guru untuk chat bot ini.',

            'bot_not_guru' => 'Nomor WhatsApp ini terdaftar, tetapi bukan sebagai akun guru. Silakan gunakan nomor yang terdaftar sebagai akun guru di Ujion.',

            'bot_not_registered_guru' => 'Nomor WhatsApp ini belum terdaftar sebagai akun guru di Ujion.',

            'bot_account_not_active' => 'Akun Anda belum aktif. Silakan tunggu verifikasi pembayaran atau hubungi admin.',

            'bot_token_reset_success' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Berikut token akses baru Anda:',
                '{token}',
                '',
                'Silakan login menggunakan nama terdaftar dan token di atas.',
                'Jangan bagikan token ini ke siapa pun.',
                '',
                'Salam,',
                'Admin Ujion',
            ])),

            'bot_status' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Status akun Anda:',
                '- Status akun: {account_status}',
                '- Status pembayaran: {payment_status}',
                '',
                '{status_note}',
            ])),

            'bot_login_link' => trim(implode("\n", [
                'Berikut link login/dashboard Ujion:',
                '{login_url}',
                '',
                'Jika Anda lupa token, balas dengan LUPA TOKEN.',
            ])),

            'bot_jadwal' => trim(implode("\n", [
                'Halo {name},',
                '',
                '{jadwal_lines}',
                '',
                'Jika butuh bantuan lain, ketik MENU.',
            ])),

            'event_activation_token' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Akun Ujion Anda sudah aktif.',
                'Token akses Anda: {token}',
                '',
                'Silakan login menggunakan nama yang terdaftar dan token akses di atas.',
                'Mohon simpan token ini dan jangan dibagikan ke pihak lain.',
                '',
                'Terima kasih.',
                'Admin Ujion',
            ])),

            'event_payment_approved' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Pembayaran Anda sudah kami verifikasi.',
                'Akun Anda telah diaktifkan dan siap digunakan.',
                'Token akses Anda:',
                '{token}',
                '',
                'Silakan login menggunakan nama yang terdaftar dan token akses tersebut.',
                'Jika ada kendala saat login, balas pesan ini ya.',
                '',
                'Salam,',
                'Admin Ujion',
            ])),

            'event_payment_rejected' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Terima kasih, bukti pembayaran Anda sudah kami cek.',
                'Saat ini pembayaran belum bisa kami verifikasi.',
                'Catatan admin: {reason}',
                '',
                'Silakan kirim ulang bukti pembayaran yang lebih jelas atau hubungi admin untuk bantuan lebih lanjut.',
                '',
                'Salam,',
                'Admin Ujion',
            ])),

            'event_exam_published' => trim(implode("\n", [
                '📢 PENGUMUMAN UJIAN/Tryout Baru',
                '',
                'Halo {name},',
                'Jadwal ujian/tryout baru *{exam_title}* telah diterbitkan dan akan dimulai pada *{exam_date}*.',
                '',
                'Silakan persiapkan siswa Anda dan cek detailnya di dashboard Ujion.',
            ])),

            'followup_payment_pending_24h' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Kami mengingatkan kembali untuk menyelesaikan pembayaran sesuai instruksi pada halaman pendaftaran.',
                'Setelah pembayaran diverifikasi, akun dan token akses Anda akan segera diproses.',
                '',
                'Salam,',
                'Admin Ujion',
            ])),

            'followup_payment_no_proof_2h' => trim(implode("\n", [
                'Halo {name},',
                '',
                'Kami belum menerima bukti pembayaran Anda. Jika Anda sudah melakukan transfer, silakan unggah bukti pembayaran agar bisa kami verifikasi.',
                '',
                'Salam,',
                'Admin Ujion',
            ])),

            'alert_gateway_down' => "[Ujion] WA Gateway DOWN\nURL: {url}\nTime: {time}\nError: {error}",

            'alert_queue_backlog' => "[Ujion] Queue menumpuk\nJobs: {jobs}\nOldest: {oldest}\nTime: {time}",
        ];
    }

    public function getBody(string $key, ?string $fallback = null): ?string
    {
        $template = WaMessageTemplate::query()
            ->active()
            ->where('key', $key)
            ->first();

        if ($template) {
            return $template->body;
        }

        $defaults = $this->defaults();

        return $defaults[$key] ?? $fallback;
    }

    public function render(string $key, array $vars = [], ?string $fallback = null): string
    {
        $body = (string) ($this->getBody($key, $fallback) ?? '');

        return $this->interpolate($body, $vars);
    }

    private function interpolate(string $body, array $vars): string
    {
        $replacements = [];

        foreach ($vars as $name => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $stringValue = $value === null ? '' : (string) $value;
            $key = (string) $name;

            $replacements['{' . $key . '}'] = $stringValue;
            $replacements['{{' . $key . '}}'] = $stringValue;
            $replacements['[' . strtoupper($key) . ']'] = $stringValue;
        }

        // Compatibility placeholders.
        if (array_key_exists('name', $vars)) {
            $replacements['[NAMA_GURU]'] = (string) $vars['name'];
        }
        if (array_key_exists('token', $vars)) {
            $replacements['[TOKEN_AKSES]'] = (string) $vars['token'];
        }
        if (array_key_exists('reason', $vars)) {
            $replacements['[ALASAN_VERIFIKASI]'] = (string) $vars['reason'];
        }
        if (array_key_exists('school_name', $vars)) {
            $replacements['[SATUAN_PENDIDIKAN]'] = (string) $vars['school_name'];
        }
        if (array_key_exists('whatsapp', $vars)) {
            $replacements['[NO_WHATSAPP]'] = (string) $vars['whatsapp'];
        }
        if (array_key_exists('amount', $vars)) {
            $replacements['[NOMINAL]'] = (string) $vars['amount'];
        }

        return strtr($body, $replacements);
    }
}
