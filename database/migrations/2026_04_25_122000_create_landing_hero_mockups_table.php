<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_hero_mockups', function (Blueprint $table): void {
            $table->id();
            $table->string('badge', 80)->nullable();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_hero_mockups');
    }
};
