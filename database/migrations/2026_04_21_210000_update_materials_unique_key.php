<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Hapus unique key lama yang tidak mencakup jenjang & mapel
            $table->dropUnique('materials_curriculum_subelement_unit_sub_unit_unique');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            // Prefix index untuk menghindari MySQL max key length (3072 bytes)
            // pada 6 kolom VARCHAR utf8mb4
            DB::statement('ALTER TABLE materials ADD UNIQUE materials_full_unique (jenjang(50), mapel(50), curriculum(50), subelement(50), unit(50), sub_unit(50))');

            return;
        }

        Schema::table('materials', function (Blueprint $table) {
            $table->unique(
                ['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit'],
                'materials_full_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropUnique('materials_full_unique');

            $table->unique(
                ['curriculum', 'subelement', 'unit', 'sub_unit'],
                'materials_curriculum_subelement_unit_sub_unit_unique'
            );
        });
    }
};
