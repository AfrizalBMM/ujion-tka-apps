<?php

namespace App\Support;

class GuruNotificationTemplates
{
    public static function activationToken(string $teacherName, string $token): string
    {
        return trim(implode("\n", [
            "Halo {$teacherName},",
            '',
            'Akun Ujion Anda sudah aktif.',
            "Token akses Anda: {$token}",
            '',
            'Silakan login menggunakan nama yang terdaftar dan token akses di atas.',
            'Mohon simpan token ini dan jangan dibagikan ke pihak lain.',
            '',
            'Terima kasih.',
            'Admin Ujion',
        ]));
    }

    public static function paymentApproved(string $teacherName, string $token = '[TOKEN_AKSES]'): string
    {
        return trim(implode("\n", [
            "Halo {$teacherName},",
            '',
            'Pembayaran Anda sudah kami verifikasi.',
            'Akun Anda telah diaktifkan dan siap digunakan.',
            "Token akses Anda: {$token}",
            '',
            'Silakan login menggunakan nama yang terdaftar dan token akses tersebut.',
            'Jika ada kendala saat login, balas pesan ini ya.',
            '',
            'Salam,',
            'Admin Ujion',
        ]));
    }

    public static function paymentRejected(string $teacherName, string $reason = '[ALASAN_VERIFIKASI]'): string
    {
        return trim(implode("\n", [
            "Halo {$teacherName},",
            '',
            'Terima kasih, bukti pembayaran Anda sudah kami cek.',
            'Saat ini pembayaran belum bisa kami verifikasi.',
            "Catatan admin: {$reason}",
            '',
            'Silakan kirim ulang bukti pembayaran yang lebih jelas atau hubungi admin untuk bantuan lebih lanjut.',
            '',
            'Salam,',
            'Admin Ujion',
        ]));
    }

    public static function newRegistrationAlert(
        string $teacherName = '[NAMA_GURU]',
        string $schoolName = '[SATUAN_PENDIDIKAN]',
        string $whatsApp = '[NO_WHATSAPP]'
    ): string {
        return trim(implode("\n", [
            'Pendaftaran guru baru masuk.',
            '',
            "Nama: {$teacherName}",
            "Satuan pendidikan: {$schoolName}",
            "WhatsApp: {$whatsApp}",
            '',
            'Tindak lanjut yang disarankan:',
            '1. Cek data pendaftaran.',
            '2. Verifikasi pembayaran.',
            '3. Aktifkan akun lalu kirim token akses.',
        ]));
    }

    public static function paymentReminder(
        string $teacherName = '[NAMA_GURU]',
        string $amount = '[NOMINAL]'
    ): string {
        return trim(implode("\n", [
            "Halo {$teacherName},",
            '',
            'Terima kasih sudah mendaftar di Ujion.',
            "Kami mengingatkan kembali untuk menyelesaikan pembayaran sebesar {$amount} sesuai instruksi yang tampil pada halaman pendaftaran.",
            'Setelah pembayaran diverifikasi, akun dan token akses Anda akan segera diproses.',
            '',
            'Salam,',
            'Admin Ujion',
        ]));
    }

    public static function paymentSubmittedAlert(
        string $teacherName = '[NAMA_GURU]',
        string $schoolName = '[SATUAN_PENDIDIKAN]',
        string $whatsApp = '[NO_WHATSAPP]'
    ): string {
        return trim(implode("\n", [
            'Bukti pembayaran guru sudah dikirim.',
            '',
            "Nama: {$teacherName}",
            "Satuan pendidikan: {$schoolName}",
            "WhatsApp: {$whatsApp}",
            '',
            'Tindak lanjut yang disarankan:',
            '1. Periksa bukti pembayaran.',
            '2. Approve atau reject pembayaran.',
            '3. Jika approve, kirim token akses ke guru.',
        ]));
    }

    public static function library(): array
    {
        return [
            [
                'title' => 'Notifikasi pembayaran diterima + token akses',
                'audience' => 'Guru',
                'body' => self::paymentApproved('[NAMA_GURU]'),
            ],
            [
                'title' => 'Notifikasi pembayaran ditolak',
                'audience' => 'Guru',
                'body' => self::paymentRejected('[NAMA_GURU]'),
            ],
            [
                'title' => 'Pengingat pembayaran pending',
                'audience' => 'Guru',
                'body' => self::paymentReminder(),
            ],
            [
                'title' => 'Alert bukti pembayaran sudah dikirim',
                'audience' => 'Superadmin',
                'body' => self::paymentSubmittedAlert(),
            ],
            [
                'title' => 'Alert pendaftaran baru masuk',
                'audience' => 'Superadmin',
                'body' => self::newRegistrationAlert(),
            ],
        ];
    }
}
