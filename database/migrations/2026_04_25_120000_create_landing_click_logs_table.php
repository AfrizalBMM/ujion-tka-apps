<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_click_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event', 64);
            $table->string('href', 2048)->nullable();
            $table->string('path', 1024)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_click_logs');
    }
};
