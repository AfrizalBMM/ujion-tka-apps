<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Jenjang;
use App\Models\PaketSoal;
use App\Models\MapelPaket;
use App\Models\Soal;
use App\Models\PilihanJawaban;
use App\Models\Material;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SuperadminGuruSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin (Login via /ngadimin/login dengan Email & Pass)
        $superadmin = User::create([
            'name' => 'Ngadimin',
            'email' => 'superadmin@ujion.com',
            'password' => Hash::make('superadmin123'),
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        // Guru (Login via /login dengan Nama & Token)
        $guru = User::create([
            'name' => 'Guru Satu',
            'email' => 'guru1@ujion.com',
            'password' => Hash::make('password'), // Tetap ada password sebagai fallback DB
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'access_token' => 'GURU12345',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'satuan_pendidikan' => 'SMP Ujion',
            'no_wa' => '081234567890',
        ]);

        $hasJenjang = Schema::hasColumn('materials', 'jenjang');
        $hasLink = Schema::hasColumn('materials', 'link');

        $materialSeeds = [
            // SMP (contoh realistis)
            ['jenjang' => 'SMP', 'curriculum' => 'Merdeka', 'subelement' => 'Matematika', 'unit' => 'Bilangan', 'sub_unit' => 'Operasi Bilangan Bulat', 'link' => null],
            ['jenjang' => 'SMP', 'curriculum' => 'Merdeka', 'subelement' => 'Matematika', 'unit' => 'Aljabar', 'sub_unit' => 'Bentuk Aljabar & Operasi', 'link' => null],
            ['jenjang' => 'SMP', 'curriculum' => 'Merdeka', 'subelement' => 'Matematika', 'unit' => 'Geometri', 'sub_unit' => 'Segitiga & Segiempat', 'link' => null],
            ['jenjang' => 'SMP', 'curriculum' => 'Merdeka', 'subelement' => 'Bahasa Indonesia', 'unit' => 'Teks Informasi', 'sub_unit' => 'Gagasan Utama & Rincian Pendukung', 'link' => null],
            ['jenjang' => 'SMP', 'curriculum' => 'Merdeka', 'subelement' => 'Bahasa Indonesia', 'unit' => 'Teks Prosedur', 'sub_unit' => 'Tujuan, Langkah, dan Kalimat Imperatif', 'link' => null],
            ['jenjang' => 'SMP', 'curriculum' => 'K-13', 'subelement' => 'Bahasa Indonesia', 'unit' => 'Teks Deskripsi', 'sub_unit' => 'Struktur & Kaidah Kebahasaan', 'link' => null],

            // SD
            ['jenjang' => 'SD', 'curriculum' => 'Merdeka', 'subelement' => 'Matematika', 'unit' => 'Bilangan', 'sub_unit' => 'Nilai Tempat & Pembulatan', 'link' => null],
            ['jenjang' => 'SD', 'curriculum' => 'Merdeka', 'subelement' => 'Matematika', 'unit' => 'Pengukuran', 'sub_unit' => 'Satuan Panjang, Massa, Waktu', 'link' => null],
            ['jenjang' => 'SD', 'curriculum' => 'Merdeka', 'subelement' => 'Bahasa Indonesia', 'unit' => 'Membaca', 'sub_unit' => 'Menemukan Informasi Tersurat', 'link' => null],
            ['jenjang' => 'SD', 'curriculum' => 'K-13', 'subelement' => 'Bahasa Indonesia', 'unit' => 'Teks Dongeng', 'sub_unit' => 'Tokoh, Latar, Amanat', 'link' => null],

            // Global (tanpa jenjang)
            ['jenjang' => null, 'curriculum' => 'Merdeka', 'subelement' => 'Literasi Umum', 'unit' => 'Strategi Membaca', 'sub_unit' => 'Skimming & Scanning', 'link' => null],
            ['jenjang' => null, 'curriculum' => 'Merdeka', 'subelement' => 'Numerasi Umum', 'unit' => 'Pemecahan Masalah', 'sub_unit' => 'Memilih Strategi & Memeriksa Kembali', 'link' => null],
        ];

        foreach ($materialSeeds as $seed) {
            $payload = [
                'curriculum' => $seed['curriculum'],
                'subelement' => $seed['subelement'],
                'unit' => $seed['unit'],
                'sub_unit' => $seed['sub_unit'],
            ];
            if ($hasJenjang) {
                $payload['jenjang'] = $seed['jenjang'];
            }
            if ($hasLink) {
                $payload['link'] = $seed['link'];
            }

            Material::firstOrCreate(
                [
                    'curriculum' => $seed['curriculum'],
                    'subelement' => $seed['subelement'],
                    'unit' => $seed['unit'],
                    'sub_unit' => $seed['sub_unit'],
                ],
                $payload
            );
        }

        // Buat contoh soal global yang terikat materi (ambil beberapa materi SMP agar demo terasa nyata)
        $materials = Material::query()
            ->when($hasJenjang, fn ($q) => $q->where('jenjang', 'SMP'))
            ->take(6)
            ->get();

        foreach ($materials as $index => $material) {
            for ($j = 1; $j <= 3; $j++) {
                Question::create([
                    'material_id' => $material->id,
                    'jenjang' => 'SMP',
                    'tingkat' => '7',
                    'kategori' => 'Sedang',
                    'tipe' => 'PG',
                    'pertanyaan' => "Contoh soal $j: {$material->unit} - {$material->sub_unit}",
                    'opsi' => json_encode([
                        'A' => 'Pilihan A',
                        'B' => 'Pilihan B',
                        'C' => 'Pilihan C',
                        'D' => 'Pilihan D',
                    ]),
                    'jawaban_benar' => 'A',
                    'is_active' => true,
                ]);
            }
        }

        $jenjang = Jenjang::where('kode', 'SMP')->first();

        if ($jenjang) {
            $paket = PaketSoal::create([
                'jenjang_id' => $jenjang->id,
                'nama' => 'Paket TKA SMP 2026',
                'tahun_ajaran' => '2025/2026',
                'is_active' => true,
                'created_by' => $superadmin->id,
            ]);

            $mapelMatematika = MapelPaket::create([
                'paket_soal_id' => $paket->id,
                'nama_mapel' => 'matematika',
                'jumlah_soal' => 30,
                'durasi_menit' => 75,
                'urutan' => 1,
            ]);

            $mapelBahasa = MapelPaket::create([
                'paket_soal_id' => $paket->id,
                'nama_mapel' => 'bahasa_indonesia',
                'jumlah_soal' => 30,
                'durasi_menit' => 75,
                'urutan' => 2,
            ]);

            foreach ([$mapelMatematika, $mapelBahasa] as $mapel) {
                for ($i = 1; $i <= 3; $i++) {
                    $soal = Soal::create([
                        'mapel_paket_id' => $mapel->id,
                        'nomor_soal' => $i,
                        'tipe_soal' => 'pilihan_ganda',
                        'indikator' => 'Memahami konsep dasar pada butir contoh.',
                        'pertanyaan' => 'Soal contoh '.$i.' untuk '.$mapel->nama_label,
                        'bobot' => 1,
                    ]);

                    foreach (['A', 'B', 'C', 'D'] as $kode) {
                        PilihanJawaban::create([
                            'soal_id' => $soal->id,
                            'kode' => $kode,
                            'teks' => 'Pilihan '.$kode,
                            'is_benar' => $kode === 'A',
                        ]);
                    }
                }
            }
        }
    }
}
