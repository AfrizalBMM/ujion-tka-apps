<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
