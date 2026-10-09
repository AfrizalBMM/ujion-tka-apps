<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('trial_status', 20)->default('none')->after('payment_rejection_reason');
            $table->dateTime('trial_ends_at')->nullable()->after('trial_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trial_status', 'trial_ends_at']);
        });
    }
};
