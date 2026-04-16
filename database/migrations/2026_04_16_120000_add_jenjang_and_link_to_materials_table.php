<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('jenjang')->nullable()->after('id');
            $table->string('link', 500)->nullable()->after('sub_unit');

            $table->index('jenjang');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex(['jenjang']);
            $table->dropColumn(['jenjang', 'link']);
        });
    }
};

