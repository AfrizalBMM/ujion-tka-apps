<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapel_pakets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_soal_id')->constrained('paket_soals')->cascadeOnDelete();
            $table->enum('nama_mapel', ['matematika', 'bahasa_indonesia']);
            $table->unsignedInteger('jumlah_soal')->default(30);
            $table->unsignedInteger('durasi_menit')->default(75);
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['paket_soal_id', 'nama_mapel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapel_pakets');
    }
};
