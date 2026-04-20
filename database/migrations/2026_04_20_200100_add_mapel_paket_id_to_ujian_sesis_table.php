<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->foreignId('mapel_paket_id')
                ->nullable()
                ->after('paket_soal_id')
                ->constrained('mapel_pakets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->dropForeign(['mapel_paket_id']);
            $table->dropColumn('mapel_paket_id');
        });
    }
};
