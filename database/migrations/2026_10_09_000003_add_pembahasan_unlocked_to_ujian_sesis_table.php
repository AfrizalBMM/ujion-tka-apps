<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add pembahasan_unlocked_at to ujian_sesis table for the new payment flow.
        // When null: pembahasan locked (paywall). When set: pembahasan is unlocked.
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->timestamp('pembahasan_unlocked_at')->nullable()->after('profil_ringkasan');
        });
    }

    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->dropColumn('pembahasan_unlocked_at');
        });
    }
};
