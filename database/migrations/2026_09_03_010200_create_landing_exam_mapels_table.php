<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_exam_mapels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mapel_paket_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('original_price', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['landing_exam_id', 'mapel_paket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_exam_mapels');
    }
};
