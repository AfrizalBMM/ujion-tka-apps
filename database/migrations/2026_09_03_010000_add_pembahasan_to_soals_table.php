<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soals', function (Blueprint $table): void {
            $table->text('pembahasan')->nullable()->after('pertanyaan');
        });
    }

    public function down(): void
    {
        Schema::table('soals', function (Blueprint $table): void {
            $table->dropColumn('pembahasan');
        });
    }
};
