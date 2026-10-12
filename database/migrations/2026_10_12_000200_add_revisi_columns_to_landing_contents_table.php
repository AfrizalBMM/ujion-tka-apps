<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_contents', function (Blueprint $table) {
            $table->text('subtitle')->nullable()->after('body');
            $table->string('cta_text', 80)->nullable()->after('button_url');
            $table->string('cta_url')->nullable()->after('cta_text');
            $table->text('note')->nullable()->after('cta_url');
        });
    }

    public function down(): void
    {
        Schema::table('landing_contents', function (Blueprint $table) {
            $table->dropColumn(['subtitle', 'cta_text', 'cta_url', 'note']);
        });
    }
};
