<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Hapus unique key lama yang tidak mencakup jenjang & mapel
            $table->dropUnique('materials_curriculum_subelement_unit_sub_unit_unique');

            // Tambah unique key baru yang mencakup jenjang, mapel, curriculum, subelement, unit, sub_unit
            // Kolom jenjang & mapel nullable → gunakan '' sebagai fallback di level app
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
