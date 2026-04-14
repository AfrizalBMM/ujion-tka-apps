<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('siswa')->after('password');
            $table->string('account_status')->default('active')->after('role');
            $table->string('access_token')->nullable()->unique()->after('account_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn(['role', 'account_status', 'access_token']);
        });
    }
};
