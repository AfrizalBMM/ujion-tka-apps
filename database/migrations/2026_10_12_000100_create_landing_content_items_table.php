<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_content_items', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40)->index();
            $table->string('kicker', 180)->nullable();
            $table->string('title', 220)->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('caption', 300)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['section', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_content_items');
    }
};
