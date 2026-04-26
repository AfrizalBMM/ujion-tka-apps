<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->select('no_wa')
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->groupBy('no_wa')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('no_wa');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Tidak bisa menambahkan unique index users.no_wa karena masih ada nomor duplikat: '
                . $duplicates->take(5)->implode(', ')
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('no_wa');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['no_wa']);
        });
    }
};
