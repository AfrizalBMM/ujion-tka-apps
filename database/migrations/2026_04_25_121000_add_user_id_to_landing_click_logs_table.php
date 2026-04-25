<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_click_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('landing_click_logs', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropColumn('user_id');
        });
    }
};
