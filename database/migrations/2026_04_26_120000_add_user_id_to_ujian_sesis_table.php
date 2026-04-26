<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('paket_soal_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'updated_at']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
