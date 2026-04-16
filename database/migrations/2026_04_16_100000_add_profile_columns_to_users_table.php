<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jenjang')->nullable()->after('access_token');
            $table->string('tingkat')->nullable()->after('jenjang');
            $table->string('satuan_pendidikan')->nullable()->after('tingkat');
            $table->string('no_wa', 20)->nullable()->after('satuan_pendidikan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['jenjang', 'tingkat', 'satuan_pendidikan', 'no_wa']);
        });
    }
};
