<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class TrialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Default trial duration: 7 days
        AppSetting::updateOrCreate(
            ['key' => 'trial_default_days'],
            ['value' => '7']
        );

        // Default pembahasan price (if not set)
        AppSetting::updateOrCreate(
            ['key' => 'pembahasan_price'],
            ['value' => '10000']
        );
    }
}
