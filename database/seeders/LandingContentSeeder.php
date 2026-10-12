<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use App\Models\LandingContentItem;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            'hero' => [
                'kicker' => 'Latihan TKA online untuk bimbel dan guru',
                'title' => 'Latih siswa untuk TKA dengan soal dan skor yang mirip ujian aslinya',
                'body' => 'Ujion TKA adalah situs ujian online khusus Tes Kemampuan Akademik (TKA). Guru dan bimbel bisa membuat ujian sendiri, siswa mengerjakannya lewat HP, dan hasilnya langsung bisa dibaca. Skor memakai skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK.',
                'subtitle' => null,
                'button_text' => 'Coba Sebagai Guru',
                'button_url' => null,
                'cta_text' => null,
                'cta_url' => null,
                'note' => null,
                'seo_title' => 'Ujion TKA - Latihan TKA online untuk bimbel dan guru',
                'seo_description' => 'Ujion TKA adalah situs latihan dan persiapan TKA untuk bimbel dan guru. Guru membuat ujian sendiri, siswa mengerjakan lewat HP.',
            ],
            'bank_soal' => [
                'kicker' => 'Bank soal dan materi',
                'title' => 'Soal dan materi mengikuti aturan resmi TKA',
                'body' => 'Semua soal dan materi disusun berdasarkan Peraturan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan (BSKAP) Kemendikdasmen Nomor 47 Tahun 2025 tentang Kerangka Asesmen TKA untuk jenjang SD/MI dan SMP/MTs, serta Peraturan BSKAP Nomor 45 Tahun 2025 untuk jenjang SMA/MA/SMK.',
            ],
            'footer' => [
                'kicker' => 'Ujion TKA adalah situs latihan dan ujian TKA untuk bimbel dan guru. Skor 0-100 untuk SD/SMP dan 200-800 untuk SMA/SMK, paket soal disusun sendiri, siswa cukup pakai HP.',
                'title' => 'Masuk',
                'body' => 'Ujion adalah situs latihan dan persiapan TKA, bukan penyelenggara ujian TKA resmi.',
                'button_text' => 'Daftar',
                'button_url' => null,
                'cta_text' => null,
                'cta_url' => null,
                'note' => 'Ikuti Saluran WhatsApp',
                'seo_title' => null,
                'seo_description' => null,
            ],
            'reasons_head' => [
                'kicker' => '3 alasan Ujion berbeda dari ujian online biasa',
                'title' => 'Dibuat khusus untuk TKA, bukan ujian online serba guna.',
            ],
            'audience_head' => [
                'kicker' => 'Untuk siapa Ujion',
                'title' => 'Cocok untuk yang menyiapkan siswa menghadapi TKA',
                'body' => 'Ujion fokus pada satu hal: latihan dan ujian TKA. Ujion bukan aplikasi ujian umum dan bukan sistem sekolah yang lengkap, jadi lebih ringan dan mudah dipakai.',
            ],
            'steps_head' => [
                'kicker' => 'Cara kerja',
                'title' => 'Cara kerja untuk guru dan siswa',
            ],
            'features_head' => [
                'kicker' => 'Fitur utama',
                'title' => 'Yang membuat Ujion cocok untuk persiapan TKA',
            ],
            'faq_head' => [
                'kicker' => 'Tanya jawab',
                'title' => 'Pertanyaan yang sering diajukan',
            ],
        ];

        foreach ($sections as $section => $attributes) {
            LandingContent::query()->updateOrCreate(
                ['section' => $section],
                $attributes + [
                    'subtitle' => $attributes['subtitle'] ?? null,
                    'button_text' => $attributes['button_text'] ?? null,
                    'button_url' => $attributes['button_url'] ?? null,
                    'cta_text' => $attributes['cta_text'] ?? null,
                    'cta_url' => $attributes['cta_url'] ?? null,
                    'note' => $attributes['note'] ?? null,
                    'seo_title' => $attributes['seo_title'] ?? null,
                    'seo_description' => $attributes['seo_description'] ?? null,
                    'is_active' => true,
                    'sort_order' => 0,
                ],
            );
        }

        $items = [
            'hero_point' => [
                ['title' => 'Skor memakai skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK'],
                ['title' => 'Susun paket soal sendiri dengan mudah'],
                ['title' => 'Siswa cukup pakai HP dan kode ujian'],
            ],
            'reason' => [
                [
                    'title' => 'Skor dan soal mirip TKA',
                    'body' => 'Hasil ujian tampil dengan skala resmi TKA: 0-100 untuk SD dan SMP, 200-800 untuk SMA dan SMK. Soalnya mengikuti kerangka asesmen TKA terbaru, jadi siswa sudah terbiasa sebelum ujian sebenarnya.',
                ],
                [
                    'title' => 'Atur soal sendiri dengan mudah',
                    'body' => 'Guru dan bimbel dapat menyusun paket soal dan mata pelajaran sendiri, lalu membagikan ujian ke siswa lewat satu kode. Hasil tiap siswa langsung terkumpul di satu tempat.',
                ],
                [
                    'title' => 'Praktis untuk guru dan siswa',
                    'body' => 'Siswa mengerjakan lewat HP tanpa membuat akun, cukup memasukkan kode ujian dari guru. Jawaban tersimpan otomatis, dan tidak perlu lab komputer.',
                ],
            ],
            'audience' => [
                [
                    'title' => 'Bimbel',
                    'body' => 'Susun paket soal sendiri, jalankan ujian dengan satu kode, dan pantau hasil semua siswa dari satu tampilan.',
                ],
                [
                    'title' => 'Guru',
                    'body' => 'Latih siswa dengan soal berbentuk TKA dan skor sesuai jenjang, lalu lihat siapa yang perlu dibantu.',
                ],
                [
                    'title' => 'Siswa',
                    'body' => 'Kerjakan ujian dari HP. Tidak perlu daftar akun, cukup masukkan kode dari guru. Jawaban tersimpan otomatis kalau sinyal putus.',
                ],
                [
                    'title' => 'Orang tua',
                    'body' => 'Anak bisa berlatih soal berbentuk TKA langsung dari HP, tanpa perlu ke lab komputer.',
                ],
            ],
            'step_guru' => [
                [
                    'title' => 'Daftar akun guru',
                    'body' => 'Akun akan diperiksa dulu sebelum aktif.',
                ],
                [
                    'title' => 'Siapkan paket soal',
                    'body' => 'Pilih mata pelajaran sesuai jenjang siswa Anda.',
                ],
                [
                    'title' => 'Adakan latihan dan ujian secara rutin',
                    'body' => 'Gunakan bank soal, teks bacaan, dan sesi ujian agar siswa terbiasa menjelang TKA.',
                ],
                [
                    'title' => 'Baca hasilnya',
                    'body' => 'Lihat nilai tiap siswa, lalu tentukan materi mana yang perlu diulang.',
                ],
            ],
            'step_siswa' => [
                [
                    'title' => 'Masukkan kode ujian',
                    'body' => 'Kode ujian dibagikan guru, lalu ujian bisa langsung dikerjakan.',
                ],
                [
                    'title' => 'Tulis nama Anda',
                    'body' => 'Cukup satu langkah identitas, tanpa membuat akun.',
                ],
                [
                    'title' => 'Kerjakan soal',
                    'body' => 'Ada timer dan penanda nomor soal. Jawaban tersimpan otomatis.',
                ],
                [
                    'title' => 'Selesai',
                    'body' => 'Hasil tersimpan rapi dan bisa dibaca guru untuk membantu belajar berikutnya.',
                ],
            ],
            'feature' => [
                ['title' => 'Skor memakai skala resmi TKA: 0-100 untuk SD/SMP, 200-800 untuk SMA/SMK'],
                ['title' => 'Susun paket soal sendiri'],
                ['title' => 'Cukup HP, siswa masuk dengan kode ujian'],
                ['title' => 'Jawaban siswa tersimpan otomatis'],
            ],
        ];

        foreach ($items as $section => $rows) {
            foreach ($rows as $index => $row) {
                LandingContentItem::query()->updateOrCreate(
                    ['section' => $section, 'title' => $row['title']],
                    $row + [
                        'kicker' => $row['kicker'] ?? null,
                        'body' => $row['body'] ?? null,
                        'caption' => $row['caption'] ?? null,
                        'image_path' => $row['image_path'] ?? null,
                        'sort_order' => $index,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
