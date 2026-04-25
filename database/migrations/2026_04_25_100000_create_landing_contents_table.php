<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40)->index();
            $table->string('kicker', 180)->nullable();
            $table->string('title', 220)->nullable();
            $table->text('body')->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_contents');
    }
};
