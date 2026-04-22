<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropUnique('materials_full_unique');
            $table->unique(
                ['jenjang', 'assessment_type', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit'],
                'materials_full_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropUnique('materials_full_unique');
            $table->unique(
                ['jenjang', 'mapel', 'curriculum', 'subelement', 'unit', 'sub_unit'],
                'materials_full_unique'
            );
        });
    }
};
