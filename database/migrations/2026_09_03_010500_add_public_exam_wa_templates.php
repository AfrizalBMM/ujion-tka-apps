<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'key' => 'event_public_exam_paid',
                'title' => 'Pembayaran Ujian Publik Berhasil',
                'body' => trim(implode("\n", [
                    'Halo {name},',
                    '',
                    'Pembayaran ujian *{exam_title} — {mapel_label}* berhasil.',
                    '',
                    'Klik link berikut untuk mulai mengerjakan ujian:',
                    '{exam_url}',
                    '',
                    'Link ini bersifat pribadi. Jangan dibagikan ke orang lain.',
                    '',
                    'Salam,',
                    'Admin Ujion',
                ])),
                'description' => 'Dikirim otomatis ke siswa setelah pembayaran ujian publik berhasil (Midtrans settlement).',
                'is_active' => true,
            ],
            [
                'key' => 'event_public_exam_completed',
                'title' => 'Ujian Selesai — Hasil & Pembahasan',
                'body' => trim(implode("\n", [
                    'Halo {name},',
                    '',
                    'Ujian *{exam_title} — {mapel_label}* telah selesai.',
                    'Skor: *{score}*',
                    '',
                    'Lihat hasil lengkap, kunci jawaban, dan pembahasan di:',
                    '{result_url}',
                    '',
                    'Salam,',
                    'Admin Ujion',
                ])),
                'description' => 'Dikirim otomatis ke siswa setelah menyelesaikan ujian publik.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            DB::table('wa_message_templates')->updateOrInsert(
                ['key' => $tpl['key']],
                array_merge($tpl, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('wa_message_templates')
            ->whereIn('key', ['event_public_exam_paid', 'event_public_exam_completed'])
            ->delete();
    }
};
