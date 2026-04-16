<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilihan_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            $table->enum('kode', ['A', 'B', 'C', 'D']);
            $table->text('teks');
            $table->string('gambar')->nullable();
            $table->boolean('is_benar')->default(false);
            $table->timestamps();

            $table->unique(['soal_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilihan_jawabans');
    }
};
