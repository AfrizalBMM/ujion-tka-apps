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
        User::create([
            'name' => 'Ngadimin',
            'email' => 'superadmin@ujion.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }
}
