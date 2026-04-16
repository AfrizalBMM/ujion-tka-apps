<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujian_sesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('paket_soal_id')->constrained('paket_soals')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nomor_wa', 20)->nullable();
            $table->string('session_token')->unique();
            $table->json('timer_state')->nullable();
            $table->enum('status', ['menunggu', 'mengerjakan', 'selesai'])->default('menunggu');
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->decimal('skor', 6, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_sesis');
    }
};
