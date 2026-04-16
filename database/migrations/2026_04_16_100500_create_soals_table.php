<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapel_paket_id')->constrained('mapel_pakets')->cascadeOnDelete();
            $table->foreignId('teks_bacaan_id')->nullable()->constrained('teks_bacaans')->nullOnDelete();
            $table->unsignedInteger('nomor_soal');
            $table->enum('tipe_soal', ['pilihan_ganda', 'menjodohkan']);
            $table->text('indikator');
            $table->text('pertanyaan');
            $table->string('gambar')->nullable();
            $table->unsignedInteger('bobot')->default(1);
            $table->timestamps();

            $table->unique(['mapel_paket_id', 'nomor_soal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soals');
    }
};
