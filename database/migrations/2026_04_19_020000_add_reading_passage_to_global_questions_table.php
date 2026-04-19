<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_questions', function (Blueprint $table) {
            $table->text('reading_passage')->nullable()->after('material_id');
        });
    }

    public function down(): void
    {
        Schema::table('global_questions', function (Blueprint $table) {
            $table->dropColumn('reading_passage');
        });
    }
};
