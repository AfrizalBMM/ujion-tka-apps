<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soals', function (Blueprint $table) {
            $table->string('jenis_instrumen', 40)->default('akademik')->after('tipe_soal');
            $table->string('dimensi')->nullable()->after('indikator');
            $table->string('subdimensi')->nullable()->after('dimensi');
            $table->string('kategori_profil')->nullable()->after('subdimensi');
            $table->string('arah_skor', 20)->default('positif')->after('kategori_profil');
        });

        Schema::table('pilihan_jawabans', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai_survey')->nullable()->after('is_benar');
            $table->string('profil_label')->nullable()->after('nilai_survey');
        });

        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->json('profil_ringkasan')->nullable()->after('skor');
        });
    }

    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->dropColumn('profil_ringkasan');
        });

        Schema::table('pilihan_jawabans', function (Blueprint $table) {
            $table->dropColumn(['nilai_survey', 'profil_label']);
        });

        Schema::table('soals', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_instrumen',
                'dimensi',
                'subdimensi',
                'kategori_profil',
                'arah_skor',
            ]);
        });
    }
};
