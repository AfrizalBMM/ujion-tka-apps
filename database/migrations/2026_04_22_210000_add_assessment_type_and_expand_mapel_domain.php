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
            $table->string('assessment_type', 50)->default('tka')->after('jenjang');
        });

        Schema::table('global_questions', function (Blueprint $table) {
            $table->string('assessment_type', 50)->default('tka')->after('jenjang_id');
        });

        Schema::table('paket_soals', function (Blueprint $table) {
            $table->string('assessment_type', 50)->default('tka')->after('jenjang_id');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('assessment_type', 50)->default('tka')->after('paket_soal_id');
        });

        Schema::table('mapel_pakets', function (Blueprint $table) {
            $table->string('nama_mapel', 100)->change();
        });

        DB::table('materials')->update(['assessment_type' => 'tka']);
        DB::table('global_questions')->update(['assessment_type' => 'tka']);
        DB::table('paket_soals')->update(['assessment_type' => 'tka']);
        DB::table('exams')->update(['assessment_type' => 'tka']);
    }

    public function down(): void
    {
        Schema::table('mapel_pakets', function (Blueprint $table) {
            $table->enum('nama_mapel', ['matematika', 'bahasa_indonesia'])->change();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('assessment_type');
        });

        Schema::table('paket_soals', function (Blueprint $table) {
            $table->dropColumn('assessment_type');
        });

        Schema::table('global_questions', function (Blueprint $table) {
            $table->dropColumn('assessment_type');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('assessment_type');
        });
    }
};
