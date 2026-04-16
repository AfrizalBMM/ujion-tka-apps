<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawaban_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_sesi_id')->constrained('ujian_sesis')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            $table->enum('tipe_soal', ['pilihan_ganda', 'menjodohkan']);
            $table->string('jawaban_pg')->nullable();
            $table->json('jawaban_menjodohkan')->nullable();
            $table->boolean('is_ragu')->default(false);
            $table->timestamps();

            $table->unique(['ujian_sesi_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_siswas');
    }
};
