<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure pricing_plans has 'period' column for plan periods (monthly/semiannual/yearly).
        // Original migration had period with default '/bulan' — we widen to accept longer values.
        if (Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'period')) {
            // Already exists — change type to varchar with more room.
            Schema::table('pricing_plans', function (Blueprint $table) {
                $table->string('period', 40)->default('monthly')->change();
            });
        }
    }

    public function down(): void
    {
        // No destructive rollback — original column default was '/bulan'.
    }
};
