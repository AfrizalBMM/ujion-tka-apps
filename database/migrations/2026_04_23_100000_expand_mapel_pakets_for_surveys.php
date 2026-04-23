<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE mapel_pakets
                MODIFY nama_mapel ENUM(
                    'matematika',
                    'bahasa_indonesia',
                    'survey_karakter',
                    'survey_lingkungan_belajar'
                ) NOT NULL
            ");
        }

        Schema::table('mapel_pakets', function (Blueprint $table) {
            $table->string('kategori_komponen', 20)->default('akademik')->after('nama_mapel');
            $table->string('mode_penilaian', 20)->default('score')->after('kategori_komponen');
            $table->string('kode_komponen', 40)->nullable()->after('mode_penilaian');
            $table->boolean('is_wajib')->default(true)->after('kode_komponen');
            $table->text('petunjuk_khusus')->nullable()->after('is_wajib');
        });

        DB::table('mapel_pakets')
            ->where('nama_mapel', 'matematika')
            ->update([
                'kategori_komponen' => 'akademik',
                'mode_penilaian' => 'score',
                'kode_komponen' => 'MAT',
                'is_wajib' => true,
            ]);

        DB::table('mapel_pakets')
            ->where('nama_mapel', 'bahasa_indonesia')
            ->update([
                'kategori_komponen' => 'akademik',
                'mode_penilaian' => 'score',
                'kode_komponen' => 'BIND',
                'is_wajib' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('mapel_pakets', function (Blueprint $table) {
            $table->dropColumn([
                'kategori_komponen',
                'mode_penilaian',
                'kode_komponen',
                'is_wajib',
                'petunjuk_khusus',
            ]);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE mapel_pakets
                MODIFY nama_mapel ENUM('matematika','bahasa_indonesia') NOT NULL
            ");
        }
    }
};
