<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teks_bacaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapel_paket_id')->constrained('mapel_pakets')->cascadeOnDelete();
            $table->string('judul')->nullable();
            $table->longText('konten');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teks_bacaans');
    }
};
