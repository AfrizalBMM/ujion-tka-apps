<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table): void {
            $table->foreignId('landing_exam_order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table): void {
            $table->dropForeign(['landing_exam_order_id']);
            $table->dropColumn('landing_exam_order_id');
        });
    }
};
