<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Material;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        ]);

        // 10 Materi
        for ($i = 1; $i <= 10; $i++) {
            $material = Material::create([
                'curriculum' => 'Kurikulum ' . $i,
                'subelement' => 'Subelemen ' . $i,
                'unit' => 'Unit ' . $i,
                'sub_unit' => 'Sub Unit ' . $i,
            ]);

            // 3 Soal per materi
            for ($j = 1; $j <= 3; $j++) {
                Question::create([
                    'material_id' => $material->id,
                    'jenjang' => 'SMP',
                    'tingkat' => '7',
                    'kategori' => 'Sedang',
                    'tipe' => 'PG',
                    'pertanyaan' => "Soal $j untuk Materi $i",
                    'opsi' => json_encode([
                        'A' => 'Opsi A',
                        'B' => 'Opsi B',
                        'C' => 'Opsi C',
                        'D' => 'Opsi D',
                    ]),
                    'jawaban_benar' => 'A',
                    'is_active' => true,
                ]);
            }
        }
    }
}
