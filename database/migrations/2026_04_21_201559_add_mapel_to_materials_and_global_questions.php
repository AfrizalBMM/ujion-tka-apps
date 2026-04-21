<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom mapel di tabel materials (sesudah jenjang)
        Schema::table('materials', function (Blueprint $table) {
            $table->string('mapel')->nullable()->after('jenjang');
        });

        // Tambah kolom snapshot material_mapel di global_questions
        Schema::table('global_questions', function (Blueprint $table) {
            $table->string('material_mapel')->nullable()->after('material_curriculum');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('mapel');
        });

        Schema::table('global_questions', function (Blueprint $table) {
            $table->dropColumn('material_mapel');
        });
    }
};
